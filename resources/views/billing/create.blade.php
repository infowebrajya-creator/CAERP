@extends('layouts.app')

@section('title', 'Generate Invoice')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 fw-bold mb-1" style="color: #1e3a5f;">Generate Professional Fee Invoice</h1>
        <p class="text-muted small mb-0">Supports CGST + SGST (Intra-state), IGST (Inter-state), or Tax Exempt billing with live totals.</p>
    </div>
    <a href="{{ route('billing.index') }}" class="btn btn-sm btn-outline-secondary">Back to Billing</a>
</div>

<form action="{{ route('billing.store') }}" method="POST" class="ca-card p-4" id="invoiceForm">
    @csrf
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Invoice Number</label>
            <input type="text" name="invoice_number" value="{{ old('invoice_number', $nextInvoiceNumber) }}" class="form-control form-control-sm" required>
        </div>
        <div class="col-md-5">
            <label class="form-label small fw-semibold">Client *</label>
            <select name="client_id" class="form-select form-select-sm" required>
                <option value="">-- Select Client --</option>
                @foreach($clients as $c)
                    <option value="{{ $c->id }}" @selected((string) old('client_id', $selectedClientId) === (string) $c->id)>{{ $c->name }} ({{ $c->gstin ?? 'Unregistered' }})</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold">Invoice Date *</label>
            <input type="date" name="invoice_date" value="{{ old('invoice_date', now()->format('Y-m-d')) }}" class="form-control form-control-sm" required>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold">Due Date *</label>
            <input type="date" name="due_date" value="{{ old('due_date', now()->addDays(15)->format('Y-m-d')) }}" class="form-control form-control-sm" required>
        </div>
    </div>

    <div class="table-responsive mb-3">
        <table class="table table-bordered align-middle mb-0" id="itemsTable">
            <thead>
                <tr>
                    <th>Service Description *</th>
                    <th style="width: 130px;">SAC Code</th>
                    <th style="width: 110px;">Qty *</th>
                    <th style="width: 150px;">Rate (INR) *</th>
                    <th style="width: 150px;">Amount (INR)</th>
                    <th style="width: 60px;"></th>
                </tr>
            </thead>
            <tbody id="itemsBody">
                <tr class="item-row">
                    <td><input type="text" name="items[0][service_description]" class="form-control form-control-sm" required placeholder="e.g. Monthly GST Return Filing &amp; Advisory"></td>
                    <td><input type="text" name="items[0][sac_code]" value="998221" class="form-control form-control-sm"></td>
                    <td><input type="number" step="0.01" min="0.01" name="items[0][quantity]" value="1" class="form-control form-control-sm item-qty" required></td>
                    <td><input type="number" step="0.01" min="0" name="items[0][unit_price]" value="5000" class="form-control form-control-sm item-price" required></td>
                    <td class="fw-semibold item-amount text-end">5000.00</td>
                    <td class="text-center"><button type="button" class="btn btn-sm btn-light border text-danger remove-row"><i class="bi bi-x"></i></button></td>
                </tr>
            </tbody>
        </table>
    </div>
    <button type="button" id="addItemBtn" class="btn btn-sm btn-outline-primary mb-4"><i class="bi bi-plus-lg me-1"></i>Add Service Line</button>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="row g-2 mb-3">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">GST Tax Mode</label>
                    <select name="tax_mode" id="taxMode" class="form-select form-select-sm">
                        @foreach(\App\Models\Invoice::TAX_MODES as $k => $v)
                            <option value="{{ $k }}">{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Discount (INR)</label>
                    <input type="number" step="0.01" min="0" name="discount_amount" id="discountInput" value="0" class="form-control form-control-sm">
                </div>
                <div class="col-md-4"><label class="form-label small fw-semibold">CGST %</label><input type="number" step="0.01" name="cgst_rate" id="cgstRate" value="9" class="form-control form-control-sm"></div>
                <div class="col-md-4"><label class="form-label small fw-semibold">SGST %</label><input type="number" step="0.01" name="sgst_rate" id="sgstRate" value="9" class="form-control form-control-sm"></div>
                <div class="col-md-4"><label class="form-label small fw-semibold">IGST %</label><input type="number" step="0.01" name="igst_rate" id="igstRate" value="18" class="form-control form-control-sm"></div>
            </div>
            <label class="form-label small fw-semibold">Invoice Notes / Payment Terms</label>
            <textarea name="notes" rows="3" class="form-control form-control-sm" placeholder="Payable via NEFT/UPI within 15 days."></textarea>
        </div>

        <div class="col-md-6">
            <div class="border rounded p-3 bg-light">
                <div class="d-flex justify-content-between mb-1 small"><span>Subtotal:</span><strong id="sumSubtotal">₹5,000.00</strong></div>
                <div class="d-flex justify-content-between mb-1 small"><span>Discount:</span><strong id="sumDiscount">₹0.00</strong></div>
                <div class="d-flex justify-content-between mb-2 small border-bottom pb-2"><span>Taxable Value:</span><strong id="sumTaxable">₹5,000.00</strong></div>
                <div class="d-flex justify-content-between mb-1 small"><span>CGST:</span><span id="sumCgst">₹450.00</span></div>
                <div class="d-flex justify-content-between mb-1 small"><span>SGST:</span><span id="sumSgst">₹450.00</span></div>
                <div class="d-flex justify-content-between mb-2 small border-bottom pb-2"><span>IGST:</span><span id="sumIgst">₹0.00</span></div>
                <div class="d-flex justify-content-between fs-5 fw-bold" style="color: #1e3a5f;"><span>Total Invoice:</span><span id="sumTotal">₹5,900.00</span></div>
            </div>
            <div class="mt-3 text-end">
                <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check2-circle me-1"></i>Generate Invoice</button>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    let idx = 1;
    const body = document.getElementById('itemsBody');

    function recalc() {
        let subtotal = 0;
        body.querySelectorAll('.item-row').forEach(row => {
            const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
            const price = parseFloat(row.querySelector('.item-price').value) || 0;
            const amt = qty * price;
            subtotal += amt;
            row.querySelector('.item-amount').textContent = amt.toFixed(2);
        });
        const discount = Math.min(subtotal, parseFloat(document.getElementById('discountInput').value) || 0);
        const taxable = Math.max(0, subtotal - discount);
        const mode = document.getElementById('taxMode').value;
        const cgstR = parseFloat(document.getElementById('cgstRate').value) || 0;
        const sgstR = parseFloat(document.getElementById('sgstRate').value) || 0;
        const igstR = parseFloat(document.getElementById('igstRate').value) || 0;

        let cgst = 0, sgst = 0, igst = 0;
        if (mode === 'cgst_sgst') {
            cgst = taxable * (cgstR / 100);
            sgst = taxable * (sgstR / 100);
        } else if (mode === 'igst') {
            igst = taxable * (igstR / 100);
        }
        const total = taxable + cgst + sgst + igst;
        const fmt = n => '₹' + n.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('sumSubtotal').textContent = fmt(subtotal);
        document.getElementById('sumDiscount').textContent = fmt(discount);
        document.getElementById('sumTaxable').textContent = fmt(taxable);
        document.getElementById('sumCgst').textContent = fmt(cgst);
        document.getElementById('sumSgst').textContent = fmt(sgst);
        document.getElementById('sumIgst').textContent = fmt(igst);
        document.getElementById('sumTotal').textContent = fmt(total);
    }

    document.getElementById('addItemBtn').addEventListener('click', () => {
        const tr = document.createElement('tr');
        tr.className = 'item-row';
        tr.innerHTML = `
            <td><input type="text" name="items[${idx}][service_description]" class="form-control form-control-sm" required></td>
            <td><input type="text" name="items[${idx}][sac_code]" value="998221" class="form-control form-control-sm"></td>
            <td><input type="number" step="0.01" min="0.01" name="items[${idx}][quantity]" value="1" class="form-control form-control-sm item-qty" required></td>
            <td><input type="number" step="0.01" min="0" name="items[${idx}][unit_price]" value="0" class="form-control form-control-sm item-price" required></td>
            <td class="fw-semibold item-amount text-end">0.00</td>
            <td class="text-center"><button type="button" class="btn btn-sm btn-light border text-danger remove-row"><i class="bi bi-x"></i></button></td>
        `;
        body.appendChild(tr);
        idx++;
        recalc();
    });

    body.addEventListener('click', (e) => {
        if (e.target.closest('.remove-row') && body.querySelectorAll('.item-row').length > 1) {
            e.target.closest('.item-row').remove();
            recalc();
        }
    });

    document.getElementById('invoiceForm').addEventListener('input', recalc);
    document.getElementById('taxMode').addEventListener('change', recalc);
    recalc();
});
</script>
@endpush

