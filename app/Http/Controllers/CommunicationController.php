<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Communication;
use App\Services\ClientAccessService;
use App\Services\CommunicationService;
use App\Services\WhatsApp\WhatsAppProviderInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CommunicationController extends Controller
{
    public function __construct(
        private readonly ClientAccessService $clientAccess,
        private readonly CommunicationService $communicationService,
        private readonly WhatsAppProviderInterface $whatsApp
    ) {}

    public function index(Request $request): View
    {
        $visibleClientIds = $this->clientAccess->visibleClientsQuery($request->user())->pluck('id');
        $query = Communication::with(['client', 'user'])->whereIn('client_id', $visibleClientIds);

        if ($clientId = $request->query('client_id')) {
            $query->where('client_id', $clientId);
        }
        if ($channel = $request->query('channel')) {
            $query->where('channel', $channel);
        }

        $communications = $query->latest()->paginate(20)->withQueryString();
        $clients = Client::whereIn('id', $visibleClientIds)->orderBy('name')->get();
        $whatsAppConfigured = $this->whatsApp->isConfigured();

        return view('communications.index', compact('communications', 'clients', 'whatsAppConfigured'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'channel' => ['required', Rule::in(array_keys(Communication::CHANNELS))],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $client = Client::findOrFail($validated['client_id']);
        abort_unless($this->clientAccess->canAccessClient($request->user(), $client), 403);

        $comm = $this->communicationService->send($client, $validated, $request->user()->id, 'staff');

        $flash = match ($comm->status) {
            'not_configured' => 'Communication logged. Note: WhatsApp Business API credentials are not configured yet, so live WhatsApp delivery was not executed.',
            'failed' => 'Communication logged, but delivery reported an issue: '.($comm->meta['error'] ?? 'Check client contact details.'),
            default => 'Communication sent and logged successfully.',
        };

        return back()->with($comm->status === 'failed' ? 'warning' : 'success', $flash);
    }
}

