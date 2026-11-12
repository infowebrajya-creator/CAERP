<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\ClientAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function __construct(
        private readonly ClientAccessService $clientAccess,
        private readonly ActivityLogger $logger
    ) {}

    public function index(Request $request): View
    {
        $query = $this->clientAccess->visibleClientsQuery($request->user())
            ->with('assignedCa');

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('client_code', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('pan', 'like', "%{$search}%")
                    ->orWhere('gstin', 'like', "%{$search}%");
            });
        }

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($assignedCa = $request->query('assigned_ca_id')) {
            $query->where('assigned_ca_id', $assignedCa);
        }

        $clients = $query->orderBy('name')->paginate(15)->withQueryString();
        $staff = User::staff()->active()->orderBy('name')->get();

        return view('clients.index', compact('clients', 'staff'));
    }

    public function create(): View
    {
        $staff = User::staff()->active()->orderBy('name')->get();
        $nextCode = $this->generateClientCode();

        return view('clients.create', compact('staff', 'nextCode'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateClient($request);

        $client = DB::transaction(function () use ($validated, $request) {
            $portalUserId = null;
            if ($request->boolean('create_portal_login') && ! empty($validated['email'])) {
                $portalPassword = $request->input('portal_password') ?: 'Client@123';
                $user = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'password' => Hash::make($portalPassword),
                    'role' => User::ROLE_CLIENT,
                    'phone' => $validated['mobile'] ?? null,
                    'is_active' => true,
                    'avatar_color' => '#0d9488',
                ]);
                $portalUserId = $user->id;
            }

            $client = Client::create([
                ...$validated,
                'user_id' => $portalUserId,
                'client_code' => ! empty($validated['client_code']) ? $validated['client_code'] : $this->generateClientCode(),
                'is_gst_registered' => $request->boolean('is_gst_registered'),
                'is_itr_applicable' => $request->boolean('is_itr_applicable'),
                'is_tds_applicable' => $request->boolean('is_tds_applicable'),
            ]);

            if (! empty($request->input('staff_ids'))) {
                $client->staff()->sync($request->input('staff_ids'));
            }

            return $client;
        });

        $this->logger->log('clients', 'created', "Added client {$client->name} ({$client->client_code})", $client);

        return redirect()->route('clients.show', $client)->with('success', 'Client profile created successfully.');
    }

    public function show(Request $request, Client $client): View
    {
        abort_unless($this->clientAccess->canAccessClient($request->user(), $client), 403);

        $tab = $request->query('tab', 'overview');
        $client->load([
            'assignedCa',
            'staff',
            'user',
            'gstReturns.assignee',
            'itrFilings.assignee',
            'tdsReturns.assignee',
            'deadlines.assignee',
            'documents.uploader',
            'documents.versions',
            'tasks.assignee',
            'invoices.payments',
            'audits.leadAuditor',
            'communications.user',
        ]);

        $staff = User::staff()->active()->orderBy('name')->get();

        return view('clients.show', compact('client', 'tab', 'staff'));
    }

    public function edit(Request $request, Client $client): View
    {
        abort_unless($this->clientAccess->canAccessClient($request->user(), $client), 403);
        $staff = User::staff()->active()->orderBy('name')->get();
        $client->load('staff');

        return view('clients.edit', compact('client', 'staff'));
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        abort_unless($this->clientAccess->canAccessClient($request->user(), $client), 403);
        $validated = $this->validateClient($request, $client);

        DB::transaction(function () use ($client, $validated, $request) {
            $client->update([
                ...$validated,
                'is_gst_registered' => $request->boolean('is_gst_registered'),
                'is_itr_applicable' => $request->boolean('is_itr_applicable'),
                'is_tds_applicable' => $request->boolean('is_tds_applicable'),
            ]);

            $client->staff()->sync($request->input('staff_ids', []));

            if ($request->boolean('create_portal_login') && ! $client->user_id && ! empty($validated['email'])) {
                $existingUser = User::where('email', $validated['email'])->first();
                if (! $existingUser) {
                    $user = User::create([
                        'name' => $validated['name'],
                        'email' => $validated['email'],
                        'password' => Hash::make($request->input('portal_password') ?: 'Client@123'),
                        'role' => User::ROLE_CLIENT,
                        'phone' => $validated['mobile'] ?? null,
                        'is_active' => true,
                        'avatar_color' => '#0d9488',
                    ]);
                    $client->update(['user_id' => $user->id]);
                }
            }
        });

        $this->logger->log('clients', 'updated', "Updated client {$client->name}", $client);

        return redirect()->route('clients.show', $client)->with('success', 'Client profile updated successfully.');
    }

    public function destroy(Request $request, Client $client): RedirectResponse
    {
        abort_unless($request->user()->isManagerOrAbove(), 403);
        $name = $client->name;
        $client->delete();

        $this->logger->log('clients', 'deleted', "Archived client {$name}");

        return redirect()->route('clients.index')->with('success', "Client {$name} archived.");
    }

    private function validateClient(Request $request, ?Client $client = null): array
    {
        return $request->validate([
            'client_code' => ['nullable', 'string', 'max:30', Rule::unique('clients', 'client_code')->ignore($client?->id)],
            'category' => ['required', Rule::in(array_keys(Client::CATEGORIES))],
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                $request->boolean('create_portal_login') && ! $client?->user_id
                    ? Rule::unique('users', 'email')
                    : 'nullable',
            ],
            'mobile' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:1000'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'pincode' => ['nullable', 'string', 'max:15'],
            'pan' => ['nullable', 'string', 'max:20'],
            'gstin' => ['nullable', 'string', 'max:20'],
            'tan' => ['nullable', 'string', 'max:20'],
            'cin' => ['nullable', 'string', 'max:30'],
            'business_type' => ['nullable', 'string', 'max:100'],
            'industry' => ['nullable', 'string', 'max:100'],
            'assigned_ca_id' => ['nullable', 'exists:users,id'],
            'registration_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(array_keys(Client::STATUSES))],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
    }

    private function generateClientCode(): string
    {
        $count = Client::withTrashed()->count() + 1;

        return sprintf('CA-CL-%04d', $count);
    }
}
