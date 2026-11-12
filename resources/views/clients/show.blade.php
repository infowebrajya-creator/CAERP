@extends('layouts.app')

@section('title', $client->name)

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div>
        <div class="d-flex align-items-center gap-2">
            <h1 class="h4 fw-bold mb-0" style="color: #1e3a5f;">{{ $client->name }}</h1>
            <x-status-badge :status="$client->status" />
            <span class="badge badge-soft-secondary">{{ ucfirst($client->category) }}</span>
        </div>
        <div class="small text-muted mt-1">
            {{ $client->client_code }} · PAN: <strong>{{ $client->pan ?? '—' }}</strong> · GSTIN: <strong>{{ $client->gstin ?? '—' }}</strong> · Outstanding: <strong class="text-danger">₹{{ number_format($client->outstandingBalance(), 2) }}</strong>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('billing.create', ['client_id' => $client->id]) }}" class="btn btn-sm btn-outline-success"><i class="bi bi-receipt me-1"></i>New Invoice</a>
        <a href="{{ route('clients.edit', $client) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil me-1"></i>Edit</a>
        @if(auth()->user()->isManagerOrAbove())
            <form action="{{ route('clients.destroy', $client) }}" method="POST" onsubmit="return confirm('Archive this client?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
            </form>
        @endif
    </div>
</div>

<!-- 6 Tabs -->
<ul class="nav nav-tabs mb-3">
    @foreach([
        'overview' => ['icon' => 'bi-person-vcard', 'label' => 'Overview'],
        'compliance' => ['icon' => 'bi-shield-check', 'label' => 'Compliance'],
        'documents' => ['icon' => 'bi-folder2-open', 'label' => 'Documents (' . $client->documents->count() . ')'],
        'tasks' => ['icon' => 'bi-check2-square', 'label' => 'Tasks (' . $client->tasks->count() . ')'],
        'invoices' => ['icon' => 'bi-cash-coin', 'label' => 'Invoices (' . $client->invoices->count() . ')'],
        'communication' => ['icon' => 'bi-chat-dots', 'label' => 'Communication (' . $client->communications->count() . ')'],
    ] as $key => $info)
        <li class="nav-item">
            <a class="nav-link {{ $tab === $key ? 'active fw-semibold' : '' }}"
               href="{{ route('clients.show', ['client' => $client, 'tab' => $key]) }}">
                <i class="bi {{ $info['icon'] }} me-1"></i>{{ $info['label'] }}
            </a>
        </li>
    @endforeach
</ul>

@if($tab === 'overview')
    <div class="row g-3">
        <div class="col-lg-6">
            <div class="ca-card p-3 h-100">
                <h2 class="h6 fw-bold mb-3">Entity &amp; Statutory Details</h2>
                <dl class="row mb-0 small">
                    <dt class="col-sm-4 text-muted">Email</dt><dd class="col-sm-8">{{ $client->email ?? '—' }}</dd>
                    <dt class="col-sm-4 text-muted">Mobile</dt><dd class="col-sm-8">{{ $client->mobile ?? '—' }}</dd>
                    <dt class="col-sm-4 text-muted">Address</dt><dd class="col-sm-8">{{ trim(implode(', ', array_filter([$client->address, $client->city, $client->state, $client->pincode]))) ?: '—' }}</dd>
                    <dt class="col-sm-4 text-muted">PAN</dt><dd class="col-sm-8">{{ $client->pan ?? '—' }}</dd>
                    <dt class="col-sm-4 text-muted">GSTIN</dt><dd class="col-sm-8">{{ $client->gstin ?? '—' }}</dd>
                    <dt class="col-sm-4 text-muted">TAN</dt><dd class="col-sm-8">{{ $client->tan ?? '—' }}</dd>
                    <dt class="col-sm-4 text-muted">CIN</dt><dd class="col-sm-8">{{ $client->cin ?? '—' }}</dd>
                    <dt class="col-sm-4 text-muted">Business / Industry</dt><dd class="col-sm-8">{{ $client->business_type ?? '—' }} @if($client->industry) ({{ $client->industry }}) @endif</dd>
                    <dt class="col-sm-4 text-muted">Registration Date</dt><dd class="col-sm-8">{{ $client->registration_date?->format('d M Y') ?? '—' }}</dd>
                </dl>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="ca-card p-3 h-100">
                <h2 class="h6 fw-bold mb-3">Practice Assignment &amp; Notes</h2>
                <p class="small mb-2"><strong>Assigned CA / RM:</strong> {{ $client->assignedCa?->name ?? 'Unassigned' }}</p>
                <p class="small mb-2"><strong>Portal Login:</strong>
                    @if($client->user)
                        <span class="badge badge-soft-success">Active ({{ $client->user->email }})</span>
                    @else
                        <span class="badge badge-soft-secondary">Not Enabled</span>
                    @endif
                </p>
                <p class="small mb-2"><strong>Team Staff:</strong>
                    {{ $client->staff->pluck('name')->implode(', ') ?: 'None' }}
                </p>
                <div class="mt-3 p-3 rounded bg-light border small">
                    <div class="fw-semibold mb-1">Client Notes</div>
                    {{ $client->notes ?: 'No internal notes recorded.' }}
                </div>
            </div>
        </div>
    </div>
@elseif($tab === 'compliance')
    <div class="row g-3">
        <div class="col-lg-6">
            <div class="ca-card p-3">
                <h2 class="h6 fw-bold mb-3">GST Returns ({{ $client->gstReturns->count() }})</h2>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Return</th><th>Period</th><th>Due Date</th><th>Status</th><th>ARN</th></tr></thead>
                        <tbody>
                            @forelse($client->gstReturns as $gst)
                                <tr>
                                    <td class="fw-semibold">{{ $gst->return_type_code }}</td>
                                    <td>{{ $gst->tax_period }}</td>
                                    <td>{{ $gst->due_date->format('d M Y') }}</td>
                                    <td><x-status-badge :status="$gst->status" :label="$gst->statusLabel()" /></td>
                                    <td class="small">{{ $gst->arn ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-muted text-center py-3">No GST returns recorded.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="ca-card p-3">
                <h2 class="h6 fw-bold mb-3">ITR Filings ({{ $client->itrFilings->count() }})</h2>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>AY</th><th>Type</th><th>Status</th><th>Ack No.</th><th>Refund</th></tr></thead>
                        <tbody>
                            @forelse($client->itrFilings as $itr)
                                <tr>
                                    <td class="fw-semibold">{{ $itr->assessment_year }}</td>
                                    <td>{{ $itr->return_type }}</td>
                                    <td><x-status-badge :status="$itr->filing_status" :label="$itr->filingStatusLabel()" /></td>
                                    <td class="small">{{ $itr->acknowledgement_number ?? '—' }}</td>
                                    <td class="small">{{ $itr->refundStatusLabel() }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-muted text-center py-3">No ITR filings recorded.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="ca-card p-3">
                <h2 class="h6 fw-bold mb-3">TDS Returns ({{ $client->tdsReturns->count() }})</h2>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>FY / Qtr</th><th>Form</th><th>Tax Deposited</th><th>Status</th></tr></thead>
                        <tbody>
                            @forelse($client->tdsReturns as $tds)
                                <tr>
                                    <td>{{ $tds->financial_year }} · {{ $tds->quarter }}</td>
                                    <td>{{ $tds->form_type }}</td>
                                    <td>₹{{ number_format((float) $tds->tax_deposited, 2) }}</td>
                                    <td><x-status-badge :status="$tds->status" :label="$tds->statusLabel()" /></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-muted text-center py-3">No TDS returns recorded.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="ca-card p-3">
                <h2 class="h6 fw-bold mb-3">Audits ({{ $client->audits->count() }})</h2>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Title</th><th>FY</th><th>Progress</th><th>Status</th></tr></thead>
                        <tbody>
                            @forelse($client->audits as $audit)
                                <tr>
                                    <td><a href="{{ route('audits.show', $audit) }}">{{ $audit->title }}</a></td>
                                    <td>{{ $audit->financial_year }}</td>
                                    <td>{{ $audit->progress_percent }}%</td>
                                    <td><x-status-badge :status="$audit->status" :label="$audit->statusLabel()" /></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-muted text-center py-3">No audits recorded.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@elseif($tab === 'documents')
    <div class="ca-card p-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h6 fw-bold mb-0">Client Documents</h2>
            <a href="{{ route('documents.index', ['client_id' => $client->id]) }}" class="btn btn-sm btn-primary">Manage / Upload in Vault</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Document</th><th>Category</th><th>Version</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                    @forelse($client->documents as $doc)
                        <tr>
                            <td class="fw-medium">{{ $doc->title }}</td>
                            <td>{{ $doc->categoryLabel() }}</td>
                            <td>v{{ $doc->current_version }}</td>
                            <td><x-status-badge :status="$doc->status" :label="$doc->statusLabel()" /></td>
                            <td>
                                @if($doc->file_path)
                                    <a href="{{ route('documents.download', $doc) }}" class="btn btn-sm btn-light border"><i class="bi bi-download"></i> Download</a>
                                @else
                                    <span class="small text-muted">Awaiting upload</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted text-center py-3">No documents on file.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@elseif($tab === 'tasks')
    <div class="ca-card p-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h6 fw-bold mb-0">Client Tasks</h2>
            <a href="{{ route('tasks.index', ['client_id' => $client->id]) }}" class="btn btn-sm btn-primary">Open Task Board</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Title</th><th>Category</th><th>Priority</th><th>Assignee</th><th>Due Date</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse($client->tasks as $task)
                        <tr>
                            <td><a href="{{ route('tasks.show', $task) }}" class="fw-medium text-decoration-none">{{ $task->title }}</a></td>
                            <td>{{ \App\Models\Task::CATEGORIES[$task->category] ?? $task->category }}</td>
                            <td><x-status-badge :status="$task->priority" :label="$task->priorityLabel()" /></td>
                            <td>{{ $task->assignee?->name ?? 'Unassigned' }}</td>
                            <td>{{ $task->due_date?->format('d M Y') ?? '—' }}</td>
                            <td><x-status-badge :status="$task->status" :label="$task->statusLabel()" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-muted text-center py-3">No tasks for this client.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@elseif($tab === 'invoices')
    <div class="ca-card p-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h6 fw-bold mb-0">Invoices &amp; Payments</h2>
            <a href="{{ route('billing.create', ['client_id' => $client->id]) }}" class="btn btn-sm btn-primary">Create Invoice</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Invoice #</th><th>Date</th><th>Total</th><th>Paid</th><th>Balance</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse($client->invoices as $inv)
                        <tr>
                            <td class="fw-semibold"><a href="{{ route('billing.show', $inv) }}">{{ $inv->invoice_number }}</a></td>
                            <td>{{ $inv->invoice_date->format('d M Y') }}</td>
                            <td>₹{{ number_format((float) $inv->total_amount, 2) }}</td>
                            <td>₹{{ number_format((float) $inv->paid_amount, 2) }}</td>
                            <td class="fw-semibold">₹{{ number_format((float) $inv->balance_amount, 2) }}</td>
                            <td><x-status-badge :status="$inv->status" :label="$inv->statusLabel()" /></td>
                            <td class="text-end"><a href="{{ route('billing.pdf', $inv) }}" class="btn btn-sm btn-light border"><i class="bi bi-file-pdf"></i> PDF</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-muted text-center py-3">No invoices generated yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@elseif($tab === 'communication')
    <div class="row g-3">
        <div class="col-lg-5">
            <div class="ca-card p-3">
                <h2 class="h6 fw-bold mb-3">Send Message / Log Communication</h2>
                <form action="{{ route('communications.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="client_id" value="{{ $client->id }}">
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Channel</label>
                        <select name="channel" class="form-select form-select-sm">
                            @foreach(\App\Models\Communication::CHANNELS as $k => $v)
                                <option value="{{ $k }}">{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Subject</label>
                        <input type="text" name="subject" class="form-control form-control-sm" placeholder="Reminder: GST / ITR documents">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Message *</label>
                        <textarea name="message" rows="4" class="form-control form-control-sm" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary w-100">Send &amp; Log</button>
                </form>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="ca-card p-3">
                <h2 class="h6 fw-bold mb-3">Communication History</h2>
                @forelse($client->communications as $comm)
                    <div class="border rounded p-3 mb-2 bg-light">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-semibold small">{{ $comm->subject ?: $comm->channelLabel() }}</span>
                            <div class="d-flex gap-1 align-items-center">
                                <span class="badge badge-soft-info">{{ $comm->channelLabel() }}</span>
                                <x-status-badge :status="$comm->status" />
                            </div>
                        </div>
                        <p class="small mb-1">{{ $comm->message }}</p>
                        <div class="text-muted" style="font-size: 0.72rem;">
                            {{ $comm->sender_type === 'client' ? $client->name : ($comm->user?->name ?? 'Staff') }} · {{ $comm->created_at->format('d M Y, h:i A') }}
                        </div>
                    </div>
                @empty
                    <div class="text-muted small text-center py-4">No communications logged yet.</div>
                @endforelse
            </div>
        </div>
    </div>
@endif
@endsection

