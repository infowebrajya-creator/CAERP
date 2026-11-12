<?php

namespace App\Services;

use App\Models\FirmSetting;
use App\Models\SystemLicense;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class LicenseService
{
    /**
     * Get or generate the unique hardware machine fingerprint.
     */
    public function getMachineFingerprint(): string
    {
        $raw = '';

        // 1. Try platform-specific hardware identifiers
        if (PHP_OS_FAMILY === 'Darwin') {
            // macOS IOPlatformUUID
            $uuid = @shell_exec("ioreg -rd1 -c IOPlatformExpertDevice 2>/dev/null | grep 'IOPlatformUUID' | awk '{print \$3}'");
            if ($uuid) {
                $raw .= trim(str_replace('"', '', $uuid));
            }
        } elseif (PHP_OS_FAMILY === 'Linux') {
            // Linux machine-id
            if (file_exists('/etc/machine-id')) {
                $raw .= trim((string) @file_get_contents('/etc/machine-id'));
            } elseif (file_exists('/var/lib/dbus/machine-id')) {
                $raw .= trim((string) @file_get_contents('/var/lib/dbus/machine-id'));
            }
        } elseif (PHP_OS_FAMILY === 'Windows') {
            // Windows MachineGuid
            $guid = @shell_exec('reg query "HKLM\SOFTWARE\Microsoft\Cryptography" /v MachineGuid 2>nul');
            if ($guid && preg_match('/MachineGuid\s+REG_SZ\s+([A-Fa-f0-9\-]+)/', $guid, $m)) {
                $raw .= trim($m[1]);
            }
        }

        // 2. Mix in system & motherboard / cpu info
        $raw .= '|' . gethostname();
        $raw .= '|' . php_uname('n') . '|' . php_uname('m');

        // 3. Fallback persistent server identifier if raw is short
        $storageIdFile = storage_path('framework/machine_id');
        if (! file_exists($storageIdFile)) {
            $persistentId = hash('sha256', $raw . '|' . microtime(true) . '|' . uniqid('', true));
            @file_put_contents($storageIdFile, $persistentId);
        } else {
            $raw .= '|' . @file_get_contents($storageIdFile);
        }

        // Generate clean formatted fingerprint e.g. WR-HW-A1B2-C3D4-E5F6
        $hash = strtoupper(hash('sha256', $raw));
        $part1 = substr($hash, 0, 4);
        $part2 = substr($hash, 4, 4);
        $part3 = substr($hash, 8, 4);
        $part4 = substr($hash, 12, 4);

        return "WR-HW-{$part1}-{$part2}-{$part3}-{$part4}";
    }

    /**
     * Generate a cryptographic license key string for a CA firm.
     */
    public function generateLicenseKey(
        string $licensedTo,
        ?string $email = null,
        string $edition = 'Lifetime Edition',
        int $maxStaff = 25,
        ?string $amcExpiresAt = null,
        ?string $boundHardwareId = null
    ): string {
        $secret = Config::get('license.secret', env('APP_KEY', 'WebRajya-Secure-License-Signer-v1'));

        $payload = [
            'to' => trim($licensedTo),
            'email' => $email ? trim($email) : null,
            'edition' => $edition,
            'staff' => $maxStaff,
            'amc' => $amcExpiresAt ?: Carbon::now()->addYear()->format('Y-m-d'),
            'hw' => $boundHardwareId ? trim($boundHardwareId) : null,
            'nonce' => substr(bin2hex(random_bytes(6)), 0, 8),
            'v' => 1,
        ];

        $json = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $b64 = rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
        $signature = hash_hmac('sha256', $b64, $secret);
        $shortSig = strtoupper(substr($signature, 0, 16));

        return "WRLIC-{$b64}-{$shortSig}";
    }

    /**
     * Decode and verify a raw license key against cryptographic signature.
     */
    public function verifyKeyFormat(string $licenseKey): ?array
    {
        $licenseKey = trim($licenseKey);
        $parts = explode('-', $licenseKey);

        if (count($parts) < 3 || $parts[0] !== 'WRLIC') {
            return null;
        }

        $b64 = $parts[1];
        $sig = $parts[2];

        $secret = Config::get('license.secret', env('APP_KEY', 'WebRajya-Secure-License-Signer-v1'));
        $expectedSig = strtoupper(substr(hash_hmac('sha256', $b64, $secret), 0, 16));

        if (! hash_equals($expectedSig, $sig)) {
            return null;
        }

        $json = base64_decode(strtr($b64, '-_', '+/'));
        $payload = json_decode($json, true);

        if (! is_array($payload) || empty($payload['to'])) {
            return null;
        }

        return $payload;
    }

    /**
     * Activate the software with a provided license key on this machine.
     */
    public function activate(string $licenseKey): array
    {
        $payload = $this->verifyKeyFormat($licenseKey);
        if (! $payload) {
            return [
                'success' => false,
                'message' => 'Invalid license key format or signature verification failed.',
            ];
        }

        $currentMachine = $this->getMachineFingerprint();
        $keyHash = hash('sha256', $licenseKey);

        // If the license is pre-bound to a specific hardware ID, verify it matches
        if (! empty($payload['hw']) && $payload['hw'] !== $currentMachine) {
            return [
                'success' => false,
                'message' => "This license key is locked to another machine ({$payload['hw']}). Your Machine ID is {$currentMachine}.",
            ];
        }

        // Check if already registered in local database
        $existing = SystemLicense::where('key_hash', $keyHash)->first();
        if ($existing && $existing->status === 'active' && $existing->machine_fingerprint !== $currentMachine) {
            return [
                'success' => false,
                'message' => 'This license key is already activated on another machine.',
            ];
        }

        // Generate offline validation token
        $offlineToken = $this->createOfflineToken($licenseKey, $currentMachine, $payload);

        // Deactivate any old active licenses locally
        SystemLicense::where('status', 'active')->update(['status' => 'superseded']);

        $license = SystemLicense::updateOrCreate(
            ['key_hash' => $keyHash],
            [
                'license_key' => $licenseKey,
                'licensed_to' => $payload['to'],
                'email' => $payload['email'] ?? null,
                'machine_fingerprint' => $currentMachine,
                'status' => 'active',
                'edition' => $payload['edition'] ?? 'Lifetime Edition',
                'max_staff_users' => (int) ($payload['staff'] ?? 25),
                'activated_at' => Carbon::now(),
                'amc_expires_at' => ! empty($payload['amc']) ? Carbon::parse($payload['amc']) : Carbon::now()->addYear(),
                'last_verified_at' => Carbon::now(),
                'offline_token' => $offlineToken,
                'meta_data' => [
                    'os' => PHP_OS_FAMILY,
                    'hostname' => gethostname(),
                    'activated_via' => 'local_activation',
                ],
            ]
        );

        // Optionally update the firm settings name if it's the default
        $firmNameSetting = FirmSetting::where('key', 'firm_name')->first();
        if (! $firmNameSetting || $firmNameSetting->value === 'WebRajya Accounting & Advisory') {
            FirmSetting::updateOrCreate(
                ['key' => 'firm_name'],
                ['value' => $payload['to']]
            );
        }

        return [
            'success' => true,
            'message' => "WebRajya ERP successfully activated for {$payload['to']}!",
            'license' => $license,
        ];
    }

    /**
     * Check if the current installation has an active, valid license bound to this hardware.
     */
    public function isActivated(): bool
    {
        if (App::environment('testing') && Config::get('license.bypass_in_testing', true)) {
            return true;
        }

        $activeLicense = SystemLicense::where('status', 'active')->first();
        if (! $activeLicense) {
            return false;
        }

        // Verify machine fingerprint matches
        $currentMachine = $this->getMachineFingerprint();
        if ($activeLicense->machine_fingerprint !== $currentMachine) {
            Log::warning("WebRajya License hardware mismatch: DB={$activeLicense->machine_fingerprint}, Machine={$currentMachine}");
            return false;
        }

        // Verify cryptographic offline token
        if (! $this->verifyOfflineToken($activeLicense, $currentMachine)) {
            Log::warning('WebRajya License offline token verification failed.');
            return false;
        }

        return true;
    }

    /**
     * Get details of the active license or default info.
     */
    public function getLicenseInfo(): array
    {
        $currentMachine = $this->getMachineFingerprint();
        $license = SystemLicense::where('status', 'active')->first();

        if (! $license) {
            return [
                'is_activated' => false,
                'status' => 'unlicensed',
                'machine_fingerprint' => $currentMachine,
                'licensed_to' => null,
                'edition' => 'Unregistered / Evaluation',
                'max_staff_users' => null,
                'amc_expires_at' => null,
                'amc_status' => 'inactive',
                'remaining_amc_days' => 0,
                'license_key' => null,
                'activated_at' => null,
            ];
        }

        $isHardwareMatch = ($license->machine_fingerprint === $currentMachine);

        return [
            'is_activated' => ($license->status === 'active' && $isHardwareMatch),
            'status' => $license->status,
            'machine_fingerprint' => $currentMachine,
            'licensed_to' => $license->licensed_to,
            'email' => $license->email,
            'edition' => $license->edition,
            'max_staff_users' => $license->max_staff_users,
            'amc_expires_at' => $license->amc_expires_at?->format('d M Y'),
            'amc_status' => $license->isAmcExpired() ? 'expired' : 'active',
            'remaining_amc_days' => $license->remainingAmcDays(),
            'license_key' => $this->maskKey($license->license_key),
            'full_license_key' => $license->license_key,
            'activated_at' => $license->activated_at?->format('d M Y, h:i A'),
            'hardware_matched' => $isHardwareMatch,
        ];
    }

    /**
     * Deactivate current license (e.g. when transferring to another machine).
     */
    public function deactivate(): bool
    {
        $license = SystemLicense::where('status', 'active')->first();
        if (! $license) {
            return false;
        }

        $license->update([
            'status' => 'deactivated',
            'last_verified_at' => Carbon::now(),
        ]);

        return true;
    }

    /**
     * Create cryptographic offline token bound to this machine.
     */
    protected function createOfflineToken(string $key, string $machine, array $payload): string
    {
        $secret = Config::get('license.secret', env('APP_KEY', 'WebRajya-Secure-License-Signer-v1'));
        $data = [
            'key' => $key,
            'hw' => $machine,
            'to' => $payload['to'] ?? '',
            'amc' => $payload['amc'] ?? '',
            'ts' => time(),
        ];

        $tokenPayload = base64_encode(json_encode($data));
        $sig = hash_hmac('sha256', $tokenPayload . '|' . $machine, $secret);

        return "{$tokenPayload}.{$sig}";
    }

    /**
     * Verify offline token integrity.
     */
    protected function verifyOfflineToken(SystemLicense $license, string $machine): bool
    {
        if (empty($license->offline_token)) {
            return false;
        }

        $parts = explode('.', $license->offline_token);
        if (count($parts) !== 2) {
            return false;
        }

        [$tokenPayload, $sig] = $parts;
        $secret = Config::get('license.secret', env('APP_KEY', 'WebRajya-Secure-License-Signer-v1'));
        $expectedSig = hash_hmac('sha256', $tokenPayload . '|' . $machine, $secret);

        return hash_equals($expectedSig, $sig);
    }

    /**
     * Mask license key for secure display in UI (e.g. WRLIC-eyJ...-ABCD -> WRLIC-***-ABCD).
     */
    protected function maskKey(string $key): string
    {
        if (strlen($key) < 16) {
            return '********';
        }

        $parts = explode('-', $key);
        if (count($parts) === 3) {
            return "WRLIC-****************-" . $parts[2];
        }

        return substr($key, 0, 6) . '...' . substr($key, -4);
    }
}
