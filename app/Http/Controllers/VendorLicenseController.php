<?php

namespace App\Http\Controllers;

use App\Models\SystemLicense;
use App\Services\ActivityLogger;
use App\Services\LicenseService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VendorLicenseController extends Controller
{
    public function __construct(
        protected LicenseService $licenseService,
        protected ActivityLogger $logger
    ) {}

    /**
     * Display vendor license management dashboard.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');

        $query = SystemLicense::query()->search($search);

        if ($status) {
            $query->where('status', $status);
        }

        $licenses = $query->latest()->paginate(15)->withQueryString();

        // Metrics for Vendor
        $totalCount = SystemLicense::count();
        $activeCount = SystemLicense::where('status', 'active')->count();
        $totalRevenue = SystemLicense::all()->sum(fn ($l) => $l->getSalePrice());
        $amcDueCount = SystemLicense::whereNotNull('amc_expires_at')
            ->where('amc_expires_at', '<=', Carbon::now()->addDays(30))
            ->count();

        return view('admin.licenses.index', [
            'licenses' => $licenses,
            'search' => $search,
            'status' => $status,
            'metrics' => [
                'total' => $totalCount,
                'active' => $activeCount,
                'revenue' => $totalRevenue,
                'amc_due' => $amcDueCount,
            ],
            'newDeliveryPackage' => session('new_delivery_package'),
        ]);
    }

    /**
     * Generate and issue a new cryptographic license key.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'licensed_to' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'edition' => 'required|string|max:100',
            'max_staff_users' => 'required|integer|min:1|max:500',
            'amc_years' => 'required|integer|min:1|max:10',
            'sale_price' => 'nullable|numeric|min:0',
            'invoice_number' => 'nullable|string|max:100',
            'machine_fingerprint' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $amcExpiryDate = Carbon::now()->addYears((int) $validated['amc_years'])->format('Y-m-d');

        $hw = ! empty($validated['machine_fingerprint']) ? $validated['machine_fingerprint'] : null;

        $key = $this->licenseService->generateLicenseKey(
            licensedTo: $validated['licensed_to'],
            email: $validated['email'] ?? null,
            edition: $validated['edition'],
            maxStaff: (int) $validated['max_staff_users'],
            amcExpiresAt: $amcExpiryDate,
            boundHardwareId: $hw
        );

        $keyHash = hash('sha256', $key);
        $salePrice = ! empty($validated['sale_price']) ? (float) $validated['sale_price'] : 45000.00;

        $license = SystemLicense::create([
            'key_hash' => $keyHash,
            'license_key' => $key,
            'licensed_to' => $validated['licensed_to'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'machine_fingerprint' => $hw,
            'status' => $hw ? 'active' : 'issued',
            'edition' => $validated['edition'],
            'max_staff_users' => (int) $validated['max_staff_users'],
            'amc_expires_at' => Carbon::parse($amcExpiryDate),
            'meta_data' => [
                'sale_price' => $salePrice,
                'invoice_number' => $validated['invoice_number'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'issued_by' => $request->user()->name,
                'issued_at' => Carbon::now()->toIso8601String(),
            ],
        ]);

        // Build ready-to-send Client Delivery Text for WhatsApp/Email
        $deliveryMessage = "Hello {$validated['licensed_to']},\n\n"
            . "Thank you for choosing WebRajya ERP — Chartered Accountant Practice Management System!\n\n"
            . "Here are your official license and activation details:\n"
            . "--------------------------------------------------\n"
            . "Firm Name     : {$validated['licensed_to']}\n"
            . "Edition       : {$validated['edition']}\n"
            . "Staff Limit   : {$validated['max_staff_users']} Users\n"
            . "AMC Validity  : {$amcExpiryDate} ({$validated['amc_years']} Year Updates Included)\n"
            . "--------------------------------------------------\n"
            . "LICENSE KEY   :\n{$key}\n"
            . "--------------------------------------------------\n\n"
            . "Quick Setup Instructions:\n"
            . "1. Download and open WebRajya ERP on your computer.\n"
            . "2. Navigate to: http://localhost:8000/license/activate\n"
            . "3. Paste your license key above and click 'Activate WebRajya ERP'.\n\n"
            . "Support: support@webrajya.in | Website: https://webrajya.in\n"
            . "Welcome to modern, secure CA practice management!";

        $this->logger->log('license', 'issued', "Generated new {$validated['edition']} license for {$validated['licensed_to']}");

        return back()
            ->with('success', "License key generated successfully for {$validated['licensed_to']}!")
            ->with('new_delivery_package', [
                'firm' => $validated['licensed_to'],
                'key' => $key,
                'message' => $deliveryMessage,
            ]);
    }

    /**
     * Renew or extend AMC for a license.
     */
    public function renewAmc(Request $request, SystemLicense $license): RedirectResponse
    {
        $years = (int) $request->input('renew_years', 1);
        $currentExpiry = $license->amc_expires_at && $license->amc_expires_at->isFuture()
            ? $license->amc_expires_at
            : Carbon::now();

        $newExpiry = $currentExpiry->copy()->addYears($years);

        // Regenerate key with updated AMC date
        $newKey = $this->licenseService->generateLicenseKey(
            licensedTo: $license->licensed_to,
            email: $license->email,
            edition: $license->edition,
            maxStaff: $license->max_staff_users,
            amcExpiresAt: $newExpiry->format('Y-m-d'),
            boundHardwareId: $license->machine_fingerprint
        );

        $license->update([
            'key_hash' => hash('sha256', $newKey),
            'license_key' => $newKey,
            'amc_expires_at' => $newExpiry,
            'status' => 'active',
            'meta_data' => array_merge($license->meta_data ?? [], [
                'last_amc_renewal' => Carbon::now()->toIso8601String(),
                'amc_renewed_by' => $request->user()->name,
            ]),
        ]);

        $this->logger->log('license', 'renewed', "Renewed AMC for {$license->licensed_to} until " . $newExpiry->format('d M Y'));

        return back()->with('success', "AMC extended for {$license->licensed_to} until " . $newExpiry->format('d M Y') . "! New license key generated.");
    }

    /**
     * Export licenses to CSV.
     */
    public function exportCsv(): StreamedResponse
    {
        $licenses = SystemLicense::latest()->get();
        $filename = 'webrajya_licenses_export_' . date('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($licenses) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'ID',
                'CA Firm Name',
                'Email',
                'Phone',
                'Edition',
                'Staff Limit',
                'Status',
                'Machine ID',
                'Sale Price (INR)',
                'Invoice Number',
                'Issued At',
                'AMC Expiry',
                'License Key',
            ]);

            foreach ($licenses as $l) {
                fputcsv($handle, [
                    $l->id,
                    $l->licensed_to,
                    $l->email ?? 'N/A',
                    $l->phone ?? 'N/A',
                    $l->edition,
                    $l->max_staff_users,
                    $l->status,
                    $l->machine_fingerprint ?? 'Pending Activation',
                    $l->getSalePrice(),
                    $l->meta_data['invoice_number'] ?? 'N/A',
                    $l->created_at->format('Y-m-d H:i:s'),
                    $l->amc_expires_at?->format('Y-m-d') ?? 'Lifetime',
                    $l->license_key,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Revoke or delete a license record.
     */
    public function destroy(SystemLicense $license): RedirectResponse
    {
        $firm = $license->licensed_to;
        $license->delete();

        $this->logger->log('license', 'revoked', "Revoked / deleted license record for {$firm}");

        return back()->with('success', "License record for '{$firm}' has been deleted.");
    }
}
