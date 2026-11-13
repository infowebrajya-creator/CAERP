<?php

namespace Tests\Feature;

use App\Models\SystemLicense;
use App\Models\User;
use App\Services\LicenseService;
use App\Services\UpdateService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UpdateSystemTest extends TestCase
{
    use RefreshDatabase;

    protected UpdateService $updateService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->updateService = app(UpdateService::class);
    }

    public function test_current_version_is_retrieved(): void
    {
        $version = $this->updateService->getCurrentVersion();
        $this->assertSame('1.0.0', $version);
    }

    public function test_update_check_detects_new_release(): void
    {
        Http::fake([
            '*' => Http::response([
                'version' => '1.1.0',
                'release_date' => '2026-12-01',
                'changelog' => ['New GST Form 3B automated reconciliation', 'Speed improvements'],
                'download_url' => 'https://github.com/infowebrajya-creator/CAERP/releases/v1.1.0',
            ], 200),
        ]);

        $result = $this->updateService->checkForUpdates(forceRefresh: true);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['has_update']);
        $this->assertSame('1.1.0', $result['latest_version']);
        $this->assertSame('1.0.0', $result['current_version']);
        $this->assertCount(2, $result['changelog']);
    }

    public function test_update_check_command(): void
    {
        Http::fake([
            '*' => Http::response([
                'version' => '1.0.0',
            ], 200),
        ]);

        $this->artisan('update:check')
            ->assertExitCode(0);
    }

    public function test_admin_can_trigger_update_check_via_web(): void
    {
        Http::fake([
            '*' => Http::response([
                'version' => '1.0.0',
            ], 200),
        ]);

        $admin = User::factory()->create([
            'role' => 'partner',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('updates.check'));
        $response->assertRedirect();
        $response->assertSessionHas('info');
    }

    public function test_amc_expired_blocks_update_permission(): void
    {
        Http::fake([
            '*' => Http::response([
                'version' => '1.2.0',
                'release_date' => '2027-01-01',
            ], 200),
        ]);

        // Create expired license
        $licenseService = app(LicenseService::class);
        $key = $licenseService->generateLicenseKey('Expired CA Firm', amcExpiresAt: '2025-01-01');
        $licenseService->activate($key);

        $result = $this->updateService->checkForUpdates(forceRefresh: true);

        $this->assertTrue($result['has_update']);
        $this->assertFalse($result['is_amc_valid']);
        $this->assertFalse($result['can_install']);
    }
}
