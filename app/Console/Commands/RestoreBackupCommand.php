<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Exception;
use Illuminate\Console\Command;

class RestoreBackupCommand extends Command
{
    protected $signature = 'backup:restore {filename : The backup filename to restore}';

    protected $description = 'Restore WebRajya ERP database from a backup file';

    public function handle(BackupService $backupService): int
    {
        $filename = $this->argument('filename');

        if (! $this->confirm("WARNING: This will overwrite the current database with data from '{$filename}'. Continue?")) {
            $this->comment('Restore cancelled.');
            return self::SUCCESS;
        }

        $this->info("Restoring database from {$filename}...");

        try {
            $result = $backupService->restoreBackup($filename);
            $this->info("Database restore completed successfully! ({$result['statements_executed']} statements executed)");
            return self::SUCCESS;
        } catch (Exception $e) {
            $this->error('Restore failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
