@extends('layouts.app')

@section('title', 'Add Client')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 fw-bold mb-1" style="color: #1e3a5f;">Onboard New Client</h1>
        <p class="text-muted small mb-0">Register an individual or business entity and optionally enable Client Portal login.</p>
    </div>
    <a href="{{ route('clients.index') }}" class="btn btn-sm btn-outline-secondary">Back to Clients</a>
</div>

<form action="{{ route('clients.store') }}" method="POST" class="ca-card p-4">
    @csrf
    @include('clients._form', ['client' => null, 'nextCode' => $nextCode, 'staff' => $staff])
    <div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2">
        <a href="{{ route('clients.index') }}" class="btn btn-light border">Cancel</a>
        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check2 me-1"></i>Save Client</button>
    </div>
</form>
@endsection

