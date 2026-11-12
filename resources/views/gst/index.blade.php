@extends('layouts.app')

@section('title', 'GST Compliance')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h4 fw-bold mb-1" style="color: #1e3a5f;">GST Compliance Management</h1>
        <p class="text-muted small mb-0">Track GSTR-1, GSTR-3B, GSTR-9 &amp; custom return types, client document checklists, ARNs, and due dates.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('gst.index', ['view' => $viewMode === 'calendar' ? 'list' : 'calendar']) }}" class="btn btn-sm btn-outline-info">
            <i class="bi {{ $viewMode === 'calendar' ? 'bi-table' : 'bi-calendar3' }} me-1"></i>
            {{ $viewMode === 'calendar' ? 'List View' : 'GST Calendar' }}
        </a>
        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#returnTypesModal">
            <i class="bi bi-gear me-1"></i>Return Types
        </button>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addGstModal">
            <i class="bi bi-plus-lg me-1"></i>Add GST Return
        </button>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><x-kpi-card title="Total GST Filings" :value="$summary['total']" icon="bi-receipt-cutoff" tone="primary" /></div>
    <div class="col-6 col-md-3"><x-kpi-card title="Filed" :value="$summary['filed']" icon="bi-check-circle" tone="success" /></div>
    <div class="col-6 col-md-3"><x-kpi-card title="Pending / In Progress" :value="$summary['pending']" icon="bi-hourglass-split" tone="warning" /></div>
    <div class="col-6 col-md-3"><x-kpi-card title="Overdue" :value="$summary['overdue']" icon="bi-exclamation-triangle" tone="danger" /></div>
</div>

<!-- Filters -->
<div class="ca-card p-3 mb-3">
    <form method="GET" action="{{ route('gst.index') }}" class="row g-2 align-items-end">
        <input type="hidden" name="view" value="{{ $viewMode }}">
        <div class="col-md-3">
            <label class="form-label small text-muted mb-1">Client / GSTIN</label>
            <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Search client or GSTIN...">
        </div>
        <div class="col-md-2">
            <label class="form-label small text-muted mb-1">Return Type</label>
            <select name="return_type_code" class="form-select form-select-sm">
                <option value="">All Types</option>
                @foreach($returnTypes as $rt)
                    <option value="{{ $rt->code }}" @selected(request('return_type_code') === $rt->code)>{{ $rt->code }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small text-muted mb-1">Month</label>
            <select name="period_month" class="form-select form-select-sm">
                <option value="">All Months</option>
                @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" @selected((string) request('period_month') === (string) $m)>{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                @endfor
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small text-muted mb-1">Year</label>
            <input type="number" name="period_year" value="{{ request('period_year') }}" class="form-control form-control-sm" placeholder="2026">
        </div>
        <div class="col-md-2">
            <label class="form-label small text-muted mb-1">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">All Statuses</option>
                @foreach(\App\Models\GstReturn::STATUSES as $k => $v)
                    <option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-1 d-grid">
            <button type="submit" class="btn btn-sm btn-primary">Filter</button>
        </div>
    </form>
</div>

@if($viewMode === 'calendar')
    <div class="ca-card p-3">
        <h2 class="h6 fw-bold mb-3">GST Filing Schedule</h2>
        <div class="row g-2">
            @forelse($calendarReturns->groupBy(fn($r) => $r->due_date->format('Y-m-d')) as $dateStr => $items)
                <div class="col-md-4 col-lg-3">
                    <div class="border rounded p-2 h-100 bg-light">
                        <div class="fw-bold small mb-2 text-primary">{{ \Carbon\Carbon::parse($dateStr)->format('d M Y (D)') }}</div>
                        @foreach($items as $item)
                            <div class="bg-white border rounded p-2 mb-1 small">
                                <div class="d-flex justify-content-between">
                                    <span class="fw-semibold">{{ $item->return_type_code }}</span>
                                    <x-status-badge :status="$item->status" :label="$item->statusLabel()" />
                                </div>
                                <div class="text-muted text-truncate">{{ $item->client->name }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="col-12 text-center text-muted py-4">No GST returns scheduled.</div>
            @endforelse
        </div>
    </div>
@else
    <div class="ca-card overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Client &amp; GSTIN</th>
                        <th>Return</th>
                        <th>Period / FY</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>ARN &amp; Filing Date</th>
                        <th>Assigned To</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($returns as $gst)
                        <tr>
                            <td>
                                <a href="{{ route('clients.show', ['client' => $gst->client_id, 'tab' => 'compliance']) }}" class="fw-semibold text-decoration-none">{{ $gst->client->name }}</a>
                                <div class="small text-muted">{{ $gst->client->gstin ?? 'No GSTIN' }}</div>
                            </td>
                            <td><span class="badge badge-soft-primary">{{ $gst->return_type_code }}</span></td>
                            <td class="small">{{ $gst->tax_period }} <div class="text-muted">FY {{ $gst->financial_year }}</div></td>
                            <td class="small {{ $gst->status !== 'filed' && $gst->due_date->isPast() ? 'text-danger fw-semibold' : '' }}">
                                {{ $gst->due_date->format('d M Y') }}
                            </td>
                            <td><x-status-badge :status="$gst->status" :label="$gst->statusLabel()" /></td>
                            <td class="small">
                                <div>{{ $gst->arn ?? '—' }}</div>
                                <div class="text-muted">{{ $gst->filing_date?->format('d M Y') ?? '' }}</div>
                            </td>
                            <td class="small">{{ $gst->assignee?->name ?? 'Unassigned' }}</td>
                            <td class="text-end text-nowrap">
                                <button class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#editGstModal{{ $gst->id }}"><i class="bi bi-pencil"></i></button>
                                <form action="{{ route('gst.destroy', $gst) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete GST record?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-light border text-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>

                        <!-- Edit Modal -->
                        <div class="modal fade" id="editGstModal{{ $gst->id }}" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <form action="{{ route('gst.update', $gst) }}" method="POST" class="modal-content">
                                    @csrf @method('PUT')
                                    <div class="modal-header">
                                        <h5 class="modal-title h6 fw-bold">Update {{ $gst->return_type_code }} — {{ $gst->client->name }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label small fw-semibold">Return Type</label>
                                            <select name="return_type_id" class="form-select form-select-sm" required>
                                                @foreach($returnTypes as $rt)
                                                    <option value="{{ $rt->id }}" @selected($gst->return_type_id == $rt->id || $gst->return_type_code === $rt->code)>{{ $rt->code }} - {{ $rt->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-semibold">Financial Year</label>
                                            <input type="text" name="financial_year" value="{{ $gst->financial_year }}" class="form-control form-control-sm" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-semibold">Tax Period</label>
                                            <input type="text" name="tax_period" value="{{ $gst->tax_period }}" class="form-control form-control-sm" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small fw-semibold">Period Month</label>
                                            <input type="number" name="period_month" value="{{ $gst->period_month }}" min="1" max="12" class="form-control form-control-sm">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small fw-semibold">Period Year</label>
                                            <input type="number" name="period_year" value="{{ $gst->period_year }}" class="form-control form-control-sm">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small fw-semibold">Due Date</label>
                                            <input type="date" name="due_date" value="{{ $gst->due_date->format('Y-m-d') }}" class="form-control form-control-sm" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small fw-semibold">Status</label>
                                            <select name="status" class="form-select form-select-sm" required>
                                                @foreach(\App\Models\GstReturn::STATUSES as $k => $v)
                                                    <option value="{{ $k }}" @selected($gst->status === $k)>{{ $v }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-semibold">ARN (Manual Entry)</label>
                                            <input type="text" name="arn" value="{{ $gst->arn }}" class="form-control form-control-sm" placeholder="AA2711260012345">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-semibold">Filing Date</label>
                                            <input type="date" name="filing_date" value="{{ $gst->filing_date?->format('Y-m-d') }}" class="form-control form-control-sm">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-semibold">Assigned Employee</label>
                                            <select name="assigned_to" class="form-select form-select-sm">
                                                <option value="">Unassigned</option>
                                                @foreach($staff as $member)
                                                    <option value="{{ $member->id }}" @selected($gst->assigned_to == $member->id)>{{ $member->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        @if(!empty($gst->document_checklist))
                                            <div class="col-12">
                                                <label class="form-label small fw-semibold">Document Checklist</label>
                                                <div class="border rounded p-2 bg-light">
                                                    @foreach($gst->document_checklist as $idx => $chk)
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="checklist_checked[]" value="{{ $idx }}" id="gstChk{{ $gst->id }}_{{ $idx }}" @checked(!empty($chk['received']))>
                                                            <label class="form-check-label small" for="gstChk{{ $gst->id }}_{{ $idx }}">{{ $chk['label'] }}</label>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                        <div class="col-12">
                                            <label class="form-label small fw-semibold">Internal Notes</label>
                                            <textarea name="internal_notes" rows="2" class="form-control form-control-sm">{{ $gst->internal_notes }}</textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-sm btn-primary">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">No GST return records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($returns->hasPages())
            <div class="p-3 border-top">{{ $returns->links() }}</div>
        @endif
    </div>
@endif

<!-- Add GST Modal -->
<div class="modal fade" id="addGstModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('gst.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title h6 fw-bold">New GST Return Tracker</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Client *</label>
                    <select name="client_id" class="form-select form-select-sm" required>
                        <option value="">-- Select Client --</option>
                        @foreach($clients as $c)
                            <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->gstin ?? 'No GSTIN' }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Return Type *</label>
                    <select name="return_type_id" class="form-select form-select-sm" required>
                        @foreach($returnTypes as $rt)
                            <option value="{{ $rt->id }}">{{ $rt->code }} — {{ $rt->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Financial Year *</label>
                    <input type="text" name="financial_year" value="2026-27" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Tax Period *</label>
                    <input type="text" name="tax_period" value="{{ now()->format('M Y') }}" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Month (1-12)</label>
                    <input type="number" name="period_month" value="{{ now()->month }}" min="1" max="12" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Year</label>
                    <input type="number" name="period_year" value="{{ now()->year }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Due Date *</label>
                    <input type="date" name="due_date" value="{{ now()->addDays(10)->format('Y-m-d') }}" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Status *</label>
                    <select name="status" class="form-select form-select-sm" required>
                        @foreach(\App\Models\GstReturn::STATUSES as $k => $v)
                            <option value="{{ $k }}">{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Assigned Employee</label>
                    <select name="assigned_to" class="form-select form-select-sm">
                        <option value="">Unassigned</option>
                        @foreach($staff as $member)
                            <option value="{{ $member->id }}">{{ $member->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-semibold">Client Document Checklist (one item per line)</label>
                    <textarea name="checklist_items" rows="3" class="form-control form-control-sm" placeholder="Sales Register / B2B Invoices&#10;Purchase Register &amp; GSTR-2B&#10;Credit/Debit Notes"></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-semibold">Internal Notes</label>
                    <textarea name="internal_notes" rows="2" class="form-control form-control-sm"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-sm btn-primary">Create GST Return</button>
            </div>
        </form>
    </div>
</div>

<!-- Configurable GST Return Types Modal -->
<div class="modal fade" id="returnTypesModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('gst.return_types.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title h6 fw-bold">Configurable GST Return Types</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    @foreach($returnTypes as $rt)
                        <div class="d-flex justify-content-between align-items-center border-bottom py-1 small">
                            <span><strong>{{ $rt->code }}</strong> — {{ $rt->name }}</span>
                            <span class="badge badge-soft-secondary">{{ ucfirst($rt->frequency) }} · Day {{ $rt->default_due_day }}</span>
                        </div>
                    @endforeach
                </div>
                <h6 class="small fw-bold mb-2">Add Custom Return Type</h6>
                <div class="row g-2">
                    <div class="col-4"><input type="text" name="code" class="form-control form-control-sm" placeholder="e.g. GSTR-4" required></div>
                    <div class="col-8"><input type="text" name="name" class="form-control form-control-sm" placeholder="Composition Annual Return" required></div>
                    <div class="col-4">
                        <select name="frequency" class="form-select form-select-sm">
                            <option value="monthly">Monthly</option>
                            <option value="quarterly">Quarterly</option>
                            <option value="annual">Annual</option>
                        </select>
                    </div>
                    <div class="col-4"><input type="number" name="default_due_day" value="20" min="1" max="31" class="form-control form-control-sm" placeholder="Due Day"></div>
                    <div class="col-4"><input type="number" name="default_due_month_offset" value="1" min="0" max="12" class="form-control form-control-sm" placeholder="Offset Mo"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-sm btn-primary">Add Return Type</button>
            </div>
        </form>
    </div>
</div>
@endsection

