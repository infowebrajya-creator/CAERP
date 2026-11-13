<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Exception;
use Illuminate\Console\Command;

class CreateBackupCommand extends Command
{
    protected $signature = 'backup:create {--name= : Optional custom label for the backup}';

    protected $description = 'Create a full SQL database backup of WebRajya ERP';

    public function handle(BackupService $backupService): int
    {
        $this->info('Creating WebRajya database backup...');

        try {
            $backup = $backupService->createDatabaseBackup($this->option('name'));
            $this->info('Backup created successfully!');
            $this->table(
                ['File', 'Size', 'Path'],
                [[$backup['filename'], $backup['size'], $backup['path']]]
            );
            return self::SUCCESS;
        } catch (Exception $e) {
            $this->error('Backup failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}

