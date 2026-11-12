@extends('layouts.app')

@section('title', $invoice->invoice_number)

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2">
            <h1 class="h4 fw-bold mb-0" style="color: #1e3a5f;">Invoice {{ $invoice->invoice_number }}</h1>
            <x-status-badge :status="$invoice->status" :label="$invoice->statusLabel()" />
        </div>
        <div class="small text-muted">Client: <a href="{{ route('clients.show', $invoice->client) }}">{{ $invoice->client->name }}</a> · Issued {{ $invoice->invoice_date->format('d M Y') }}</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('billing.pdf', $invoice) }}" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-pdf me-1"></i>Download PDF</a>
        <a href="{{ route('billing.index') }}" class="btn btn-sm btn-outline-secondary">Back</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="ca-card p-4 mb-3">
            <div class="row mb-4">
                <div class="col-6">
                    <div class="fw-bold" style="color: #1e3a5f;">{{ $firm['firm_name'] ?? 'WebRajya Accounting & Advisory' }}</div>
                    <div class="small text-muted">{{ $firm['firm_address'] ?? 'Mumbai, Maharashtra' }}</div>
                    <div class="small text-muted">GSTIN: {{ $firm['firm_gstin'] ?? '—' }}</div>
                </div>
                <div class="col-6 text-end">
                    <div class="small text-muted">Billed To</div>
                    <div class="fw-bold">{{ $invoice->client->name }}</div>
                    <div class="small text-muted">{{ $invoice->client->address ?? '' }}</div>
                    <div class="small text-muted">GSTIN: {{ $invoice->client->gstin ?? 'Unregistered' }}</div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered mb-3">
                    <thead><tr><th>Service Description</th><th>SAC</th><th class="text-end">Qty</th><th class="text-end">Rate</th><th class="text-end">Amount</th></tr></thead>
                    <tbody>
                        @foreach($invoice->items as $item)
                            <tr>
                                <td>{{ $item->service_description }}</td>
                                <td class="small">{{ $item->sac_code ?? '—' }}</td>
                                <td class="text-end">{{ number_format((float) $item->quantity, 2) }}</td>
                                <td class="text-end">₹{{ number_format((float) $item->unit_price, 2) }}</td>
                                <td class="text-end fw-medium">₹{{ number_format((float) $item->amount, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="row justify-content-end">
                <div class="col-md-5 small">
                    <div class="d-flex justify-content-between mb-1"><span>Subtotal</span><span>₹{{ number_format((float) $invoice->subtotal, 2) }}</span></div>
                    @if((float) $invoice->discount_amount > 0)
                        <div class="d-flex justify-content-between mb-1"><span>Discount</span><span>-₹{{ number_format((float) $invoice->discount_amount, 2) }}</span></div>
                    @endif
                    <div class="d-flex justify-content-between mb-1"><span>Taxable Amount</span><span>₹{{ number_format((float) $invoice->taxable_amount, 2) }}</span></div>
                    @if($invoice->tax_mode === 'cgst_sgst')
                        <div class="d-flex justify-content-between mb-1"><span>CGST ({{ $invoice->cgst_rate }}%)</span><span>₹{{ number_format((float) $invoice->cgst_amount, 2) }}</span></div>
                        <div class="d-flex justify-content-between mb-1"><span>SGST ({{ $invoice->sgst_rate }}%)</span><span>₹{{ number_format((float) $invoice->sgst_amount, 2) }}</span></div>
                    @elseif($invoice->tax_mode === 'igst')
                        <div class="d-flex justify-content-between mb-1"><span>IGST ({{ $invoice->igst_rate }}%)</span><span>₹{{ number_format((float) $invoice->igst_amount, 2) }}</span></div>
                    @endif
                    <div class="d-flex justify-content-between fw-bold fs-6 border-top pt-2 mt-2"><span>Total</span><span>₹{{ number_format((float) $invoice->total_amount, 2) }}</span></div>
                    <div class="d-flex justify-content-between text-success"><span>Paid</span><span>₹{{ number_format((float) $invoice->paid_amount, 2) }}</span></div>
                    <div class="d-flex justify-content-between fw-bold text-danger"><span>Balance Due</span><span>₹{{ number_format((float) $invoice->balance_amount, 2) }}</span></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        @if((float) $invoice->balance_amount > 0)
            <div class="ca-card p-3 mb-3">
                <h2 class="h6 fw-bold mb-3">Record Payment</h2>
                <form action="{{ route('billing.payments.store', $invoice) }}" method="POST">
                    @csrf
                    <div class="mb-2"><label class="form-label small fw-semibold">Payment Date *</label><input type="date" name="payment_date" value="{{ now()->format('Y-m-d') }}" class="form-control form-control-sm" required></div>
                    <div class="mb-2"><label class="form-label small fw-semibold">Amount (Max ₹{{ number_format((float) $invoice->balance_amount, 2) }}) *</label><input type="number" step="0.01" max="{{ $invoice->balance_amount }}" name="amount" value="{{ $invoice->balance_amount }}" class="form-control form-control-sm" required></div>
                    <div class="mb-2"><label class="form-label small fw-semibold">Payment Mode *</label><select name="payment_mode" class="form-select form-select-sm">@foreach(\App\Models\Payment::MODES as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
                    <div class="mb-2"><label class="form-label small fw-semibold">Reference / UTR No.</label><input type="text" name="reference_number" class="form-control form-control-sm"></div>
                    <div class="mb-3"><label class="form-label small fw-semibold">Notes</label><input type="text" name="notes" class="form-control form-control-sm"></div>
                    <button type="submit" class="btn btn-sm btn-success w-100"><i class="bi bi-check2-circle me-1"></i>Save Payment</button>
                </form>
            </div>
        @endif

        <div class="ca-card p-3">
            <h2 class="h6 fw-bold mb-3">Payment History</h2>
            @forelse($invoice->payments as $pay)
                <div class="border rounded p-2 mb-2 bg-light small">
                    <div class="d-flex justify-content-between fw-semibold">
                        <span>₹{{ number_format((float) $pay->amount, 2) }}</span>
                        <span>{{ $pay->payment_date->format('d M Y') }}</span>
                    </div>
                    <div class="text-muted">{{ \App\Models\Payment::MODES[$pay->payment_mode] ?? $pay->payment_mode }} · Ref: {{ $pay->reference_number ?? '—' }}</div>
                </div>
            @empty
                <div class="text-muted small">No payments recorded yet.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection

