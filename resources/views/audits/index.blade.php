@extends('layouts.app')

@section('title', 'Audit Management')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h4 fw-bold mb-1" style="color: #1e3a5f;">Audit &amp; Assurance Management</h1>
        <p class="text-muted small mb-0">Manage statutory, tax, GST &amp; internal audits, checklists, working papers, and partner sign-offs.</p>
    </div>
    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addAuditModal">
        <i class="bi bi-plus-lg me-1"></i>New Audit Assignment
    </button>
</div>

<div class="ca-card p-3 mb-3">
    <form method="GET" action="{{ route('audits.index') }}" class="row g-2 align-items-end">
        <div class="col-md-4"><label class="form-label small text-muted mb-1">Client</label><select name="client_id" class="form-select form-select-sm"><option value="">All Clients</option>@foreach($clients as $c)<option value="{{ $c->id }}" @selected((string) request('client_id') === (string) $c->id)>{{ $c->name }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label small text-muted mb-1">Audit Type</label><select name="audit_type" class="form-select form-select-sm"><option value="">All Types</option>@foreach(\App\Models\Audit::AUDIT_TYPES as $k => $v)<option value="{{ $k }}" @selected(request('audit_type') === $k)>{{ $v }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label small text-muted mb-1">Status</label><select name="status" class="form-select form-select-sm"><option value="">All Statuses</option>@foreach(\App\Models\Audit::STATUSES as $k => $v)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>@endforeach</select></div>
        <div class="col-md-2 d-flex gap-1"><button type="submit" class="btn btn-sm btn-primary flex-grow-1">Filter</button><a href="{{ route('audits.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a></div>
    </form>
</div>

<div class="row g-3">
    @forelse($audits as $audit)
        <div class="col-md-6 col-xl-4">
            <div class="ca-card p-3 h-100 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                        <span class="badge badge-soft-info">{{ $audit->typeLabel() }}</span>
                        <x-status-badge :status="$audit->status" :label="$audit->statusLabel()" />
                    </div>
                    <h2 class="h6 fw-bold mb-1">
                        <a href="{{ route('audits.show', $audit) }}" class="text-decoration-none" style="color: #1e3a5f;">{{ $audit->title }}</a>
                    </h2>
                    <div class="small text-muted mb-3">{{ $audit->client->name }} · FY {{ $audit->financial_year }}</div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <span class="text-muted">Checklist Progress</span>
                            <span class="fw-semibold">{{ $audit->progress_percent }}%</span>
                        </div>
                        <div class="progress" style="height: 7px;">
                            <div class="progress-bar bg-success" style="width: {{ $audit->progress_percent }}%;"></div>
                        </div>
                    </div>
                </div>

                <div class="pt-2 border-top d-flex justify-content-between align-items-center small">
                    <span class="text-muted">Lead: {{ $audit->leadAuditor?->name ?? 'Unassigned' }}</span>
                    <a href="{{ route('audits.show', $audit) }}" class="btn btn-sm btn-outline-primary">Open Workspace</a>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12"><div class="ca-card p-5 text-center text-muted">No audit assignments found.</div></div>
    @endforelse
</div>

@if($audits->hasPages())
    <div class="mt-3">{{ $audits->links() }}</div>
@endif

<div class="modal fade" id="addAuditModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('audits.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header"><h5 class="modal-title h6 fw-bold">Create Audit Assignment</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body row g-3">
                <div class="col-md-6"><label class="form-label small fw-semibold">Client *</label><select name="client_id" class="form-select form-select-sm" required><option value="">-- Select Client --</option>@foreach($clients as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
                <div class="col-md-6"><label class="form-label small fw-semibold">Audit Title *</label><input type="text" name="title" class="form-control form-control-sm" required placeholder="Statutory Audit FY 2025-26"></div>
                <div class="col-md-4"><label class="form-label small fw-semibold">Audit Type</label><select name="audit_type" class="form-select form-select-sm">@foreach(\App\Models\Audit::AUDIT_TYPES as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label small fw-semibold">Financial Year *</label><input type="text" name="financial_year" value="2025-26" class="form-control form-control-sm" required></div>
                <div class="col-md-4"><label class="form-label small fw-semibold">Status</label><select name="status" class="form-select form-select-sm">@foreach(\App\Models\Audit::STATUSES as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label small fw-semibold">Lead Auditor</label><select name="lead_auditor_id" class="form-select form-select-sm"><option value="">Unassigned</option>@foreach($staff as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label small fw-semibold">Start Date</label><input type="date" name="start_date" class="form-control form-control-sm"></div>
                <div class="col-md-4"><label class="form-label small fw-semibold">Due Date</label><input type="date" name="due_date" class="form-control form-control-sm"></div>
                <div class="col-md-6"><label class="form-label small fw-semibold">Audit Team Members</label><select name="team_member_ids[]" class="form-select form-select-sm" multiple size="3">@foreach($staff as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>@endforeach</select></div>
                <div class="col-md-6"><label class="form-label small fw-semibold">Audit Checklist Items (one per line)</label><textarea name="checklist_items" rows="3" class="form-control form-control-sm" placeholder="Engagement Letter&#10;Trial Balance Review&#10;Bank Reconciliation"></textarea></div>
                <div class="col-12"><label class="form-label small fw-semibold">Initial Observations</label><textarea name="observations" rows="2" class="form-control form-control-sm"></textarea></div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-sm btn-primary">Create Audit</button></div>
        </form>
    </div>
</div>
@endsection

