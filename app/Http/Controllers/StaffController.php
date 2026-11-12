<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Task;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger) {}

    public function index(Request $request): View
    {
        $query = User::staff()->withCount([
            'assignedClients',
            'assignedTasks as total_tasks_count',
            'assignedTasks as open_tasks_count' => fn ($q) => $q->where('status', '!=', 'completed'),
            'assignedTasks as completed_tasks_count' => fn ($q) => $q->where('status', 'completed'),
            'assignedTasks as overdue_tasks_count' => fn ($q) => $q->where('status', '!=', 'completed')->whereDate('due_date', '<', today()),
        ]);

        if ($role = $request->query('role')) {
            $query->where('role', $role);
        }
        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('employee_code', 'like', "%{$search}%")
                    ->orWhere('designation', 'like', "%{$search}%");
            });
        }

        $staffMembers = $query->orderBy('name')->paginate(15)->withQueryString();
        $reviewQueue = Task::with(['client', 'assignee'])
            ->where('status', 'under_review')
            ->orderBy('due_date')
            ->get();
        $clients = Client::orderBy('name')->get();

        return view('staff.index', compact('staffMembers', 'reviewQueue', 'clients'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isManagerOrAbove(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', Rule::in(User::STAFF_ROLES)],
            'employee_code' => ['nullable', 'string', 'max:30', 'unique:users,employee_code'],
            'phone' => ['nullable', 'string', 'max:20'],
            'designation' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:100'],
            'avatar_color' => ['nullable', 'string', 'max:20'],
        ]);

        $employee = User::create([
            ...$validated,
            'password' => Hash::make($validated['password']),
            'employee_code' => ! empty($validated['employee_code']) ? $validated['employee_code'] : sprintf('EMP-%03d', User::staff()->count() + 1),
            'is_active' => true,
            'avatar_color' => ! empty($validated['avatar_color']) ? $validated['avatar_color'] : '#1e3a5f',
        ]);

        $this->logger->log('staff', 'created', "Added staff member {$employee->name} ({$employee->roleLabel()})", $employee);

        return back()->with('success', "Staff member {$employee->name} added.");
    }

    public function update(Request $request, User $staff): RedirectResponse
    {
        abort_unless($request->user()->isManagerOrAbove(), 403);
        abort_unless($staff->isStaff(), 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($staff->id)],
            'role' => ['required', Rule::in(User::STAFF_ROLES)],
            'employee_code' => ['nullable', 'string', 'max:30', Rule::unique('users', 'employee_code')->ignore($staff->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'designation' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        $payload = collect($validated)->except(['password', 'is_active'])->toArray();
        $payload['is_active'] = $request->boolean('is_active', true);
        if (! empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        $staff->update($payload);
        $this->logger->log('staff', 'updated', "Updated staff member {$staff->name}", $staff);

        return back()->with('success', 'Employee profile updated.');
    }

    public function assignClients(Request $request, User $staff): RedirectResponse
    {
        abort_unless($request->user()->isManagerOrAbove(), 403);
        $clientIds = $request->input('client_ids', []);
        $staff->secondaryClients()->sync($clientIds);

        return back()->with('success', "Updated client assignments for {$staff->name}.");
    }
}
