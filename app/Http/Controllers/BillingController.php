<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\FirmSetting;
use App\Models\Invoice;
use App\Services\BillingService;
use App\Services\ClientAccessService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function __construct(
        private readonly BillingService $billingService,
        private readonly ClientAccessService $clientAccess
    ) {}

    public function index(Request $request): View
    {
        $visibleClientIds = $this->clientAccess->visibleClientsQuery($request->user())->pluck('id');
        $query = Invoice::with(['client', 'payments'])->whereIn('client_id', $visibleClientIds);

        if ($clientId = $request->query('client_id')) {
            $query->where('client_id', $clientId);
        }
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('client', fn ($cq) => $cq->where('name', 'like', "%{$search}%"));
            });
        }

        $invoices = $query->orderByDesc('invoice_date')->orderByDesc('id')->paginate(15)->withQueryString();
        $clients = Client::whereIn('id', $visibleClientIds)->orderBy('name')->get();

        $summary = [
            'total_billed' => (float) Invoice::whereIn('client_id', $visibleClientIds)->sum('total_amount'),
            'total_collected' => (float) Invoice::whereIn('client_id', $visibleClientIds)->sum('paid_amount'),
            'outstanding' => (float) Invoice::whereIn('client_id', $visibleClientIds)->whereIn('status', ['unpaid', 'partially_paid', 'overdue'])->sum('balance_amount'),
            'overdue_count' => Invoice::whereIn('client_id', $visibleClientIds)->where('status', 'overdue')->count(),
        ];

        return view('billing.index', compact('invoices', 'clients', 'summary'));
    }

    public function create(Request $request): View
    {
        $visibleClientIds = $this->clientAccess->visibleClientsQuery($request->user())->pluck('id');
        $clients = Client::whereIn('id', $visibleClientIds)->orderBy('name')->get();
        $nextInvoiceNumber = $this->billingService->generateInvoiceNumber();
        $selectedClientId = $request->query('client_id');

        return view('billing.create', compact('clients', 'nextInvoiceNumber', 'selectedClientId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'invoice_number' => ['nullable', 'string', 'max:40', 'unique:invoices,invoice_number'],
            'client_id' => ['required', 'exists:clients,id'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:invoice_date'],
            'tax_mode' => ['required', Rule::in(array_keys(Invoice::TAX_MODES))],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'cgst_rate' => ['nullable', 'numeric', 'min:0', 'max:50'],
            'sgst_rate' => ['nullable', 'numeric', 'min:0', 'max:50'],
            'igst_rate' => ['nullable', 'numeric', 'min:0', 'max:50'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.service_description' => ['required', 'string', 'max:255'],
            'items.*.sac_code' => ['nullable', 'string', 'max:20'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $invoice = $this->billingService->createInvoice($validated, $validated['items'], $request->user()->id);

        return redirect()->route('billing.show', $invoice)->with('success', "Invoice {$invoice->invoice_number} created.");
    }

    public function show(Request $request, Invoice $invoice): View
    {
        abort_unless($this->clientAccess->canAccessClient($request->user(), $invoice->client), 403);
        $invoice->load(['client', 'items', 'payments.recorder', 'creator']);
        $firm = FirmSetting::allAsMap();

        return view('billing.show', compact('invoice', 'firm'));
    }

    public function recordPayment(Request $request, Invoice $invoice): RedirectResponse
    {
        abort_unless($this->clientAccess->canAccessClient($request->user(), $invoice->client), 403);

        $validated = $request->validate([
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:'.max(0.01, (float) $invoice->balance_amount)],
            'payment_mode' => ['required', Rule::in(array_keys(\App\Models\Payment::MODES))],
            'reference_number' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->billingService->recordPayment($invoice, $validated, $request->user()->id);

        return back()->with('success', 'Payment recorded and invoice balance updated.');
    }

    public function downloadPdf(Request $request, Invoice $invoice): Response
    {
        abort_unless($this->clientAccess->canAccessClient($request->user(), $invoice->client), 403);
        $invoice->load(['client', 'items', 'payments']);
        $firm = FirmSetting::allAsMap();

        $pdf = Pdf::loadView('billing.pdf', compact('invoice', 'firm'))
            ->setPaper('a4');

        return $pdf->download($invoice->invoice_number.'.pdf');
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $invoice->delete();

        return redirect()->route('billing.index')->with('success', 'Invoice deleted.');
    }
}

