<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class BackupSystemTest extends TestCase
{
    use RefreshDatabase;

    protected BackupService $backupService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->backupService = app(BackupService::class);
    }

    public function test_backup_can_be_created_and_listed(): void
    {
        $backup = $this->backupService->createDatabaseBackup('test_snap');

        $this->assertNotEmpty($backup['filename']);
        $this->assertFileExists($backup['path']);

        $list = $this->backupService->listBackups();
        $this->assertNotEmpty($list);
        $this->assertSame($backup['filename'], $list[0]['filename']);

        // Clean up
        $this->backupService->deleteBackup($backup['filename']);
    }

    public function test_backup_create_and_list_artisan_commands(): void
    {
        $this->artisan('backup:create', ['--name' => 'artisan_test'])
            ->assertExitCode(0);

        $this->artisan('backup:list')
            ->assertExitCode(0);

        // Clean up
        $backups = $this->backupService->listBackups();
        foreach ($backups as $b) {
            if (str_contains($b['filename'], 'artisan_test')) {
                $this->backupService->deleteBackup($b['filename']);
            }
        }
    }

    public function test_admin_can_create_backup_via_web(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('backups.store'), [
            'name' => 'web_test',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Clean up
        $backups = $this->backupService->listBackups();
        foreach ($backups as $b) {
            if (str_contains($b['filename'], 'web_test')) {
                $this->backupService->deleteBackup($b['filename']);
            }
        }
    }

    public function test_admin_can_download_and_delete_backup(): void
    {
        $admin = User::factory()->create([
            'role' => 'partner',
            'is_active' => true,
        ]);
        $backup = $this->backupService->createDatabaseBackup('download_test');

        // Download
        $downloadResponse = $this->actingAs($admin)->get(route('backups.download', $backup['filename']));
        $downloadResponse->assertOk();

        // Delete
        $deleteResponse = $this->actingAs($admin)->delete(route('backups.destroy', $backup['filename']));
        $deleteResponse->assertRedirect();
        $deleteResponse->assertSessionHas('success');

        $this->assertFileDoesNotExist($backup['path']);
    }

    public function test_client_portal_user_cannot_access_backups(): void
    {
        $clientUser = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
        ]);

        $response = $this->actingAs($clientUser)->post(route('backups.store'));
        $response->assertRedirect(route('portal.dashboard'));

        $employee = User::factory()->create([
            'role' => 'employee',
            'is_active' => true,
        ]);

        $employeeResponse = $this->actingAs($employee)->post(route('backups.store'));
        $employeeResponse->assertForbidden();
    }
}
