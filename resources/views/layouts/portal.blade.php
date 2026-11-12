<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Client Portal') — {{ config('brand.app_name', 'WebRajya ERP') }}</title>
    <meta name="description" content="{{ config('brand.description') }}">
    <meta property="og:title" content="{{ config('brand.app_name', 'WebRajya ERP') }}">
    <meta property="og:description" content="{{ config('brand.description') }}">
    <meta property="og:type" content="website">
    <link rel="icon" type="image/x-icon" href="{{ asset(config('brand.favicon_path', '/images/favicon.ico')) }}">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body>
@php($user = auth()->user())
<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top py-2">
    <div class="container">
        <a class="navbar-brand text-decoration-none" href="{{ route('portal.dashboard') }}">
            <x-ca-logo :size="34" subtitle="Client Portal" />
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#portalNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="portalNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4 gap-lg-1">
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('portal.dashboard') ? 'active fw-semibold text-primary' : '' }}" href="{{ route('portal.dashboard') }}">Overview</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('portal.profile') ? 'active fw-semibold text-primary' : '' }}" href="{{ route('portal.profile') }}">My Companies</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('portal.documents') ? 'active fw-semibold text-primary' : '' }}" href="{{ route('portal.documents') }}">My Documents</a></li>
            </ul>
            <div class="d-flex align-items-center gap-3">
                <span class="small text-muted">{{ $user->name }}</span>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-box-arrow-right me-1"></i>Logout</button>
                </form>
            </div>
        </div>
    </div>
</nav>

<main class="container py-4">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm">
            <ul class="mb-0 small">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @yield('content')
</main>
</body>
</html>

