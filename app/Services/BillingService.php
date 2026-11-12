<?php

namespace App\Services;

use App\Models\FirmSetting;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

/**
 * Handles invoice creation, GST calculation (CGST+SGST / IGST), and payment recording.
 */
class BillingService
{
    public function __construct(private readonly ActivityLogger $logger) {}

    public function generateInvoiceNumber(): string
    {
        $prefix = FirmSetting::getValue('invoice_prefix', 'CA-INV');
        $year = now()->format('Y');
        $latest = Invoice::query()
            ->where('invoice_number', 'like', "{$prefix}-{$year}-%")
            ->orderByDesc('id')
            ->first();

        $nextSeq = 1;
        if ($latest && preg_match('/(\d+)$/', $latest->invoice_number, $m)) {
            $nextSeq = ((int) $m[1]) + 1;
        }

        return sprintf('%s-%s-%04d', $prefix, $year, $nextSeq);
    }

    public function createInvoice(array $data, array $items, int $createdBy): Invoice
    {
        return DB::transaction(function () use ($data, $items, $createdBy) {
            $calculated = $this->calculateTotals($items, $data);

            $invoice = Invoice::create([
                'invoice_number' => $data['invoice_number'] ?? $this->generateInvoiceNumber(),
                'client_id' => $data['client_id'],
                'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'],
                'tax_mode' => $calculated['tax_mode'],
                'subtotal' => $calculated['subtotal'],
                'discount_amount' => $calculated['discount_amount'],
                'taxable_amount' => $calculated['taxable_amount'],
                'cgst_rate' => $calculated['cgst_rate'],
                'cgst_amount' => $calculated['cgst_amount'],
                'sgst_rate' => $calculated['sgst_rate'],
                'sgst_amount' => $calculated['sgst_amount'],
                'igst_rate' => $calculated['igst_rate'],
                'igst_amount' => $calculated['igst_amount'],
                'total_amount' => $calculated['total_amount'],
                'paid_amount' => 0,
                'balance_amount' => $calculated['total_amount'],
                'status' => 'unpaid',
                'notes' => $data['notes'] ?? null,
                'created_by' => $createdBy,
            ]);

            foreach ($calculated['line_items'] as $item) {
                $invoice->items()->create($item);
            }

            $this->logger->log(
                'billing',
                'created',
                "Generated invoice {$invoice->invoice_number} for INR ".number_format((float) $invoice->total_amount, 2),
                $invoice
            );

            return $invoice->fresh(['client', 'items']);
        });
    }

    public function updateInvoice(Invoice $invoice, array $data, array $items): Invoice
    {
        return DB::transaction(function () use ($invoice, $data, $items) {
            $calculated = $this->calculateTotals($items, $data);

            $invoice->update([
                'client_id' => $data['client_id'],
                'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'],
                'tax_mode' => $calculated['tax_mode'],
                'subtotal' => $calculated['subtotal'],
                'discount_amount' => $calculated['discount_amount'],
                'taxable_amount' => $calculated['taxable_amount'],
                'cgst_rate' => $calculated['cgst_rate'],
                'cgst_amount' => $calculated['cgst_amount'],
                'sgst_rate' => $calculated['sgst_rate'],
                'sgst_amount' => $calculated['sgst_amount'],
                'igst_rate' => $calculated['igst_rate'],
                'igst_amount' => $calculated['igst_amount'],
                'total_amount' => $calculated['total_amount'],
                'notes' => $data['notes'] ?? null,
            ]);

            $invoice->items()->delete();
            foreach ($calculated['line_items'] as $item) {
                $invoice->items()->create($item);
            }

            $invoice->syncPaymentTotals();

            $this->logger->log('billing', 'updated', "Updated invoice {$invoice->invoice_number}", $invoice);

            return $invoice->fresh(['client', 'items', 'payments']);
        });
    }

    public function recordPayment(Invoice $invoice, array $data, int $recordedBy): Payment
    {
        return DB::transaction(function () use ($invoice, $data, $recordedBy) {
            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'client_id' => $invoice->client_id,
                'payment_date' => $data['payment_date'],
                'amount' => $data['amount'],
                'payment_mode' => $data['payment_mode'],
                'reference_number' => $data['reference_number'] ?? null,
                'notes' => $data['notes'] ?? null,
                'recorded_by' => $recordedBy,
            ]);

            $invoice->syncPaymentTotals();

            $this->logger->log(
                'billing',
                'payment_recorded',
                "Recorded payment of INR ".number_format((float) $payment->amount, 2)." against {$invoice->invoice_number}",
                $invoice
            );

            return $payment;
        });
    }

    private function calculateTotals(array $items, array $data): array
    {
        $lineItems = [];
        $subtotal = 0.0;

        foreach ($items as $item) {
            $qty = max(0.01, (float) ($item['quantity'] ?? 1));
            $price = max(0, (float) ($item['unit_price'] ?? 0));
            $amount = round($qty * $price, 2);
            $subtotal += $amount;

            $lineItems[] = [
                'service_description' => $item['service_description'],
                'sac_code' => $item['sac_code'] ?? null,
                'quantity' => $qty,
                'unit_price' => $price,
                'amount' => $amount,
            ];
        }

        $discount = min($subtotal, max(0, (float) ($data['discount_amount'] ?? 0)));
        $taxable = round(max(0, $subtotal - $discount), 2);
        $taxMode = $data['tax_mode'] ?? 'cgst_sgst';

        $cgstRate = (float) ($data['cgst_rate'] ?? 9);
        $sgstRate = (float) ($data['sgst_rate'] ?? 9);
        $igstRate = (float) ($data['igst_rate'] ?? 18);

        $cgstAmount = 0.0;
        $sgstAmount = 0.0;
        $igstAmount = 0.0;

        if ($taxMode === 'cgst_sgst') {
            $cgstAmount = round($taxable * ($cgstRate / 100), 2);
            $sgstAmount = round($taxable * ($sgstRate / 100), 2);
            $igstRate = 0;
        } elseif ($taxMode === 'igst') {
            $igstAmount = round($taxable * ($igstRate / 100), 2);
            $cgstRate = 0;
            $sgstRate = 0;
        } else {
            $cgstRate = 0;
            $sgstRate = 0;
            $igstRate = 0;
        }

        $total = round($taxable + $cgstAmount + $sgstAmount + $igstAmount, 2);

        return [
            'tax_mode' => $taxMode,
            'subtotal' => round($subtotal, 2),
            'discount_amount' => round($discount, 2),
            'taxable_amount' => $taxable,
            'cgst_rate' => $cgstRate,
            'cgst_amount' => $cgstAmount,
            'sgst_rate' => $sgstRate,
            'sgst_amount' => $sgstAmount,
            'igst_rate' => $igstRate,
            'igst_amount' => $igstAmount,
            'total_amount' => $total,
            'line_items' => $lineItems,
        ];
    }
}

