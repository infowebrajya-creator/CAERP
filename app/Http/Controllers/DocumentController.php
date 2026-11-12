<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Notifications\PracticeAlertNotification;
use App\Services\ActivityLogger;
use App\Services\ClientAccessService;
use App\Services\DocumentStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function __construct(
        private readonly ClientAccessService $clientAccess,
        private readonly DocumentStorageService $storageService,
        private readonly ActivityLogger $logger
    ) {}

    public function index(Request $request): View
    {
        $visibleClientIds = $this->clientAccess->visibleClientsQuery($request->user())->pluck('id');
        $query = Document::with(['client', 'requester', 'uploader', 'reviewer', 'versions.uploader'])
            ->whereIn('client_id', $visibleClientIds);

        if ($clientId = $request->query('client_id')) {
            $query->where('client_id', $clientId);
        }
        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhereHas('client', fn ($cq) => $cq->where('name', 'like', "%{$search}%"));
            });
        }

        $documents = $query->latest()->paginate(15)->withQueryString();
        $clients = Client::whereIn('id', $visibleClientIds)->orderBy('name')->get();

        $summary = [
            'requested' => Document::whereIn('client_id', $visibleClientIds)->where('status', 'requested')->count(),
            'uploaded' => Document::whereIn('client_id', $visibleClientIds)->where('status', 'uploaded')->count(),
            'approved' => Document::whereIn('client_id', $visibleClientIds)->where('status', 'approved')->count(),
            'rejected' => Document::whereIn('client_id', $visibleClientIds)->where('status', 'rejected')->count(),
        ];

        return view('documents.index', compact('documents', 'clients', 'summary'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(array_keys(Document::CATEGORIES))],
            'due_date' => ['nullable', 'date'],
            'request_notes' => ['nullable', 'string', 'max:2000'],
            'is_shared_with_client' => ['nullable', 'boolean'],
            'file' => ['nullable', 'file', 'max:20480'],
        ]);

        $client = Client::findOrFail($validated['client_id']);
        abort_unless($this->clientAccess->canAccessClient($request->user(), $client), 403);

        $doc = Document::create([
            'client_id' => $client->id,
            'title' => $validated['title'],
            'category' => $validated['category'],
            'status' => 'requested',
            'requested_by' => $request->user()->id,
            'due_date' => $validated['due_date'] ?? null,
            'request_notes' => $validated['request_notes'] ?? null,
            'is_shared_with_client' => $request->boolean('is_shared_with_client', true),
        ]);

        if ($request->hasFile('file')) {
            $this->storageService->storeUpload($doc, $request->file('file'), $request->user()->id, 'Initial upload');
        } else {
            if ($client->user) {
                $client->user->notify(new PracticeAlertNotification(
                    "Document Requested: {$doc->title}",
                    'Please upload the requested document via your Client Portal.',
                    'document_request',
                    route('portal.documents')
                ));
            }
            $this->logger->log('documents', 'requested', "Requested document '{$doc->title}' from {$client->name}", $doc);
        }

        return back()->with('success', $request->hasFile('file') ? 'Document uploaded successfully.' : 'Document request created.');
    }

    public function uploadVersion(Request $request, Document $document): RedirectResponse
    {
        abort_unless($this->clientAccess->canAccessClient($request->user(), $document->client), 403);

        $request->validate([
            'file' => ['required', 'file', 'max:20480'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->storageService->storeUpload($document, $request->file('file'), $request->user()->id, $request->input('notes'));

        return back()->with('success', "Uploaded new version v{$document->fresh()->current_version}.");
    }

    public function approve(Request $request, Document $document): RedirectResponse
    {
        abort_unless($this->clientAccess->canAccessClient($request->user(), $document->client), 403);
        $this->storageService->approve($document, $request->user()->id);

        return back()->with('success', 'Document approved.');
    }

    public function reject(Request $request, Document $document): RedirectResponse
    {
        abort_unless($this->clientAccess->canAccessClient($request->user(), $document->client), 403);
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        $this->storageService->reject($document, $request->user()->id, $validated['rejection_reason']);

        if ($document->client->user) {
            $document->client->user->notify(new PracticeAlertNotification(
                "Document Rejected: {$document->title}",
                $validated['rejection_reason'],
                'document_rejected',
                route('portal.documents')
            ));
        }

        return back()->with('success', 'Document marked as rejected.');
    }

    public function download(Request $request, Document $document, ?DocumentVersion $version = null): StreamedResponse
    {
        abort_unless($this->clientAccess->canAccessClient($request->user(), $document->client), 403);
        if ($version) {
            abort_unless((int) $version->document_id === (int) $document->id, 404);
        }

        return $this->storageService->download($document, $version);
    }

    public function destroy(Request $request, Document $document): RedirectResponse
    {
        abort_unless($this->clientAccess->canAccessClient($request->user(), $document->client), 403);
        $document->delete();

        return back()->with('success', 'Document record deleted.');
    }
}

