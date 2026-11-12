@extends('layouts.app')

@section('title', 'Staff & Workload')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h4 fw-bold mb-1" style="color: #1e3a5f;">Employee &amp; Staff Management</h1>
        <p class="text-muted small mb-0">Manage CA partners, managers, accountants, client allocations, workload metrics, and review queues.</p>
    </div>
    @if(auth()->user()->isManagerOrAbove())
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addStaffModal">
            <i class="bi bi-person-plus me-1"></i>Add Staff Member
        </button>
    @endif
</div>

<div class="ca-card p-3 mb-3">
    <form method="GET" action="{{ route('staff.index') }}" class="row g-2 align-items-end">
        <div class="col-md-5"><label class="form-label small text-muted mb-1">Search</label><input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Name, code, email, designation..."></div>
        <div class="col-md-4"><label class="form-label small text-muted mb-1">Role</label><select name="role" class="form-select form-select-sm"><option value="">All Roles</option>@foreach(\App\Models\User::STAFF_ROLES as $r)<option value="{{ $r }}" @selected(request('role') === $r)>{{ \App\Models\User::ROLES[$r] }}</option>@endforeach</select></div>
        <div class="col-md-3 d-flex gap-1"><button type="submit" class="btn btn-sm btn-primary flex-grow-1">Filter</button><a href="{{ route('staff.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a></div>
    </form>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="ca-card overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Staff Member</th>
                            <th>Role &amp; Dept</th>
                            <th>Clients</th>
                            <th>Workload (Open / Done)</th>
                            <th>Completion</th>
                            <th>Status</th>
                            @if(auth()->user()->isManagerOrAbove())<th class="text-end">Actions</th>@endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($staffMembers as $member)
                            @php($rate = $member->total_tasks_count > 0 ? round(($member->completed_tasks_count / $member->total_tasks_count) * 100) : 0)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="ca-avatar" style="background-color: {{ $member->avatar_color ?? '#1e3a5f' }};">{{ $member->initials() }}</span>
                                        <div>
                                            <div class="fw-semibold">{{ $member->name }}</div>
                                            <div class="small text-muted">{{ $member->employee_code }} · {{ $member->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="small">
                                    <span class="badge badge-soft-primary">{{ $member->roleLabel() }}</span>
                                    <div class="text-muted mt-1">{{ $member->designation ?? $member->department ?? '—' }}</div>
                                </td>
                                <td class="small fw-semibold">{{ $member->assigned_clients_count }}</td>
                                <td class="small">
                                    <span class="text-primary fw-semibold">{{ $member->open_tasks_count }} open</span> /
                                    <span class="text-success">{{ $member->completed_tasks_count }} done</span>
                                    @if($member->overdue_tasks_count > 0)
                                        <div class="text-danger">{{ $member->overdue_tasks_count }} overdue</div>
                                    @endif
                                </td>
                                <td style="min-width: 110px;">
                                    <div class="small fw-semibold">{{ $rate }}%</div>
                                    <div class="progress" style="height: 6px;"><div class="progress-bar bg-success" style="width: {{ $rate }}%;"></div></div>
                                </td>
                                <td><x-status-badge :status="$member->is_active ? 'active' : 'inactive'" /></td>
                                @if(auth()->user()->isManagerOrAbove())
                                    <td class="text-end text-nowrap">
                                        <button class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#editStaffModal{{ $member->id }}"><i class="bi bi-pencil"></i></button>
                                        <button class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#assignClientsModal{{ $member->id }}" title="Assign Clients"><i class="bi bi-buildings"></i></button>
                                    </td>
                                @endif
                            </tr>

                            @if(auth()->user()->isManagerOrAbove())
                                <div class="modal fade" id="editStaffModal{{ $member->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <form action="{{ route('staff.update', $member) }}" method="POST" class="modal-content">
                                            @csrf @method('PUT')
                                            <div class="modal-header"><h5 class="modal-title h6 fw-bold">Edit {{ $member->name }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                            <div class="modal-body row g-2">
                                                <div class="col-6"><label class="form-label small fw-semibold">Name</label><input type="text" name="name" value="{{ $member->name }}" class="form-control form-control-sm" required></div>
                                                <div class="col-6"><label class="form-label small fw-semibold">Email</label><input type="email" name="email" value="{{ $member->email }}" class="form-control form-control-sm" required></div>
                                                <div class="col-6"><label class="form-label small fw-semibold">Role</label><select name="role" class="form-select form-select-sm">@foreach(\App\Models\User::STAFF_ROLES as $r)<option value="{{ $r }}" @selected($member->role === $r)>{{ \App\Models\User::ROLES[$r] }}</option>@endforeach</select></div>
                                                <div class="col-6"><label class="form-label small fw-semibold">Employee Code</label><input type="text" name="employee_code" value="{{ $member->employee_code }}" class="form-control form-control-sm"></div>
                                                <div class="col-6"><label class="form-label small fw-semibold">Designation</label><input type="text" name="designation" value="{{ $member->designation }}" class="form-control form-control-sm"></div>
                                                <div class="col-6"><label class="form-label small fw-semibold">Department</label><input type="text" name="department" value="{{ $member->department }}" class="form-control form-control-sm"></div>
                                                <div class="col-6"><label class="form-label small fw-semibold">Phone</label><input type="text" name="phone" value="{{ $member->phone }}" class="form-control form-control-sm"></div>
                                                <div class="col-6"><label class="form-label small fw-semibold">New Password (optional)</label><input type="password" name="password" class="form-control form-control-sm"></div>
                                                <div class="col-12"><div class="form-check mt-1"><input class="form-check-input" type="checkbox" name="is_active" value="1" @checked($member->is_active)><label class="form-check-label small">Active Account</label></div></div>
                                            </div>
                                            <div class="modal-footer"><button type="submit" class="btn btn-sm btn-primary">Save</button></div>
                                        </form>
                                    </div>
                                </div>

                                <div class="modal fade" id="assignClientsModal{{ $member->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <form action="{{ route('staff.assign_clients', $member) }}" method="POST" class="modal-content">
                                            @csrf
                                            <div class="modal-header"><h5 class="modal-title h6 fw-bold">Assign Secondary Clients — {{ $member->name }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                            <div class="modal-body">
                                                @php($assignedIds = $member->secondaryClients->pluck('id')->toArray())
                                                <select name="client_ids[]" class="form-select" multiple size="8">
                                                    @foreach($clients as $c)<option value="{{ $c->id }}" @selected(in_array($c->id, $assignedIds))>{{ $c->name }}</option>@endforeach
                                                </select>
                                            </div>
                                            <div class="modal-footer"><button type="submit" class="btn btn-sm btn-primary">Update Assignments</button></div>
                                        </form>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($staffMembers->hasPages())<div class="p-3 border-top">{{ $staffMembers->links() }}</div>@endif
        </div>
    </div>

    <div class="col-lg-4">
        <div class="ca-card p-3">
            <h2 class="h6 fw-bold mb-3"><i class="bi bi-clipboard-check me-2 text-info"></i>Task Review &amp; Approval Queue ({{ $reviewQueue->count() }})</h2>
            @forelse($reviewQueue as $task)
                <div class="border rounded p-2 mb-2 bg-light">
                    <div class="fw-semibold small"><a href="{{ route('tasks.show', $task) }}" class="text-decoration-none">{{ $task->title }}</a></div>
                    <div class="text-muted mb-2" style="font-size: 0.75rem;">{{ $task->assignee?->name ?? 'Unassigned' }} · {{ $task->client?->name ?? 'Internal' }}</div>
                    <form action="{{ route('tasks.status', $task) }}" method="POST" class="d-flex gap-1">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="completed">
                        <button type="submit" class="btn btn-sm btn-success flex-grow-1"><i class="bi bi-check2 me-1"></i>Approve &amp; Complete</button>
                        <a href="{{ route('tasks.show', $task) }}" class="btn btn-sm btn-light border">Inspect</a>
                    </form>
                </div>
            @empty
                <div class="text-muted small text-center py-4">No tasks awaiting review.</div>
            @endforelse
        </div>
    </div>
</div>

@if(auth()->user()->isManagerOrAbove())
<div class="modal fade" id="addStaffModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('staff.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header"><h5 class="modal-title h6 fw-bold">Add Staff Member</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body row g-2">
                <div class="col-6"><label class="form-label small fw-semibold">Full Name *</label><input type="text" name="name" class="form-control form-control-sm" required></div>
                <div class="col-6"><label class="form-label small fw-semibold">Email *</label><input type="email" name="email" class="form-control form-control-sm" required></div>
                <div class="col-6"><label class="form-label small fw-semibold">Role *</label><select name="role" class="form-select form-select-sm">@foreach(\App\Models\User::STAFF_ROLES as $r)<option value="{{ $r }}" @selected($r === 'employee')>{{ \App\Models\User::ROLES[$r] }}</option>@endforeach</select></div>
                <div class="col-6"><label class="form-label small fw-semibold">Initial Password *</label><input type="password" name="password" value="password" class="form-control form-control-sm" required></div>
                <div class="col-6"><label class="form-label small fw-semibold">Designation</label><input type="text" name="designation" class="form-control form-control-sm" placeholder="Senior Article / CA"></div>
                <div class="col-6"><label class="form-label small fw-semibold">Department</label><input type="text" name="department" class="form-control form-control-sm" placeholder="Direct Tax / GST / Audit"></div>
                <div class="col-6"><label class="form-label small fw-semibold">Phone</label><input type="text" name="phone" class="form-control form-control-sm"></div>
                <div class="col-6"><label class="form-label small fw-semibold">Employee Code</label><input type="text" name="employee_code" class="form-control form-control-sm" placeholder="Auto-generated if empty"></div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-sm btn-primary">Create Employee</button></div>
        </form>
    </div>
</div>
@endif
@endsection

