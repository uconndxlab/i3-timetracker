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
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <script src="https://unpkg.com/htmx.org@2.0.4" integrity="sha384-HGfztofotfshcF7+8n44JQL2oJmowVChPTg48S+jvZoztPfvwD79OC/LTtG6dMp+" crossorigin="anonymous"></script>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-uconn">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="{{ route('landing') }}">
                <i class="bi me-2"></i>
                <div class="d-flex flex-column">
                    <span class="fw-bold">i3 Time Tracker</span>
                    <small class="opacity-75" style="font-size: 0.7rem; line-height: 1; margin-top: -2px;">University of Connecticut</small>
                </div>
            </a>
    
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
    
            <div class="collapse navbar-collapse" id="mainNavbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('projects.*') ? 'active' : '' }}" href="{{ route('projects.index') }}">
                            Projects
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('shifts.*') ? 'active' : '' }}" href="{{ route('shifts.index') }}">
                            Shifts
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('shifts.create') }}">
                            <i class="bi me-1"></i>Log Shift
                        </a>
                    </li>

                    @if (Auth::check() && Auth::user()->isAdmin())
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle {{ request()->routeIs('admin.*') ? 'active' : '' }}" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Admin
                            </a>
                            <ul class="dropdown-menu dropdown-menu-dark" aria-labelledby="adminDropdown">
                                <li>
                                    <a class="dropdown-item {{ request()->routeIs('admin.shifts.*') ? 'active' : '' }}" href="{{ route('admin.shifts.index') }}">
                                        All Staff Shifts
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                                        Manage Users
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endif
                </ul>

                <div class="d-flex align-items-center ms-auto">
                    @if ( Auth::check() )
                        <a class="navbar-text me-3 text-decoration-none text-dark">
                            <span class="navbar-text me-3">
                                <i class="bi bi-person-circle me-1"></i>{{ Auth::user()->name ?? 'User' }}
                            </span>
                        </a>
                        <ul class="navbar-nav">
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('logout') }}">
                                    <i class="bi bi-box-arrow-right me-1"></i>Logout
                                </a>
                            </li>
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </nav>

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


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>

</html>
