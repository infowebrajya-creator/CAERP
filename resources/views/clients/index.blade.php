@extends('layouts.app')

@section('title', 'Clients CRM')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h4 fw-bold mb-1" style="color: #1e3a5f;">Client Management CRM</h1>
        <p class="text-muted small mb-0">Central directory of individual and business clients, tax registrations, and relationship managers.</p>
    </div>
    <a href="{{ route('clients.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-building-add me-1"></i>Add Client
    </a>
</div>

<div class="ca-card p-3 mb-3">
    <form method="GET" action="{{ route('clients.index') }}" class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label small text-muted mb-1">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Name, Code, PAN, GSTIN, Email...">
        </div>
        <div class="col-md-2">
            <label class="form-label small text-muted mb-1">Category</label>
            <select name="category" class="form-select form-select-sm">
                <option value="">All Categories</option>
                @foreach(\App\Models\Client::CATEGORIES as $k => $v)
                    <option value="{{ $k }}" @selected(request('category') === $k)>{{ $v }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small text-muted mb-1">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">All Statuses</option>
                @foreach(\App\Models\Client::STATUSES as $k => $v)
                    <option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small text-muted mb-1">Assigned CA</label>
            <select name="assigned_ca_id" class="form-select form-select-sm">
                <option value="">All Staff</option>
                @foreach($staff as $member)
                    <option value="{{ $member->id }}" @selected((string) request('assigned_ca_id') === (string) $member->id)>{{ $member->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 d-flex gap-1">
            <button type="submit" class="btn btn-sm btn-primary flex-grow-1">Filter</button>
            <a href="{{ route('clients.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
        </div>
    </form>
</div>

<div class="ca-card overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Category</th>
                    <th>PAN / GSTIN</th>
                    <th>Assigned CA</th>
                    <th>Applicability</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($clients as $client)
                    <tr>
                        <td>
                            <a href="{{ route('clients.show', $client) }}" class="fw-semibold text-decoration-none" style="color: #1e3a5f;">{{ $client->name }}</a>
                            <div class="small text-muted">{{ $client->client_code }} · {{ $client->email ?? $client->mobile ?? 'No contact' }}</div>
                        </td>
                        <td><span class="badge badge-soft-secondary">{{ ucfirst($client->category) }}</span></td>
                        <td class="small">
                            <div>PAN: <span class="fw-medium">{{ $client->pan ?? '—' }}</span></div>
                            <div class="text-muted">GSTIN: {{ $client->gstin ?? '—' }}</div>
                        </td>
                        <td class="small">{{ $client->assignedCa?->name ?? 'Unassigned' }}</td>
                        <td>
                            @if($client->is_gst_registered)<span class="badge badge-soft-info">GST</span>@endif
                            @if($client->is_itr_applicable)<span class="badge badge-soft-success">ITR</span>@endif
                            @if($client->is_tds_applicable)<span class="badge badge-soft-primary">TDS</span>@endif
                        </td>
                        <td><x-status-badge :status="$client->status" /></td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('clients.show', $client) }}" class="btn btn-sm btn-light border"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('clients.edit', $client) }}" class="btn btn-sm btn-light border"><i class="bi bi-pencil"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No clients found matching filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($clients->hasPages())
        <div class="p-3 border-top">{{ $clients->links() }}</div>
    @endif
</div>
@endsection

