<?php

namespace App\Http\Controllers;

use App\Services\LicenseService;
use Illuminate\Http\Request;

class LicenseController extends Controller
{
    public function __construct(
        protected LicenseService $licenseService
    ) {}

    /**
     * Show the license activation view.
     */
    public function showActivation()
    {
        $info = $this->licenseService->getLicenseInfo();
        $fingerprint = $this->licenseService->getMachineFingerprint();

        return view('license.activate', [
            'info' => $info,
            'fingerprint' => $fingerprint,
        ]);
    }

    /**
     * Handle license activation submission.
     */
    public function activate(Request $request)
    {
        $request->validate([
            'license_key' => 'required|string|min:20',
        ], [
            'license_key.required' => 'Please enter your WebRajya ERP license key.',
            'license_key.min' => 'Invalid license key format.',
        ]);

        $result = $this->licenseService->activate($request->input('license_key'));

        if (! $result['success']) {
            return back()
                ->withInput()
                ->withErrors(['license_key' => $result['message']]);
        }

        return redirect()->route('dashboard')->with('success', $result['message']);
    }

    /**
     * Deactivate current license (for transferring to another machine).
     */
    public function deactivate(Request $request)
    {
        $this->licenseService->deactivate();

        return redirect()->route('license.activate')->with('info', 'License has been deactivated from this machine.');
    }

    /**
     * Show the End User License Agreement (EULA).
     */
    public function showEula()
    {
        return view('license.eula');
    }

    /**
     * Return JSON status of current license.
     */
    public function status()
    {
        return response()->json($this->licenseService->getLicenseInfo());
    }
}
