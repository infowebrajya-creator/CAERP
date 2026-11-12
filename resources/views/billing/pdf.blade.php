<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #1e293b; font-size: 12px; margin: 0; padding: 24px; }
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #1e3a5f; padding-bottom: 14px; margin-bottom: 18px; }
        .firm-title { font-size: 18px; font-weight: bold; color: #1e3a5f; }
        .inv-title { font-size: 20px; font-weight: bold; color: #0d9488; text-align: right; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #cbd5e1; padding: 8px; }
        th { background-color: #eff6ff; color: #1e3a5f; text-align: left; font-size: 11px; text-transform: uppercase; }
        .text-end { text-align: right; }
        .totals { width: 45%; margin-left: auto; margin-top: 14px; }
        .totals td { border: none; padding: 4px 6px; }
        .grand-total { border-top: 2px solid #1e3a5f !important; font-weight: bold; font-size: 14px; color: #1e3a5f; }
    </style>
</head>
<body>
<table style="border: none; margin-top: 0;">
    <tr>
        <td style="border: none; padding: 0; vertical-align: top;">
            <div class="firm-title">{{ $firm['firm_name'] ?? 'WebRajya Accounting & Advisory' }}</div>
            <div>{{ $firm['firm_address'] ?? 'Mumbai, Maharashtra' }}</div>
            <div>PAN: {{ $firm['firm_pan'] ?? '—' }} | GSTIN: {{ $firm['firm_gstin'] ?? '—' }}</div>
            <div>Email: {{ $firm['firm_email'] ?? 'billing@webrajya.in' }} | Phone: {{ $firm['firm_phone'] ?? '—' }}</div>
        </td>
        <td style="border: none; padding: 0; vertical-align: top; text-align: right;">
            <div class="inv-title">TAX INVOICE</div>
            <div><strong>Invoice #:</strong> {{ $invoice->invoice_number }}</div>
            <div><strong>Date:</strong> {{ $invoice->invoice_date->format('d M Y') }}</div>
            <div><strong>Due Date:</strong> {{ $invoice->due_date->format('d M Y') }}</div>
            <div><strong>Status:</strong> {{ $invoice->statusLabel() }}</div>
        </td>
    </tr>
</table>

<div style="margin-top: 16px; padding: 10px; background: #f8fafc; border: 1px solid #e2e8f0;">
    <strong>Billed To:</strong> {{ $invoice->client->name }}<br>
    {{ $invoice->client->address ?? '' }} {{ $invoice->client->city ?? '' }} {{ $invoice->client->state ?? '' }}<br>
    PAN: {{ $invoice->client->pan ?? '—' }} | GSTIN: {{ $invoice->client->gstin ?? 'Unregistered' }}
</div>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Service Description</th>
            <th>SAC</th>
            <th class="text-end">Qty</th>
            <th class="text-end">Rate (INR)</th>
            <th class="text-end">Amount (INR)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($invoice->items as $idx => $item)
            <tr>
                <td>{{ $idx + 1 }}</td>
                <td>{{ $item->service_description }}</td>
                <td>{{ $item->sac_code ?? '—' }}</td>
                <td class="text-end">{{ number_format((float) $item->quantity, 2) }}</td>
                <td class="text-end">{{ number_format((float) $item->unit_price, 2) }}</td>
                <td class="text-end">{{ number_format((float) $item->amount, 2) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<table class="totals">
    <tr><td>Subtotal</td><td class="text-end">INR {{ number_format((float) $invoice->subtotal, 2) }}</td></tr>
    @if((float) $invoice->discount_amount > 0)
        <tr><td>Discount</td><td class="text-end">- INR {{ number_format((float) $invoice->discount_amount, 2) }}</td></tr>
    @endif
    <tr><td>Taxable Value</td><td class="text-end">INR {{ number_format((float) $invoice->taxable_amount, 2) }}</td></tr>
    @if($invoice->tax_mode === 'cgst_sgst')
        <tr><td>CGST ({{ $invoice->cgst_rate }}%)</td><td class="text-end">INR {{ number_format((float) $invoice->cgst_amount, 2) }}</td></tr>
        <tr><td>SGST ({{ $invoice->sgst_rate }}%)</td><td class="text-end">INR {{ number_format((float) $invoice->sgst_amount, 2) }}</td></tr>
    @elseif($invoice->tax_mode === 'igst')
        <tr><td>IGST ({{ $invoice->igst_rate }}%)</td><td class="text-end">INR {{ number_format((float) $invoice->igst_amount, 2) }}</td></tr>
    @endif
    <tr><td class="grand-total">Total Invoice</td><td class="text-end grand-total">INR {{ number_format((float) $invoice->total_amount, 2) }}</td></tr>
    <tr><td>Paid Amount</td><td class="text-end">INR {{ number_format((float) $invoice->paid_amount, 2) }}</td></tr>
    <tr><td><strong>Balance Due</strong></td><td class="text-end"><strong>INR {{ number_format((float) $invoice->balance_amount, 2) }}</strong></td></tr>
</table>

@if($invoice->notes)
    <div style="margin-top: 20px; font-size: 11px; color: #475569;">
        <strong>Notes:</strong> {{ $invoice->notes }}
    </div>
@endif
</body>
</html>

