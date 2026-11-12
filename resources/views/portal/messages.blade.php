@extends('layouts.portal')

@section('title', 'Messages')

@section('content')
<h1 class="h4 fw-bold mb-3" style="color: #1e3a5f;">Messages with Your CA Firm</h1>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="ca-card p-3">
            <h2 class="h6 fw-bold mb-3">Send Message to CA Firm</h2>
            <form action="{{ route('portal.messages.send') }}" method="POST">
                @csrf
                <div class="mb-2"><label class="form-label small fw-semibold">Subject *</label><input type="text" name="subject" class="form-control form-control-sm" required></div>
                <div class="mb-3"><label class="form-label small fw-semibold">Message *</label><textarea name="message" rows="4" class="form-control form-control-sm" required></textarea></div>
                <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-send me-1"></i>Send Message</button>
            </form>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="ca-card p-3">
            <h2 class="h6 fw-bold mb-3">Conversation History</h2>
            @forelse($messages as $msg)
                <div class="border rounded p-3 mb-2 {{ $msg->sender_type === 'client' ? 'bg-light' : 'bg-info-subtle bg-opacity-25' }}">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="fw-semibold small">{{ $msg->subject ?: $msg->channelLabel() }}</span>
                        <span class="badge badge-soft-info">{{ $msg->sender_type === 'client' ? 'You' : 'CA Firm' }}</span>
                    </div>
                    <p class="small mb-1">{{ $msg->message }}</p>
                    <div class="text-muted" style="font-size: 0.72rem;">{{ $msg->created_at->format('d M Y, h:i A') }}</div>
                </div>
            @empty
                <div class="text-muted small text-center py-4">No messages yet.</div>
            @endforelse
            @if($messages->hasPages())<div class="mt-3">{{ $messages->links() }}</div>@endif
        </div>
    </div>
</div>
@endsection

