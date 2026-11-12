@extends('layouts.app')

@section('title', 'Client Communications')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h4 fw-bold mb-1" style="color: #1e3a5f;">Client Communication &amp; Reminders</h1>
        <p class="text-muted small mb-0">Send portal messages, email reminders, log calls/meetings, and trigger WhatsApp Business API notifications.</p>
    </div>
    <div>
        @if($whatsAppConfigured)
            <span class="badge badge-soft-success p-2"><i class="bi bi-whatsapp me-1"></i>WhatsApp Business API: Configured</span>
        @else
            <span class="badge badge-soft-warning p-2"><i class="bi bi-whatsapp me-1"></i>WhatsApp API: Integration Ready (Not Configured)</span>
        @endif
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="ca-card p-3">
            <h2 class="h6 fw-bold mb-3">Dispatch Message / Reminder</h2>
            <form action="{{ route('communications.store') }}" method="POST">
                @csrf
                <div class="mb-2">
                    <label class="form-label small fw-semibold">Client *</label>
                    <select name="client_id" class="form-select form-select-sm" required>
                        <option value="">-- Select Client --</option>
                        @foreach($clients as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-semibold">Channel *</label>
                    <select name="channel" class="form-select form-select-sm" required>
                        @foreach(\App\Models\Communication::CHANNELS as $k => $v)
                            <option value="{{ $k }}">{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-semibold">Subject</label>
                    <input type="text" name="subject" class="form-control form-control-sm" placeholder="Compliance / Document / Fee Reminder">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Message *</label>
                    <textarea name="message" rows="5" class="form-control form-control-sm" required placeholder="Dear Client, please upload your pending bank statements for GST reconciliation..."></textarea>
                </div>
                <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-send me-1"></i>Send &amp; Log Communication</button>
            </form>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="ca-card p-3 mb-3">
            <form method="GET" action="{{ route('communications.index') }}" class="row g-2 align-items-end">
                <div class="col-md-5"><select name="client_id" class="form-select form-select-sm"><option value="">All Clients</option>@foreach($clients as $c)<option value="{{ $c->id }}" @selected((string) request('client_id') === (string) $c->id)>{{ $c->name }}</option>@endforeach</select></div>
                <div class="col-md-4"><select name="channel" class="form-select form-select-sm"><option value="">All Channels</option>@foreach(\App\Models\Communication::CHANNELS as $k => $v)<option value="{{ $k }}" @selected(request('channel') === $k)>{{ $v }}</option>@endforeach</select></div>
                <div class="col-md-3 d-flex gap-1"><button type="submit" class="btn btn-sm btn-primary flex-grow-1">Filter</button><a href="{{ route('communications.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a></div>
            </form>
        </div>

        <div class="ca-card p-3">
            <h2 class="h6 fw-bold mb-3">Communication Log</h2>
            @forelse($communications as $comm)
                <div class="border rounded p-3 mb-2 bg-light">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-1">
                        <div>
                            <span class="fw-bold small">{{ $comm->client->name }}</span>
                            <span class="text-muted small">· {{ $comm->subject ?: $comm->channelLabel() }}</span>
                        </div>
                        <div class="d-flex gap-1">
                            <span class="badge badge-soft-info">{{ $comm->channelLabel() }}</span>
                            <x-status-badge :status="$comm->status" />
                        </div>
                    </div>
                    <p class="small mb-1">{{ $comm->message }}</p>
                    @if(!empty($comm->meta['provider_note']))
                        <div class="text-warning-emphasis small"><i class="bi bi-info-circle me-1"></i>{{ $comm->meta['provider_note'] }}</div>
                    @endif
                    <div class="text-muted" style="font-size: 0.72rem;">
                        {{ $comm->sender_type === 'client' ? 'From Client' : 'By ' . ($comm->user?->name ?? 'Staff') }} · {{ $comm->created_at->format('d M Y, h:i A') }}
                    </div>
                </div>
            @empty
                <div class="text-muted small text-center py-4">No communication logs yet.</div>
            @endforelse
            @if($communications->hasPages())<div class="mt-3">{{ $communications->links() }}</div>@endif
        </div>
    </div>
</div>
@endsection

