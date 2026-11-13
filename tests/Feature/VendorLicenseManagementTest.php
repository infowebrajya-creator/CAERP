<?php

namespace Tests\Feature;

use App\Models\SystemLicense;
use App\Models\User;
use App\Services\LicenseService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorLicenseManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_access_vendor_license_manager(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($superAdmin)->get(route('admin.licenses.index'));
        $response->assertOk();
        $response->assertSee('Vendor License Manager');
        $response->assertSee('Issue License Key');
    }

    public function test_super_admin_can_generate_license_key(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($superAdmin)->post(route('admin.licenses.store'), [
            'licensed_to' => 'Singhania & Sons Chartered Accountants',
            'email' => 'singhania@ca.in',
            'phone' => '+91 9988776655',
            'edition' => 'Lifetime Enterprise',
            'max_staff_users' => 50,
            'amc_years' => 1,
            'sale_price' => 45000.00,
            'invoice_number' => 'WR-2026-001',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $response->assertSessionHas('new_delivery_package');

        $this->assertDatabaseHas('system_licenses', [
            'licensed_to' => 'Singhania & Sons Chartered Accountants',
            'email' => 'singhania@ca.in',
            'edition' => 'Lifetime Enterprise',
            'max_staff_users' => 50,
        ]);
    }

    public function test_super_admin_can_renew_amc(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $licenseService = app(LicenseService::class);
        $key = $licenseService->generateLicenseKey('Renewal Firm CA', amcExpiresAt: '2026-11-01');
        $licenseService->activate($key);

        $license = SystemLicense::where('licensed_to', 'Renewal Firm CA')->first();

        $response = $this->actingAs($superAdmin)->post(route('admin.licenses.renew_amc', $license->id), [
            'renew_years' => 1,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $license->refresh();
        $this->assertTrue($license->amc_expires_at->isFuture());
    }

    public function test_super_admin_can_export_licenses_to_csv(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($superAdmin)->get(route('admin.licenses.export_csv'));
        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
    }

    public function test_super_admin_can_delete_license(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $license = SystemLicense::create([
            'key_hash' => hash('sha256', 'dummy-key'),
            'license_key' => 'dummy-key',
            'licensed_to' => 'To Be Deleted CA',
            'status' => 'issued',
            'edition' => 'Lifetime Edition',
            'max_staff_users' => 10,
        ]);

        $response = $this->actingAs($superAdmin)->delete(route('admin.licenses.destroy', $license->id));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('system_licenses', [
            'licensed_to' => 'To Be Deleted CA',
        ]);
    }

    public function test_non_super_admin_cannot_access_vendor_license_manager(): void
    {
        $partner = User::factory()->create([
            'role' => 'partner',
            'is_active' => true,
        ]);

        $response = $this->actingAs($partner)->get(route('admin.licenses.index'));
        $response->assertForbidden();

        $employee = User::factory()->create([
            'role' => 'employee',
            'is_active' => true,
        ]);

        $employeeResponse = $this->actingAs($employee)->get(route('admin.licenses.index'));
        $employeeResponse->assertForbidden();
    }
}
