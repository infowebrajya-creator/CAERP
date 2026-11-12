@extends('layouts.app')

@section('title', 'Tasks & Workflow')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h4 fw-bold mb-1" style="color: #1e3a5f;">Task &amp; Workflow Management</h1>
        <p class="text-muted small mb-0">Drag and drop cards across stages in Kanban view or manage bulk assignments in Table view.</p>
    </div>
    <div class="d-flex gap-2">
        <div class="btn-group btn-group-sm">
            <a href="{{ route('tasks.index', array_merge(request()->query(), ['view' => 'kanban'])) }}" class="btn {{ $viewMode === 'kanban' ? 'btn-primary' : 'btn-outline-primary' }}"><i class="bi bi-kanban me-1"></i>Kanban</a>
            <a href="{{ route('tasks.index', array_merge(request()->query(), ['view' => 'table'])) }}" class="btn {{ $viewMode === 'table' ? 'btn-primary' : 'btn-outline-primary' }}"><i class="bi bi-table me-1"></i>Table</a>
        </div>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addTaskModal"><i class="bi bi-plus-lg me-1"></i>New Task</button>
    </div>
</div>

<div class="ca-card p-3 mb-3">
    <form method="GET" action="{{ route('tasks.index') }}" class="row g-2 align-items-end">
        <input type="hidden" name="view" value="{{ $viewMode }}">
        <div class="col-md-3"><input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Search task title..."></div>
        <div class="col-md-2"><select name="client_id" class="form-select form-select-sm"><option value="">All Clients</option>@foreach($clients as $c)<option value="{{ $c->id }}" @selected((string) request('client_id') === (string) $c->id)>{{ $c->name }}</option>@endforeach</select></div>
        <div class="col-md-2"><select name="assigned_to" class="form-select form-select-sm"><option value="">All Assignees</option>@foreach($staff as $m)<option value="{{ $m->id }}" @selected((string) request('assigned_to') === (string) $m->id)>{{ $m->name }}</option>@endforeach</select></div>
        <div class="col-md-2"><select name="category" class="form-select form-select-sm"><option value="">All Categories</option>@foreach(\App\Models\Task::CATEGORIES as $k => $v)<option value="{{ $k }}" @selected(request('category') === $k)>{{ $v }}</option>@endforeach</select></div>
        <div class="col-md-2"><select name="priority" class="form-select form-select-sm"><option value="">All Priorities</option>@foreach(\App\Models\Task::PRIORITIES as $k => $v)<option value="{{ $k }}" @selected(request('priority') === $k)>{{ $v }}</option>@endforeach</select></div>
        <div class="col-md-1 d-grid"><button type="submit" class="btn btn-sm btn-primary">Filter</button></div>
    </form>
</div>

@if($viewMode === 'kanban')
    <div class="row g-3">
        @foreach(\App\Models\Task::STATUSES as $statusKey => $statusLabel)
            @php($colTasks = $tasks->where('status', $statusKey))
            <div class="col-md-6 col-xl-3">
                <div class="kanban-col">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="fw-bold small" style="color: #1e3a5f;">{{ $statusLabel }}</span>
                        <span class="badge rounded-pill bg-white text-dark border">{{ $colTasks->count() }}</span>
                    </div>
                    <div class="kanban-dropzone" data-status="{{ $statusKey }}" style="min-height: 350px;">
                        @foreach($colTasks as $task)
                            <div class="kanban-card" draggable="true" data-task-id="{{ $task->id }}">
                                <div class="d-flex justify-content-between align-items-start gap-1 mb-1">
                                    <span class="badge badge-soft-info">{{ \App\Models\Task::CATEGORIES[$task->category] ?? $task->category }}</span>
                                    <x-status-badge :status="$task->priority" :label="$task->priorityLabel()" />
                                </div>
                                <a href="{{ route('tasks.show', $task) }}" class="fw-semibold d-block text-decoration-none text-dark mb-1">{{ $task->title }}</a>
                                <div class="small text-muted mb-2">{{ $task->client?->name ?? 'Internal Practice' }}</div>
                                <div class="d-flex justify-content-between align-items-center pt-2 border-top small">
                                    <span class="text-muted">{{ $task->assignee?->name ?? 'Unassigned' }}</span>
                                    <span class="{{ $task->isOverdue() ? 'text-danger fw-semibold' : 'text-muted' }}">
                                        {{ $task->due_date?->format('d M') ?? 'No due' }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@else
    <div class="ca-card overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Task</th><th>Client</th><th>Category</th><th>Priority</th><th>Assignee</th><th>Due Date</th><th>Status</th><th class="text-end">Action</th></tr></thead>
                <tbody>
                    @forelse($tasks as $task)
                        <tr>
                            <td><a href="{{ route('tasks.show', $task) }}" class="fw-semibold text-decoration-none">{{ $task->title }}</a></td>
                            <td class="small">{{ $task->client?->name ?? 'Internal' }}</td>
                            <td class="small">{{ \App\Models\Task::CATEGORIES[$task->category] ?? $task->category }}</td>
                            <td><x-status-badge :status="$task->priority" :label="$task->priorityLabel()" /></td>
                            <td class="small">{{ $task->assignee?->name ?? 'Unassigned' }}</td>
                            <td class="small {{ $task->isOverdue() ? 'text-danger fw-bold' : '' }}">{{ $task->due_date?->format('d M Y') ?? '—' }}</td>
                            <td><x-status-badge :status="$task->status" :label="$task->statusLabel()" /></td>
                            <td class="text-end"><a href="{{ route('tasks.show', $task) }}" class="btn btn-sm btn-light border">Open</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">No tasks found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($tasks instanceof \Illuminate\Pagination\LengthAwarePaginator && $tasks->hasPages())
            <div class="p-3 border-top">{{ $tasks->links() }}</div>
        @endif
    </div>
@endif

<div class="modal fade" id="addTaskModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('tasks.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header"><h5 class="modal-title h6 fw-bold">Create New Task</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body row g-3">
                <div class="col-md-8"><label class="form-label small fw-semibold">Task Title *</label><input type="text" name="title" class="form-control form-control-sm" required></div>
                <div class="col-md-4"><label class="form-label small fw-semibold">Category *</label><select name="category" class="form-select form-select-sm">@foreach(\App\Models\Task::CATEGORIES as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label small fw-semibold">Client</label><select name="client_id" class="form-select form-select-sm"><option value="">Internal Practice</option>@foreach($clients as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label small fw-semibold">Assign To</label><select name="assigned_to" class="form-select form-select-sm"><option value="">Unassigned</option>@foreach($staff as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label small fw-semibold">Due Date</label><input type="date" name="due_date" class="form-control form-control-sm"></div>
                <div class="col-md-6"><label class="form-label small fw-semibold">Priority</label><select name="priority" class="form-select form-select-sm">@foreach(\App\Models\Task::PRIORITIES as $k => $v)<option value="{{ $k }}" @selected($k === 'medium')>{{ $v }}</option>@endforeach</select></div>
                <div class="col-md-6"><label class="form-label small fw-semibold">Status</label><select name="status" class="form-select form-select-sm">@foreach(\App\Models\Task::STATUSES as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
                <div class="col-12"><label class="form-label small fw-semibold">Description</label><textarea name="description" rows="3" class="form-control form-control-sm"></textarea></div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-sm btn-primary">Create Task</button></div>
        </form>
    </div>
</div>
@endsection

