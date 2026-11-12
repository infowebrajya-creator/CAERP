@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2">
            <h1 class="page-title mb-0">{{ $roleMeta['heading'] ?? 'Practice Overview' }}</h1>
            <span class="badge text-bg-light border text-uppercase">{{ str_replace('_', ' ', auth()->user()->role) }}</span>
        </div>
        <p class="page-subtitle mb-0">{{ $roleMeta['subheading'] ?? 'Real-time compliance, tax, audit, and practice health metrics.' }}</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        @if($isEmployee)
            <a href="{{ route('tasks.index') }}" class="btn btn-primary btn-sm"><i class="bi bi-check2-square me-1"></i>My Tasks</a>
            <a href="{{ route('documents.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-folder2-open me-1"></i>Upload Document</a>
        @else
            <a href="{{ route('clients.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-person-plus me-1"></i>Add Client</a>
            @if($isExecutive)
                <a href="{{ route('billing.create') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-receipt me-1"></i>New Invoice</a>
            @else
                <a href="{{ route('tasks.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-kanban me-1"></i>Task Board</a>
            @endif
        @endif
    </div>
</div>

<!-- Role-Tailored KPI Grid -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card kpi-card h-100">
            <div class="card-body">
                <div class="kpi-label">{{ $isEmployee ? 'My Assigned Clients' : 'Active Clients' }}</div>
                <div class="kpi-value">{{ $kpis['active_clients'] }}</div>
                <div class="kpi-meta"><span class="text-success"><i class="bi bi-building-check me-1"></i>GST: {{ $kpis['gst_clients'] }}</span> · ITR: {{ $kpis['itr_clients'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card kpi-card h-100">
            <div class="card-body">
                <div class="kpi-label">{{ $isEmployee ? 'My Pending GST' : 'Pending GST' }}</div>
                <div class="kpi-value text-primary">{{ $kpis['pending_gst'] }}</div>
                <div class="kpi-meta"><a href="{{ route('gst.index') }}" class="text-decoration-none">Open GST Tracker →</a></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card kpi-card h-100">
            <div class="card-body">
                <div class="kpi-label">{{ $isEmployee ? 'My Pending ITR' : 'Pending ITR' }}</div>
                <div class="kpi-value text-info">{{ $kpis['pending_itr'] }}</div>
                <div class="kpi-meta"><a href="{{ route('itr.index') }}" class="text-decoration-none">Open ITR Tracker →</a></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card kpi-card h-100">
            <div class="card-body">
                <div class="kpi-label">{{ $isEmployee ? 'My Pending TDS' : 'Pending TDS' }}</div>
                <div class="kpi-value text-secondary">{{ $kpis['pending_tds'] }}</div>
                <div class="kpi-meta"><a href="{{ route('tds.index') }}" class="text-decoration-none">Open TDS Tracker →</a></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card kpi-card h-100">
            <div class="card-body">
                <div class="kpi-label">Upcoming Deadlines</div>
                <div class="kpi-value text-warning">{{ $kpis['upcoming_deadlines'] }}</div>
                <div class="kpi-meta">Next 14 days · <a href="{{ route('calendar.index') }}" class="text-decoration-none">Calendar</a></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card kpi-card h-100">
            <div class="card-body">
                <div class="kpi-label">{{ $isEmployee ? 'My Overdue Tasks' : 'Overdue Tasks' }}</div>
                <div class="kpi-value text-danger">{{ $kpis['overdue_tasks'] }}</div>
                <div class="kpi-meta"><a href="{{ route('tasks.index') }}" class="text-decoration-none">View Task Board →</a></div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-4 col-xl-3">
        <div class="card kpi-card h-100">
            <div class="card-body">
                <div class="kpi-label">Awaiting Documents</div>
                <div class="kpi-value">{{ $kpis['awaiting_documents'] }}</div>
                <div class="kpi-meta">Requested or pending review · <a href="{{ route('documents.index') }}" class="text-decoration-none">Vault</a></div>
            </div>
        </div>
    </div>

    @if($isExecutive)
        <div class="col-6 col-md-4 col-xl-3">
            <div class="card kpi-card h-100">
                <div class="card-body">
                    <div class="kpi-label">Outstanding Fees</div>
                    <div class="kpi-value text-danger">₹{{ number_format($kpis['outstanding_fees'], 0) }}</div>
                    <div class="kpi-meta"><a href="{{ route('billing.index') }}" class="text-decoration-none">Follow up receivables →</a></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-3">
            <div class="card kpi-card h-100">
                <div class="card-body">
                    <div class="kpi-label">Monthly Revenue</div>
                    <div class="kpi-value text-success">₹{{ number_format($kpis['monthly_revenue'], 0) }}</div>
                    <div class="kpi-meta">Collected this month</div>
                </div>
            </div>
        </div>
    @elseif($isEmployee)
        <div class="col-6 col-md-4 col-xl-3">
            <div class="card kpi-card h-100">
                <div class="card-body">
                    <div class="kpi-label">My Open Tasks</div>
                    <div class="kpi-value text-primary">{{ $kpis['open_tasks'] }}</div>
                    <div class="kpi-meta">Assigned to you · <a href="{{ route('tasks.index') }}" class="text-decoration-none">Open Board</a></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-3">
            <div class="card kpi-card h-100">
                <div class="card-body">
                    <div class="kpi-label">Under Review</div>
                    <div class="kpi-value text-info">{{ $kpis['under_review_tasks'] }}</div>
                    <div class="kpi-meta">Awaiting manager/partner sign-off</div>
                </div>
            </div>
        </div>
    @else
        <div class="col-6 col-md-4 col-xl-3">
            <div class="card kpi-card h-100">
                <div class="card-body">
                    <div class="kpi-label">Tasks Under Review</div>
                    <div class="kpi-value text-primary">{{ $kpis['under_review_tasks'] }}</div>
                    <div class="kpi-meta">Pending manager sign-off · <a href="{{ route('tasks.index') }}" class="text-decoration-none">Review</a></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-3">
            <div class="card kpi-card h-100">
                <div class="card-body">
                    <div class="kpi-label">Active Staff</div>
                    <div class="kpi-value text-success">{{ $kpis['active_staff'] }}</div>
                    <div class="kpi-meta">{{ $kpis['open_tasks'] }} open team tasks · <a href="{{ route('staff.index') }}" class="text-decoration-none">Workload</a></div>
                </div>
            </div>
        </div>
    @endif

    <div class="col-6 col-md-4 col-xl-3">
        <div class="card kpi-card h-100">
            <div class="card-body">
                <div class="kpi-label">{{ $isEmployee ? 'My Completion Rate' : 'Task Completion Rate' }}</div>
                <div class="kpi-value">{{ $kpis['task_completion_rate'] }}%</div>
                <div class="kpi-meta">{{ $kpis['completed_tasks'] }} of {{ $kpis['total_tasks'] }} tasks completed</div>
            </div>
        </div>
    </div>
</div>

<!-- Role-Tailored Charts -->
<div class="row g-3 mb-4">
    @if(!is_null($charts['monthly_revenue']))
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Monthly Revenue vs Invoiced (6 Months)</span>
                    <span class="badge text-bg-light">Billing</span>
                </div>
                <div class="card-body">
                    <canvas id="revenueChart" height="140"></canvas>
                </div>
            </div>
        </div>
    @endif

    <div class="{{ is_null($charts['monthly_revenue']) ? 'col-lg-6' : 'col-lg-6' }}">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>{{ $isEmployee ? 'My Compliance Completion Status' : 'Compliance Completion Status' }}</span>
                <span class="badge text-bg-light">GST · ITR · TDS</span>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <div style="max-width: 300px; width: 100%;">
                    <canvas id="complianceChart" height="220"></canvas>
                </div>
            </div>
        </div>
    </div>

    @if(!is_null($charts['client_growth']))
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Client Growth Trend</span>
                    <span class="badge text-bg-light">CRM</span>
                </div>
                <div class="card-body">
                    <canvas id="clientGrowthChart" height="140"></canvas>
                </div>
            </div>
        </div>
    @endif

    @if(!is_null($charts['employee_workload']))
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Team Open Workload</span>
                    <span class="badge text-bg-light">Staff</span>
                </div>
                <div class="card-body">
                    <canvas id="workloadChart" height="140"></canvas>
                </div>
            </div>
        </div>
    @endif
</div>

<!-- Role-Specific Operational Queue + Deadlines + Activity -->
<div class="row g-3">
    @if($isEmployee)
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>My Active Task Queue</span>
                    <a href="{{ route('tasks.index') }}" class="btn btn-sm btn-outline-primary">Open Kanban Board</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Task</th>
                                <th>Client</th>
                                <th>Priority</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($myTasks as $task)
                                <tr>
                                    <td class="fw-semibold">{{ $task->title }}</td>
                                    <td>{{ $task->client?->name ?? 'Internal' }}</td>
                                    <td><span class="badge text-bg-{{ $task->priority === 'urgent' ? 'danger' : ($task->priority === 'high' ? 'warning' : 'light') }}">{{ ucfirst($task->priority) }}</span></td>
                                    <td>{{ $task->due_date?->format('d M Y') ?? '—' }}</td>
                                    <td><span class="badge text-bg-secondary">{{ str_replace('_', ' ', ucfirst($task->status)) }}</span></td>
                                    <td class="text-end">
                                        <a href="{{ route('tasks.show', $task) }}" class="btn btn-sm btn-outline-secondary">Open</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No active tasks assigned to you right now.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @elseif(auth()->user()->isManagerOrAbove())
        <div class="{{ $isExecutive ? 'col-lg-6' : 'col-12' }}">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Tasks Awaiting Review</span>
                    <a href="{{ route('tasks.index') }}" class="btn btn-sm btn-outline-primary">All Tasks</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Task</th>
                                <th>Assignee</th>
                                <th>Client</th>
                                <th class="text-end">Quick Review</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reviewQueue as $task)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $task->title }}</div>
                                        <small class="text-muted">Due {{ $task->due_date?->format('d M Y') ?? '—' }}</small>
                                    </td>
                                    <td>{{ $task->assignee?->name ?? 'Unassigned' }}</td>
                                    <td>{{ $task->client?->name ?? 'Internal' }}</td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-1">
                                            <a href="{{ route('tasks.show', $task) }}" class="btn btn-sm btn-outline-secondary">Inspect</a>
                                            <form method="POST" action="{{ route('tasks.status', $task) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="completed">
                                                <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">No tasks waiting in the review queue.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @if($isExecutive)
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Audits in Progress & Sign-Off</span>
                        <a href="{{ route('audits.index') }}" class="btn btn-sm btn-outline-primary">Audit Module</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Audit Engagement</th>
                                    <th>Lead</th>
                                    <th>Progress</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($auditsForReview as $audit)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $audit->title }}</div>
                                            <small class="text-muted">{{ $audit->client?->name ?? 'Client' }} · FY {{ $audit->financial_year }}</small>
                                        </td>
                                        <td>{{ $audit->leadUser?->name ?? 'Unassigned' }}</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress flex-grow-1" style="height: 6px; min-width: 70px;">
                                                    <div class="progress-bar bg-primary" style="width: {{ $audit->completion_percentage }}%"></div>
                                                </div>
                                                <small>{{ $audit->completion_percentage }}%</small>
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('audits.show', $audit) }}" class="btn btn-sm btn-outline-secondary">Review</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">No active audits requiring review.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    @endif

    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Upcoming Compliance Deadlines</span>
                <a href="{{ route('calendar.index') }}" class="btn btn-sm btn-outline-primary">Full Calendar</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Deadline</th>
                            <th>Client</th>
                            <th>Due Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($upcomingDeadlines as $deadline)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $deadline->title }}</div>
                                    <small class="text-muted">{{ strtoupper($deadline->category) }}</small>
                                </td>
                                <td>{{ $deadline->client?->name ?? 'All Applicable Clients' }}</td>
                                <td>
                                    <span class="fw-medium">{{ $deadline->due_date->format('d M Y') }}</span>
                                    <br><small class="text-muted">{{ $deadline->due_date->diffForHumans() }}</small>
                                </td>
                                <td><span class="badge text-bg-light border">{{ ucfirst($deadline->status) }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No upcoming compliance deadlines in the next 14 days.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>{{ $isEmployee ? 'My Recent Activity' : 'Recent Practice Activity' }}</span>
                @if(auth()->user()->isAdminOrPartner())
                    <a href="{{ route('settings.index') }}" class="btn btn-sm btn-outline-secondary">Audit Logs</a>
                @endif
            </div>
            <div class="list-group list-group-flush">
                @forelse($recentActivities as $activity)
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between">
                            <span class="fw-semibold small">{{ $activity->description }}</span>
                            <small class="text-muted">{{ $activity->created_at->diffForHumans() }}</small>
                        </div>
                        <small class="text-muted">{{ $activity->user?->name ?? 'System' }} · {{ ucfirst($activity->module) }}</small>
                    </div>
                @empty
                    <div class="list-group-item text-center text-muted py-4">No activity recorded yet.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
    const revenueCanvas = document.getElementById('revenueChart');
    if (revenueCanvas) {
        const revenueData = @json($charts['monthly_revenue']);
        new Chart(revenueCanvas, {
            type: 'bar',
            data: {
                labels: revenueData.labels,
                datasets: [
                    { label: 'Collected Revenue (₹)', data: revenueData.values, backgroundColor: 'rgba(13, 148, 136, 0.78)', borderColor: '#0d9488', borderWidth: 1.5, borderRadius: 6 }
                ]
            },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });
    }

    const complianceData = @json($charts['compliance_completion']);
    new Chart(document.getElementById('complianceChart'), {
        type: 'bar',
        data: {
            labels: complianceData.labels,
            datasets: [
                { label: 'Completed', data: complianceData.completed, backgroundColor: '#0d9488', borderRadius: 6 },
                { label: 'Pending', data: complianceData.pending, backgroundColor: '#f59e0b', borderRadius: 6 }
            ]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
    });

    const growthCanvas = document.getElementById('clientGrowthChart');
    if (growthCanvas) {
        const growthData = @json($charts['client_growth']);
        new Chart(growthCanvas, {
            type: 'line',
            data: {
                labels: growthData.labels,
                datasets: [{
                    label: 'New Clients',
                    data: growthData.values,
                    borderColor: '#0284c7',
                    backgroundColor: 'rgba(2, 132, 199, 0.14)',
                    fill: true,
                    tension: 0.35
                }]
            },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });
    }

    const workloadCanvas = document.getElementById('workloadChart');
    if (workloadCanvas) {
        const workloadData = @json($charts['employee_workload']);
        new Chart(workloadCanvas, {
            type: 'bar',
            data: {
                labels: workloadData.labels,
                datasets: [
                    { label: 'Active Tasks', data: workloadData.active, backgroundColor: '#1e3a5f', borderRadius: 6 },
                    { label: 'Completed Tasks', data: workloadData.completed, backgroundColor: '#0d9488', borderRadius: 6 }
                ]
            },
            options: { indexAxis: 'y', responsive: true, plugins: { legend: { position: 'bottom' } } }
        });
    }
</script>
@endpush
