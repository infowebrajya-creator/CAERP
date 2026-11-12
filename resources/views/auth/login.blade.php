<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — {{ config('brand.app_name', 'WebRajya ERP') }}</title>
    <meta name="description" content="{{ config('brand.description') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset(config('brand.favicon_path', '/images/favicon.ico')) }}">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body class="min-vh-100 d-flex align-items-center justify-content-center p-3" style="background: linear-gradient(135deg, #f8fafc 0%, #eff6ff 55%, #f0fdfa 100%);">
<div class="container" style="max-width: 960px;">
    <div class="row g-0 ca-card overflow-hidden shadow-sm">
        <div class="col-lg-5 p-4 p-md-5 d-flex flex-column justify-content-between" style="background: linear-gradient(160deg, #eff6ff 0%, #f0fdfa 100%); border-right: 1px solid #e2e8f0;">
            <div>
                <x-ca-logo :size="42" />
                <h2 class="h4 fw-bold mt-4" style="color: #1e3a5f;">Unified Practice Management for Accounting Professionals</h2>
                <p class="text-muted small mt-2">
                    Manage clients, GST &amp; ITR compliance, TDS returns, statutory audits, document workflows, employee tasks, and GST-compliant billing in one secure workspace.
                </p>
            </div>

            <div class="mt-4">
                <div class="small fw-semibold text-uppercase text-muted mb-2" style="font-size: 0.7rem; letter-spacing: 0.06em;">Demo Accounts (Click to Fill)</div>
                <div class="d-grid gap-1">
                    @foreach([
                        ['label' => 'Super Admin', 'email' => 'admin@webrajya.in', 'pass' => 'password'],
                        ['label' => 'Principal / Partner', 'email' => 'partner@webrajya.in', 'pass' => 'password'],
                        ['label' => 'Manager', 'email' => 'manager@webrajya.in', 'pass' => 'password'],
                        ['label' => 'Accountant / Employee', 'email' => 'employee@webrajya.in', 'pass' => 'password'],
                        ['label' => 'Client Portal User', 'email' => 'client@apexprecision.in', 'pass' => 'password'],
                    ] as $demo)
                        <button type="button"
                                class="btn btn-sm btn-light border text-start d-flex justify-content-between align-items-center demo-fill-btn"
                                data-email="{{ $demo['email'] }}" data-pass="{{ $demo['pass'] }}">
                            <span class="fw-medium">{{ $demo['label'] }}</span>
                            <span class="text-muted small">{{ $demo['email'] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-lg-7 p-4 p-md-5 bg-white">
            <h1 class="h4 fw-bold mb-1" style="color: #0f172a;">Sign in to your account</h1>
            <p class="text-muted small mb-4">Enter your firm or client portal credentials below.</p>

            @if(session('success'))
                <div class="alert alert-success small">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger small">{{ $errors->first() }}</div>
            @endif

            <form action="{{ route('login.submit') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="email" class="form-label fw-medium">Email Address</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" class="form-control form-control-lg" required autofocus placeholder="name@firm.in">
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label fw-medium">Password</label>
                    <input type="password" id="password" name="password" class="form-control form-control-lg" required placeholder="••••••••">
                </div>
                <div class="mb-4 d-flex justify-content-between align-items-center">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label small text-muted" for="remember">Keep me signed in</label>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-lg w-100 fw-semibold">
                    <i class="bi bi-shield-lock me-2"></i>Sign In
                </button>
            </form>
        </div>
    </div>
</div>
<script>
document.querySelectorAll('.demo-fill-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('email').value = btn.dataset.email;
        document.getElementById('password').value = btn.dataset.pass;
    });
});
</script>
</body>
</html>

