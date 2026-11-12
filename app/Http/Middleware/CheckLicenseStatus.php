<?php

namespace App\Http\Middleware;

use App\Services\LicenseService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckLicenseStatus
{
    public function __construct(
        protected LicenseService $licenseService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Allowed paths that don't require an active license
        if (
            $request->is('license/*') ||
            $request->is('license') ||
            $request->is('up') ||
            $request->is('login') ||
            $request->is('logout')
        ) {
            return $next($request);
        }

        if (! $this->licenseService->isActivated()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'error' => 'WebRajya ERP is not activated or hardware mismatch detected.',
                    'machine_fingerprint' => $this->licenseService->getMachineFingerprint(),
                    'activate_url' => route('license.activate'),
                ], 403);
            }

            return redirect()->route('license.activate')->with('warning', 'Please activate your WebRajya ERP license for this computer.');
        }

        return $next($request);
    }
}
