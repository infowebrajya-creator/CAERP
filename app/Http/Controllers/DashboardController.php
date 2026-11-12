<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Task;
use App\Services\ClientAccessService;
use App\Services\DashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService,
        private readonly ClientAccessService $clientAccess
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->isClient()) {
            return redirect()->route('portal.dashboard');
        }

        $data = $this->dashboardService->getDashboardData($user);

        return view('dashboard.index', $data);
    }

    public function search(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $user = $request->user();
        $visibleClientIds = $this->clientAccess->visibleClientsQuery($user)->pluck('id');

        $clients = collect();
        $tasks = collect();
        $invoices = collect();

        if ($q !== '') {
            $clients = Client::whereIn('id', $visibleClientIds)
                ->where(function ($query) use ($q) {
                    $query->where('name', 'like', "%{$q}%")
                        ->orWhere('client_code', 'like', "%{$q}%")
                        ->orWhere('pan', 'like', "%{$q}%")
                        ->orWhere('gstin', 'like', "%{$q}%");
                })
                ->limit(15)
                ->get();

            $tasks = $this->clientAccess->visibleTasksQuery($user)
                ->with('client')
                ->where('title', 'like', "%{$q}%")
                ->limit(15)
                ->get();

            if ($user->isManagerOrAbove()) {
                $invoices = Invoice::with('client')
                    ->whereIn('client_id', $visibleClientIds)
                    ->where('invoice_number', 'like', "%{$q}%")
                    ->limit(15)
                    ->get();
            }
        }

        return view('dashboard.search', compact('q', 'clients', 'tasks', 'invoices'));
    }

    public function markNotificationRead(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->markAsRead();
            $actionUrl = $notification->data['action_url'] ?? null;
            if ($actionUrl) {
                return redirect($actionUrl);
            }
        }

        return back();
    }

    public function markAllNotificationsRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'All notifications marked as read.');
    }
}

