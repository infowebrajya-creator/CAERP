@extends('layouts.app')

@section('title', 'TDS Management')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h4 fw-bold mb-1" style="color: #1e3a5f;">TDS Deduction &amp; Return Management</h1>
        <p class="text-muted small mb-0">Manage quarterly Form 24Q, 26Q, 27Q &amp; 27EQ returns, challan deposits, BSR codes, and filing status.</p>
    </div>
    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addTdsModal">
        <i class="bi bi-plus-lg me-1"></i>Add TDS Return
    </button>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><x-kpi-card title="Total TDS Deducted" :value="'₹' . number_format($summary['total_deduction'], 0)" icon="bi-percent" tone="primary" /></div>
    <div class="col-6 col-md-3"><x-kpi-card title="Tax Deposited" :value="'₹' . number_format($summary['total_deposited'], 0)" icon="bi-bank" tone="info" /></div>
    <div class="col-6 col-md-3"><x-kpi-card title="Returns Filed" :value="$summary['filed']" icon="bi-check-circle" tone="success" /></div>
    <div class="col-6 col-md-3"><x-kpi-card title="Pending Returns" :value="$summary['pending']" icon="bi-hourglass-split" tone="warning" /></div>
</div>

<div class="ca-card p-3 mb-3">
    <form method="GET" action="{{ route('tds.index') }}" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small text-muted mb-1">Client</label>
            <select name="client_id" class="form-select form-select-sm">
                <option value="">All Clients</option>
                @foreach($clients as $c)<option value="{{ $c->id }}" @selected((string) request('client_id') === (string) $c->id)>{{ $c->name }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small text-muted mb-1">Quarter</label>
            <select name="quarter" class="form-select form-select-sm">
                <option value="">All Quarters</option>
                @foreach(\App\Models\TdsReturn::QUARTERS as $q)<option value="{{ $q }}" @selected(request('quarter') === $q)>{{ $q }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small text-muted mb-1">Form Type</label>
            <select name="form_type" class="form-select form-select-sm">
                <option value="">All Forms</option>
                @foreach(\App\Models\TdsReturn::FORM_TYPES as $f)<option value="{{ $f }}" @selected(request('form_type') === $f)>{{ $f }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small text-muted mb-1">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">All Statuses</option>
                @foreach(\App\Models\TdsReturn::STATUSES as $k => $v)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-2 d-flex gap-1">
            <button type="submit" class="btn btn-sm btn-primary flex-grow-1">Filter</button>
            <a href="{{ route('tds.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
        </div>
    </form>
</div>

<div class="ca-card overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Client &amp; TAN</th>
                    <th>FY &amp; Quarter</th>
                    <th>Form</th>
                    <th>Deducted / Deposited</th>
                    <th>Challan Details</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Assigned</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tdsReturns as $tds)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $tds->client->name }}</div>
                            <div class="small text-muted">TAN: {{ $tds->client->tan ?? '—' }}</div>
                        </td>
                        <td class="small">{{ $tds->financial_year }} <div class="text-muted">{{ $tds->quarter }}</div></td>
                        <td><span class="badge badge-soft-primary">{{ $tds->form_type }}</span></td>
                        <td class="small">
                            <div>Ded: ₹{{ number_format((float) $tds->deduction_amount, 2) }}</div>
                            <div class="text-success">Dep: ₹{{ number_format((float) $tds->tax_deposited, 2) }}</div>
                        </td>
                        <td class="small">
                            <div>Challan: {{ $tds->challan_number ?? '—' }}</div>
                            <div class="text-muted">BSR: {{ $tds->bsr_code ?? '—' }}</div>
                        </td>
                        <td class="small">{{ $tds->due_date?->format('d M Y') ?? '—' }}</td>
                        <td><x-status-badge :status="$tds->status" :label="$tds->statusLabel()" /></td>
                        <td class="small">{{ $tds->assignee?->name ?? 'Unassigned' }}</td>
                        <td class="text-end text-nowrap">
                            <button class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#editTdsModal{{ $tds->id }}"><i class="bi bi-pencil"></i></button>
                            <form action="{{ route('tds.destroy', $tds) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete TDS record?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-light border text-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>

                    <div class="modal fade" id="editTdsModal{{ $tds->id }}" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <form action="{{ route('tds.update', $tds) }}" method="POST" class="modal-content">
                                @csrf @method('PUT')
                                <div class="modal-header"><h5 class="modal-title h6 fw-bold">Update TDS — {{ $tds->client->name }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                <div class="modal-body row g-3">
                                    <div class="col-md-4"><label class="form-label small fw-semibold">FY</label><input type="text" name="financial_year" value="{{ $tds->financial_year }}" class="form-control form-control-sm" required></div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold">Quarter</label>
                                        <select name="quarter" class="form-select form-select-sm">@foreach(\App\Models\TdsReturn::QUARTERS as $q)<option value="{{ $q }}" @selected($tds->quarter === $q)>{{ $q }}</option>@endforeach</select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold">Form</label>
                                        <select name="form_type" class="form-select form-select-sm">@foreach(\App\Models\TdsReturn::FORM_TYPES as $f)<option value="{{ $f }}" @selected($tds->form_type === $f)>{{ $f }}</option>@endforeach</select>
                                    </div>
                                    <div class="col-md-4"><label class="form-label small fw-semibold">Deduction Amount</label><input type="number" step="0.01" name="deduction_amount" value="{{ $tds->deduction_amount }}" class="form-control form-control-sm" required></div>
                                    <div class="col-md-4"><label class="form-label small fw-semibold">Tax Deposited</label><input type="number" step="0.01" name="tax_deposited" value="{{ $tds->tax_deposited }}" class="form-control form-control-sm" required></div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold">Status</label>
                                        <select name="status" class="form-select form-select-sm">@foreach(\App\Models\TdsReturn::STATUSES as $k => $v)<option value="{{ $k }}" @selected($tds->status === $k)>{{ $v }}</option>@endforeach</select>
                                    </div>
                                    <div class="col-md-4"><label class="form-label small fw-semibold">Challan Number</label><input type="text" name="challan_number" value="{{ $tds->challan_number }}" class="form-control form-control-sm"></div>
                                    <div class="col-md-4"><label class="form-label small fw-semibold">BSR Code</label><input type="text" name="bsr_code" value="{{ $tds->bsr_code }}" class="form-control form-control-sm"></div>
                                    <div class="col-md-4"><label class="form-label small fw-semibold">Challan Date</label><input type="date" name="challan_date" value="{{ $tds->challan_date?->format('Y-m-d') }}" class="form-control form-control-sm"></div>
                                    <div class="col-md-4"><label class="form-label small fw-semibold">Due Date</label><input type="date" name="due_date" value="{{ $tds->due_date?->format('Y-m-d') }}" class="form-control form-control-sm"></div>
                                    <div class="col-md-4"><label class="form-label small fw-semibold">Filing Date</label><input type="date" name="filing_date" value="{{ $tds->filing_date?->format('Y-m-d') }}" class="form-control form-control-sm"></div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold">Assigned To</label>
                                        <select name="assigned_to" class="form-select form-select-sm">
                                            <option value="">Unassigned</option>
                                            @foreach($staff as $member)<option value="{{ $member->id }}" @selected($tds->assigned_to == $member->id)>{{ $member->name }}</option>@endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6"><label class="form-label small fw-semibold">Acknowledgement / Token No.</label><input type="text" name="acknowledgement_number" value="{{ $tds->acknowledgement_number }}" class="form-control form-control-sm"></div>
                                    <div class="col-md-6"><label class="form-label small fw-semibold">Notes</label><input type="text" name="notes" value="{{ $tds->notes }}" class="form-control form-control-sm"></div>
                                </div>
                                <div class="modal-footer"><button type="submit" class="btn btn-sm btn-primary">Save Changes</button></div>
                            </form>
                        </div>
                    </div>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">No TDS return records found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($tdsReturns->hasPages())
        <div class="p-3 border-top">{{ $tdsReturns->links() }}</div>
    @endif
</div>

<div class="modal fade" id="addTdsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('tds.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header"><h5 class="modal-title h6 fw-bold">Add TDS Return</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Client *</label>
                    <select name="client_id" class="form-select form-select-sm" required>
                        <option value="">-- Select Client --</option>
                        @foreach($clients as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-2"><label class="form-label small fw-semibold">FY *</label><input type="text" name="financial_year" value="2026-27" class="form-control form-control-sm" required></div>
                <div class="col-md-2"><label class="form-label small fw-semibold">Quarter</label><select name="quarter" class="form-select form-select-sm">@foreach(\App\Models\TdsReturn::QUARTERS as $q)<option value="{{ $q }}">{{ $q }}</option>@endforeach</select></div>
                <div class="col-md-2"><label class="form-label small fw-semibold">Form</label><select name="form_type" class="form-select form-select-sm">@foreach(\App\Models\TdsReturn::FORM_TYPES as $f)<option value="{{ $f }}">{{ $f }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label small fw-semibold">Deduction Amount</label><input type="number" step="0.01" name="deduction_amount" value="0" class="form-control form-control-sm" required></div>
                <div class="col-md-4"><label class="form-label small fw-semibold">Tax Deposited</label><input type="number" step="0.01" name="tax_deposited" value="0" class="form-control form-control-sm" required></div>
                <div class="col-md-4"><label class="form-label small fw-semibold">Status</label><select name="status" class="form-select form-select-sm">@foreach(\App\Models\TdsReturn::STATUSES as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label small fw-semibold">Challan No.</label><input type="text" name="challan_number" class="form-control form-control-sm"></div>
                <div class="col-md-4"><label class="form-label small fw-semibold">BSR Code</label><input type="text" name="bsr_code" class="form-control form-control-sm"></div>
                <div class="col-md-4"><label class="form-label small fw-semibold">Due Date</label><input type="date" name="due_date" class="form-control form-control-sm"></div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Assigned Employee</label>
                    <select name="assigned_to" class="form-select form-select-sm"><option value="">Unassigned</option>@foreach($staff as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>@endforeach</select>
                </div>
                <div class="col-md-6"><label class="form-label small fw-semibold">Notes</label><input type="text" name="notes" class="form-control form-control-sm"></div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-sm btn-primary">Save TDS Return</button></div>
        </form>
    </div>
</div>
@endsection

