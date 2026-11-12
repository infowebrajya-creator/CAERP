@extends('layouts.portal')

@section('title', 'My Documents')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h4 fw-bold mb-0" style="color: #1e3a5f;">My Documents &amp; Requests</h1>
    <div class="d-flex align-items-center gap-2">
        @if($companies->count() > 1)
            <form method="GET" action="{{ route('portal.documents') }}" class="d-flex align-items-center gap-2">
                <select name="client_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All My Companies</option>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}" @selected((string) request('client_id') === (string) $company->id)>{{ $company->name }}</option>
                    @endforeach
                </select>
            </form>
        @endif
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#portalUploadModal"><i class="bi bi-cloud-upload me-1"></i>Upload New Document</button>
    </div>
</div>

<div class="ca-card overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Document</th><th>Company</th><th>Category</th><th>Version</th><th>Status</th><th>Upload / Download</th></tr></thead>
            <tbody>
                @forelse($documents as $doc)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $doc->title }}</div>
                            @if($doc->request_notes)<div class="small text-muted">{{ $doc->request_notes }}</div>@endif
                            @if($doc->rejection_reason)<div class="small text-danger">Rejected: {{ $doc->rejection_reason }}</div>@endif
                        </td>
                        <td class="small">{{ $doc->client?->name ?? '—' }}</td>
                        <td><span class="badge badge-soft-secondary">{{ $doc->categoryLabel() }}</span></td>
                        <td class="small">{{ $doc->current_version > 0 ? 'v'.$doc->current_version : '—' }}</td>
                        <td><x-status-badge :status="$doc->status" :label="$doc->statusLabel()" /></td>
                        <td>
                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                @if($doc->file_path)
                                    <a href="{{ route('portal.documents.download', $doc) }}" class="btn btn-sm btn-light border"><i class="bi bi-download me-1"></i>Download</a>
                                @endif
                                <form action="{{ route('portal.documents.upload') }}" method="POST" enctype="multipart/form-data" class="d-flex gap-1 align-items-center">
                                    @csrf
                                    <input type="hidden" name="document_id" value="{{ $doc->id }}">
                                    <input type="file" name="file" class="form-control form-control-sm" style="max-width: 210px;" required>
                                    <button type="submit" class="btn btn-sm btn-primary text-nowrap">Upload</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No shared documents or requests yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($documents->hasPages())<div class="p-3 border-top">{{ $documents->links() }}</div>@endif
</div>

<div class="modal fade" id="portalUploadModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('portal.documents.upload') }}" method="POST" enctype="multipart/form-data" class="modal-content">
            @csrf
            <div class="modal-header"><h5 class="modal-title h6 fw-bold">Upload Document to CA Firm</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body row g-2">
                @if($companies->count() > 1)
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Company *</label>
                        <select name="client_id" class="form-select form-select-sm" required>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}">{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <input type="hidden" name="client_id" value="{{ $companies->first()?->id }}">
                @endif
                <div class="col-12"><label class="form-label small fw-semibold">Document Title *</label><input type="text" name="title" class="form-control form-control-sm" required></div>
                <div class="col-12"><label class="form-label small fw-semibold">Category *</label><select name="category" class="form-select form-select-sm">@foreach(\App\Models\Document::CATEGORIES as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
                <div class="col-12"><label class="form-label small fw-semibold">File *</label><input type="file" name="file" class="form-control form-control-sm" required></div>
                <div class="col-12"><label class="form-label small fw-semibold">Notes</label><input type="text" name="notes" class="form-control form-control-sm"></div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-sm btn-primary">Upload</button></div>
        </form>
    </div>
</div>
@endsection
