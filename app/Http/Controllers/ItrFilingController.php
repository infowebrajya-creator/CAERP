<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ItrFiling;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\ClientAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ItrFilingController extends Controller
{
    public function __construct(
        private readonly ClientAccessService $clientAccess,
        private readonly ActivityLogger $logger
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $visibleClientIds = $this->clientAccess->visibleClientsQuery($user)->pluck('id');

        $query = ItrFiling::with(['client', 'assignee'])->whereIn('client_id', $visibleClientIds);

        if ($user->hasRole(User::ROLE_EMPLOYEE)) {
            $query->where(function ($q) use ($user) {
                $q->where('assigned_to', $user->id)->orWhereNull('assigned_to');
            });
        }

        if ($clientId = $request->query('client_id')) {
            $query->where('client_id', $clientId);
        }
        if ($ay = $request->query('assessment_year')) {
            $query->where('assessment_year', $ay);
        }
        if ($returnType = $request->query('return_type')) {
            $query->where('return_type', $returnType);
        }
        if ($status = $request->query('filing_status')) {
            $query->where('filing_status', $status);
        }
        if ($search = trim((string) $request->query('search', ''))) {
            $query->whereHas('client', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('pan', 'like', "%{$search}%"));
        }

        $filings = $query->orderByDesc('assessment_year')->orderBy('due_date')->paginate(15)->withQueryString();
        $clients = Client::whereIn('id', $visibleClientIds)->orderBy('name')->get();
        $staff = User::staff()->active()->orderBy('name')->get();

        $summary = [
            'total' => ItrFiling::whereIn('client_id', $visibleClientIds)->count(),
            'filed' => ItrFiling::whereIn('client_id', $visibleClientIds)->whereIn('filing_status', ['filed', 'verified'])->count(),
            'pending' => ItrFiling::whereIn('client_id', $visibleClientIds)->whereNotIn('filing_status', ['filed', 'verified', 'overdue'])->count(),
            'refund_pending' => ItrFiling::whereIn('client_id', $visibleClientIds)->where('refund_status', 'pending')->count(),
        ];

        return view('itr.index', compact('filings', 'clients', 'staff', 'summary'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'assessment_year' => ['required', 'string', 'max:20'],
            'financial_year' => ['required', 'string', 'max:20'],
            'itr_category' => ['required', 'string', 'max:50'],
            'return_type' => ['required', Rule::in(ItrFiling::RETURN_TYPES)],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'document_status' => ['required', Rule::in(array_keys(ItrFiling::DOCUMENT_STATUSES))],
            'filing_status' => ['required', Rule::in(array_keys(ItrFiling::FILING_STATUSES))],
            'due_date' => ['nullable', 'date'],
            'filing_date' => ['nullable', 'date'],
            'acknowledgement_number' => ['nullable', 'string', 'max:60'],
            'refund_status' => ['required', Rule::in(array_keys(ItrFiling::REFUND_STATUSES))],
            'refund_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'checklist_items' => ['nullable', 'string'],
        ]);

        $lines = array_filter(array_map('trim', explode("\n", (string) $request->input('checklist_items'))));
        if (empty($lines)) {
            $lines = ['Form 16 / 16A', 'AIS & TIS Statement', 'Form 26AS', 'Bank Statements & Interest Certificates', 'Capital Gains / Investment Proofs'];
        }
        $checklist = array_values(array_map(fn ($label) => ['label' => $label, 'received' => false], $lines));

        $itr = ItrFiling::create([
            ...$validated,
            'refund_amount' => $validated['refund_amount'] ?? 0,
            'pending_documents_checklist' => $checklist,
        ]);

        $this->logger->log('itr', 'created', "Added {$itr->return_type} (AY {$itr->assessment_year}) for {$itr->client->name}", $itr);

        return back()->with('success', 'ITR filing record created.');
    }

    public function update(Request $request, ItrFiling $itr): RedirectResponse
    {
        $validated = $request->validate([
            'assessment_year' => ['required', 'string', 'max:20'],
            'financial_year' => ['required', 'string', 'max:20'],
            'itr_category' => ['required', 'string', 'max:50'],
            'return_type' => ['required', Rule::in(ItrFiling::RETURN_TYPES)],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'document_status' => ['required', Rule::in(array_keys(ItrFiling::DOCUMENT_STATUSES))],
            'filing_status' => ['required', Rule::in(array_keys(ItrFiling::FILING_STATUSES))],
            'due_date' => ['nullable', 'date'],
            'filing_date' => ['nullable', 'date'],
            'acknowledgement_number' => ['nullable', 'string', 'max:60'],
            'refund_status' => ['required', Rule::in(array_keys(ItrFiling::REFUND_STATUSES))],
            'refund_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $checklist = $itr->pending_documents_checklist ?? [];
        if ($request->has('checklist_checked')) {
            $checked = array_map('intval', (array) $request->input('checklist_checked', []));
            foreach ($checklist as $idx => &$item) {
                $item['received'] = in_array($idx, $checked, true);
            }
        }

        $itr->update([
            ...$validated,
            'refund_amount' => $validated['refund_amount'] ?? 0,
            'pending_documents_checklist' => $checklist,
        ]);

        $this->logger->log('itr', 'updated', "Updated {$itr->return_type} (AY {$itr->assessment_year}) -> {$itr->filingStatusLabel()}", $itr);

        return back()->with('success', 'ITR filing updated.');
    }

    public function destroy(ItrFiling $itr): RedirectResponse
    {
        $itr->delete();
        $this->logger->log('itr', 'deleted', 'Deleted ITR filing record.');

        return back()->with('success', 'ITR filing deleted.');
    }
}

