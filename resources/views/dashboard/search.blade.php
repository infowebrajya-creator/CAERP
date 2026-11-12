@extends('layouts.app')

@section('title', 'Global Search')

@section('content')
<div class="mb-4">
    <h1 class="h4 fw-bold" style="color: #1e3a5f;">Search Results @if($q) for "{{ $q }}" @endif</h1>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="ca-card p-3">
            <h2 class="h6 fw-bold mb-3">Clients ({{ $clients->count() }})</h2>
            @forelse($clients as $client)
                <a href="{{ route('clients.show', $client) }}" class="d-block p-2 rounded border mb-2 text-decoration-none text-dark">
                    <div class="fw-semibold">{{ $client->name }}</div>
                    <div class="small text-muted">{{ $client->client_code }} · PAN: {{ $client->pan ?? '—' }}</div>
                </a>
            @empty
                <div class="text-muted small">No matching clients.</div>
            @endforelse
        </div>
    </div>
    <div class="col-lg-4">
        <div class="ca-card p-3">
            <h2 class="h6 fw-bold mb-3">Tasks ({{ $tasks->count() }})</h2>
            @forelse($tasks as $task)
                <a href="{{ route('tasks.show', $task) }}" class="d-block p-2 rounded border mb-2 text-decoration-none text-dark">
                    <div class="fw-semibold">{{ $task->title }}</div>
                    <div class="small text-muted">{{ $task->client?->name ?? 'Internal' }} · {{ $task->statusLabel() }}</div>
                </a>
            @empty
                <div class="text-muted small">No matching tasks.</div>
            @endforelse
        </div>
    </div>
    <div class="col-lg-4">
        <div class="ca-card p-3">
            <h2 class="h6 fw-bold mb-3">Invoices ({{ $invoices->count() }})</h2>
            @forelse($invoices as $invoice)
                <a href="{{ route('billing.show', $invoice) }}" class="d-block p-2 rounded border mb-2 text-decoration-none text-dark">
                    <div class="fw-semibold">{{ $invoice->invoice_number }}</div>
                    <div class="small text-muted">{{ $invoice->client?->name }} · ₹{{ number_format((float) $invoice->total_amount, 2) }}</div>
                </a>
            @empty
                <div class="text-muted small">No matching invoices.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection

