@props(['title', 'value', 'icon' => 'bi-bar-chart', 'tone' => 'primary', 'subtitle' => null, 'href' => null])

@php
    $tones = [
        'primary' => ['bg' => '#eff6ff', 'fg' => '#1e3a5f'],
        'info' => ['bg' => '#e0f2fe', 'fg' => '#0284c7'],
        'success' => ['bg' => '#f0fdfa', 'fg' => '#0d9488'],
        'warning' => ['bg' => '#fffbeb', 'fg' => '#d97706'],
        'danger' => ['bg' => '#fef2f2', 'fg' => '#dc2626'],
    ];
    $c = $tones[$tone] ?? $tones['primary'];
@endphp

<div class="ca-kpi-card d-flex flex-column justify-content-between">
    <div class="d-flex align-items-start justify-content-between gap-2">
        <div>
            <div class="text-muted fw-medium mb-1" style="font-size: 0.78rem;">{{ $title }}</div>
            <div class="fw-bold" style="font-size: 1.45rem; color: #0f172a;">{{ $value }}</div>
        </div>
        <span class="ca-kpi-icon" style="background-color: {{ $c['bg'] }}; color: {{ $c['fg'] }};">
            <i class="bi {{ $icon }}"></i>
        </span>
    </div>
    @if($subtitle || $href)
        <div class="mt-2 pt-2 border-top d-flex align-items-center justify-content-between" style="font-size: 0.76rem;">
            <span class="text-muted">{{ $subtitle }}</span>
            @if($href)
                <a href="{{ $href }}" class="text-decoration-none fw-semibold" style="color: #0284c7;">View <i class="bi bi-arrow-right-short"></i></a>
            @endif
        </div>
    @endif
</div>

