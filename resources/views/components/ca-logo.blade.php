@props(['size' => 36, 'showWordmark' => true, 'subtitle' => 'Practice Management'])

@php
    $brandName = config('brand.name', 'WebRajya');
    $logoPath = config('brand.logo_path', '/images/webrajya-logo.png');
    $hasCustomLogo = file_exists(public_path($logoPath)) && filesize(public_path($logoPath)) > 500;
@endphp

<div {{ $attributes->merge(['class' => 'd-inline-flex align-items-center gap-2']) }}>
    @if($hasCustomLogo)
        <img src="{{ asset($logoPath) }}" alt="{{ $brandName }}" width="{{ $size }}" height="{{ $size }}" class="rounded object-fit-contain">
    @else
        <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 44 44" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <rect x="2" y="2" width="40" height="40" rx="11" fill="#EFF6FF" stroke="#BFDBFE" stroke-width="1.5"/>
            <!-- Minimalist Document Outline -->
            <path d="M13 10.5C13 9.39543 13.8954 8.5 15 8.5H24.5L31 15V33.5C31 34.6046 30.1046 35.5 29 35.5H15C13.8954 35.5 13 34.6046 13 33.5V10.5Z" fill="#FFFFFF" stroke="#1E3A5F" stroke-width="1.75" stroke-linejoin="round"/>
            <path d="M24.5 8.5V15H31" stroke="#1E3A5F" stroke-width="1.6" stroke-linejoin="round"/>
            <!-- Financial Growth Bars -->
            <rect x="16.5" y="24" width="2.6" height="6.5" rx="1" fill="#93C5FD"/>
            <rect x="20.7" y="21" width="2.6" height="9.5" rx="1" fill="#0284C7"/>
            <rect x="24.9" y="18" width="2.6" height="12.5" rx="1" fill="#1E3A5F"/>
            <!-- Compliance Checkmark Badge -->
            <circle cx="32.5" cy="31.5" r="7.5" fill="#0D9488" stroke="#FFFFFF" stroke-width="2"/>
            <path d="M29.4 31.6L31.5 33.7L35.7 29.5" stroke="#FFFFFF" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    @endif
    @if($showWordmark)
        <div class="lh-sm">
            <div class="fw-bold tracking-tight" style="color: #1e3a5f; font-size: 1.05rem; letter-spacing: -0.02em;">
                {{ $brandName }} <span class="fw-semibold" style="color: #0d9488;">ERP</span>
            </div>
            @if($subtitle)
                <div class="text-muted" style="font-size: 0.7rem;">{{ $subtitle }}</div>
            @endif
        </div>
    @endif
</div>
