<!doctype html>
<html lang="en" data-bs-theme="light">

<head>
    <meta charset="utf-8">
    <title>i3 Time Tracker</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="University of Connecticut i3 Time Tracking System">

    <script>
        (function () {
            const stored = localStorage.getItem('theme');
            const theme = stored === 'dark' || stored === 'light'
                ? stored
                : (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-bs-theme', theme);
        })();
    </script>

    <link rel="icon" href="{{ asset('i3.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>

<body>
    <nav class="navbar py-2">
        <div class="container-fluid navbar-branding px-3 px-md-4">
            <div class="navbar-branding-left d-flex align-items-center">
                <a href="{{ route('landing') }}" class="d-inline-block">
                    <img src="{{ asset('i3.svg') }}" alt="i3" class="navbar-logo" width="48" height="48">
                </a>
                @auth
                    @if (Auth::user()->isAdmin() && !request()->routeIs('admin.users.dashboard'))
                        <div class="dashboard-segment navbar-view-toggle" id="navbarViewToggle" role="group" aria-label="Switch between user and admin view">
                            <button type="button" class="dashboard-segment__btn active" data-view="user" aria-pressed="true">User</button>
                            <button type="button" class="dashboard-segment__btn" data-view="admin" aria-pressed="false">Admin</button>
                        </div>
                    @endif
                @endauth
            </div>

            <div class="navbar-branding-center text-center">
                <div class="navbar-app-title">i3 Time Tracker</div>
                <div class="navbar-branding-meta d-inline-flex align-items-center justify-content-center gap-2 mt-1">
                    <span class="navbar-chip navbar-chip--pill">UNIVERSITY OF CONNECTICUT</span>
                    <button type="button" class="navbar-chip navbar-chip--toggle" id="themeToggle" aria-label="Toggle light and dark mode">
                        <i class="bi bi-moon-fill" id="themeToggleIcon" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <div class="navbar-branding-right d-flex align-items-center gap-2 gap-md-3">
                @auth
                    <span class="navbar-username text-nowrap">
                        {{ Auth::user()->name ?? 'User' }}
                    </span>
                    <a href="{{ route('logout') }}" class="btn btn-sm navbar-logout-btn text-nowrap">
                        Logout <i class="bi bi-box-arrow-right ms-1"></i>
                    </a>
                @endauth
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


    @stack('bootstrap-js')
    <script>
        (function () {
            const toggle = document.getElementById('themeToggle');
            if (!toggle) {
                return;
            }

            const icon = document.getElementById('themeToggleIcon');

            const applyTheme = (theme) => {
                document.documentElement.setAttribute('data-bs-theme', theme);
                localStorage.setItem('theme', theme);
                if (icon) {
                    icon.className = theme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
                }
            };

            applyTheme(document.documentElement.getAttribute('data-bs-theme') || 'light');

            toggle.addEventListener('click', () => {
                const next = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
                applyTheme(next);
            });
        })();

        (function () {
            const viewToggle = document.getElementById('navbarViewToggle');
            if (!viewToggle) {
                return;
            }

            const buttons = viewToggle.querySelectorAll('.dashboard-segment__btn');
            const storageKey = 'navbarViewMode';

            const setView = (view) => {
                buttons.forEach((btn) => {
                    const active = btn.dataset.view === view;
                    btn.classList.toggle('active', active);
                    btn.setAttribute('aria-pressed', active ? 'true' : 'false');
                });
                localStorage.setItem(storageKey, view);
                document.dispatchEvent(new CustomEvent('navbar-view-change', { detail: { view } }));
            };

            const urlView = new URLSearchParams(window.location.search).get('view');
            if (urlView === 'admin' || urlView === 'user') {
                setView(urlView);
            } else {
                const stored = localStorage.getItem(storageKey);
                if (stored === 'admin' || stored === 'user') {
                    setView(stored);
                }
            }

            buttons.forEach((btn) => {
                btn.addEventListener('click', () => setView(btn.dataset.view));
            });
        })();
    </script>
    @stack('scripts')
</body>

</html>
