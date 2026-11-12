@props(['status', 'label' => null])

@php
    $normalized = strtolower((string) $status);
    $badgeClass = match (true) {
        in_array($normalized, ['filed', 'verified', 'approved', 'completed', 'paid', 'active', 'complete', 'sent']) => 'badge-soft-success',
        in_array($normalized, ['in_progress', 'computation_ready', 'challan_paid', 'uploaded', 'under_review', 'partially_paid', 'onboarding']) => 'badge-soft-info',
        in_array($normalized, ['documents_pending', 'pending', 'requested', 'partial', 'upcoming', 'planned', 'medium', 'not_configured', 'logged']) => 'badge-soft-warning',
        in_array($normalized, ['overdue', 'rejected', 'urgent', 'failed', 'high']) => 'badge-soft-danger',
        default => 'badge-soft-secondary',
    };
    $display = $label ?? ucwords(str_replace('_', ' ', (string) $status));
@endphp

<span {{ $attributes->merge(['class' => "badge rounded-pill fw-semibold px-2 py-1 {$badgeClass}"]) }} style="font-size: 0.73rem;">
    {{ $display }}
</span>

