<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\TaskAttachment;
use App\Models\TaskComment;
use App\Models\User;
use App\Notifications\PracticeAlertNotification;
use App\Services\ActivityLogger;
use App\Services\ClientAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TaskController extends Controller
{
    public function __construct(
        private readonly ClientAccessService $clientAccess,
        private readonly ActivityLogger $logger
    ) {}

    public function index(Request $request): View
    {
        $visibleClientIds = $this->clientAccess->visibleClientsQuery($request->user())->pluck('id');
        $viewMode = $request->query('view', 'kanban');

        $query = $this->clientAccess->visibleTasksQuery($request->user())
            ->with(['client', 'assignee', 'creator']);

        if ($clientId = $request->query('client_id')) {
            $query->where('client_id', $clientId);
        }
        if ($assignedTo = $request->query('assigned_to')) {
            $query->where('assigned_to', $assignedTo);
        }
        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }
        if ($priority = $request->query('priority')) {
            $query->where('priority', $priority);
        }
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($search = trim((string) $request->query('search', ''))) {
            $query->where('title', 'like', "%{$search}%");
        }

        $tasks = $viewMode === 'table'
            ? (clone $query)->orderBy('due_date')->paginate(15)->withQueryString()
            : (clone $query)->orderBy('due_date')->get();

        $clients = Client::whereIn('id', $visibleClientIds)->orderBy('name')->get();
        $staff = User::staff()->active()->orderBy('name')->get();

        return view('tasks.index', compact('tasks', 'viewMode', 'clients', 'staff'));
    }

    public function show(Request $request, Task $task): View
    {
        abort_unless($this->clientAccess->canAccessTask($request->user(), $task), 403);

        $task->load(['client', 'assignee', 'creator', 'approver', 'comments.user', 'attachments.user', 'activities.user']);
        $staff = User::staff()->active()->orderBy('name')->get();
        $clients = $this->clientAccess->visibleClientsQuery($request->user())->orderBy('name')->get();

        return view('tasks.show', compact('task', 'staff', 'clients'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'category' => ['required', Rule::in(array_keys(Task::CATEGORIES))],
            'priority' => ['required', Rule::in(array_keys(Task::PRIORITIES))],
            'status' => ['required', Rule::in(array_keys(Task::STATUSES))],
            'due_date' => ['nullable', 'date'],
        ]);

        if (!empty($validated['client_id'])) {
            $client = Client::findOrFail($validated['client_id']);
            abort_unless($this->clientAccess->canAccessClient($request->user(), $client), 403);
        }

        $task = Task::create([
            ...$validated,
            'created_by' => $request->user()->id,
            'completed_at' => $validated['status'] === 'completed' ? now() : null,
        ]);

        TaskActivity::create([
            'task_id' => $task->id,
            'user_id' => $request->user()->id,
            'action' => 'created',
            'details' => "Task created with priority {$task->priorityLabel()}",
        ]);

        if ($task->assignee && $task->assignee->id !== $request->user()->id) {
            $task->assignee->notify(new PracticeAlertNotification(
                "Task Assigned: {$task->title}",
                'Due '.($task->due_date?->format('d M Y') ?? 'No due date'),
                'task',
                route('tasks.show', $task)
            ));
        }

        $this->logger->log('tasks', 'created', "Created task '{$task->title}'", $task);

        return back()->with('success', 'Task created successfully.');
    }

    public function update(Request $request, Task $task): RedirectResponse
    {
        abort_unless($this->clientAccess->canAccessTask($request->user(), $task), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'category' => ['required', Rule::in(array_keys(Task::CATEGORIES))],
            'priority' => ['required', Rule::in(array_keys(Task::PRIORITIES))],
            'status' => ['required', Rule::in(array_keys(Task::STATUSES))],
            'due_date' => ['nullable', 'date'],
        ]);

        $oldStatus = $task->status;
        $extra = [
            'completed_at' => $validated['status'] === 'completed' ? ($task->completed_at ?? now()) : null,
        ];
        if ($validated['status'] === 'completed' && $oldStatus === 'under_review') {
            $extra['approved_by'] = $request->user()->id;
            $extra['approved_at'] = now();
        }

        $task->update([...$validated, ...$extra]);

        TaskActivity::create([
            'task_id' => $task->id,
            'user_id' => $request->user()->id,
            'action' => 'updated',
            'details' => $oldStatus !== $task->status
                ? 'Status changed from '.(Task::STATUSES[$oldStatus] ?? $oldStatus)." to {$task->statusLabel()}"
                : 'Task details updated',
        ]);

        $this->logger->log('tasks', 'updated', "Updated task '{$task->title}' -> {$task->statusLabel()}", $task);

        return back()->with('success', 'Task updated.');
    }

    public function updateStatus(Request $request, Task $task): JsonResponse|RedirectResponse
    {
        abort_unless($this->clientAccess->canAccessTask($request->user(), $task), 403);

        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(Task::STATUSES))],
        ]);

        $oldStatus = $task->status;
        $task->update([
            'status' => $validated['status'],
            'completed_at' => $validated['status'] === 'completed' ? now() : null,
            'approved_by' => ($validated['status'] === 'completed' && $oldStatus === 'under_review') ? $request->user()->id : $task->approved_by,
            'approved_at' => ($validated['status'] === 'completed' && $oldStatus === 'under_review') ? now() : $task->approved_at,
        ]);

        TaskActivity::create([
            'task_id' => $task->id,
            'user_id' => $request->user()->id,
            'action' => 'status_changed',
            'details' => "Moved to {$task->statusLabel()}",
        ]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'status' => $task->status, 'label' => $task->statusLabel()]);
        }

        return back()->with('success', "Task moved to {$task->statusLabel()}.");
    }

    public function addComment(Request $request, Task $task): RedirectResponse
    {
        abort_unless($this->clientAccess->canAccessTask($request->user(), $task), 403);

        $validated = $request->validate([
            'comment' => ['required', 'string', 'max:3000'],
            'is_internal' => ['nullable', 'boolean'],
        ]);

        TaskComment::create([
            'task_id' => $task->id,
            'user_id' => $request->user()->id,
            'comment' => $validated['comment'],
            'is_internal' => $request->boolean('is_internal', true),
        ]);

        TaskActivity::create([
            'task_id' => $task->id,
            'user_id' => $request->user()->id,
            'action' => 'commented',
            'details' => 'Added a comment',
        ]);

        return back()->with('success', 'Comment posted.');
    }

    public function addAttachment(Request $request, Task $task): RedirectResponse
    {
        abort_unless($this->clientAccess->canAccessTask($request->user(), $task), 403);

        $request->validate([
            'file' => ['required', 'file', 'max:20480'],
        ]);

        $file = $request->file('file');
        $path = $file->store("tasks/task_{$task->id}", 'local');

        TaskAttachment::create([
            'task_id' => $task->id,
            'user_id' => $request->user()->id,
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
        ]);

        TaskActivity::create([
            'task_id' => $task->id,
            'user_id' => $request->user()->id,
            'action' => 'attachment_added',
            'details' => "Attached {$file->getClientOriginalName()}",
        ]);

        return back()->with('success', 'Attachment uploaded.');
    }

    public function downloadAttachment(Request $request, Task $task, TaskAttachment $attachment): StreamedResponse
    {
        abort_unless($this->clientAccess->canAccessTask($request->user(), $task), 403);
        abort_unless((int) $attachment->task_id === (int) $task->id, 404);
        abort_unless(Storage::disk('local')->exists($attachment->file_path), 404);

        return Storage::disk('local')->download($attachment->file_path, $attachment->original_filename);
    }

    public function destroy(Request $request, Task $task): RedirectResponse
    {
        abort_unless($this->clientAccess->canAccessTask($request->user(), $task), 403);

        $task->delete();

        return redirect()->route('tasks.index')->with('success', 'Task deleted.');
    }
}

