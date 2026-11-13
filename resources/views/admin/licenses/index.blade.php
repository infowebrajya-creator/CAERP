@extends('layouts.app')

@section('title', 'Vendor License Manager')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h4 fw-bold mb-1" style="color: #1e3a5f;">Vendor License Manager</h1>
        <p class="text-muted small mb-0">Generate cryptographic license keys, track CA firm activations, manage AMC renewals, and export sales records.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.licenses.export_csv') }}" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span>Export CSV</span>
        </a>
    </div>
</div>

<!-- 4 KPI Metrics -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="ca-card p-3 d-flex align-items-center gap-3">
            <div class="rounded-circle p-3 d-flex align-items-center justify-content-center bg-primary-subtle text-primary" style="width: 48px; height: 48px;">
                <i class="bi bi-key fs-4"></i>
            </div>
            <div>
                <div class="text-muted small fw-medium">Total Licenses</div>
                <div class="fs-4 fw-bold text-dark">{{ $metrics['total'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="ca-card p-3 d-flex align-items-center gap-3">
            <div class="rounded-circle p-3 d-flex align-items-center justify-content-center bg-success-subtle text-success" style="width: 48px; height: 48px;">
                <i class="bi bi-shield-check fs-4"></i>
            </div>
            <div>
                <div class="text-muted small fw-medium">Active Devices</div>
                <div class="fs-4 fw-bold text-dark">{{ $metrics['active'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="ca-card p-3 d-flex align-items-center gap-3">
            <div class="rounded-circle p-3 d-flex align-items-center justify-content-center bg-info-subtle text-info" style="width: 48px; height: 48px;">
                <i class="bi bi-currency-rupee fs-4"></i>
            </div>
            <div>
                <div class="text-muted small fw-medium">Gross License Sales</div>
                <div class="fs-4 fw-bold text-dark">₹{{ number_format($metrics['revenue']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="ca-card p-3 d-flex align-items-center gap-3">
            <div class="rounded-circle p-3 d-flex align-items-center justify-content-center {{ $metrics['amc_due'] > 0 ? 'bg-warning-subtle text-warning' : 'bg-secondary-subtle text-secondary' }}" style="width: 48px; height: 48px;">
                <i class="bi bi-clock-history fs-4"></i>
            </div>
            <div>
                <div class="text-muted small fw-medium">AMC Due (&le;30 Days)</div>
                <div class="fs-4 fw-bold text-dark">{{ $metrics['amc_due'] }}</div>
            </div>
        </div>
    </div>
</div>

@if($newDeliveryPackage)
    <!-- Newly Generated License Delivery Package Card -->
    <div class="ca-card p-4 mb-4 border border-success bg-success-subtle bg-opacity-10">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-check-circle-fill text-success fs-5"></i>
                <h2 class="h6 fw-bold mb-0 text-success">License Generated for {{ $newDeliveryPackage['firm'] }}</h2>
            </div>
            <span class="badge bg-success">Ready for Delivery</span>
        </div>

        <div class="mb-3">
            <label class="form-label small fw-semibold text-muted">Cryptographic License Key:</label>
            <div class="input-group">
                <input type="text" class="form-control font-monospace form-control-sm" id="newKeyField" value="{{ $newDeliveryPackage['key'] }}" readonly>
                <button class="btn btn-sm btn-outline-success" type="button" onclick="navigator.clipboard.writeText('{{ $newDeliveryPackage['key'] }}'); this.innerText='Copied!';">Copy Key</button>
            </div>
        </div>

        <div>
            <div class="d-flex justify-content-between align-items-center mb-1">
                <label class="form-label small fw-semibold text-muted mb-0">Ready-to-Send Client Message (WhatsApp / Email):</label>
                <button type="button" class="btn btn-xs btn-primary" onclick="navigator.clipboard.writeText(document.getElementById('deliveryMsgField').value); this.innerText='Message Copied!';">Copy WhatsApp Message</button>
            </div>
            <textarea id="deliveryMsgField" rows="7" class="form-control font-monospace small bg-white" readonly>{{ $newDeliveryPackage['message'] }}</textarea>
        </div>
    </div>
@endif

<div class="row g-3">
    <!-- Issue New License Form -->
    <div class="col-lg-4">
        <form action="{{ route('admin.licenses.store') }}" method="POST" class="ca-card p-3">
            @csrf
            <h2 class="h6 fw-bold mb-3 d-flex align-items-center gap-2">
                <i class="bi bi-plus-circle text-primary"></i>
                <span>Issue License Key</span>
            </h2>

            <div class="mb-2">
                <label class="form-label small fw-semibold">CA Firm / Licensee Name *</label>
                <input type="text" name="licensed_to" class="form-control form-control-sm" placeholder="e.g. Mittal & Associates CA" required value="{{ old('licensed_to') }}">
            </div>

            <div class="row g-2 mb-2">
                <div class="col-6">
                    <label class="form-label small fw-semibold">Email</label>
                    <input type="email" name="email" class="form-control form-control-sm" placeholder="ca@firm.in" value="{{ old('email') }}">
                </div>
                <div class="col-6">
                    <label class="form-label small fw-semibold">Phone (WhatsApp)</label>
                    <input type="text" name="phone" class="form-control form-control-sm" placeholder="+91 98765 43210" value="{{ old('phone') }}">
                </div>
            </div>

            <div class="row g-2 mb-2">
                <div class="col-6">
                    <label class="form-label small fw-semibold">Edition</label>
                    <select name="edition" class="form-select form-select-sm">
                        <option value="Lifetime Edition" selected>Lifetime Edition</option>
                        <option value="Lifetime Pro">Lifetime Pro</option>
                        <option value="Lifetime Enterprise">Lifetime Enterprise</option>
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label small fw-semibold">Staff Limit</label>
                    <input type="number" name="max_staff_users" class="form-control form-control-sm" value="25" min="1" max="500" required>
                </div>
            </div>

            <div class="row g-2 mb-2">
                <div class="col-6">
                    <label class="form-label small fw-semibold">AMC Updates</label>
                    <select name="amc_years" class="form-select form-select-sm">
                        <option value="1" selected>1 Year Included</option>
                        <option value="2">2 Years</option>
                        <option value="3">3 Years</option>
                        <option value="5">5 Years</option>
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label small fw-semibold">Sale Price (₹)</label>
                    <input type="number" step="0.01" name="sale_price" class="form-control form-control-sm" value="45000.00" required>
                </div>
            </div>

            <div class="mb-2">
                <label class="form-label small fw-semibold">Tax Invoice Number</label>
                <input type="text" name="invoice_number" class="form-control form-control-sm" placeholder="e.g. WR-INV-2026-001" value="{{ old('invoice_number') }}">
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold">Pre-Lock Machine ID (Optional)</label>
                <input type="text" name="machine_fingerprint" class="form-control form-control-sm font-monospace" placeholder="Leave blank to bind on first activation">
                <div class="form-text small" style="font-size: 0.7rem;">If empty, the key locks to the first computer where it is activated.</div>
            </div>

            <button type="submit" class="btn btn-sm btn-primary w-100 fw-semibold">
                Generate &amp; Issue License Key
            </button>
        </form>
    </div>

    <!-- All Issued Licenses Table -->
    <div class="col-lg-8">
        <div class="ca-card overflow-hidden">
            <!-- Table Header & Search -->
            <div class="p-3 border-bottom d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                <h2 class="h6 fw-bold mb-0">Issued Client Licenses</h2>
                <form action="{{ route('admin.licenses.index') }}" method="GET" class="d-flex gap-2">
                    <input type="text" name="search" value="{{ $search }}" class="form-control form-control-sm" placeholder="Search firm, key, phone...">
                    <select name="status" class="form-select form-select-sm" style="width: 120px;" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="issued" {{ $status === 'issued' ? 'selected' : '' }}>Issued</option>
                        <option value="expired" {{ $status === 'expired' ? 'selected' : '' }}>Expired</option>
                    </select>
                    <button type="submit" class="btn btn-sm btn-outline-primary">Filter</button>
                </form>
            </div>

            <!-- Table -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="small">Firm / Licensee</th>
                            <th class="small">Edition</th>
                            <th class="small">License Key</th>
                            <th class="small">Machine ID</th>
                            <th class="small">AMC Validity</th>
                            <th class="small text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($licenses as $l)
                            <tr>
                                <td>
                                    <div class="fw-semibold text-dark small">{{ $l->licensed_to }}</div>
                                    <div class="text-muted" style="font-size: 0.72rem;">
                                        {{ $l->email ?: 'No email' }} &bull; {{ $l->phone ?: 'No phone' }}
                                    </div>
                                    <span class="badge bg-light text-muted border font-monospace mt-1" style="font-size: 0.65rem;">
                                        ₹{{ number_format($l->getSalePrice()) }} &bull; {{ $l->meta_data['invoice_number'] ?? 'No Inv' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="small fw-medium">{{ $l->edition }}</div>
                                    <div class="text-muted" style="font-size: 0.72rem;">{{ $l->max_staff_users }} Staff</div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        <code class="small text-muted font-monospace" style="font-size: 0.75rem;">{{ substr($l->license_key, 0, 10) }}...{{ substr($l->license_key, -6) }}</code>
                                        <button type="button" class="btn btn-xs btn-link p-0 text-decoration-none" onclick="navigator.clipboard.writeText('{{ $l->license_key }}'); this.innerText='✓';" title="Copy Full Key">
                                            <i class="bi bi-clipboard"></i>
                                        </button>
                                    </div>
                                </td>
                                <td>
                                    @if($l->machine_fingerprint)
                                        <code class="small text-dark font-monospace" style="font-size: 0.75rem;">{{ $l->machine_fingerprint }}</code>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.7rem;">Pending First Run</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="small fw-medium {{ $l->isAmcExpired() ? 'text-danger' : 'text-success' }}">
                                        {{ $l->amc_expires_at?->format('d M Y') ?? 'Lifetime' }}
                                    </div>
                                    <div class="text-muted" style="font-size: 0.7rem;">
                                        @if($l->remainingAmcDays() > 0)
                                            {{ $l->remainingAmcDays() }} days left
                                        @else
                                            <span class="text-danger">Expired</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-end text-nowrap">
                                    <!-- Renew AMC -->
                                    <form action="{{ route('admin.licenses.renew_amc', $l->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Renew AMC for {{ $l->licensed_to }} for 1 year (₹5,000)?');">
                                        @csrf
                                        <input type="hidden" name="renew_years" value="1">
                                        <button type="submit" class="btn btn-xs btn-outline-success" title="Renew AMC for 1 year">+1 Yr AMC</button>
                                    </form>

                                    <!-- Delete / Revoke -->
                                    <form action="{{ route('admin.licenses.destroy', $l->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Permanently revoke and delete this license?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-xs btn-outline-danger" title="Revoke">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4 small">
                                    No licenses found. Use the form on the left to issue your first license key!
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($licenses->hasPages())
                <div class="p-3 border-top">
                    {{ $licenses->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

