@extends('layouts.app')

@section('title', 'Edit Client')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 fw-bold mb-1" style="color: #1e3a5f;">Edit Client: {{ $client->name }}</h1>
    </div>
    <a href="{{ route('clients.show', $client) }}" class="btn btn-sm btn-outline-secondary">Back to Profile</a>
</div>

<form action="{{ route('clients.update', $client) }}" method="POST" class="ca-card p-4">
    @csrf
    @method('PUT')
    @include('clients._form', ['client' => $client, 'nextCode' => $client->client_code, 'staff' => $staff])
    <div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2">
        <a href="{{ route('clients.show', $client) }}" class="btn btn-light border">Cancel</a>
        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check2 me-1"></i>Update Client</button>
    </div>
</form>
@endsection

