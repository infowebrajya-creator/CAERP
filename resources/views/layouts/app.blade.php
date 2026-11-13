<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ config('brand.app_name', 'WebRajya ERP') }}</title>
    <meta name="description" content="{{ config('brand.description') }}">
    <meta property="og:title" content="{{ config('brand.app_name', 'WebRajya ERP') }}">
    <meta property="og:description" content="{{ config('brand.description') }}">
    <meta property="og:type" content="website">
    <link rel="icon" type="image/x-icon" href="{{ asset(config('brand.favicon_path', '/images/favicon.ico')) }}">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
    @stack('styles')
</head>
<body>
@php
    $user = auth()->user();
    $unreadNotifications = $user ? $user->unreadNotifications()->latest()->limit(6)->get() : collect();
    $navItems = [
        'Overview' => [
            ['route' => 'dashboard', 'pattern' => 'dashboard', 'icon' => 'bi-grid-1x2-fill', 'label' => 'Dashboard'],
            ['route' => 'clients.index', 'pattern' => 'clients.*', 'icon' => 'bi-buildings', 'label' => 'Clients CRM'],
        ],
        'Tax & Compliance' => [
            ['route' => 'gst.index', 'pattern' => 'gst.*', 'icon' => 'bi-receipt-cutoff', 'label' => 'GST Compliance'],
            ['route' => 'itr.index', 'pattern' => 'itr.*', 'icon' => 'bi-file-earmark-check', 'label' => 'ITR Filings'],
            ['route' => 'tds.index', 'pattern' => 'tds.*', 'icon' => 'bi-percent', 'label' => 'TDS Management'],
            ['route' => 'audits.index', 'pattern' => 'audits.*', 'icon' => 'bi-clipboard2-check', 'label' => 'Audits & Assurance'],
            ['route' => 'calendar.index', 'pattern' => 'calendar.*', 'icon' => 'bi-calendar3', 'label' => 'Compliance Calendar'],
        ],
        'Practice Operations' => array_values(array_filter([
            ['route' => 'documents.index', 'pattern' => 'documents.*', 'icon' => 'bi-folder2-open', 'label' => 'Document Vault'],
            ['route' => 'tasks.index', 'pattern' => 'tasks.*', 'icon' => 'bi-kanban', 'label' => 'Tasks & Workflow'],
            ($user && $user->isManagerOrAbove()) ? ['route' => 'billing.index', 'pattern' => 'billing.*', 'icon' => 'bi-cash-coin', 'label' => 'Billing & Invoices'] : null,
            ($user && $user->isManagerOrAbove()) ? ['route' => 'staff.index', 'pattern' => 'staff.*', 'icon' => 'bi-people', 'label' => 'Staff & Workload'] : null,
            ['route' => 'communications.index', 'pattern' => 'communications.*', 'icon' => 'bi-chat-dots', 'label' => 'Communications'],
        ])),
    ];
    if ($user && $user->isManagerOrAbove()) {
        $navItems['Insights & Admin'] = [
            ['route' => 'reports.index', 'pattern' => 'reports.*', 'icon' => 'bi-graph-up-arrow', 'label' => 'Reports & Exports'],
        ];
    }
    if ($user && $user->isAdminOrPartner()) {
        $navItems['Insights & Admin'][] = ['route' => 'settings.index', 'pattern' => 'settings.*', 'icon' => 'bi-sliders', 'label' => 'Settings & Audit Logs'];
    }
    if ($user && $user->role === 'super_admin') {
        $navItems['Insights & Admin'][] = ['route' => 'admin.licenses.index', 'pattern' => 'admin.licenses.*', 'icon' => 'bi-key-fill', 'label' => 'Vendor License Manager'];
    }
@endphp

<div class="ca-shell">
    <!-- Desktop Sidebar -->
    <aside class="ca-sidebar d-none d-lg-flex flex-column">
        <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
            <a href="{{ route('dashboard') }}" class="text-decoration-none">
                <x-ca-logo :size="36" />
            </a>
        </div>

        <nav class="flex-grow-1 py-2">
            @foreach($navItems as $section => $items)
                <div class="nav-section-label">{{ $section }}</div>
                @foreach($items as $item)
                    <a href="{{ route($item['route']) }}"
                       class="ca-nav-link {{ request()->routeIs($item['pattern']) ? 'active' : '' }}">
                        <i class="bi {{ $item['icon'] }}"></i>
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            @endforeach
        </nav>

        <div class="p-3 border-top bg-light">
            <div class="d-flex align-items-center gap-2">
                <span class="ca-avatar" style="background-color: {{ $user->avatar_color ?? '#1e3a5f' }};">
                    {{ $user->initials() }}
                </span>
                <div class="min-w-0 flex-grow-1">
                    <div class="fw-semibold text-truncate" style="font-size: 0.84rem;">{{ $user->name }}</div>
                    <div class="text-muted text-truncate" style="font-size: 0.72rem;">{{ $user->roleLabel() }}</div>
                </div>
            </div>
        </div>
    </aside>

    <!-- Mobile Offcanvas Sidebar -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="mobileSidebar" style="width: 270px;">
        <div class="offcanvas-header border-bottom">
            <x-ca-logo :size="32" />
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body p-2">
            @foreach($navItems as $section => $items)
                <div class="small text-uppercase fw-bold text-muted px-3 pt-3 pb-1" style="font-size: 0.68rem;">{{ $section }}</div>
                @foreach($items as $item)
                    <a href="{{ route($item['route']) }}"
                       class="d-flex align-items-center gap-2 px-3 py-2 rounded text-decoration-none {{ request()->routeIs($item['pattern']) ? 'bg-primary-subtle text-primary fw-semibold' : 'text-dark' }}">
                        <i class="bi {{ $item['icon'] }}"></i>
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            @endforeach
        </div>
    </div>

    <div class="ca-main">
        <!-- Topbar -->
        <header class="ca-topbar d-flex align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-2 flex-grow-1">
                <button class="btn btn-outline-secondary btn-sm d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <form action="{{ route('global.search') }}" method="GET" class="d-none d-sm-flex flex-grow-1" style="max-width: 420px;">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" name="q" value="{{ request('q') }}" class="form-control bg-light border-start-0" placeholder="Search clients, PAN, GSTIN, invoices, tasks...">
                    </div>
                </form>
            </div>

            <div class="d-flex align-items-center gap-2">
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="bi bi-plus-lg me-1"></i> Quick Add
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        @if($user && $user->isManagerOrAbove())
                            <li><a class="dropdown-item" href="{{ route('clients.create') }}"><i class="bi bi-building-add me-2 text-primary"></i>New Client</a></li>
                        @endif
                        @if($user && $user->isAdminOrPartner())
                            <li><a class="dropdown-item" href="{{ route('billing.create') }}"><i class="bi bi-receipt me-2 text-success"></i>New Invoice</a></li>
                        @endif
                        <li><a class="dropdown-item" href="{{ route('tasks.index') }}"><i class="bi bi-check2-square me-2 text-info"></i>New Task</a></li>
                        <li><a class="dropdown-item" href="{{ route('documents.index') }}"><i class="bi bi-file-earmark-arrow-up me-2 text-warning"></i>Request Document</a></li>
                    </ul>
                </div>

                <!-- Notifications -->
                <div class="dropdown">
                    <button class="btn btn-sm btn-light border position-relative" data-bs-toggle="dropdown" aria-label="Notifications">
                        <i class="bi bi-bell"></i>
                        @if($unreadNotifications->count() > 0)
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.62rem;">
                                {{ $unreadNotifications->count() }}
                            </span>
                        @endif
                    </button>
                    <div class="dropdown-menu dropdown-menu-end shadow-sm p-0" style="width: 320px;">
                        <div class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center">
                            <span class="fw-semibold small">Notifications</span>
                            @if($unreadNotifications->count() > 0)
                                <form action="{{ route('notifications.read_all') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-link btn-sm p-0 text-decoration-none">Mark all read</button>
                                </form>
                            @endif
                        </div>
                        <div class="list-group list-group-flush" style="max-height: 280px; overflow-y: auto;">
                            @forelse($unreadNotifications as $notification)
                                <form action="{{ route('notifications.read', $notification->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="list-group-item list-group-item-action text-start py-2">
                                        <div class="fw-semibold small">{{ $notification->data['title'] ?? 'Alert' }}</div>
                                        <div class="text-muted" style="font-size: 0.76rem;">{{ $notification->data['body'] ?? '' }}</div>
                                    </button>
                                </form>
                            @empty
                                <div class="p-3 text-center text-muted small">You are all caught up.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- User Menu -->
                <div class="dropdown">
                    <button class="btn btn-sm btn-light border d-flex align-items-center gap-2 dropdown-toggle" data-bs-toggle="dropdown">
                        <span class="ca-avatar" style="width: 26px; height: 26px; font-size: 0.68rem; background-color: {{ $user->avatar_color ?? '#1e3a5f' }};">
                            {{ $user->initials() }}
                        </span>
                        <span class="d-none d-md-inline small fw-medium">{{ $user->name }}</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li class="px-3 py-2 border-bottom">
                            <div class="fw-semibold small">{{ $user->name }}</div>
                            <div class="text-muted" style="font-size: 0.75rem;">{{ $user->roleLabel() }}</div>
                        </li>
                        <li>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Sign Out</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Page Body -->
        <main class="p-3 p-md-4 flex-grow-1">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('warning'))
                <div class="alert alert-warning alert-dismissible fade show border-0 shadow-sm" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('warning') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
                    <div class="fw-semibold mb-1"><i class="bi bi-x-circle-fill me-2"></i>Please fix the following errors:</div>
                    <ul class="mb-0 small">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

@stack('scripts')
</body>
</html>

