<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\GstReturn;
use App\Models\GstReturnType;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\ClientAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GstReturnController extends Controller
{
    public function __construct(
        private readonly ClientAccessService $clientAccess,
        private readonly ActivityLogger $logger
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $visibleClientIds = $this->clientAccess->visibleClientsQuery($user)->pluck('id');

        $query = GstReturn::with(['client', 'assignee', 'returnType'])
            ->whereIn('client_id', $visibleClientIds);

        if ($user->hasRole(User::ROLE_EMPLOYEE)) {
            $query->where(function ($q) use ($user) {
                $q->where('assigned_to', $user->id)->orWhereNull('assigned_to');
            });
        }

        if ($clientId = $request->query('client_id')) {
            $query->where('client_id', $clientId);
        }
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($returnType = $request->query('return_type_code')) {
            $query->where('return_type_code', $returnType);
        }
        if ($month = $request->query('period_month')) {
            $query->where('period_month', $month);
        }
        if ($year = $request->query('period_year')) {
            $query->where('period_year', $year);
        }
        if ($search = trim((string) $request->query('search', ''))) {
            $query->whereHas('client', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('gstin', 'like', "%{$search}%"));
        }

        $returns = $query->orderBy('due_date')->paginate(15)->withQueryString();
        $clients = Client::whereIn('id', $visibleClientIds)->orderBy('name')->get();
        $returnTypes = GstReturnType::where('is_active', true)->orderBy('code')->get();
        $staff = User::staff()->active()->orderBy('name')->get();

        $summary = [
            'total' => GstReturn::whereIn('client_id', $visibleClientIds)->count(),
            'filed' => GstReturn::whereIn('client_id', $visibleClientIds)->where('status', 'filed')->count(),
            'pending' => GstReturn::whereIn('client_id', $visibleClientIds)->whereNotIn('status', ['filed', 'overdue'])->count(),
            'overdue' => GstReturn::whereIn('client_id', $visibleClientIds)->where('status', 'overdue')->count(),
        ];

        $viewMode = $request->query('view', 'list');
        $calendarReturns = $viewMode === 'calendar'
            ? GstReturn::with('client')->whereIn('client_id', $visibleClientIds)->orderBy('due_date')->get()
            : collect();

        return view('gst.index', compact(
            'returns',
            'clients',
            'returnTypes',
            'staff',
            'summary',
            'viewMode',
            'calendarReturns'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'return_type_id' => ['required', 'exists:gst_return_types,id'],
            'financial_year' => ['required', 'string', 'max:20'],
            'tax_period' => ['required', 'string', 'max:30'],
            'period_month' => ['nullable', 'integer', 'between:1,12'],
            'period_year' => ['nullable', 'integer', 'between:2020,2040'],
            'due_date' => ['required', 'date'],
            'status' => ['required', Rule::in(array_keys(GstReturn::STATUSES))],
            'arn' => ['nullable', 'string', 'max:60'],
            'filing_date' => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'internal_notes' => ['nullable', 'string', 'max:3000'],
            'checklist_items' => ['nullable', 'string'],
        ]);

        $type = GstReturnType::findOrFail($validated['return_type_id']);
        $checklist = $this->parseChecklistInput($request->input('checklist_items'));

        $gst = GstReturn::create([
            ...$validated,
            'return_type_code' => $type->code,
            'document_checklist' => $checklist,
        ]);

        $this->logger->log('gst', 'created', "Created {$gst->return_type_code} ({$gst->tax_period}) for {$gst->client->name}", $gst);

        return back()->with('success', 'GST return record added.');
    }

    public function update(Request $request, GstReturn $gst): RedirectResponse
    {
        $validated = $request->validate([
            'return_type_id' => ['required', 'exists:gst_return_types,id'],
            'financial_year' => ['required', 'string', 'max:20'],
            'tax_period' => ['required', 'string', 'max:30'],
            'period_month' => ['nullable', 'integer', 'between:1,12'],
            'period_year' => ['nullable', 'integer', 'between:2020,2040'],
            'due_date' => ['required', 'date'],
            'status' => ['required', Rule::in(array_keys(GstReturn::STATUSES))],
            'arn' => ['nullable', 'string', 'max:60'],
            'filing_date' => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'internal_notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $type = GstReturnType::findOrFail($validated['return_type_id']);
        $checklist = $gst->document_checklist ?? [];
        if ($request->has('checklist_checked')) {
            $checkedIndices = array_map('intval', (array) $request->input('checklist_checked', []));
            foreach ($checklist as $idx => &$item) {
                $item['received'] = in_array($idx, $checkedIndices, true);
            }
        }

        $gst->update([
            ...$validated,
            'return_type_code' => $type->code,
            'document_checklist' => $checklist,
        ]);

        $this->logger->log('gst', 'updated', "Updated {$gst->return_type_code} ({$gst->tax_period}) -> {$gst->statusLabel()}", $gst);

        return back()->with('success', 'GST return updated.');
    }

    public function destroy(GstReturn $gst): RedirectResponse
    {
        $gst->delete();
        $this->logger->log('gst', 'deleted', 'Deleted GST return record.');

        return back()->with('success', 'GST return deleted.');
    }

    public function storeReturnType(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:30', 'unique:gst_return_types,code'],
            'name' => ['required', 'string', 'max:120'],
            'frequency' => ['required', Rule::in(['monthly', 'quarterly', 'annual'])],
            'default_due_day' => ['required', 'integer', 'between:1,31'],
            'default_due_month_offset' => ['required', 'integer', 'between:0,12'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        GstReturnType::create([...$validated, 'is_active' => true]);

        return back()->with('success', 'Configurable GST return type added.');
    }

    private function parseChecklistInput(?string $raw): array
    {
        $lines = array_filter(array_map('trim', explode("\n", (string) $raw)));
        if (empty($lines)) {
            $lines = ['Sales Register / B2B Invoices', 'Purchase Register & ITC Reconciliation (2B)', 'Credit/Debit Notes & E-Way Bills'];
        }

        return array_values(array_map(fn ($label) => ['label' => $label, 'received' => false], $lines));
    }
}

