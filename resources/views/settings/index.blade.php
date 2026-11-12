@extends('layouts.app')

@section('title', 'Firm Settings & Audit Logs')

@section('content')
<div class="mb-4">
    <h1 class="h4 fw-bold mb-1" style="color: #1e3a5f;">Firm Settings &amp; System Audit Logs</h1>
    <p class="text-muted small mb-0">Configure CA firm profile, billing defaults, WhatsApp integration readiness, and inspect audit logs.</p>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <form action="{{ route('settings.update') }}" method="POST" class="ca-card p-3 mb-3">
            @csrf
            <h2 class="h6 fw-bold mb-3">CA Firm &amp; Billing Settings</h2>
            <div class="mb-2"><label class="form-label small fw-semibold">Firm Name *</label><input type="text" name="firm_name" value="{{ $settings['firm_name'] ?? 'WebRajya Accounting & Advisory' }}" class="form-control form-control-sm" required></div>
            <div class="mb-2"><label class="form-label small fw-semibold">Tagline</label><input type="text" name="firm_tagline" value="{{ $settings['firm_tagline'] ?? 'Accounting, Tax & Assurance Advisors' }}" class="form-control form-control-sm"></div>
            <div class="row g-2 mb-2">
                <div class="col-6"><label class="form-label small fw-semibold">Email</label><input type="email" name="firm_email" value="{{ $settings['firm_email'] ?? 'office@webrajya.in' }}" class="form-control form-control-sm"></div>
                <div class="col-6"><label class="form-label small fw-semibold">Phone</label><input type="text" name="firm_phone" value="{{ $settings['firm_phone'] ?? '+91 22 4001 9900' }}" class="form-control form-control-sm"></div>
            </div>
            <div class="mb-2"><label class="form-label small fw-semibold">Office Address</label><textarea name="firm_address" rows="2" class="form-control form-control-sm">{{ $settings['firm_address'] ?? 'Nariman Point, Mumbai, Maharashtra 400021' }}</textarea></div>
            <div class="row g-2 mb-2">
                <div class="col-6"><label class="form-label small fw-semibold">Firm PAN</label><input type="text" name="firm_pan" value="{{ $settings['firm_pan'] ?? 'AABFC1234D' }}" class="form-control form-control-sm"></div>
                <div class="col-6"><label class="form-label small fw-semibold">Firm GSTIN</label><input type="text" name="firm_gstin" value="{{ $settings['firm_gstin'] ?? '27AABFC1234D1Z5' }}" class="form-control form-control-sm"></div>
            </div>
            <div class="row g-2 mb-3">
                <div class="col-3"><label class="form-label small fw-semibold">Inv Prefix</label><input type="text" name="invoice_prefix" value="{{ $settings['invoice_prefix'] ?? 'CA-INV' }}" class="form-control form-control-sm" required></div>
                <div class="col-3"><label class="form-label small fw-semibold">CGST %</label><input type="number" step="0.01" name="default_cgst_rate" value="{{ $settings['default_cgst_rate'] ?? '9' }}" class="form-control form-control-sm" required></div>
                <div class="col-3"><label class="form-label small fw-semibold">SGST %</label><input type="number" step="0.01" name="default_sgst_rate" value="{{ $settings['default_sgst_rate'] ?? '9' }}" class="form-control form-control-sm" required></div>
                <div class="col-3"><label class="form-label small fw-semibold">IGST %</label><input type="number" step="0.01" name="default_igst_rate" value="{{ $settings['default_igst_rate'] ?? '18' }}" class="form-control form-control-sm" required></div>
            </div>
            <button type="submit" class="btn btn-sm btn-primary w-100">Save Firm Settings</button>
        </form>

        <div class="ca-card p-3 mb-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <h2 class="h6 fw-bold mb-0">Software License &amp; Device Lock</h2>
                @if($licenseInfo['is_activated'])
                    <span class="badge bg-success-subtle text-success border border-success-subtle">Activated</span>
                @else
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Unregistered</span>
                @endif
            </div>
            <div class="small">
                <div class="mb-1"><span class="text-muted">Licensee:</span> <strong>{{ $licenseInfo['licensed_to'] ?? 'Evaluation Mode' }}</strong></div>
                <div class="mb-1"><span class="text-muted">Edition:</span> <span class="badge bg-secondary-subtle text-secondary">{{ $licenseInfo['edition'] }}</span></div>
                <div class="mb-1"><span class="text-muted">Machine ID:</span> <code class="text-dark">{{ $licenseInfo['machine_fingerprint'] }}</code></div>
                <div class="mb-1"><span class="text-muted">License Key:</span> <code class="text-muted">{{ $licenseInfo['license_key'] ?? 'None' }}</code></div>
                <div class="mb-2">
                    <span class="text-muted">AMC / Updates:</span>
                    <strong class="{{ $licenseInfo['amc_status'] === 'expired' ? 'text-danger' : 'text-success' }}">
                        {{ $licenseInfo['amc_expires_at'] ?? 'Lifetime' }}
                        @if($licenseInfo['remaining_amc_days'] > 0)
                            ({{ $licenseInfo['remaining_amc_days'] }} days remaining)
                        @endif
                    </strong>
                </div>
            </div>
            <div class="d-flex gap-2 mt-3 pt-2 border-top">
                <a href="{{ route('license.activate') }}" class="btn btn-xs btn-outline-primary">Manage License</a>
                @if($licenseInfo['is_activated'])
                    <form action="{{ route('license.deactivate') }}" method="POST" onsubmit="return confirm('Deactivate license on this machine to transfer to another PC?');" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-xs btn-outline-danger">Transfer License</button>
                    </form>
                @endif
            </div>
        </div>

        <div class="ca-card p-3">
            <h2 class="h6 fw-bold mb-2">WhatsApp Business API Integration Status</h2>
            <p class="small text-muted mb-2">Configure <code>WHATSAPP_ENABLED=true</code>, <code>WHATSAPP_PHONE_NUMBER_ID</code>, and <code>WHATSAPP_ACCESS_TOKEN</code> in <code>.env</code> to enable live Cloud API delivery.</p>
            @if($whatsAppConfigured)
                <span class="badge badge-soft-success">Connected &amp; Enabled</span>
            @else
                <span class="badge badge-soft-warning">Credentials Not Configured (Safe Logging Mode)</span>
            @endif
        </div>
    </div>

    <div class="col-lg-7">
        <div class="ca-card overflow-hidden">
            <div class="p-3 border-bottom"><h2 class="h6 fw-bold mb-0">System Audit Trail</h2></div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Timestamp</th><th>User</th><th>Module</th><th>Description</th></tr></thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td class="small text-nowrap">{{ $log->created_at->format('d M Y, h:i A') }}</td>
                                <td class="small fw-medium">{{ $log->user?->name ?? 'System' }}</td>
                                <td><span class="badge badge-soft-info">{{ ucfirst($log->module) }}</span></td>
                                <td class="small">{{ $log->description }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">No audit logs recorded.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($logs->hasPages())<div class="p-3 border-top">{{ $logs->links() }}</div>@endif
        </div>
    </div>
</div>
@endsection

