<?php

namespace App\Http\Controllers;

use App\Services\BackupService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    public function __construct(
        protected BackupService $backupService
    ) {}

    /**
     * Trigger a new database backup.
     */
    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdminOrPartner(), 403);

        try {
            $backup = $this->backupService->createDatabaseBackup($request->input('name'));
            return back()->with('success', "Database backup created successfully: {$backup['filename']} ({$backup['size']})");
        } catch (Exception $e) {
            return back()->with('error', 'Backup failed: ' . $e->getMessage());
        }
    }

    /**
     * Download a specific backup file.
     */
    public function download(Request $request, string $filename): BinaryFileResponse
    {
        abort_unless($request->user()->isAdminOrPartner(), 403);

        $filename = basename($filename);
        $filepath = $this->backupService->getBackupDirectory() . DIRECTORY_SEPARATOR . $filename;

        if (! file_exists($filepath)) {
            abort(404, 'Backup file not found.');
        }

        return response()->download($filepath, $filename, [
            'Content-Type' => str_ends_with($filename, '.gz') ? 'application/gzip' : 'application/sql',
        ]);
    }

    /**
     * Restore database from an existing backup.
     */
    public function restore(Request $request, string $filename): RedirectResponse
    {
        abort_unless($request->user()->isAdminOrPartner(), 403);

        try {
            $result = $this->backupService->restoreBackup($filename);
            return back()->with('success', "Database successfully restored from {$result['filename']} ({$result['statements_executed']} statements executed).");
        } catch (Exception $e) {
            return back()->with('error', 'Database restore failed: ' . $e->getMessage());
        }
    }

    /**
     * Upload and save an external backup file.
     */
    public function upload(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdminOrPartner(), 403);

        $request->validate([
            'backup_file' => 'required|file|max:102400', // max 100MB
        ]);

        $file = $request->file('backup_file');
        $ext = strtolower($file->getClientOriginalExtension());

        if (! in_array($ext, ['sql', 'gz'])) {
            return back()->with('error', 'Invalid file type. Only .sql or .sql.gz files are supported.');
        }

        $filename = 'uploaded_' . date('Y-m-d_His') . '_' . preg_replace('/[^a-zA-Z0-9_\.]/', '_', $file->getClientOriginalName());
        $file->move($this->backupService->getBackupDirectory(), $filename);

        return back()->with('success', "Backup file '{$filename}' uploaded successfully.");
    }

    /**
     * Delete a backup file.
     */
    public function destroy(Request $request, string $filename): RedirectResponse
    {
        abort_unless($request->user()->isAdminOrPartner(), 403);

        $deleted = $this->backupService->deleteBackup($filename);
        if ($deleted) {
            return back()->with('success', 'Backup file deleted.');
        }

        return back()->with('error', 'Failed to delete backup file.');
    }
}

