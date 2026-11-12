@extends('layouts.app')

@section('title', 'Billing & Invoices')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h4 fw-bold mb-1" style="color: #1e3a5f;">Billing &amp; Payment Management</h1>
        <p class="text-muted small mb-0">Generate GST-compliant professional fee invoices (CGST/SGST or IGST), record payments, and download PDFs.</p>
    </div>
    <a href="{{ route('billing.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>Generate Invoice</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><x-kpi-card title="Total Billed" :value="'₹' . number_format($summary['total_billed'], 0)" icon="bi-receipt" tone="primary" /></div>
    <div class="col-6 col-md-3"><x-kpi-card title="Total Collected" :value="'₹' . number_format($summary['total_collected'], 0)" icon="bi-cash-stack" tone="success" /></div>
    <div class="col-6 col-md-3"><x-kpi-card title="Outstanding Balance" :value="'₹' . number_format($summary['outstanding'], 0)" icon="bi-wallet2" tone="warning" /></div>
    <div class="col-6 col-md-3"><x-kpi-card title="Overdue Invoices" :value="$summary['overdue_count']" icon="bi-exclamation-triangle" tone="danger" /></div>
</div>

<div class="ca-card p-3 mb-3">
    <form method="GET" action="{{ route('billing.index') }}" class="row g-2 align-items-end">
        <div class="col-md-4"><label class="form-label small text-muted mb-1">Invoice # or Client</label><input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Search invoice number or client..."></div>
        <div class="col-md-3"><label class="form-label small text-muted mb-1">Client</label><select name="client_id" class="form-select form-select-sm"><option value="">All Clients</option>@foreach($clients as $c)<option value="{{ $c->id }}" @selected((string) request('client_id') === (string) $c->id)>{{ $c->name }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label small text-muted mb-1">Payment Status</label><select name="status" class="form-select form-select-sm"><option value="">All Statuses</option>@foreach(\App\Models\Invoice::STATUSES as $k => $v)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>@endforeach</select></div>
        <div class="col-md-2 d-flex gap-1"><button type="submit" class="btn btn-sm btn-primary flex-grow-1">Filter</button><a href="{{ route('billing.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a></div>
    </form>
</div>

<div class="ca-card overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Client</th>
                    <th>Invoice / Due Date</th>
                    <th>Taxable</th>
                    <th>GST</th>
                    <th>Total</th>
                    <th>Paid / Balance</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $inv)
                    @php($gstTotal = (float) $inv->cgst_amount + (float) $inv->sgst_amount + (float) $inv->igst_amount)
                    <tr>
                        <td class="fw-semibold"><a href="{{ route('billing.show', $inv) }}" class="text-decoration-none">{{ $inv->invoice_number }}</a></td>
                        <td class="small">{{ $inv->client->name }}</td>
                        <td class="small">{{ $inv->invoice_date->format('d M Y') }}<div class="text-muted">Due: {{ $inv->due_date->format('d M Y') }}</div></td>
                        <td class="small">₹{{ number_format((float) $inv->taxable_amount, 2) }}</td>
                        <td class="small">₹{{ number_format($gstTotal, 2) }}</td>
                        <td class="fw-semibold">₹{{ number_format((float) $inv->total_amount, 2) }}</td>
                        <td class="small">
                            <div class="text-success">Paid: ₹{{ number_format((float) $inv->paid_amount, 2) }}</div>
                            <div class="fw-semibold text-danger">Bal: ₹{{ number_format((float) $inv->balance_amount, 2) }}</div>
                        </td>
                        <td><x-status-badge :status="$inv->status" :label="$inv->statusLabel()" /></td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('billing.show', $inv) }}" class="btn btn-sm btn-light border"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('billing.pdf', $inv) }}" class="btn btn-sm btn-light border"><i class="bi bi-file-earmark-pdf text-danger"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">No invoices generated yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($invoices->hasPages())
        <div class="p-3 border-top">{{ $invoices->links() }}</div>
    @endif
</div>
@endsection

