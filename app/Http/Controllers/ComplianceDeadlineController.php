<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ComplianceDeadline;
use App\Models\ComplianceRule;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\ClientAccessService;
use App\Services\ComplianceCalendarService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ComplianceDeadlineController extends Controller
{
    public function __construct(
        private readonly ClientAccessService $clientAccess,
        private readonly ComplianceCalendarService $calendarService,
        private readonly ActivityLogger $logger
    ) {}

    public function index(Request $request): View
    {
        $visibleClientIds = $this->clientAccess->visibleClientsQuery($request->user())->pluck('id');
        $month = (int) $request->query('month', now()->month);
        $year = (int) $request->query('year', now()->year);
        $currentMonth = Carbon::create($year, $month, 1);

        $query = ComplianceDeadline::with(['client', 'assignee'])
            ->where(function ($q) use ($visibleClientIds) {
                $q->whereNull('client_id')->orWhereIn('client_id', $visibleClientIds);
            });

        if ($type = $request->query('compliance_type')) {
            $query->where('compliance_type', $type);
        }
        if ($status = $request->query('status')) {
            if ($status === 'overdue_only') {
                $query->where('status', '!=', 'completed')->whereDate('due_date', '<', today());
            } else {
                $query->where('status', $status);
            }
        }
        if ($clientId = $request->query('client_id')) {
            $query->where('client_id', $clientId);
        }

        $deadlines = (clone $query)->orderBy('due_date')->paginate(15)->withQueryString();

        $monthDeadlines = (clone $query)
            ->whereBetween('due_date', [$currentMonth->copy()->startOfMonth(), $currentMonth->copy()->endOfMonth()])
            ->orderBy('due_date')
            ->get()
            ->groupBy(fn ($d) => $d->due_date->format('Y-m-d'));

        $overdueCount = ComplianceDeadline::where(function ($q) use ($visibleClientIds) {
            $q->whereNull('client_id')->orWhereIn('client_id', $visibleClientIds);
        })->where('status', '!=', 'completed')->whereDate('due_date', '<', today())->count();

        $clients = Client::whereIn('id', $visibleClientIds)->orderBy('name')->get();
        $staff = User::staff()->active()->orderBy('name')->get();
        $rules = ComplianceRule::orderBy('name')->get();

        return view('calendar.index', compact(
            'deadlines',
            'monthDeadlines',
            'currentMonth',
            'overdueCount',
            'clients',
            'staff',
            'rules'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'compliance_type' => ['required', Rule::in(array_keys(ComplianceDeadline::TYPES))],
            'client_id' => ['nullable', 'exists:clients,id'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'due_date' => ['required', 'date'],
            'reminder_date' => ['nullable', 'date'],
            'priority' => ['required', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $deadline = ComplianceDeadline::create([
            ...$validated,
            'status' => Carbon::parse($validated['due_date'])->isPast() ? 'overdue' : 'upcoming',
        ]);

        $this->logger->log('calendar', 'created', "Added compliance deadline '{$deadline->title}'", $deadline);

        return back()->with('success', 'Compliance deadline added.');
    }

    public function update(Request $request, ComplianceDeadline $deadline): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'compliance_type' => ['required', Rule::in(array_keys(ComplianceDeadline::TYPES))],
            'client_id' => ['nullable', 'exists:clients,id'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'due_date' => ['required', 'date'],
            'reminder_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(array_keys(ComplianceDeadline::STATUSES))],
            'priority' => ['required', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $deadline->update([
            ...$validated,
            'completed_at' => $validated['status'] === 'completed' ? ($deadline->completed_at ?? now()) : null,
        ]);

        $this->logger->log('calendar', 'updated', "Updated deadline '{$deadline->title}' -> {$deadline->statusLabel()}", $deadline);

        return back()->with('success', 'Compliance deadline updated.');
    }

    public function destroy(ComplianceDeadline $deadline): RedirectResponse
    {
        $deadline->delete();

        return back()->with('success', 'Deadline removed.');
    }

    public function storeRule(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'compliance_type' => ['required', Rule::in(array_keys(ComplianceDeadline::TYPES))],
            'frequency' => ['required', Rule::in(['monthly', 'quarterly', 'annual', 'one_time'])],
            'due_day' => ['required', 'integer', 'between:1,31'],
            'due_month' => ['nullable', 'integer', 'between:1,12'],
            'month_offset' => ['required', 'integer', 'between:0,12'],
            'reminder_days_before' => ['required', 'integer', 'between:0,60'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        ComplianceRule::create([...$validated, 'is_active' => true]);

        return back()->with('success', 'Configurable compliance rule saved.');
    }

    public function generateFromRule(Request $request, ComplianceRule $rule): RedirectResponse
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'between:2020,2040'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'assigned_to' => ['nullable', 'exists:users,id'],
        ]);

        $deadline = $this->calendarService->generateFromRule(
            $rule,
            (int) $validated['year'],
            ! empty($validated['month']) ? (int) $validated['month'] : null,
            $validated['client_id'] ?? null,
            $validated['assigned_to'] ?? null
        );

        $this->logger->log('calendar', 'generated', "Generated deadline '{$deadline->title}' from rule '{$rule->name}'", $deadline);

        return back()->with('success', "Generated deadline for {$deadline->due_date->format('d M Y')}.");
    }
}
