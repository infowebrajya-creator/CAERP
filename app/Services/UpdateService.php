<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class UpdateService
{
    public function __construct(
        protected LicenseService $licenseService,
        protected ActivityLogger $logger
    ) {}

    /**
     * Get currently installed software version.
     */
    public function getCurrentVersion(): string
    {
        $versionFile = base_path('version.json');
        if (File::exists($versionFile)) {
            $data = json_decode(File::get($versionFile), true);
            if (! empty($data['version'])) {
                return (string) $data['version'];
            }
        }

        return (string) Config::get('updater.current_version', '1.0.0');
    }

    /**
     * Check remote manifest for available software updates.
     */
    public function checkForUpdates(bool $forceRefresh = false): array
    {
        $cacheKey = 'webrajya_update_check';

        if (! $forceRefresh && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $currentVersion = $this->getCurrentVersion();
        $manifestUrl = Config::get('updater.manifest_url');
        $licenseInfo = $this->licenseService->getLicenseInfo();
        $isAmcValid = ($licenseInfo['amc_status'] !== 'expired');

        try {
            $response = Http::timeout(5)
                ->withHeaders(['User-Agent' => 'WebRajya-ERP-Client/' . $currentVersion])
                ->get($manifestUrl);

            if (! $response->successful()) {
                throw new Exception('Remote update server returned HTTP status: ' . $response->status());
            }

            $remote = $response->json();
            if (! is_array($remote) || empty($remote['version'])) {
                throw new Exception('Invalid remote update manifest format.');
            }

            $latestVersion = (string) $remote['version'];
            $hasUpdate = version_compare($latestVersion, $currentVersion, '>');

            $result = [
                'success' => true,
                'has_update' => $hasUpdate,
                'current_version' => $currentVersion,
                'latest_version' => $latestVersion,
                'release_date' => $remote['release_date'] ?? null,
                'changelog' => $remote['changelog'] ?? [],
                'download_url' => $remote['download_url'] ?? null,
                'is_amc_valid' => $isAmcValid,
                'can_install' => ($hasUpdate && $isAmcValid),
                'checked_at' => now()->format('d M Y, h:i A'),
            ];

            Cache::put($cacheKey, $result, now()->addMinutes((int) Config::get('updater.cache_ttl_minutes', 60)));

            return $result;
        } catch (Exception $e) {
            Log::info('WebRajya update check failed or offline: ' . $e->getMessage());

            $fallback = [
                'success' => false,
                'has_update' => false,
                'current_version' => $currentVersion,
                'latest_version' => $currentVersion,
                'is_amc_valid' => $isAmcValid,
                'can_install' => false,
                'error' => $e->getMessage(),
                'checked_at' => now()->format('d M Y, h:i A'),
            ];

            return $fallback;
        }
    }

    /**
     * Apply an update archive (ZIP file).
     */
    public function applyZipUpdate(string $zipFilePath): array
    {
        if (! class_exists('ZipArchive')) {
            throw new Exception('ZipArchive PHP extension is required to extract updates.');
        }

        if (! File::exists($zipFilePath)) {
            throw new Exception('Update package file not found.');
        }

        $licenseInfo = $this->licenseService->getLicenseInfo();
        if ($licenseInfo['amc_status'] === 'expired' && Config::get('updater.require_valid_amc', true)) {
            throw new Exception('Cannot install updates. Your Annual Maintenance Contract (AMC) has expired. Please renew your AMC license.');
        }

        $zip = new ZipArchive();
        if ($zip->open($zipFilePath) !== true) {
            throw new Exception('Failed to open update archive.');
        }

        // Extract to application base path
        $zip->extractTo(base_path());
        $zip->close();

        // Run database migrations
        Artisan::call('migrate', ['--force' => true]);

        // Clear all caches
        Artisan::call('optimize:clear');

        // Clear update check cache
        Cache::forget('webrajya_update_check');

        $newVersion = $this->getCurrentVersion();
        $this->logger->log('system', 'updated', "WebRajya ERP successfully updated to version {$newVersion}");

        return [
            'success' => true,
            'message' => "WebRajya ERP updated to version {$newVersion}!",
            'version' => $newVersion,
        ];
    }
}

