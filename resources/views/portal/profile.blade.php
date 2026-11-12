@extends('layouts.portal')

@section('title', 'My Companies')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 fw-bold mb-0" style="color: #1e3a5f;">My Companies &amp; Entities</h1>
    <a href="{{ route('portal.documents') }}" class="btn btn-sm btn-primary"><i class="bi bi-folder2-open me-1"></i>My Documents</a>
</div>

<div class="row g-3">
    @forelse($companies as $company)
        <div class="col-12">
            <div class="ca-card p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 border-bottom pb-2">
                    <div>
                        <h2 class="h5 fw-bold mb-0" style="color: #1e3a5f;">{{ $company->name }}</h2>
                        <span class="text-muted small">{{ $company->client_code }} · {{ ucfirst($company->category) }}</span>
                    </div>
                    <a href="{{ route('portal.documents', ['client_id' => $company->id]) }}" class="btn btn-sm btn-outline-primary">View Company Documents</a>
                </div>
                <dl class="row mb-0">
                    <dt class="col-sm-3 text-muted">Email / Mobile</dt><dd class="col-sm-9">{{ $company->email }} · {{ $company->mobile ?? '—' }}</dd>
                    <dt class="col-sm-3 text-muted">PAN</dt><dd class="col-sm-9">{{ $company->pan ?? '—' }}</dd>
                    <dt class="col-sm-3 text-muted">GSTIN</dt><dd class="col-sm-9">{{ $company->gstin ?? '—' }}</dd>
                    <dt class="col-sm-3 text-muted">TAN / CIN</dt><dd class="col-sm-9">{{ $company->tan ?? '—' }} / {{ $company->cin ?? '—' }}</dd>
                    <dt class="col-sm-3 text-muted">Address</dt><dd class="col-sm-9">{{ trim(implode(', ', array_filter([$company->address, $company->city, $company->state, $company->pincode]))) ?: '—' }}</dd>
                    <dt class="col-sm-3 text-muted">Assigned CA</dt><dd class="col-sm-9">{{ $company->assignedCa?->name ?? 'Practice Desk' }}</dd>
                </dl>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="ca-card p-4 text-center text-muted">No companies are linked to your account.</div>
        </div>
    @endforelse
</div>
@endsection
