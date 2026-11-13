@extends('layouts.app')

@section('title', 'Firm Settings & System Maintenance')

@section('content')
<div class="mb-4">
    <h1 class="h4 fw-bold mb-1" style="color: #1e3a5f;">Firm Settings &amp; System Maintenance</h1>
    <p class="text-muted small mb-0">Configure CA firm profile, software licensing, database backups, updates, and inspect audit logs.</p>
</div>

<div class="row g-3">
    <!-- Left Column: Firm Profile, Licensing, Updates & WhatsApp -->
    <div class="col-lg-5">
        <!-- Firm & Billing Profile Form -->
        <form action="{{ route('settings.update') }}" method="POST" class="ca-card p-3 mb-3">
            @csrf
            <h2 class="h6 fw-bold mb-3">CA Firm &amp; Billing Profile</h2>
            <div class="mb-2">
                <label class="form-label small fw-semibold">Firm Name *</label>
                <input type="text" name="firm_name" value="{{ $settings['firm_name'] ?? 'WebRajya Accounting & Advisory' }}" class="form-control form-control-sm" required>
            </div>
            <div class="mb-2">
                <label class="form-label small fw-semibold">Tagline</label>
                <input type="text" name="firm_tagline" value="{{ $settings['firm_tagline'] ?? 'Accounting, Tax & Assurance Advisors' }}" class="form-control form-control-sm">
            </div>
            <div class="row g-2 mb-2">
                <div class="col-6">
                    <label class="form-label small fw-semibold">Email</label>
                    <input type="email" name="firm_email" value="{{ $settings['firm_email'] ?? 'office@webrajya.in' }}" class="form-control form-control-sm">
                </div>
                <div class="col-6">
                    <label class="form-label small fw-semibold">Phone</label>
                    <input type="text" name="firm_phone" value="{{ $settings['firm_phone'] ?? '+91 22 4001 9900' }}" class="form-control form-control-sm">
                </div>
            </div>
            <div class="mb-2">
                <label class="form-label small fw-semibold">Office Address</label>
                <textarea name="firm_address" rows="2" class="form-control form-control-sm">{{ $settings['firm_address'] ?? 'Nariman Point, Mumbai, Maharashtra 400021' }}</textarea>
            </div>
            <div class="row g-2 mb-2">
                <div class="col-6">
                    <label class="form-label small fw-semibold">Firm PAN</label>
                    <input type="text" name="firm_pan" value="{{ $settings['firm_pan'] ?? 'AABFC1234D' }}" class="form-control form-control-sm">
                </div>
                <div class="col-6">
                    <label class="form-label small fw-semibold">Firm GSTIN</label>
                    <input type="text" name="firm_gstin" value="{{ $settings['firm_gstin'] ?? '27AABFC1234D1Z5' }}" class="form-control form-control-sm">
                </div>
            </div>
            <div class="row g-2 mb-3">
                <div class="col-3">
                    <label class="form-label small fw-semibold">Inv Prefix</label>
                    <input type="text" name="invoice_prefix" value="{{ $settings['invoice_prefix'] ?? 'CA-INV' }}" class="form-control form-control-sm" required>
                </div>
                <div class="col-3">
                    <label class="form-label small fw-semibold">CGST %</label>
                    <input type="number" step="0.01" name="default_cgst_rate" value="{{ $settings['default_cgst_rate'] ?? '9' }}" class="form-control form-control-sm" required>
                </div>
                <div class="col-3">
                    <label class="form-label small fw-semibold">SGST %</label>
                    <input type="number" step="0.01" name="default_sgst_rate" value="{{ $settings['default_sgst_rate'] ?? '9' }}" class="form-control form-control-sm" required>
                </div>
                <div class="col-3">
                    <label class="form-label small fw-semibold">IGST %</label>
                    <input type="number" step="0.01" name="default_igst_rate" value="{{ $settings['default_igst_rate'] ?? '18' }}" class="form-control form-control-sm" required>
                </div>
            </div>
            <button type="submit" class="btn btn-sm btn-primary w-100">Save Firm Profile</button>
        </form>

        <!-- Software License & Device Lock Card -->
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
                <div class="mb-1"><span class="text-muted">Licensee:</span> <strong>{{ $licenseInfo['licensed_to'] ?? 'Evaluation / Unlicensed' }}</strong></div>
                <div class="mb-1"><span class="text-muted">Edition:</span> <span class="badge bg-secondary-subtle text-secondary">{{ $licenseInfo['edition'] }}</span></div>
                <div class="mb-1"><span class="text-muted">Machine ID:</span> <code class="text-dark">{{ $licenseInfo['machine_fingerprint'] }}</code></div>
                <div class="mb-1"><span class="text-muted">License Key:</span> <code class="text-muted">{{ $licenseInfo['license_key'] ?? 'None' }}</code></div>
                <div class="mb-2">
                    <span class="text-muted">AMC / Updates:</span>
                    <strong class="{{ $licenseInfo['amc_status'] === 'expired' ? 'text-danger' : 'text-success' }}">
                        {{ $licenseInfo['amc_expires_at'] ?? 'Lifetime' }}
                        @if($licenseInfo['remaining_amc_days'] > 0)
                            ({{ $licenseInfo['remaining_amc_days'] }} days remaining)
                        @elseif($licenseInfo['amc_status'] === 'expired')
                            (Expired — Renewal required for updates)
                        @endif
                    </strong>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2 mt-3 pt-2 border-top">
                <a href="{{ route('license.activate') }}" class="btn btn-xs btn-outline-primary">Manage License</a>
                <a href="{{ route('license.eula') }}" target="_blank" class="btn btn-xs btn-outline-secondary">View EULA</a>
                @if($licenseInfo['is_activated'])
                    <form action="{{ route('license.deactivate') }}" method="POST" onsubmit="return confirm('Deactivate license on this machine to transfer to another PC?');" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-xs btn-outline-danger">Transfer License</button>
                    </form>
                @endif
            </div>
        </div>

        <!-- Software Updates & Releases Card -->
        <div class="ca-card p-3 mb-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <h2 class="h6 fw-bold mb-0">Software Updates</h2>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">v{{ $updateInfo['current_version'] ?? '1.0.0' }}</span>
            </div>
            <p class="small text-muted mb-2">WebRajya ERP checks for statutory compliance and bug fix releases.</p>

            <div class="p-2 rounded bg-light border small mb-3">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted">Status:</span>
                    @if(!empty($updateInfo['has_update']))
                        <strong class="text-success">Update Available (v{{ $updateInfo['latest_version'] }})</strong>
                    @else
                        <strong class="text-secondary">Up to Date</strong>
                    @endif
                </div>
                @if(!empty($updateInfo['has_update']))
                    <div class="mt-2 pt-2 border-top">
                        <div class="fw-semibold text-dark">What's New in v{{ $updateInfo['latest_version'] }}:</div>
                        <ul class="mb-1 ps-3 text-muted" style="font-size: 0.75rem;">
                            @foreach(($updateInfo['changelog'] ?? []) as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                        @if(!empty($updateInfo['download_url']))
                            <a href="{{ $updateInfo['download_url'] }}" target="_blank" class="btn btn-xs btn-success mt-1">Download Update Package</a>
                        @endif
                    </div>
                @endif
            </div>

            <div class="d-flex gap-2 align-items-center">
                <form action="{{ route('updates.check') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-xs btn-outline-primary">Check for Updates</button>
                </form>
            </div>

            <!-- Manual Patch Upload -->
            <form action="{{ route('updates.apply_patch') }}" method="POST" enctype="multipart/form-data" class="mt-3 pt-2 border-top">
                @csrf
                <label class="form-label small fw-semibold text-muted mb-1">Install Manual Patch (.zip):</label>
                <div class="input-group input-group-sm">
                    <input type="file" name="patch_file" accept=".zip" class="form-control form-control-sm" required>
                    <button type="submit" class="btn btn-outline-secondary">Install</button>
                </div>
            </form>
        </div>

        <!-- WhatsApp Business API Status Card -->
        <div class="ca-card p-3">
            <h2 class="h6 fw-bold mb-2">WhatsApp Business API Integration</h2>
            <p class="small text-muted mb-2">Configure <code>WHATSAPP_ENABLED=true</code>, <code>WHATSAPP_PHONE_NUMBER_ID</code>, and <code>WHATSAPP_ACCESS_TOKEN</code> in <code>.env</code> to enable live Cloud API delivery.</p>
            @if($whatsAppConfigured)
                <span class="badge badge-soft-success">Connected &amp; Enabled</span>
            @else
                <span class="badge badge-soft-warning">Credentials Not Configured (Safe Logging Mode)</span>
            @endif
        </div>
    </div>

    <!-- Right Column: Backups & System Audit Trail -->
    <div class="col-lg-7">
        <!-- Database Backups & Disaster Recovery -->
        <div class="ca-card mb-3 p-3">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <h2 class="h6 fw-bold mb-0">Database Backups &amp; Disaster Recovery</h2>
                    <p class="text-muted small mb-0">Create instant SQL backups, download them to secure external storage, or restore previous snapshots.</p>
                </div>
                <form action="{{ route('backups.store') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-primary d-flex align-items-center gap-1">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        <span>Create Backup</span>
                    </button>
                </form>
            </div>

            <!-- Backups Table -->
            <div class="table-responsive border rounded mb-3">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="small">Backup File</th>
                            <th class="small">Size</th>
                            <th class="small">Date</th>
                            <th class="small text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($backups as $b)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-secondary-subtle text-secondary font-monospace" style="font-size: 0.65rem;">{{ $b['is_compressed'] ? 'GZIP' : 'SQL' }}</span>
                                        <span class="small fw-medium font-monospace text-truncate" style="max-width: 220px;" title="{{ $b['filename'] }}">{{ $b['filename'] }}</span>
                                    </div>
                                </td>
                                <td class="small text-muted">{{ $b['size'] }}</td>
                                <td class="small text-nowrap">{{ $b['created_at']->format('d M Y, h:i A') }}</td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('backups.download', $b['filename']) }}" class="btn btn-xs btn-outline-primary" title="Download backup to PC">Download</a>
                                    <form action="{{ route('backups.restore', $b['filename']) }}" method="POST" class="d-inline" onsubmit="return confirm('WARNING: Restoring will overwrite the current database with this snapshot. Are you sure?');">
                                        @csrf
                                        <button type="submit" class="btn btn-xs btn-outline-warning" title="Restore this snapshot">Restore</button>
                                    </form>
                                    <form action="{{ route('backups.destroy', $b['filename']) }}" method="POST" class="d-inline" onsubmit="return confirm('Permanently delete this backup file?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-xs btn-outline-danger" title="Delete">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3 small">
                                    No backups generated yet. Click <strong>Create Backup</strong> to save your first snapshot.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Upload External Backup -->
            <form action="{{ route('backups.upload') }}" method="POST" enctype="multipart/form-data" class="d-flex align-items-center gap-2 pt-2 border-top">
                @csrf
                <label class="form-label small fw-semibold text-nowrap mb-0">Upload Backup (.sql/.gz):</label>
                <input type="file" name="backup_file" accept=".sql,.gz" class="form-control form-control-sm" required>
                <button type="submit" class="btn btn-sm btn-outline-secondary text-nowrap">Upload</button>
            </form>
        </div>

        <!-- System Audit Trail -->
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
