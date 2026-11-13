<?php

namespace App\Http\Controllers;

use App\Services\UpdateService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UpdateController extends Controller
{
    public function __construct(
        protected UpdateService $updateService
    ) {}

    /**
     * Check for updates manually from web UI.
     */
    public function check(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdminOrPartner(), 403);

        $result = $this->updateService->checkForUpdates(forceRefresh: true);

        if (! $result['success']) {
            return back()->with('info', 'Update check status: ' . ($result['error'] ?? 'Could not reach update server.'));
        }

        if ($result['has_update']) {
            if (! $result['is_amc_valid']) {
                return back()->with('warning', "New version {$result['latest_version']} is available! However, your AMC maintenance period has expired. Please renew to install new updates.");
            }

            return back()->with('success', "A new version of WebRajya ERP ({$result['latest_version']}) is available for download!");
        }

        return back()->with('info', "WebRajya ERP is up to date (Version {$result['current_version']}).");
    }

    /**
     * Upload and apply a manual update patch (.zip).
     */
    public function applyPatch(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdminOrPartner(), 403);

        $request->validate([
            'patch_file' => 'required|file|mimes:zip|max:51200', // max 50MB
        ]);

        $file = $request->file('patch_file');
        $tempPath = storage_path('app/temp_patch_' . time() . '.zip');
        $file->move(dirname($tempPath), basename($tempPath));

        try {
            $result = $this->updateService->applyZipUpdate($tempPath);
            @unlink($tempPath);
            return back()->with('success', $result['message']);
        } catch (Exception $e) {
            @unlink($tempPath);
            return back()->with('error', 'Update failed: ' . $e->getMessage());
        }
    }
}

