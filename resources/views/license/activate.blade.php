<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Software Activation — {{ config('brand.app_name', 'WebRajya ERP') }}</title>
    <meta name="description" content="License activation and hardware verification for WebRajya ERP">
    <link rel="icon" type="image/x-icon" href="{{ asset(config('brand.favicon_path', '/images/favicon.ico')) }}">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body class="min-vh-100 d-flex align-items-center justify-content-center p-3" style="background: linear-gradient(135deg, #f8fafc 0%, #eff6ff 55%, #f0fdfa 100%);">
<div class="container" style="max-width: 900px;">
    <div class="row g-0 ca-card overflow-hidden shadow-sm">
        <!-- Sidebar Brand Information -->
        <div class="col-lg-5 p-4 p-md-5 d-flex flex-column justify-content-between" style="background: linear-gradient(160deg, #eff6ff 0%, #f0fdfa 100%); border-right: 1px solid #e2e8f0;">
            <div>
                <x-ca-logo :size="42" />
                <h2 class="h5 fw-bold mt-4" style="color: #1e3a5f;">Software Licensing &amp; Hardware Lock</h2>
                <p class="text-muted small mt-2">
                    WebRajya ERP uses hardware-locked cryptographic licensing to ensure your firm's data stays on this authorized machine without internet dependency.
                </p>

                <div class="p-3 bg-white rounded border mt-4">
                    <div class="small fw-semibold text-uppercase text-muted" style="font-size: 0.7rem; letter-spacing: 0.05em;">This Computer's Machine ID</div>
                    <div class="d-flex align-items-center justify-content-between mt-2">
                        <code class="fw-bold text-dark fs-6 font-monospace" id="machine-fp">{{ $fingerprint }}</code>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="navigator.clipboard.writeText('{{ $fingerprint }}'); this.innerText='Copied!';" title="Copy Machine ID">Copy</button>
                    </div>
                    <div class="text-muted mt-2" style="font-size: 0.72rem;">
                        Unique hardware fingerprint tied to this device's motherboard &amp; CPU.
                    </div>
                </div>
            </div>

            <div class="mt-4 pt-3 border-top">
                <div class="d-flex align-items-center gap-2 text-muted small">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    <span>100% On-Premise &amp; Offline Compatible</span>
                </div>
                <div class="mt-2 text-muted" style="font-size: 0.75rem;">
                    Support: <a href="mailto:{{ config('brand.support_email', 'support@webrajya.in') }}" class="text-decoration-none">{{ config('brand.support_email', 'support@webrajya.in') }}</a>
                </div>
            </div>
        </div>

        <!-- Form & Details -->
        <div class="col-lg-7 p-4 p-md-5 bg-white">
            @if(session('success'))
                <div class="alert alert-success d-flex align-items-center gap-2 small mb-4">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if(session('warning'))
                <div class="alert alert-warning small mb-4">{{ session('warning') }}</div>
            @endif

            @if(session('info'))
                <div class="alert alert-info small mb-4">{{ session('info') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger small mb-4">{{ $errors->first() }}</div>
            @endif

            @if($info['is_activated'])
                <!-- Already Activated State -->
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h1 class="h4 fw-bold mb-1" style="color: #0f172a;">License Activated</h1>
                        <p class="text-muted small mb-0">This installation is licensed and bound to this device.</p>
                    </div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fs-6">Active</span>
                </div>

                <div class="border rounded p-3 mb-4 bg-light">
                    <div class="row g-3 small">
                        <div class="col-sm-6">
                            <span class="text-muted d-block">Licensed To:</span>
                            <strong class="text-dark">{{ $info['licensed_to'] }}</strong>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted d-block">Edition:</span>
                            <strong class="text-dark">{{ $info['edition'] }}</strong>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted d-block">License Key:</span>
                            <code class="text-dark">{{ $info['license_key'] }}</code>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted d-block">Activated Date:</span>
                            <span class="text-dark">{{ $info['activated_at'] }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted d-block">AMC Validity (Updates):</span>
                            <strong class="{{ $info['amc_status'] === 'expired' ? 'text-danger' : 'text-success' }}">
                                {{ $info['amc_expires_at'] ?? 'Lifetime' }}
                                @if($info['remaining_amc_days'] > 0)
                                    ({{ $info['remaining_amc_days'] }} days remaining)
                                @elseif($info['amc_status'] === 'expired')
                                    (Expired — Renewal required for new updates)
                                @endif
                            </strong>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted d-block">Max Staff Users:</span>
                            <span class="text-dark">{{ $info['max_staff_users'] }} Users</span>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <a href="{{ route('dashboard') }}" class="btn btn-primary px-4">Go to Dashboard</a>
                    @auth
                        <form action="{{ route('license.deactivate') }}" method="POST" onsubmit="return confirm('Are you sure you want to deactivate this license? WebRajya will be locked until reactivated.');">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger">Deactivate &amp; Transfer</button>
                        </form>
                    @endauth
                </div>

            @else
                <!-- Unactivated State -->
                <h1 class="h4 fw-bold mb-1" style="color: #0f172a;">Activate Your License</h1>
                <p class="text-muted small mb-4">Enter the 256-bit cryptographic license key issued to your CA firm.</p>

                <form action="{{ route('license.submit') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="license_key" class="form-label fw-medium">License Key</label>
                        <textarea id="license_key" name="license_key" rows="3" class="form-control font-monospace @error('license_key') is-invalid @enderror" placeholder="WRLIC-eyJ0byI6Li4ufQ...-ABCD" required>{{ old('license_key') }}</textarea>
                        @error('license_key')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text small">
                            Format: Starts with <code>WRLIC-</code> followed by signed payload and checksum.
                        </div>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="agree_terms" required checked>
                        <label class="form-check-label small text-muted" for="agree_terms">
                            I accept the <a href="{{ route('license.eula') }}" target="_blank" class="text-decoration-none fw-medium">End User License Agreement (EULA)</a> and AMC terms.
                        </label>
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary btn-lg fw-semibold" style="background: linear-gradient(135deg, #1e3a5f 0%, #0d9488 100%); border: none;">
                            Activate WebRajya ERP
                        </button>
                    </div>
                </form>

                <div class="mt-4 pt-3 border-top text-center">
                    <span class="text-muted small">Need a license or transfer assistance?</span>
                    <a href="mailto:{{ config('brand.support_email', 'sales@webrajya.in') }}" class="small ms-1 fw-medium text-decoration-none">Contact WebRajya Sales</a>
                </div>
            @endif
        </div>
    </div>
</div>
</body>
</html>
