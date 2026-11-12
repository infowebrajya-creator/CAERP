<?php

namespace Tests\Feature;

use App\Models\SystemLicense;
use App\Models\User;
use App\Services\LicenseService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LicenseSystemTest extends TestCase
{
    use RefreshDatabase;

    protected LicenseService $licenseService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->licenseService = app(LicenseService::class);
    }

    public function test_machine_fingerprint_is_generated_and_formatted(): void
    {
        $fingerprint = $this->licenseService->getMachineFingerprint();

        $this->assertNotEmpty($fingerprint);
        $this->assertStringStartsWith('WR-HW-', $fingerprint);
    }

    public function test_license_key_generation_and_cryptographic_verification(): void
    {
        $key = $this->licenseService->generateLicenseKey(
            licensedTo: 'Gupta & Co. Chartered Accountants',
            email: 'ca@guptaco.in',
            edition: 'Lifetime Pro',
            maxStaff: 30,
            amcExpiresAt: '2027-12-31'
        );

        $this->assertStringStartsWith('WRLIC-', $key);

        $payload = $this->licenseService->verifyKeyFormat($key);
        $this->assertNotNull($payload);
        $this->assertSame('Gupta & Co. Chartered Accountants', $payload['to']);
        $this->assertSame('Lifetime Pro', $payload['edition']);
        $this->assertSame(30, $payload['staff']);
        $this->assertSame('2027-12-31', $payload['amc']);
    }

    public function test_tampered_license_key_is_rejected(): void
    {
        $key = $this->licenseService->generateLicenseKey('Original Firm');
        $tamperedKey = substr($key, 0, -4) . 'ZZZZ';

        $payload = $this->licenseService->verifyKeyFormat($tamperedKey);
        $this->assertNull($payload);
    }

    public function test_activation_succeeds_and_binds_to_current_machine(): void
    {
        $key = $this->licenseService->generateLicenseKey('Mehta & Associates CA');
        $currentHw = $this->licenseService->getMachineFingerprint();

        $result = $this->licenseService->activate($key);

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('system_licenses', [
            'licensed_to' => 'Mehta & Associates CA',
            'status' => 'active',
            'machine_fingerprint' => $currentHw,
        ]);

        $info = $this->licenseService->getLicenseInfo();
        $this->assertTrue($info['is_activated']);
        $this->assertSame('Mehta & Associates CA', $info['licensed_to']);
    }

    public function test_hardware_mismatched_key_fails_activation(): void
    {
        // Generate key bound to a different machine
        $key = $this->licenseService->generateLicenseKey(
            licensedTo: 'Locked Firm CA',
            boundHardwareId: 'WR-HW-9999-8888-7777-6666'
        );

        $result = $this->licenseService->activate($key);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('locked to another machine', $result['message']);
    }

    public function test_deactivation_works_properly(): void
    {
        $key = $this->licenseService->generateLicenseKey('Deactivation Test CA');
        $this->licenseService->activate($key);

        $this->assertTrue($this->licenseService->isActivated());

        $deactivated = $this->licenseService->deactivate();
        $this->assertTrue($deactivated);

        $info = $this->licenseService->getLicenseInfo();
        $this->assertFalse($info['is_activated']);
    }

    public function test_license_status_artisan_command(): void
    {
        $this->artisan('license:status')
            ->assertExitCode(0);
    }

    public function test_license_generate_artisan_command(): void
    {
        $this->artisan('license:generate', [
            '--to' => 'Joshi & Partners CA',
            '--email' => 'joshi@ca.in',
            '--edition' => 'Lifetime Edition',
            '--staff' => 15,
            '--amc-years' => 2,
        ])->assertExitCode(0);
    }

    public function test_license_activate_web_route(): void
    {
        $key = $this->licenseService->generateLicenseKey('Web Activated CA');

        $response = $this->post(route('license.submit'), [
            'license_key' => $key,
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertTrue($this->licenseService->isActivated());
    }
}
