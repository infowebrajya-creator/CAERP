@extends('layouts.portal')

@section('title', 'Client Portal Overview')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h4 fw-bold mb-1" style="color: #1e3a5f;">Welcome, {{ auth()->user()->name }}</h1>
        <p class="text-muted small mb-0">You have access to {{ $companies->count() }} {{ \Illuminate\Support\Str::plural('company', $companies->count()) }} and your shared document vault.</p>
    </div>
    <a href="{{ route('portal.documents') }}" class="btn btn-sm btn-primary"><i class="bi bi-cloud-upload me-1"></i>Upload Documents</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-4"><x-kpi-card title="My Companies" :value="$companies->count()" icon="bi-buildings" tone="primary" :href="route('portal.profile')" /></div>
    <div class="col-6 col-md-4"><x-kpi-card title="Pending Document Requests" :value="$pendingDocs->count()" icon="bi-folder-exclamation" tone="warning" :href="route('portal.documents')" /></div>
    <div class="col-12 col-md-4"><x-kpi-card title="Shared Documents" :value="$recentDocs->count()" icon="bi-file-earmark-check" tone="info" :href="route('portal.documents')" /></div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="ca-card p-3 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h6 fw-bold mb-0">My Companies</h2>
                <a href="{{ route('portal.profile') }}" class="small text-decoration-none">View all →</a>
            </div>
            @forelse($companies as $company)
                <div class="border rounded p-2 mb-2 bg-light d-flex justify-content-between align-items-center gap-2">
                    <div>
                        <div class="fw-semibold small">{{ $company->name }}</div>
                        <div class="text-muted" style="font-size: 0.74rem;">
                            {{ $company->client_code }}
                            @if($company->gstin) · GSTIN: {{ $company->gstin }} @endif
                            @if($company->pan) · PAN: {{ $company->pan }} @endif
                        </div>
                    </div>
                    <a href="{{ route('portal.documents', ['client_id' => $company->id]) }}" class="btn btn-sm btn-outline-primary text-nowrap">Docs</a>
                </div>
            @empty
                <div class="text-muted small text-center py-4">No companies linked to your account.</div>
            @endforelse
        </div>
    </div>

    <div class="col-lg-6">
        <div class="ca-card p-3 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h6 fw-bold mb-0">Pending Document Submissions</h2>
                <a href="{{ route('portal.documents') }}" class="small text-decoration-none">Open Vault →</a>
            </div>
            @forelse($pendingDocs as $doc)
                <div class="border rounded p-2 mb-2 bg-light d-flex justify-content-between align-items-center gap-2">
                    <div>
                        <div class="fw-semibold small">{{ $doc->title }}</div>
                        <div class="text-muted" style="font-size: 0.74rem;">{{ $doc->client?->name }} · {{ $doc->categoryLabel() }} · Due: {{ $doc->due_date?->format('d M Y') ?? 'As soon as possible' }}</div>
                        @if($doc->rejection_reason)
                            <div class="text-danger small">Re-upload requested: {{ $doc->rejection_reason }}</div>
                        @endif
                    </div>
                    <a href="{{ route('portal.documents') }}" class="btn btn-sm btn-primary text-nowrap">Upload</a>
                </div>
            @empty
                <div class="text-muted small text-center py-4">No pending document requests.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
