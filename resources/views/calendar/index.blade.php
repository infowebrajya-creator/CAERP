@extends('layouts.app')

@section('title', 'Compliance Calendar')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h4 fw-bold mb-1" style="color: #1e3a5f;">Deadline &amp; Compliance Calendar</h1>
        <p class="text-muted small mb-0">Centralized calendar for GST, ITR, TDS, Advance Tax, ROC, Audit, and configurable deadline rules.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('calendar.index', ['status' => 'overdue_only']) }}" class="btn btn-sm btn-outline-danger">
            <i class="bi bi-exclamation-circle me-1"></i>Overdue ({{ $overdueCount }})
        </a>
        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#rulesModal">
            <i class="bi bi-sliders me-1"></i>Configurable Rules
        </button>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addDeadlineModal">
            <i class="bi bi-plus-lg me-1"></i>Add Deadline
        </button>
    </div>
</div>

<!-- Filters & Month Nav -->
<div class="ca-card p-3 mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-4 d-flex align-items-center gap-2">
            @php
                $prev = $currentMonth->copy()->subMonth();
                $next = $currentMonth->copy()->addMonth();
            @endphp
            <a href="{{ route('calendar.index', array_merge(request()->query(), ['month' => $prev->month, 'year' => $prev->year])) }}" class="btn btn-sm btn-light border"><i class="bi bi-chevron-left"></i></a>
            <span class="fw-bold px-2" style="color: #1e3a5f;">{{ $currentMonth->format('F Y') }}</span>
            <a href="{{ route('calendar.index', array_merge(request()->query(), ['month' => $next->month, 'year' => $next->year])) }}" class="btn btn-sm btn-light border"><i class="bi bi-chevron-right"></i></a>
        </div>
        <div class="col-md-8">
            <form method="GET" action="{{ route('calendar.index') }}" class="row g-2">
                <input type="hidden" name="month" value="{{ $currentMonth->month }}">
                <input type="hidden" name="year" value="{{ $currentMonth->year }}">
                <div class="col-md-4">
                    <select name="compliance_type" class="form-select form-select-sm">
                        <option value="">All Compliance Types</option>
                        @foreach(\App\Models\ComplianceDeadline::TYPES as $k => $v)
                            <option value="{{ $k }}" @selected(request('compliance_type') === $k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        @foreach(\App\Models\ComplianceDeadline::STATUSES as $k => $v)
                            <option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="client_id" class="form-select form-select-sm">
                        <option value="">All Clients</option>
                        @foreach($clients as $c)<option value="{{ $c->id }}" @selected((string) request('client_id') === (string) $c->id)>{{ $c->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-2 d-grid"><button type="submit" class="btn btn-sm btn-primary">Filter</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Visual Monthly Calendar Grid -->
@php
    $startOfCal = $currentMonth->copy()->startOfMonth()->startOfWeek(\Carbon\Carbon::SUNDAY);
    $endOfCal = $currentMonth->copy()->endOfMonth()->endOfWeek(\Carbon\Carbon::SATURDAY);
@endphp
<div class="ca-card p-3 mb-4 d-none d-md-block">
    <div class="calendar-grid mb-1 text-center bg-light fw-bold small py-2" style="grid-template-columns: repeat(7, 1fr);">
        @foreach(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $wd)
            <div>{{ $wd }}</div>
        @endforeach
    </div>
    <div class="calendar-grid">
        @for($day = $startOfCal->copy(); $day->lte($endOfCal); $day->addDay())
            @php
                $key = $day->format('Y-m-d');
                $dayItems = $monthDeadlines->get($key, collect());
            @endphp
            <div class="calendar-day {{ $day->month !== $currentMonth->month ? 'other-month' : '' }} {{ $day->isToday() ? 'today' : '' }}">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="small fw-bold">{{ $day->day }}</span>
                    @if($dayItems->count() > 0)
                        <span class="badge rounded-pill bg-primary-subtle text-primary" style="font-size: 0.65rem;">{{ $dayItems->count() }}</span>
                    @endif
                </div>
                @foreach($dayItems->take(3) as $item)
                    <div class="rounded px-1 py-0 mb-1 text-truncate border {{ $item->status === 'completed' ? 'bg-success-subtle text-success' : ($item->due_date->isPast() ? 'bg-danger-subtle text-danger' : 'bg-info-subtle text-primary') }}" style="font-size: 0.7rem;" title="{{ $item->title }}">
                        {{ $item->typeLabel() }}: {{ $item->title }}
                    </div>
                @endforeach
                @if($dayItems->count() > 3)
                    <div class="text-muted" style="font-size: 0.68rem;">+{{ $dayItems->count() - 3 }} more</div>
                @endif
            </div>
        @endfor
    </div>
</div>

<!-- Deadlines Table -->
<div class="ca-card overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Type</th>
                    <th>Client</th>
                    <th>Due Date</th>
                    <th>Reminder</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Assigned To</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($deadlines as $dl)
                    <tr>
                        <td class="fw-semibold">{{ $dl->title }}</td>
                        <td><span class="badge badge-soft-info">{{ $dl->typeLabel() }}</span></td>
                        <td class="small">{{ $dl->client?->name ?? 'Firm-wide' }}</td>
                        <td class="small {{ $dl->status !== 'completed' && $dl->due_date->isPast() ? 'text-danger fw-bold' : '' }}">{{ $dl->due_date->format('d M Y') }}</td>
                        <td class="small text-muted">{{ $dl->reminder_date?->format('d M Y') ?? '—' }}</td>
                        <td><x-status-badge :status="$dl->priority" /></td>
                        <td><x-status-badge :status="$dl->status" :label="$dl->statusLabel()" /></td>
                        <td class="small">{{ $dl->assignee?->name ?? 'Unassigned' }}</td>
                        <td class="text-end text-nowrap">
                            <button class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#editDlModal{{ $dl->id }}"><i class="bi bi-pencil"></i></button>
                            <form action="{{ route('calendar.destroy', $dl) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete deadline?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-light border text-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>

                    <div class="modal fade" id="editDlModal{{ $dl->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <form action="{{ route('calendar.update', $dl) }}" method="POST" class="modal-content">
                                @csrf @method('PUT')
                                <div class="modal-header"><h5 class="modal-title h6 fw-bold">Update Deadline</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                <div class="modal-body row g-2">
                                    <div class="col-12"><label class="form-label small fw-semibold">Title</label><input type="text" name="title" value="{{ $dl->title }}" class="form-control form-control-sm" required></div>
                                    <div class="col-6"><label class="form-label small fw-semibold">Type</label><select name="compliance_type" class="form-select form-select-sm">@foreach(\App\Models\ComplianceDeadline::TYPES as $k => $v)<option value="{{ $k }}" @selected($dl->compliance_type === $k)>{{ $v }}</option>@endforeach</select></div>
                                    <div class="col-6"><label class="form-label small fw-semibold">Status</label><select name="status" class="form-select form-select-sm">@foreach(\App\Models\ComplianceDeadline::STATUSES as $k => $v)<option value="{{ $k }}" @selected($dl->status === $k)>{{ $v }}</option>@endforeach</select></div>
                                    <div class="col-6"><label class="form-label small fw-semibold">Due Date</label><input type="date" name="due_date" value="{{ $dl->due_date->format('Y-m-d') }}" class="form-control form-control-sm" required></div>
                                    <div class="col-6"><label class="form-label small fw-semibold">Reminder Date</label><input type="date" name="reminder_date" value="{{ $dl->reminder_date?->format('Y-m-d') }}" class="form-control form-control-sm"></div>
                                    <div class="col-6"><label class="form-label small fw-semibold">Client</label><select name="client_id" class="form-select form-select-sm"><option value="">Firm-wide</option>@foreach($clients as $c)<option value="{{ $c->id }}" @selected($dl->client_id == $c->id)>{{ $c->name }}</option>@endforeach</select></div>
                                    <div class="col-6"><label class="form-label small fw-semibold">Assigned To</label><select name="assigned_to" class="form-select form-select-sm"><option value="">Unassigned</option>@foreach($staff as $m)<option value="{{ $m->id }}" @selected($dl->assigned_to == $m->id)>{{ $m->name }}</option>@endforeach</select></div>
                                    <div class="col-12"><label class="form-label small fw-semibold">Priority</label><select name="priority" class="form-select form-select-sm">@foreach(['low','medium','high','urgent'] as $p)<option value="{{ $p }}" @selected($dl->priority === $p)>{{ ucfirst($p) }}</option>@endforeach</select></div>
                                    <div class="col-12"><label class="form-label small fw-semibold">Description</label><textarea name="description" rows="2" class="form-control form-control-sm">{{ $dl->description }}</textarea></div>
                                </div>
                                <div class="modal-footer"><button type="submit" class="btn btn-sm btn-primary">Save</button></div>
                            </form>
                        </div>
                    </div>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">No deadlines match the selected filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($deadlines->hasPages())
        <div class="p-3 border-top">{{ $deadlines->links() }}</div>
    @endif
</div>

<!-- Add Deadline Modal -->
<div class="modal fade" id="addDeadlineModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('calendar.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header"><h5 class="modal-title h6 fw-bold">Add Compliance Deadline</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body row g-2">
                <div class="col-12"><label class="form-label small fw-semibold">Title *</label><input type="text" name="title" class="form-control form-control-sm" required></div>
                <div class="col-6"><label class="form-label small fw-semibold">Type *</label><select name="compliance_type" class="form-select form-select-sm">@foreach(\App\Models\ComplianceDeadline::TYPES as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
                <div class="col-6"><label class="form-label small fw-semibold">Priority</label><select name="priority" class="form-select form-select-sm">@foreach(['low','medium','high','urgent'] as $p)<option value="{{ $p }}" @selected($p === 'medium')>{{ ucfirst($p) }}</option>@endforeach</select></div>
                <div class="col-6"><label class="form-label small fw-semibold">Due Date *</label><input type="date" name="due_date" class="form-control form-control-sm" required></div>
                <div class="col-6"><label class="form-label small fw-semibold">Reminder Date</label><input type="date" name="reminder_date" class="form-control form-control-sm"></div>
                <div class="col-6"><label class="form-label small fw-semibold">Client</label><select name="client_id" class="form-select form-select-sm"><option value="">Firm-wide</option>@foreach($clients as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
                <div class="col-6"><label class="form-label small fw-semibold">Assign To</label><select name="assigned_to" class="form-select form-select-sm"><option value="">Unassigned</option>@foreach($staff as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>@endforeach</select></div>
                <div class="col-12"><label class="form-label small fw-semibold">Description</label><textarea name="description" rows="2" class="form-control form-control-sm"></textarea></div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-sm btn-primary">Save Deadline</button></div>
        </form>
    </div>
</div>

<!-- Configurable Rules Modal -->
<div class="modal fade" id="rulesModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title h6 fw-bold">Configurable Compliance Due-Date Rules</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3">
                    @foreach($rules as $rule)
                        <form action="{{ route('calendar.rules.generate', $rule) }}" method="POST" class="d-flex flex-wrap justify-content-between align-items-center border rounded p-2 mb-2 bg-light gap-2">
                            @csrf
                            <div>
                                <div class="fw-semibold small">{{ $rule->name }} <span class="badge badge-soft-info">{{ strtoupper($rule->compliance_type) }}</span></div>
                                <div class="text-muted" style="font-size: 0.74rem;">Day {{ $rule->due_day }} · {{ ucfirst($rule->frequency) }} · Offset +{{ $rule->month_offset }} mo · Remind {{ $rule->reminder_days_before }}d before</div>
                            </div>
                            <div class="d-flex gap-1 align-items-center">
                                <input type="number" name="month" value="{{ now()->month }}" min="1" max="12" class="form-control form-control-sm" style="width: 65px;" title="Month">
                                <input type="number" name="year" value="{{ now()->year }}" class="form-control form-control-sm" style="width: 80px;" title="Year">
                                <button type="submit" class="btn btn-sm btn-outline-primary">Generate</button>
                            </div>
                        </form>
                    @endforeach
                </div>
                <hr>
                <form action="{{ route('calendar.rules.store') }}" method="POST" class="row g-2">
                    @csrf
                    <div class="col-12 fw-bold small">Create New Configurable Rule</div>
                    <div class="col-md-5"><input type="text" name="name" class="form-control form-control-sm" placeholder="Rule Name (e.g. GSTR-1 Monthly)" required></div>
                    <div class="col-md-3"><select name="compliance_type" class="form-select form-select-sm">@foreach(\App\Models\ComplianceDeadline::TYPES as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
                    <div class="col-md-4"><select name="frequency" class="form-select form-select-sm"><option value="monthly">Monthly</option><option value="quarterly">Quarterly</option><option value="annual">Annual</option></select></div>
                    <div class="col-md-3"><input type="number" name="due_day" value="11" min="1" max="31" class="form-control form-control-sm" placeholder="Due Day" required></div>
                    <div class="col-md-3"><input type="number" name="due_month" class="form-control form-control-sm" min="1" max="12" placeholder="Month (opt)"></div>
                    <div class="col-md-3"><input type="number" name="month_offset" value="1" min="0" max="12" class="form-control form-control-sm" placeholder="Offset Mo"></div>
                    <div class="col-md-3"><input type="number" name="reminder_days_before" value="5" min="0" max="60" class="form-control form-control-sm" placeholder="Remind Days"></div>
                    <div class="col-12 text-end"><button type="submit" class="btn btn-sm btn-primary">Save Rule</button></div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

