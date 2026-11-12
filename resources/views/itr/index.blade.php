@extends('layouts.app')

@section('title', 'ITR Management')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h4 fw-bold mb-1" style="color: #1e3a5f;">Income Tax Return (ITR) Management</h1>
        <p class="text-muted small mb-0">Track client ITR categories, assessment years, pending document checklists, acknowledgement numbers, and refunds.</p>
    </div>
    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addItrModal">
        <i class="bi bi-plus-lg me-1"></i>Add ITR Filing
    </button>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><x-kpi-card title="Total ITRs" :value="$summary['total']" icon="bi-file-earmark-check" tone="primary" /></div>
    <div class="col-6 col-md-3"><x-kpi-card title="Filed / E-Verified" :value="$summary['filed']" icon="bi-check2-circle" tone="success" /></div>
    <div class="col-6 col-md-3"><x-kpi-card title="Pending" :value="$summary['pending']" icon="bi-hourglass-split" tone="warning" /></div>
    <div class="col-6 col-md-3"><x-kpi-card title="Refunds Pending" :value="$summary['refund_pending']" icon="bi-cash-coin" tone="info" /></div>
</div>

<div class="ca-card p-3 mb-3">
    <form method="GET" action="{{ route('itr.index') }}" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small text-muted mb-1">Client / PAN</label>
            <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Search client or PAN...">
        </div>
        <div class="col-md-2">
            <label class="form-label small text-muted mb-1">Assessment Year</label>
            <input type="text" name="assessment_year" value="{{ request('assessment_year') }}" class="form-control form-control-sm" placeholder="2026-27">
        </div>
        <div class="col-md-2">
            <label class="form-label small text-muted mb-1">Return Form</label>
            <select name="return_type" class="form-select form-select-sm">
                <option value="">All Forms</option>
                @foreach(\App\Models\ItrFiling::RETURN_TYPES as $rt)
                    <option value="{{ $rt }}" @selected(request('return_type') === $rt)>{{ $rt }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small text-muted mb-1">Filing Status</label>
            <select name="filing_status" class="form-select form-select-sm">
                <option value="">All Statuses</option>
                @foreach(\App\Models\ItrFiling::FILING_STATUSES as $k => $v)
                    <option value="{{ $k }}" @selected(request('filing_status') === $k)>{{ $v }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 d-flex gap-1">
            <button type="submit" class="btn btn-sm btn-primary flex-grow-1">Filter</button>
            <a href="{{ route('itr.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
        </div>
    </form>
</div>

<div class="ca-card overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Client &amp; PAN</th>
                    <th>AY / FY</th>
                    <th>Form &amp; Category</th>
                    <th>Docs</th>
                    <th>Filing Status</th>
                    <th>Ack No. &amp; Date</th>
                    <th>Refund</th>
                    <th>Assigned</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($filings as $itr)
                    <tr>
                        <td>
                            <a href="{{ route('clients.show', ['client' => $itr->client_id, 'tab' => 'compliance']) }}" class="fw-semibold text-decoration-none">{{ $itr->client->name }}</a>
                            <div class="small text-muted">{{ $itr->client->pan ?? 'No PAN' }}</div>
                        </td>
                        <td class="small"><strong>AY {{ $itr->assessment_year }}</strong><div class="text-muted">FY {{ $itr->financial_year }}</div></td>
                        <td class="small"><span class="badge badge-soft-primary">{{ $itr->return_type }}</span><div class="text-muted">{{ $itr->itr_category }}</div></td>
                        <td><x-status-badge :status="$itr->document_status" /></td>
                        <td><x-status-badge :status="$itr->filing_status" :label="$itr->filingStatusLabel()" /></td>
                        <td class="small">{{ $itr->acknowledgement_number ?? '—' }}<div class="text-muted">{{ $itr->filing_date?->format('d M Y') ?? '' }}</div></td>
                        <td class="small">
                            <x-status-badge :status="$itr->refund_status" :label="$itr->refundStatusLabel()" />
                            @if((float) $itr->refund_amount > 0)
                                <div class="text-muted mt-1">₹{{ number_format((float) $itr->refund_amount, 0) }}</div>
                            @endif
                        </td>
                        <td class="small">{{ $itr->assignee?->name ?? 'Unassigned' }}</td>
                        <td class="text-end text-nowrap">
                            <button class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#editItrModal{{ $itr->id }}"><i class="bi bi-pencil"></i></button>
                            <form action="{{ route('itr.destroy', $itr) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete ITR record?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-light border text-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>

                    <!-- Edit Modal -->
                    <div class="modal fade" id="editItrModal{{ $itr->id }}" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <form action="{{ route('itr.update', $itr) }}" method="POST" class="modal-content">
                                @csrf @method('PUT')
                                <div class="modal-header">
                                    <h5 class="modal-title h6 fw-bold">Update ITR — {{ $itr->client->name }} (AY {{ $itr->assessment_year }})</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body row g-3">
                                    <div class="col-md-3"><label class="form-label small fw-semibold">AY</label><input type="text" name="assessment_year" value="{{ $itr->assessment_year }}" class="form-control form-control-sm" required></div>
                                    <div class="col-md-3"><label class="form-label small fw-semibold">FY</label><input type="text" name="financial_year" value="{{ $itr->financial_year }}" class="form-control form-control-sm" required></div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-semibold">Category</label>
                                        <select name="itr_category" class="form-select form-select-sm">
                                            @foreach(\App\Models\ItrFiling::CATEGORIES as $cat)
                                                <option value="{{ $cat }}" @selected($itr->itr_category === $cat)>{{ $cat }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-semibold">Form</label>
                                        <select name="return_type" class="form-select form-select-sm">
                                            @foreach(\App\Models\ItrFiling::RETURN_TYPES as $rt)
                                                <option value="{{ $rt }}" @selected($itr->return_type === $rt)>{{ $rt }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold">Document Status</label>
                                        <select name="document_status" class="form-select form-select-sm">
                                            @foreach(\App\Models\ItrFiling::DOCUMENT_STATUSES as $k => $v)
                                                <option value="{{ $k }}" @selected($itr->document_status === $k)>{{ $v }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold">Filing Status</label>
                                        <select name="filing_status" class="form-select form-select-sm">
                                            @foreach(\App\Models\ItrFiling::FILING_STATUSES as $k => $v)
                                                <option value="{{ $k }}" @selected($itr->filing_status === $k)>{{ $v }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold">Assigned To</label>
                                        <select name="assigned_to" class="form-select form-select-sm">
                                            <option value="">Unassigned</option>
                                            @foreach($staff as $member)
                                                <option value="{{ $member->id }}" @selected($itr->assigned_to == $member->id)>{{ $member->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3"><label class="form-label small fw-semibold">Due Date</label><input type="date" name="due_date" value="{{ $itr->due_date?->format('Y-m-d') }}" class="form-control form-control-sm"></div>
                                    <div class="col-md-3"><label class="form-label small fw-semibold">Filing Date</label><input type="date" name="filing_date" value="{{ $itr->filing_date?->format('Y-m-d') }}" class="form-control form-control-sm"></div>
                                    <div class="col-md-6"><label class="form-label small fw-semibold">Acknowledgement No.</label><input type="text" name="acknowledgement_number" value="{{ $itr->acknowledgement_number }}" class="form-control form-control-sm"></div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Refund Status</label>
                                        <select name="refund_status" class="form-select form-select-sm">
                                            @foreach(\App\Models\ItrFiling::REFUND_STATUSES as $k => $v)
                                                <option value="{{ $k }}" @selected($itr->refund_status === $k)>{{ $v }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6"><label class="form-label small fw-semibold">Refund Amount (INR)</label><input type="number" step="0.01" name="refund_amount" value="{{ $itr->refund_amount }}" class="form-control form-control-sm"></div>
                                    @if(!empty($itr->pending_documents_checklist))
                                        <div class="col-12">
                                            <label class="form-label small fw-semibold">Pending Document Checklist</label>
                                            <div class="border rounded p-2 bg-light">
                                                @foreach($itr->pending_documents_checklist as $idx => $chk)
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="checklist_checked[]" value="{{ $idx }}" id="itrChk{{ $itr->id }}_{{ $idx }}" @checked(!empty($chk['received']))>
                                                        <label class="form-check-label small" for="itrChk{{ $itr->id }}_{{ $idx }}">{{ $chk['label'] }}</label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                    <div class="col-12"><label class="form-label small fw-semibold">Notes</label><textarea name="notes" rows="2" class="form-control form-control-sm">{{ $itr->notes }}</textarea></div>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-sm btn-primary">Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">No ITR filings found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($filings->hasPages())
        <div class="p-3 border-top">{{ $filings->links() }}</div>
    @endif
</div>

<!-- Add ITR Modal -->
<div class="modal fade" id="addItrModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('itr.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header"><h5 class="modal-title h6 fw-bold">New ITR Filing</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Client *</label>
                    <select name="client_id" class="form-select form-select-sm" required>
                        <option value="">-- Select Client --</option>
                        @foreach($clients as $c)<option value="{{ $c->id }}">{{ $c->name }} ({{ $c->pan ?? 'No PAN' }})</option>@endforeach
                    </select>
                </div>
                <div class="col-md-3"><label class="form-label small fw-semibold">AY *</label><input type="text" name="assessment_year" value="2026-27" class="form-control form-control-sm" required></div>
                <div class="col-md-3"><label class="form-label small fw-semibold">FY *</label><input type="text" name="financial_year" value="2025-26" class="form-control form-control-sm" required></div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">ITR Category</label>
                    <select name="itr_category" class="form-select form-select-sm">
                        @foreach(\App\Models\ItrFiling::CATEGORIES as $cat)<option value="{{ $cat }}">{{ $cat }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Return Form</label>
                    <select name="return_type" class="form-select form-select-sm">
                        @foreach(\App\Models\ItrFiling::RETURN_TYPES as $rt)<option value="{{ $rt }}">{{ $rt }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Assigned To</label>
                    <select name="assigned_to" class="form-select form-select-sm">
                        <option value="">Unassigned</option>
                        @foreach($staff as $member)<option value="{{ $member->id }}">{{ $member->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Document Status</label>
                    <select name="document_status" class="form-select form-select-sm">
                        @foreach(\App\Models\ItrFiling::DOCUMENT_STATUSES as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Filing Status</label>
                    <select name="filing_status" class="form-select form-select-sm">
                        @foreach(\App\Models\ItrFiling::FILING_STATUSES as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-4"><label class="form-label small fw-semibold">Due Date</label><input type="date" name="due_date" class="form-control form-control-sm"></div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Refund Status</label>
                    <select name="refund_status" class="form-select form-select-sm">
                        @foreach(\App\Models\ItrFiling::REFUND_STATUSES as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-6"><label class="form-label small fw-semibold">Refund Amount</label><input type="number" step="0.01" name="refund_amount" value="0" class="form-control form-control-sm"></div>
                <div class="col-12"><label class="form-label small fw-semibold">Document Checklist (one per line)</label><textarea name="checklist_items" rows="3" class="form-control form-control-sm" placeholder="Form 16 / 16A&#10;AIS &amp; TIS&#10;Bank Statements"></textarea></div>
                <div class="col-12"><label class="form-label small fw-semibold">Notes</label><textarea name="notes" rows="2" class="form-control form-control-sm"></textarea></div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-sm btn-primary">Create ITR Filing</button></div>
        </form>
    </div>
</div>
@endsection

