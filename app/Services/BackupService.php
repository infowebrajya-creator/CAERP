<?php

namespace App\Services;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class BackupService
{
    protected string $backupDir;

    public function __construct(
        protected ActivityLogger $logger
    ) {
        $this->backupDir = storage_path('app/backups');
        if (! File::exists($this->backupDir)) {
            File::makeDirectory($this->backupDir, 0755, true, true);
        }
    }

    /**
     * Get the absolute path to backups directory.
     */
    public function getBackupDirectory(): string
    {
        return $this->backupDir;
    }

    /**
     * List all available backup files.
     */
    public function listBackups(): array
    {
        if (! File::exists($this->backupDir)) {
            return [];
        }

        $files = File::files($this->backupDir);
        $backups = [];

        foreach ($files as $file) {
            $extension = strtolower($file->getExtension());
            if (! in_array($extension, ['sql', 'gz'])) {
                continue;
            }

            $sizeBytes = $file->getSize();
            $formattedSize = $this->formatBytes($sizeBytes);

            $backups[] = [
                'filename' => $file->getFilename(),
                'path' => $file->getPathname(),
                'size_bytes' => $sizeBytes,
                'size' => $formattedSize,
                'created_at' => Carbon::createFromTimestamp($file->getMTime()),
                'is_compressed' => ($extension === 'gz'),
            ];
        }

        // Sort descending by modification time (newest first)
        usort($backups, fn ($a, $b) => $b['created_at']->timestamp <=> $a['created_at']->timestamp);

        return $backups;
    }

    /**
     * Create a full database backup.
     * Uses pure PHP PDO generation so it runs on ANY machine without requiring external mysqldump binary.
     */
    public function createDatabaseBackup(?string $customName = null): array
    {
        $timestamp = Carbon::now()->format('Y-m-d_His');
        $prefix = $customName ? preg_replace('/[^a-zA-Z0-9_\-]/', '_', $customName) : 'webrajya_backup';
        $filename = "{$prefix}_{$timestamp}.sql";
        $filepath = $this->backupDir . DIRECTORY_SEPARATOR . $filename;

        $pdo = DB::connection()->getPdo();
        $driver = DB::connection()->getDriverName();
        $databaseName = DB::connection()->getDatabaseName();

        $handle = fopen($filepath, 'w');
        if (! $handle) {
            throw new Exception("Unable to open file for writing: {$filepath}");
        }

        // SQL Header
        fwrite($handle, "-- =========================================================\n");
        fwrite($handle, "-- WebRajya ERP Database Backup\n");
        fwrite($handle, "-- Generated: " . Carbon::now()->format('Y-m-d H:i:s') . "\n");
        fwrite($handle, "-- Database: {$databaseName} ({$driver})\n");
        fwrite($handle, "-- Platform: " . PHP_OS_FAMILY . " (" . php_uname('s') . ")\n");
        fwrite($handle, "-- =========================================================\n\n");

        if ($driver === 'mysql') {
            fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n");
            fwrite($handle, "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n");
            fwrite($handle, "START TRANSACTION;\n");
            fwrite($handle, "SET time_zone = '+00:00';\n\n");

            // Fetch all MySQL tables
            $tablesStmt = $pdo->query('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"');
            $tables = $tablesStmt->fetchAll(\PDO::FETCH_NUM);

            foreach ($tables as $tableRow) {
                $table = $tableRow[0];

                // 1. Table structure
                fwrite($handle, "--\n-- Table structure for table `{$table}`\n--\n");
                fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");

                $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`");
                $createRow = $createStmt->fetch(\PDO::FETCH_ASSOC);
                $createSql = $createRow['Create Table'] ?? $createRow['Create View'] ?? '';
                fwrite($handle, $createSql . ";\n\n");

                // 2. Table data
                $rowsStmt = $pdo->query("SELECT * FROM `{$table}`");
                $rows = $rowsStmt->fetchAll(\PDO::FETCH_ASSOC);

                if (! empty($rows)) {
                    fwrite($handle, "--\n-- Dumping data for table `{$table}`\n--\n");
                    $columns = array_map(fn ($c) => "`{$c}`", array_keys($rows[0]));
                    $columnsSql = implode(', ', $columns);

                    $batches = array_chunk($rows, 50);
                    foreach ($batches as $batch) {
                        $insertValues = [];
                        foreach ($batch as $row) {
                            $escapedRow = [];
                            foreach ($row as $val) {
                                if ($val === null) {
                                    $escapedRow[] = 'NULL';
                                } else {
                                    $escapedRow[] = $pdo->quote($val);
                                }
                            }
                            $insertValues[] = '(' . implode(', ', $escapedRow) . ')';
                        }
                        fwrite($handle, "INSERT INTO `{$table}` ({$columnsSql}) VALUES\n" . implode(",\n", $insertValues) . ";\n");
                    }
                    fwrite($handle, "\n");
                }
            }

            fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
            fwrite($handle, "COMMIT;\n");
        } else {
            // SQLite support
            fwrite($handle, "PRAGMA foreign_keys = OFF;\n\n");

            $tablesStmt = $pdo->query("SELECT name, sql FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            $tables = $tablesStmt->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($tables as $tableRow) {
                $table = $tableRow['name'];
                $createSql = $tableRow['sql'];

                fwrite($handle, "-- Table `{$table}`\n");
                fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");
                fwrite($handle, $createSql . ";\n\n");

                $rowsStmt = $pdo->query("SELECT * FROM `{$table}`");
                $rows = $rowsStmt->fetchAll(\PDO::FETCH_ASSOC);

                if (! empty($rows)) {
                    $columns = array_map(fn ($c) => "`{$c}`", array_keys($rows[0]));
                    $columnsSql = implode(', ', $columns);

                    $batches = array_chunk($rows, 50);
                    foreach ($batches as $batch) {
                        $insertValues = [];
                        foreach ($batch as $row) {
                            $escapedRow = [];
                            foreach ($row as $val) {
                                if ($val === null) {
                                    $escapedRow[] = 'NULL';
                                } else {
                                    $escapedRow[] = $pdo->quote($val);
                                }
                            }
                            $insertValues[] = '(' . implode(', ', $escapedRow) . ')';
                        }
                        fwrite($handle, "INSERT INTO `{$table}` ({$columnsSql}) VALUES\n" . implode(",\n", $insertValues) . ";\n");
                    }
                    fwrite($handle, "\n");
                }
            }

            fwrite($handle, "PRAGMA foreign_keys = ON;\n");
        }

        fclose($handle);

        // Optional GZIP compression if extension is loaded
        if (function_exists('gzopen')) {
            $gzPath = $filepath . '.gz';
            $gzHandle = gzopen($gzPath, 'w9');
            $sqlHandle = fopen($filepath, 'r');
            while (! feof($sqlHandle)) {
                gzwrite($gzHandle, fread($sqlHandle, 65536));
            }
            fclose($sqlHandle);
            gzclose($gzHandle);

            // Remove raw .sql to save disk space
            unlink($filepath);
            $filepath = $gzPath;
            $filename = basename($gzPath);
        }

        $size = filesize($filepath);

        $this->logger->log('backup', 'created', "Created database backup: {$filename} (" . $this->formatBytes($size) . ')');

        return [
            'filename' => $filename,
            'path' => $filepath,
            'size' => $this->formatBytes($size),
            'size_bytes' => $size,
        ];
    }

    /**
     * Restore database from a backup file.
     */
    public function restoreBackup(string $filename): array
    {
        // Sanitize filename
        $filename = basename($filename);
        $filepath = $this->backupDir . DIRECTORY_SEPARATOR . $filename;

        if (! File::exists($filepath)) {
            throw new Exception("Backup file not found: {$filename}");
        }

        // Read content (decompress if .gz)
        if (str_ends_with($filename, '.gz')) {
            if (! function_exists('gzopen')) {
                throw new Exception("zlib/gzip extension is required to restore .gz backups.");
            }
            $gz = gzopen($filepath, 'r');
            $sql = '';
            while (! gzeof($gz)) {
                $sql .= gzread($gz, 65536);
            }
            gzclose($gz);
        } else {
            $sql = File::get($filepath);
        }

        if (empty($sql)) {
            throw new Exception("Backup file is empty or corrupted.");
        }

        $pdo = DB::connection()->getPdo();
        $driver = DB::connection()->getDriverName();

        // Execute raw multi-query safely
        $pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, 0);
        if ($driver === 'mysql') {
            $pdo->exec('SET FOREIGN_KEY_CHECKS=0;');
        } else {
            $pdo->exec('PRAGMA foreign_keys = OFF;');
        }

        // Split queries by semicolon not inside quotes
        $queries = $this->splitSqlQueries($sql);
        $executed = 0;

        foreach ($queries as $query) {
            $query = trim($query);
            if (! empty($query) && ! str_starts_with($query, '--')) {
                $pdo->exec($query);
                $executed++;
            }
        }

        if ($driver === 'mysql') {
            $pdo->exec('SET FOREIGN_KEY_CHECKS=1;');
        } else {
            $pdo->exec('PRAGMA foreign_keys = ON;');
        }

        $this->logger->log('backup', 'restored', "Restored database from backup: {$filename} ({$executed} statements)");

        return [
            'success' => true,
            'statements_executed' => $executed,
            'filename' => $filename,
        ];
    }

    /**
     * Delete a backup file.
     */
    public function deleteBackup(string $filename): bool
    {
        $filename = basename($filename);
        $filepath = $this->backupDir . DIRECTORY_SEPARATOR . $filename;

        if (File::exists($filepath)) {
            File::delete($filepath);
            $this->logger->log('backup', 'deleted', "Deleted database backup: {$filename}");
            return true;
        }

        return false;
    }

    /**
     * Split SQL script into individual executable statements.
     */
    protected function splitSqlQueries(string $sql): array
    {
        // Remove multi-line comments
        $sql = preg_replace('!/\*.*?\*/!s', '', $sql);

        $queries = [];
        $query = '';
        $inString = false;
        $stringChar = '';
        $escaped = false;

        $len = strlen($sql);
        for ($i = 0; $i < $len; $i++) {
            $char = $sql[$i];

            if ($inString) {
                if ($escaped) {
                    $escaped = false;
                } elseif ($char === '\\') {
                    $escaped = true;
                } elseif ($char === $stringChar) {
                    $inString = false;
                }
                $query .= $char;
            } else {
                if ($char === "'" || $char === '"') {
                    $inString = true;
                    $stringChar = $char;
                    $query .= $char;
                } elseif ($char === ';') {
                    if (trim($query) !== '') {
                        $queries[] = $query;
                    }
                    $query = '';
                } else {
                    $query .= $char;
                }
            }
        }

        if (trim($query) !== '') {
            $queries[] = $query;
        }

        return $queries;
    }

    /**
     * Format bytes into human readable string.
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
