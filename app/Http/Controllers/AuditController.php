<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use App\Models\AuditWorkingPaper;
use App\Models\Client;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\ClientAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditController extends Controller
{
    public function __construct(
        private readonly ClientAccessService $clientAccess,
        private readonly ActivityLogger $logger
    ) {}

    public function index(Request $request): View
    {
        $visibleClientIds = $this->clientAccess->visibleClientsQuery($request->user())->pluck('id');
        $query = $this->clientAccess->visibleAuditsQuery($request->user())
            ->with(['client', 'leadAuditor', 'teamMembers', 'workingPapers']);

        if ($clientId = $request->query('client_id')) {
            $query->where('client_id', $clientId);
        }
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($type = $request->query('audit_type')) {
            $query->where('audit_type', $type);
        }

        $audits = $query->orderBy('due_date')->paginate(12)->withQueryString();
        $clients = Client::whereIn('id', $visibleClientIds)->orderBy('name')->get();
        $staff = User::staff()->active()->orderBy('name')->get();

        return view('audits.index', compact('audits', 'clients', 'staff'));
    }

    public function show(Request $request, Audit $audit): View
    {
        abort_unless($this->clientAccess->canAccessAudit($request->user(), $audit), 403);
        $audit->load(['client', 'leadAuditor', 'approver', 'teamMembers', 'workingPapers.uploader']);
        $staff = User::staff()->active()->orderBy('name')->get();

        return view('audits.show', compact('audit', 'staff'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'title' => ['required', 'string', 'max:255'],
            'audit_type' => ['required', Rule::in(array_keys(Audit::AUDIT_TYPES))],
            'financial_year' => ['required', 'string', 'max:20'],
            'lead_auditor_id' => ['nullable', 'exists:users,id'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(array_keys(Audit::STATUSES))],
            'observations' => ['nullable', 'string', 'max:5000'],
            'checklist_items' => ['nullable', 'string'],
        ]);

        $lines = array_filter(array_map('trim', explode("\n", (string) $request->input('checklist_items'))));
        if (empty($lines)) {
            $lines = [
                'Engagement Letter & Independence Confirmation',
                'Trial Balance & General Ledger Verification',
                'Bank Reconciliation & Cash Verification',
                'GST & TDS Books Reconciliation',
                'Fixed Assets & Depreciation Schedule',
                'Draft Audit Report & Partner Review',
            ];
        }
        $checklist = array_values(array_map(fn ($label) => ['item' => $label, 'completed' => false, 'remark' => ''], $lines));

        $audit = Audit::create([
            ...$validated,
            'checklist' => $checklist,
            'progress_percent' => 0,
        ]);

        if ($request->filled('team_member_ids')) {
            $audit->teamMembers()->sync($request->input('team_member_ids'));
        }

        $this->logger->log('audits', 'created', "Created audit assignment '{$audit->title}'", $audit);

        return redirect()->route('audits.show', $audit)->with('success', 'Audit assignment created.');
    }

    public function update(Request $request, Audit $audit): RedirectResponse
    {
        abort_unless($this->clientAccess->canAccessAudit($request->user(), $audit), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'audit_type' => ['required', Rule::in(array_keys(Audit::AUDIT_TYPES))],
            'financial_year' => ['required', 'string', 'max:20'],
            'lead_auditor_id' => ['nullable', 'exists:users,id'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'completion_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(array_keys(Audit::STATUSES))],
            'observations' => ['nullable', 'string', 'max:5000'],
            'review_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $checklist = $audit->checklist ?? [];
        $checked = array_map('intval', (array) $request->input('checklist_checked', []));
        $remarks = (array) $request->input('checklist_remarks', []);

        foreach ($checklist as $idx => &$entry) {
            $entry['completed'] = in_array($idx, $checked, true);
            if (array_key_exists($idx, $remarks)) {
                $entry['remark'] = $remarks[$idx];
            }
        }

        $extraData = [
            'checklist' => $checklist,
        ];

        if ($validated['status'] === 'approved' && ! $audit->approved_at) {
            $extraData['approved_by'] = $request->user()->id;
            $extraData['approved_at'] = now();
        }
        if ($validated['status'] === 'completed' && empty($validated['completion_date'])) {
            $extraData['completion_date'] = today();
        }

        $audit->fill([...$validated, ...$extraData]);
        $audit->progress_percent = $audit->recalculateProgress();
        $audit->save();

        if ($request->has('team_member_ids')) {
            $audit->teamMembers()->sync($request->input('team_member_ids', []));
        }

        $this->logger->log('audits', 'updated', "Updated audit '{$audit->title}' ({$audit->progress_percent}%)", $audit);

        return back()->with('success', 'Audit details and checklist updated.');
    }

    public function uploadWorkingPaper(Request $request, Audit $audit): RedirectResponse
    {
        abort_unless($this->clientAccess->canAccessAudit($request->user(), $audit), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'file' => ['required', 'file', 'max:20480'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $file = $request->file('file');
        $path = $file->store("audits/audit_{$audit->id}", 'local');

        AuditWorkingPaper::create([
            'audit_id' => $audit->id,
            'title' => $validated['title'],
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'uploaded_by' => $request->user()->id,
            'notes' => $validated['notes'] ?? null,
        ]);

        $this->logger->log('audits', 'working_paper', "Uploaded working paper '{$validated['title']}' to '{$audit->title}'", $audit);

        return back()->with('success', 'Working paper uploaded.');
    }

    public function downloadWorkingPaper(Request $request, Audit $audit, AuditWorkingPaper $paper): StreamedResponse
    {
        abort_unless($this->clientAccess->canAccessAudit($request->user(), $audit), 403);
        abort_unless((int) $paper->audit_id === (int) $audit->id, 404);
        abort_unless(Storage::disk('local')->exists($paper->file_path), 404);

        return Storage::disk('local')->download($paper->file_path, $paper->original_filename);
    }

    public function destroy(Request $request, Audit $audit): RedirectResponse
    {
        abort_unless($this->clientAccess->canAccessAudit($request->user(), $audit), 403);

        $audit->delete();

        return redirect()->route('audits.index')->with('success', 'Audit assignment deleted.');
    }
}

