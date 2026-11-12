<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\TdsReturn;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\ClientAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TdsReturnController extends Controller
{
    public function __construct(
        private readonly ClientAccessService $clientAccess,
        private readonly ActivityLogger $logger
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $visibleClientIds = $this->clientAccess->visibleClientsQuery($user)->pluck('id');
        $query = TdsReturn::with(['client', 'assignee'])->whereIn('client_id', $visibleClientIds);

        if ($user->hasRole(User::ROLE_EMPLOYEE)) {
            $query->where(function ($q) use ($user) {
                $q->where('assigned_to', $user->id)->orWhereNull('assigned_to');
            });
        }

        if ($clientId = $request->query('client_id')) {
            $query->where('client_id', $clientId);
        }
        if ($quarter = $request->query('quarter')) {
            $query->where('quarter', $quarter);
        }
        if ($formType = $request->query('form_type')) {
            $query->where('form_type', $formType);
        }
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $tdsReturns = $query->orderBy('due_date')->paginate(15)->withQueryString();
        $clients = Client::whereIn('id', $visibleClientIds)->orderBy('name')->get();
        $staff = User::staff()->active()->orderBy('name')->get();

        $summary = [
            'total_deduction' => (float) TdsReturn::whereIn('client_id', $visibleClientIds)->sum('deduction_amount'),
            'total_deposited' => (float) TdsReturn::whereIn('client_id', $visibleClientIds)->sum('tax_deposited'),
            'filed' => TdsReturn::whereIn('client_id', $visibleClientIds)->where('status', 'filed')->count(),
            'pending' => TdsReturn::whereIn('client_id', $visibleClientIds)->where('status', '!=', 'filed')->count(),
        ];

        return view('tds.index', compact('tdsReturns', 'clients', 'staff', 'summary'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'financial_year' => ['required', 'string', 'max:20'],
            'quarter' => ['required', Rule::in(TdsReturn::QUARTERS)],
            'form_type' => ['required', Rule::in(TdsReturn::FORM_TYPES)],
            'deduction_amount' => ['required', 'numeric', 'min:0'],
            'tax_deposited' => ['required', 'numeric', 'min:0'],
            'challan_number' => ['nullable', 'string', 'max:50'],
            'bsr_code' => ['nullable', 'string', 'max:20'],
            'challan_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'filing_date' => ['nullable', 'date'],
            'acknowledgement_number' => ['nullable', 'string', 'max:60'],
            'status' => ['required', Rule::in(array_keys(TdsReturn::STATUSES))],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $tds = TdsReturn::create($validated);
        $this->logger->log('tds', 'created', "Added TDS Form {$tds->form_type} ({$tds->quarter}) for {$tds->client->name}", $tds);

        return back()->with('success', 'TDS return record added.');
    }

    public function update(Request $request, TdsReturn $td): RedirectResponse
    {
        $validated = $request->validate([
            'financial_year' => ['required', 'string', 'max:20'],
            'quarter' => ['required', Rule::in(TdsReturn::QUARTERS)],
            'form_type' => ['required', Rule::in(TdsReturn::FORM_TYPES)],
            'deduction_amount' => ['required', 'numeric', 'min:0'],
            'tax_deposited' => ['required', 'numeric', 'min:0'],
            'challan_number' => ['nullable', 'string', 'max:50'],
            'bsr_code' => ['nullable', 'string', 'max:20'],
            'challan_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'filing_date' => ['nullable', 'date'],
            'acknowledgement_number' => ['nullable', 'string', 'max:60'],
            'status' => ['required', Rule::in(array_keys(TdsReturn::STATUSES))],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $td->update($validated);
        $this->logger->log('tds', 'updated', "Updated TDS Form {$td->form_type} ({$td->quarter}) -> {$td->statusLabel()}", $td);

        return back()->with('success', 'TDS return updated.');
    }

    public function destroy(TdsReturn $td): RedirectResponse
    {
        $td->delete();
        $this->logger->log('tds', 'deleted', 'Deleted TDS return record.');

        return back()->with('success', 'TDS return deleted.');
    }
}

