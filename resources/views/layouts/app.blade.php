<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>i3 Time Tracker</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="University of Connecticut i3 Time Tracking System">

    <link rel="icon" href="{{ asset('i3.svg') }}" type="image/svg+xml">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://unpkg.com/htmx.org@2.0.4" integrity="sha384-HGfztofotfshcF7+8n44JQL2oJmowVChPTg48S+jvZoztPfvwD79OC/LTtG6dMp+" crossorigin="anonymous"></script>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-uconn">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="{{ route('landing') }}">
                <img src="{{ asset('i3.svg') }}" alt="i3" class="navbar-brand-logo me-2">
                <div class="d-flex flex-column">
                    <span class="fw" style="font-size: 1.25rem;">i3 Time Tracker</span>
                    <small class="opacity-75" style="font-size: 0.55rem; line-height: 1; margin-top: -2px;">Institutional Insights & Innovation</small>
                </div>
            </a>
    
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
    
            <div class="collapse navbar-collapse" id="mainNavbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('projects.*') && !request()->routeIs('projects.create') ? 'active' : '' }}" href="{{ route('projects.index') }}">
                            Projects
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('shifts.index') || request()->routeIs('shifts.edit') ? 'active' : '' }}" href="{{ route('shifts.index') }}">
                            Your Shifts
                        </a>
                    </li>
                    <li>
                        <a class="nav-link {{ request()->routeIs('shifts.manage') ? 'active' : '' }}" href="{{ route('shifts.manage') }}">
                            All Shifts
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('shifts.create') ? 'active' : '' }}" href="{{ route('shifts.create') }}">
                            Log Shift
                        </a>
                    </li>
                </ul>

                <div class="d-flex align-items-center ms-auto">
                    @if ( Auth::check() )
                        @if (Auth::user()->isAdmin())
                            <ul class="navbar-nav me-2">
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle {{ request()->routeIs('admin.users.index') || request()->routeIs('projects.create') ? 'active' : '' }}"
                                       href="#"
                                       id="adminDropdown"
                                       role="button"
                                       data-bs-toggle="dropdown"
                                       aria-expanded="false">
                                        Admin
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="adminDropdown">
                                        <li>
                                            <a class="dropdown-item {{ request()->routeIs('admin.users.index') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                                                Manage Staff
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item {{ request()->routeIs('projects.create') ? 'active' : '' }}" href="{{ route('projects.create') }}">
                                                Create Project
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                            </ul>
                        @endif


                        <ul class="navbar-nav">
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('logout') }}">
                                    <i class="bi me-1"></i>Logout ({{ Auth::user()->name ?? 'User' }})
                                </a>
                            </li>
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </nav>

    <main class="app-main">
        <div class="container mt-4">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>Please correct the following errors:</strong>
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('message'))
                <div class="alert alert-success">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    {{ session('message') }}
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger">
                    <i class="bi bi-x-circle-fill me-2"></i>
                    {{ session('error') }}
                </div>
            @endif
        </div>

        <div class="container page-content">
            @yield('content')
        </div>
    </main>

    <footer class="internal-footer">
        <div class="container d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
            <p class="mb-0 internal-footer-brand d-flex align-items-center gap-2">
                {{-- <img src="{{ asset('i3.svg') }}" alt="i3" class="internal-footer-logo"> --}}
                <span>Institutional Insights & Innovation · University of Connecticut</span>
            </p>
        </div>
    </footer>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>

</html>
