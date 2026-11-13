<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class ListBackupsCommand extends Command
{
    protected $signature = 'backup:list';

    protected $description = 'List all available database backups for WebRajya ERP';

    public function handle(BackupService $backupService): int
    {
        $backups = $backupService->listBackups();

        if (empty($backups)) {
            $this->comment('No backups found in ' . $backupService->getBackupDirectory());
            return self::SUCCESS;
        }

        $rows = array_map(fn ($b) => [
            $b['filename'],
            $b['size'],
            $b['created_at']->format('d M Y, h:i A'),
            $b['is_compressed'] ? 'GZIP' : 'SQL',
        ], $backups);

        $this->table(['Filename', 'Size', 'Created At', 'Format'], $rows);

        return self::SUCCESS;
    }
}
