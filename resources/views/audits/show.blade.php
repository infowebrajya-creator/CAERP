@extends('layouts.app')

@section('title', $audit->title)

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2">
            <h1 class="h4 fw-bold mb-0" style="color: #1e3a5f;">{{ $audit->title }}</h1>
            <x-status-badge :status="$audit->status" :label="$audit->statusLabel()" />
        </div>
        <div class="small text-muted mt-1">
            Client: <a href="{{ route('clients.show', $audit->client) }}">{{ $audit->client->name }}</a> · {{ $audit->typeLabel() }} · FY {{ $audit->financial_year }} · Progress: <strong>{{ $audit->progress_percent }}%</strong>
        </div>
    </div>
    <a href="{{ route('audits.index') }}" class="btn btn-sm btn-outline-secondary">Back to Audits</a>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <form action="{{ route('audits.update', $audit) }}" method="POST" class="ca-card p-3">
            @csrf @method('PUT')
            <h2 class="h6 fw-bold mb-3">Audit Workflow, Checklist &amp; Review</h2>
            <div class="row g-2 mb-3">
                <div class="col-md-6"><label class="form-label small fw-semibold">Title</label><input type="text" name="title" value="{{ $audit->title }}" class="form-control form-control-sm" required></div>
                <div class="col-md-3"><label class="form-label small fw-semibold">Type</label><select name="audit_type" class="form-select form-select-sm">@foreach(\App\Models\Audit::AUDIT_TYPES as $k => $v)<option value="{{ $k }}" @selected($audit->audit_type === $k)>{{ $v }}</option>@endforeach</select></div>
                <div class="col-md-3"><label class="form-label small fw-semibold">FY</label><input type="text" name="financial_year" value="{{ $audit->financial_year }}" class="form-control form-control-sm" required></div>
                <div class="col-md-4"><label class="form-label small fw-semibold">Status</label><select name="status" class="form-select form-select-sm">@foreach(\App\Models\Audit::STATUSES as $k => $v)<option value="{{ $k }}" @selected($audit->status === $k)>{{ $v }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label small fw-semibold">Lead Auditor</label><select name="lead_auditor_id" class="form-select form-select-sm"><option value="">Unassigned</option>@foreach($staff as $m)<option value="{{ $m->id }}" @selected($audit->lead_auditor_id == $m->id)>{{ $m->name }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label small fw-semibold">Due Date</label><input type="date" name="due_date" value="{{ $audit->due_date?->format('Y-m-d') }}" class="form-control form-control-sm"></div>
                <div class="col-12">
                    <label class="form-label small fw-semibold">Audit Team Members</label>
                    @php($selectedTeam = $audit->teamMembers->pluck('id')->toArray())
                    <select name="team_member_ids[]" class="form-select form-select-sm" multiple size="3">
                        @foreach($staff as $m)<option value="{{ $m->id }}" @selected(in_array($m->id, $selectedTeam))>{{ $m->name }}</option>@endforeach
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold">Audit Program Checklist</label>
                <div class="border rounded p-2 bg-light">
                    @foreach($audit->checklist ?? [] as $idx => $entry)
                        <div class="row g-2 align-items-center mb-2 pb-2 border-bottom">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="checklist_checked[]" value="{{ $idx }}" id="auditChk{{ $idx }}" @checked(!empty($entry['completed']))>
                                    <label class="form-check-label small fw-medium" for="auditChk{{ $idx }}">{{ $entry['item'] }}</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <input type="text" name="checklist_remarks[{{ $idx }}]" value="{{ $entry['remark'] ?? '' }}" class="form-control form-control-sm" placeholder="Audit remark...">
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold">Audit Observations &amp; Notes</label>
                <textarea name="observations" rows="3" class="form-control form-control-sm">{{ $audit->observations }}</textarea>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold">Partner Review / Approval Notes</label>
                <textarea name="review_notes" rows="2" class="form-control form-control-sm">{{ $audit->review_notes }}</textarea>
                @if($audit->approved_at)
                    <div class="small text-success mt-1"><i class="bi bi-patch-check-fill me-1"></i>Approved by {{ $audit->approver?->name }} on {{ $audit->approved_at->format('d M Y, h:i A') }}</div>
                @endif
            </div>

            <div class="text-end"><button type="submit" class="btn btn-sm btn-primary px-4">Save Audit Progress</button></div>
        </form>
    </div>

    <div class="col-lg-5">
        <div class="ca-card p-3 mb-3">
            <h2 class="h6 fw-bold mb-3">Working Paper Uploads</h2>
            <form action="{{ route('audits.papers.store', $audit) }}" method="POST" enctype="multipart/form-data" class="mb-3 border rounded p-3 bg-light">
                @csrf
                <div class="mb-2"><label class="form-label small fw-semibold">Working Paper Title *</label><input type="text" name="title" class="form-control form-control-sm" required placeholder="e.g. Fixed Asset Register Verification"></div>
                <div class="mb-2"><label class="form-label small fw-semibold">File *</label><input type="file" name="file" class="form-control form-control-sm" required></div>
                <div class="mb-2"><label class="form-label small fw-semibold">Notes</label><input type="text" name="notes" class="form-control form-control-sm"></div>
                <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-upload me-1"></i>Upload Working Paper</button>
            </form>

            <div class="list-group list-group-flush">
                @forelse($audit->workingPapers as $paper)
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-semibold small">{{ $paper->title }}</div>
                            <div class="text-muted" style="font-size: 0.74rem;">{{ $paper->original_filename }} · {{ $paper->uploader?->name }}</div>
                        </div>
                        <a href="{{ route('audits.papers.download', [$audit, $paper]) }}" class="btn btn-sm btn-light border"><i class="bi bi-download"></i></a>
                    </div>
                @empty
                    <div class="text-muted small text-center py-3">No working papers uploaded yet.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

