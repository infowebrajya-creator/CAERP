<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>End User License Agreement (EULA) — {{ config('brand.app_name', 'WebRajya ERP') }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset(config('brand.favicon_path', '/images/favicon.ico')) }}">
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; }
            .eula-container { max-width: 100% !important; box-shadow: none !important; border: none !important; }
        }
    </style>
</head>
<body class="bg-light py-4">
<div class="container eula-container" style="max-width: 860px;">
    <!-- Top Action Bar -->
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <a href="javascript:history.back()" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            <span>Back</span>
        </a>
        <button onclick="window.print()" class="btn btn-sm btn-primary d-flex align-items-center gap-1">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            <span>Print / Save as PDF</span>
        </button>
    </div>

    <!-- Agreement Card -->
    <div class="ca-card p-4 p-md-5 bg-white shadow-sm border">
        <div class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-4">
            <div>
                <x-ca-logo :size="36" />
                <h1 class="h5 fw-bold mt-3 mb-1" style="color: #1e3a5f;">End User License Agreement (EULA)</h1>
                <div class="text-muted small">Standard Chartered Accountant Software License &amp; Maintenance Terms</div>
            </div>
            <div class="text-end small text-muted">
                <div><strong>Effective Date:</strong> 13 Nov 2026</div>
                <div><strong>Jurisdiction:</strong> Republic of India</div>
                <div class="badge bg-success-subtle text-success border border-success-subtle mt-1">Legally Binding</div>
            </div>
        </div>

        <div class="agreement-content text-secondary" style="font-size: 0.9rem; line-height: 1.65;">
            <div class="alert alert-secondary small mb-4">
                <strong>IMPORTANT NOTICE:</strong> By activating, installing, or executing <strong>WebRajya ERP</strong>, the licensee ("You", "CA Firm", or "Practice Entity") agrees to be bound by the terms of this Agreement governed under the <strong>Information Technology Act, 2000</strong> and the <strong>Indian Contract Act, 1872</strong>.
            </div>

            <h2 class="h6 fw-bold text-dark mt-4 mb-2">1. Grant of Perpetual Software License</h2>
            <p>Subject to payment of the agreed one-time license fee, WebRajya Technologies grants the Licensee a non-exclusive, non-transferable perpetual right to install and run WebRajya ERP on one (1) designated primary computer or local LAN workstation in India. The "Lifetime Edition" grants permanent operational rights to the installed software version with zero monthly cloud or subscription fees.</p>

            <h2 class="h6 fw-bold text-dark mt-4 mb-2">2. Hardware Fingerprint Lock &amp; Transferability</h2>
            <p>WebRajya ERP is cryptographically bound to the unique hardware fingerprint of the designated device. If the CA replaces their office computer, license transfer is permitted up to two (2) times per 12-month period by deactivating the previous machine and reactivating the license on the replacement machine.</p>

            <h2 class="h6 fw-bold text-dark mt-4 mb-2">3. Statutory Updates &amp; Annual Maintenance Contract (AMC)</h2>
            <p><strong>Year 1:</strong> Includes 12 months of complimentary statutory tax updates (GST, ITR, TDS), bug fixes, and technical assistance.<br>
            <strong>Year 2 Onwards:</strong> Continued access to new statutory tax compliance changes (Union Budget amendments, schema updates, GST portal revisions) requires an active <strong>Annual Maintenance Contract (AMC)</strong> at ₹5,000 + 18% GST per year.<br>
            <em>Note: If the AMC lapses, the software continues to work normally for everyday operations; only automatic updates and priority support are paused.</em></p>

            <h2 class="h6 fw-bold text-dark mt-4 mb-2">4. Complete Data Sovereignty &amp; Privacy</h2>
            <p>WebRajya ERP is a 100% on-premise local software application. All taxpayer PANs, client books, vouchers, audit working papers, and invoices remain strictly on the CA's local storage. WebRajya Technologies does not transmit, store, or access client financial records on external servers, fulfilling Indian Data Fiduciary obligations under the <strong>Digital Personal Data Protection Act, 2023</strong>.</p>

            <h2 class="h6 fw-bold text-dark mt-4 mb-2">5. Backups &amp; Disaster Recovery</h2>
            <p>While the software includes built-in 1-click database backup and GZIP compression tools, regular creation and offsite preservation of backups (e.g. on external USB drives or secure storage) is the sole responsibility of the Licensee. WebRajya Technologies is not liable for data loss caused by hardware crashes, power surges, or malware on local machines.</p>

            <h2 class="h6 fw-bold text-dark mt-4 mb-2">6. GST Compliance &amp; Input Tax Credit (ITC)</h2>
            <p>Software license sales and AMC fees are billed under SAC Code <strong>998314</strong> with 18% GST. A valid Tax Invoice is issued for each purchase enabling GST-registered CA firms to claim full Input Tax Credit (ITC).</p>

            <h2 class="h6 fw-bold text-dark mt-4 mb-2">7. Limitation of Liability</h2>
            <p>WebRajya Technologies' aggregate liability under all circumstances shall not exceed the actual license fee paid by the Licensee for the software.</p>
        </div>

        <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center text-muted small">
            <div>WebRajya Technologies &bull; support@webrajya.in</div>
            <div>Official Software EULA &bull; Version 1.0</div>
        </div>
    </div>
</div>
</body>
</html>
