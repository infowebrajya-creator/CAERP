@extends('layouts.app')

@section('title', 'Document Vault')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h4 fw-bold mb-1" style="color: #1e3a5f;">Client Document Collection &amp; Vault</h1>
        <p class="text-muted small mb-0">Request client documents, upload files with version control, and approve or reject submissions.</p>
    </div>
    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newDocumentModal">
        <i class="bi bi-cloud-upload me-1"></i>Request / Upload Document
    </button>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><x-kpi-card title="Awaiting Client (Requested)" :value="$summary['requested']" icon="bi-clock-history" tone="warning" /></div>
    <div class="col-6 col-md-3"><x-kpi-card title="Uploaded (For Review)" :value="$summary['uploaded']" icon="bi-file-earmark-arrow-up" tone="info" /></div>
    <div class="col-6 col-md-3"><x-kpi-card title="Approved" :value="$summary['approved']" icon="bi-patch-check" tone="success" /></div>
    <div class="col-6 col-md-3"><x-kpi-card title="Rejected" :value="$summary['rejected']" icon="bi-x-octagon" tone="danger" /></div>
</div>

<div class="ca-card p-3 mb-3">
    <form method="GET" action="{{ route('documents.index') }}" class="row g-2 align-items-end">
        <div class="col-md-3"><label class="form-label small text-muted mb-1">Search</label><input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Title or client..."></div>
        <div class="col-md-3">
            <label class="form-label small text-muted mb-1">Client</label>
            <select name="client_id" class="form-select form-select-sm"><option value="">All Clients</option>@foreach($clients as $c)<option value="{{ $c->id }}" @selected((string) request('client_id') === (string) $c->id)>{{ $c->name }}</option>@endforeach</select>
        </div>
        <div class="col-md-2">
            <label class="form-label small text-muted mb-1">Category</label>
            <select name="category" class="form-select form-select-sm"><option value="">All Categories</option>@foreach(\App\Models\Document::CATEGORIES as $k => $v)<option value="{{ $k }}" @selected(request('category') === $k)>{{ $v }}</option>@endforeach</select>
        </div>
        <div class="col-md-2">
            <label class="form-label small text-muted mb-1">Status</label>
            <select name="status" class="form-select form-select-sm"><option value="">All Statuses</option>@foreach(\App\Models\Document::STATUSES as $k => $v)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>@endforeach</select>
        </div>
        <div class="col-md-2 d-flex gap-1">
            <button type="submit" class="btn btn-sm btn-primary flex-grow-1">Filter</button>
            <a href="{{ route('documents.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
        </div>
    </form>
</div>

<div class="ca-card overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Document Title</th>
                    <th>Client</th>
                    <th>Category</th>
                    <th>Version &amp; File</th>
                    <th>Uploaded By</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($documents as $doc)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $doc->title }}</div>
                            @if($doc->rejection_reason)
                                <div class="small text-danger">Rejected: {{ $doc->rejection_reason }}</div>
                            @elseif($doc->request_notes)
                                <div class="small text-muted">{{ $doc->request_notes }}</div>
                            @endif
                        </td>
                        <td class="small">{{ $doc->client->name }}</td>
                        <td><span class="badge badge-soft-secondary">{{ $doc->categoryLabel() }}</span></td>
                        <td class="small">
                            @if($doc->current_version > 0)
                                <span class="fw-semibold">v{{ $doc->current_version }}</span> · {{ $doc->original_filename }}
                                <div class="text-muted">{{ $doc->formattedSize() }}</div>
                            @else
                                <span class="text-muted">No file uploaded</span>
                            @endif
                        </td>
                        <td class="small">{{ $doc->uploader?->name ?? '—' }}<div class="text-muted">{{ $doc->updated_at->format('d M Y') }}</div></td>
                        <td><x-status-badge :status="$doc->status" :label="$doc->statusLabel()" /></td>
                        <td class="text-end text-nowrap">
                            @if($doc->file_path)
                                <a href="{{ route('documents.download', $doc) }}" class="btn btn-sm btn-light border" title="Download"><i class="bi bi-download"></i></a>
                            @endif
                            <button class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#uploadVersionModal{{ $doc->id }}" title="Upload New Version"><i class="bi bi-upload"></i></button>
                            @if($doc->status === 'uploaded')
                                <form action="{{ route('documents.approve', $doc) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-success" title="Approve"><i class="bi bi-check-lg"></i></button>
                                </form>
                                <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectDocModal{{ $doc->id }}" title="Reject"><i class="bi bi-x-lg"></i></button>
                            @endif
                        </td>
                    </tr>

                    <!-- Upload Version Modal -->
                    <div class="modal fade" id="uploadVersionModal{{ $doc->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <form action="{{ route('documents.upload', $doc) }}" method="POST" enctype="multipart/form-data" class="modal-content">
                                @csrf
                                <div class="modal-header"><h5 class="modal-title h6 fw-bold">Upload Version (v{{ $doc->current_version + 1 }}) — {{ $doc->title }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                <div class="modal-body">
                                    <div class="mb-3"><label class="form-label small fw-semibold">Select File *</label><input type="file" name="file" class="form-control form-control-sm" required></div>
                                    <div class="mb-3"><label class="form-label small fw-semibold">Version Notes</label><input type="text" name="notes" class="form-control form-control-sm" placeholder="Updated copy with sign &amp; stamp"></div>
                                    @if($doc->versions->count() > 0)
                                        <div class="small fw-semibold mb-1">Version History</div>
                                        @foreach($doc->versions as $ver)
                                            <div class="d-flex justify-content-between align-items-center small border-bottom py-1">
                                                <span>v{{ $ver->version_number }} · {{ $ver->original_filename }}</span>
                                                <a href="{{ route('documents.download', ['document' => $doc->id, 'version' => $ver->id]) }}" class="text-decoration-none">Download</a>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                                <div class="modal-footer"><button type="submit" class="btn btn-sm btn-primary">Upload</button></div>
                            </form>
                        </div>
                    </div>

                    <!-- Reject Modal -->
                    <div class="modal fade" id="rejectDocModal{{ $doc->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <form action="{{ route('documents.reject', $doc) }}" method="POST" class="modal-content">
                                @csrf
                                <div class="modal-header"><h5 class="modal-title h6 fw-bold">Reject Document</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                <div class="modal-body">
                                    <label class="form-label small fw-semibold">Rejection Reason *</label>
                                    <textarea name="rejection_reason" rows="3" class="form-control form-control-sm" required placeholder="Scan is blurry / missing Q4 pages"></textarea>
                                </div>
                                <div class="modal-footer"><button type="submit" class="btn btn-sm btn-danger">Mark Rejected</button></div>
                            </form>
                        </div>
                    </div>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No documents found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($documents->hasPages())
        <div class="p-3 border-top">{{ $documents->links() }}</div>
    @endif
</div>

<!-- New Request / Upload Modal -->
<div class="modal fade" id="newDocumentModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('documents.store') }}" method="POST" enctype="multipart/form-data" class="modal-content">
            @csrf
            <div class="modal-header"><h5 class="modal-title h6 fw-bold">Request or Upload Client Document</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body row g-2">
                <div class="col-12">
                    <label class="form-label small fw-semibold">Client *</label>
                    <select name="client_id" class="form-select form-select-sm" required>
                        <option value="">-- Select Client --</option>
                        @foreach($clients as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-12"><label class="form-label small fw-semibold">Document Title *</label><input type="text" name="title" class="form-control form-control-sm" required placeholder="e.g. FY 2025-26 HDFC Bank Statement"></div>
                <div class="col-6"><label class="form-label small fw-semibold">Category *</label><select name="category" class="form-select form-select-sm">@foreach(\App\Models\Document::CATEGORIES as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
                <div class="col-6"><label class="form-label small fw-semibold">Due Date</label><input type="date" name="due_date" class="form-control form-control-sm"></div>
                <div class="col-12"><label class="form-label small fw-semibold">File (leave empty to send as a Client Document Request)</label><input type="file" name="file" class="form-control form-control-sm"></div>
                <div class="col-12"><label class="form-label small fw-semibold">Request Notes</label><textarea name="request_notes" rows="2" class="form-control form-control-sm"></textarea></div>
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_shared_with_client" value="1" id="sharePortal" checked>
                        <label class="form-check-label small" for="sharePortal">Visible in Client Portal</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-sm btn-primary">Submit</button></div>
        </form>
    </div>
</div>
@endsection

