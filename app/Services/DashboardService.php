<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Audit;
use App\Models\Client;
use App\Models\ComplianceDeadline;
use App\Models\Document;
use App\Models\GstReturn;
use App\Models\Invoice;
use App\Models\ItrFiling;
use App\Models\Payment;
use App\Models\Task;
use App\Models\TdsReturn;
use App\Models\User;
use Carbon\Carbon;

/**
 * Aggregates role-tailored database metrics, charts, and activity feeds for each staff role.
 */
class DashboardService
{
    public function __construct(private readonly ClientAccessService $clientAccess) {}

    public function getDashboardData(User $user): array
    {
        $today = Carbon::today();
        $visibleClientIds = $this->clientAccess->visibleClientsQuery($user)->pluck('id');
        $isExecutive = $user->isAdminOrPartner();
        $isEmployee = $user->role === User::ROLE_EMPLOYEE;

        $totalActiveClients = Client::whereIn('id', $visibleClientIds)->where('status', 'active')->count();
        $totalGstClients = Client::whereIn('id', $visibleClientIds)->where('is_gst_registered', true)->count();
        $totalItrClients = Client::whereIn('id', $visibleClientIds)->where('is_itr_applicable', true)->count();

        $gstQuery = GstReturn::whereIn('client_id', $visibleClientIds)->where('status', '!=', 'filed');
        $itrQuery = ItrFiling::whereIn('client_id', $visibleClientIds)->whereNotIn('filing_status', ['filed', 'verified']);
        $tdsQuery = TdsReturn::whereIn('client_id', $visibleClientIds)->where('status', '!=', 'filed');

        if ($isEmployee) {
            $gstQuery->where('assigned_to', $user->id);
            $itrQuery->where('assigned_to', $user->id);
            $tdsQuery->where('assigned_to', $user->id);
        }

        $pendingGstReturns = $gstQuery->count();
        $pendingItrFilings = $itrQuery->count();
        $pendingTdsReturns = $tdsQuery->count();

        $deadlineBase = ComplianceDeadline::query()
            ->where(function ($q) use ($visibleClientIds, $isEmployee, $user) {
                if ($isEmployee) {
                    $q->where('assigned_to', $user->id)->orWhereIn('client_id', $visibleClientIds);
                } else {
                    $q->whereNull('client_id')->orWhereIn('client_id', $visibleClientIds);
                }
            })
            ->where('status', '!=', 'completed');

        $upcomingDeadlinesCount = (clone $deadlineBase)
            ->whereBetween('due_date', [$today, $today->copy()->addDays(30)])
            ->count();

        $taskScope = $isEmployee
            ? Task::query()->where('assigned_to', $user->id)
            : Task::query()->where(function ($q) use ($visibleClientIds, $user) {
                $q->whereIn('client_id', $visibleClientIds)->orWhere('assigned_to', $user->id);
            });

        $overdueTasksCount = (clone $taskScope)
            ->where('status', '!=', 'completed')
            ->whereDate('due_date', '<', $today)
            ->count();

        $awaitingDocsCount = Document::whereIn('client_id', $visibleClientIds)
            ->where('status', 'requested')
            ->count();

        $outstandingFees = $isExecutive
            ? (float) Invoice::whereIn('client_id', $visibleClientIds)
                ->whereIn('status', ['unpaid', 'partially_paid', 'overdue'])
                ->sum('balance_amount')
            : 0.0;

        $monthlyRevenue = $isExecutive
            ? (float) Payment::whereIn('client_id', $visibleClientIds)
                ->whereBetween('payment_date', [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()])
                ->sum('amount')
            : 0.0;

        $totalTasks = (clone $taskScope)->count();
        $completedTasks = (clone $taskScope)->where('status', 'completed')->count();
        $openTasks = (clone $taskScope)->where('status', '!=', 'completed')->count();
        $underReviewTasksCount = Task::where('status', 'under_review')->count();
        $activeStaffCount = User::staff()->active()->count();
        $taskCompletionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 1) : 0.0;

        $upcomingDeadlines = (clone $deadlineBase)
            ->with(['client', 'assignee'])
            ->orderBy('due_date')
            ->limit(8)
            ->get();

        $recentActivities = $isEmployee
            ? ActivityLog::with('user')->where('user_id', $user->id)->latest()->limit(8)->get()
            : ActivityLog::with('user')->latest()->limit(10)->get();

        $myTasks = $isEmployee
            ? Task::with('client')->where('assigned_to', $user->id)->where('status', '!=', 'completed')->orderBy('due_date')->limit(8)->get()
            : collect();

        $reviewQueue = $user->isManagerOrAbove()
            ? Task::with(['client', 'assignee'])->where('status', 'under_review')->orderBy('due_date')->limit(6)->get()
            : collect();

        $auditsForReview = $isExecutive
            ? Audit::with(['client', 'leadAuditor'])->whereIn('status', ['in_progress', 'under_review'])->orderBy('due_date')->limit(5)->get()
            : collect();

        $roleMeta = match ($user->role) {
            User::ROLE_SUPER_ADMIN => [
                'heading' => 'Executive & System Control Dashboard',
                'subheading' => 'Full firm-wide revenue, compliance, team workload, and system audit telemetry.',
            ],
            User::ROLE_PARTNER => [
                'heading' => 'Partner Practice & Assurance Dashboard',
                'subheading' => 'Practice financials, client portfolio growth, audit progress, and partner sign-offs.',
            ],
            User::ROLE_MANAGER => [
                'heading' => 'Operations & Team Delivery Dashboard',
                'subheading' => 'Team compliance execution, task review queue, and staff workload balancing.',
            ],
            default => [
                'heading' => 'My Work & Assigned Clients Dashboard',
                'subheading' => 'Your assigned clients, pending filings, personal task queue, and upcoming deadlines.',
            ],
        };

        return [
            'roleMeta' => $roleMeta,
            'isExecutive' => $isExecutive,
            'isEmployee' => $isEmployee,
            'kpis' => [
                'active_clients' => $totalActiveClients,
                'gst_clients' => $totalGstClients,
                'itr_clients' => $totalItrClients,
                'pending_gst' => $pendingGstReturns,
                'pending_itr' => $pendingItrFilings,
                'pending_tds' => $pendingTdsReturns,
                'upcoming_deadlines' => $upcomingDeadlinesCount,
                'overdue_tasks' => $overdueTasksCount,
                'awaiting_documents' => $awaitingDocsCount,
                'outstanding_fees' => $outstandingFees,
                'monthly_revenue' => $monthlyRevenue,
                'task_completion_rate' => $taskCompletionRate,
                'completed_tasks' => $completedTasks,
                'total_tasks' => $totalTasks,
                'open_tasks' => $openTasks,
                'under_review_tasks' => $underReviewTasksCount,
                'active_staff' => $activeStaffCount,
            ],
            'charts' => [
                'monthly_revenue' => $isExecutive ? $this->monthlyRevenueChart($visibleClientIds) : null,
                'compliance_completion' => $this->complianceCompletionChart($visibleClientIds, $isEmployee ? $user->id : null),
                'client_growth' => $isExecutive ? $this->clientGrowthChart($visibleClientIds) : null,
                'employee_workload' => ! $isEmployee ? $this->employeeWorkloadChart() : null,
            ],
            'upcomingDeadlines' => $upcomingDeadlines,
            'recentActivities' => $recentActivities,
            'myTasks' => $myTasks,
            'reviewQueue' => $reviewQueue,
            'auditsForReview' => $auditsForReview,
        ];
    }

    private function monthlyRevenueChart($visibleClientIds): array
    {
        $labels = [];
        $values = [];

        for ($i = 5; $i >= 0; $i--) {
            $monthStart = now()->subMonths($i)->startOfMonth();
            $monthEnd = now()->subMonths($i)->endOfMonth();
            $labels[] = $monthStart->format('M Y');
            $values[] = round((float) Payment::whereIn('client_id', $visibleClientIds)
                ->whereBetween('payment_date', [$monthStart, $monthEnd])
                ->sum('amount'), 2);
        }

        return ['labels' => $labels, 'values' => $values];
    }

    private function complianceCompletionChart($visibleClientIds, ?int $assignedUserId = null): array
    {
        $gstBase = GstReturn::whereIn('client_id', $visibleClientIds);
        $itrBase = ItrFiling::whereIn('client_id', $visibleClientIds);
        $tdsBase = TdsReturn::whereIn('client_id', $visibleClientIds);
        $auditBase = Audit::whereIn('client_id', $visibleClientIds);

        if ($assignedUserId) {
            $gstBase->where('assigned_to', $assignedUserId);
            $itrBase->where('assigned_to', $assignedUserId);
            $tdsBase->where('assigned_to', $assignedUserId);
            $auditBase->where(function ($q) use ($assignedUserId) {
                $q->where('lead_auditor_id', $assignedUserId)
                    ->orWhereHas('teamMembers', fn ($tm) => $tm->where('users.id', $assignedUserId));
            });
        }

        $gstTotal = (clone $gstBase)->count();
        $gstDone = (clone $gstBase)->where('status', 'filed')->count();

        $itrTotal = (clone $itrBase)->count();
        $itrDone = (clone $itrBase)->whereIn('filing_status', ['filed', 'verified'])->count();

        $tdsTotal = (clone $tdsBase)->count();
        $tdsDone = (clone $tdsBase)->where('status', 'filed')->count();

        $auditTotal = (clone $auditBase)->count();
        $auditDone = (clone $auditBase)->whereIn('status', ['approved', 'completed'])->count();

        return [
            'labels' => ['GST Returns', 'ITR Filings', 'TDS Returns', 'Audits'],
            'completed' => [$gstDone, $itrDone, $tdsDone, $auditDone],
            'pending' => [
                max(0, $gstTotal - $gstDone),
                max(0, $itrTotal - $itrDone),
                max(0, $tdsTotal - $tdsDone),
                max(0, $auditTotal - $auditDone),
            ],
        ];
    }

    private function clientGrowthChart($visibleClientIds): array
    {
        $labels = [];
        $values = [];

        for ($i = 5; $i >= 0; $i--) {
            $monthEnd = now()->subMonths($i)->endOfMonth();
            $labels[] = $monthEnd->format('M Y');
            $values[] = Client::whereIn('id', $visibleClientIds)
                ->where('created_at', '<=', $monthEnd)
                ->count();
        }

        return ['labels' => $labels, 'values' => $values];
    }

    private function employeeWorkloadChart(): array
    {
        $staff = User::staff()->active()
            ->withCount([
                'assignedTasks as active_tasks_count' => fn ($q) => $q->where('status', '!=', 'completed'),
                'assignedTasks as completed_tasks_count' => fn ($q) => $q->where('status', 'completed'),
            ])
            ->orderByDesc('active_tasks_count')
            ->limit(7)
            ->get();

        return [
            'labels' => $staff->pluck('name')->values()->toArray(),
            'active' => $staff->pluck('active_tasks_count')->values()->toArray(),
            'completed' => $staff->pluck('completed_tasks_count')->values()->toArray(),
        ];
    }
}
