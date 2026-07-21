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
@if(config('preview.enabled'))
    {{-- Development Preview Banner --}}
    <div id="previewBanner" class="alert alert-warning rounded-0 mb-0 py-2 border-0 border-bottom" role="alert" style="border-bottom-width: 1px !important;">
        <div class="container-fluid px-3 px-md-4 d-flex align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-cone-striped flex-shrink-0" aria-hidden="true"></i>
                <span class="small">
                    <strong>Development Preview</strong> &mdash;
                    You are viewing the dev version of the i3 Time Tracker.
                    To track your actual time, visit
                    <a href="{{ config('preview.production_url') }}" class="alert-link fw-semibold">the production site</a>.
                </span>
            </div>
            <button type="button" id="previewBannerClose" class="btn-close flex-shrink-0" aria-label="Dismiss preview banner"></button>
        </div>
    </div>

    {{-- Development Preview Modal --}}
    <div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-modal="true" role="dialog" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom-0 pb-0">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-cone-striped text-warning fs-4" aria-hidden="true"></i>
                        <h5 class="modal-title fw-bold" id="previewModalLabel">Development Preview</h5>
                    </div>
                </div>
                <div class="modal-body pt-2">
                    <p class="mb-3">You are currently viewing the <strong>development preview</strong> of the i3 Time Tracker. Data entered here may not be preserved.</p>
                    <p class="mb-0">To track your actual time, please use the production site:</p>
                    <div class="mt-3">
                        <a href="{{ config('preview.production_url') }}" class="btn btn-warning fw-semibold w-100">
                            <i class="bi bi-box-arrow-up-right me-2" aria-hidden="true"></i>Go to Production Site
                        </a>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="previewModalDismiss">
                        Continue on Dev Preview
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var BANNER_KEY = 'previewBannerDismissed';
            var MODAL_KEY = 'previewModalShown';

            // Banner: runs immediately — element is already in DOM above this script
            var banner = document.getElementById('previewBanner');
            var bannerClose = document.getElementById('previewBannerClose');

            if (banner) {
                if (sessionStorage.getItem(BANNER_KEY)) {
                    banner.style.display = 'none';
                }

                if (bannerClose) {
                    bannerClose.addEventListener('click', function () {
                        banner.style.display = 'none';
                        sessionStorage.setItem(BANNER_KEY, '1');
                    });
                }
            }

            // Modal: wait for DOMContentLoaded so Bootstrap JS is available
            document.addEventListener('DOMContentLoaded', function () {
                if (!sessionStorage.getItem(MODAL_KEY)) {
                    sessionStorage.setItem(MODAL_KEY, '1');
                    var modalEl = document.getElementById('previewModal');
                    if (modalEl && window.bootstrap) {
                        bootstrap.Modal.getOrCreateInstance(modalEl).show();
                    }
                }

                var dismissBtn = document.getElementById('previewModalDismiss');
                if (dismissBtn) {
                    dismissBtn.addEventListener('click', function () {
                        var modalEl = document.getElementById('previewModal');
                        if (modalEl && window.bootstrap) {
                            bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                        }
                    });
                }
            });
        })();
    </script>
@endif

    <nav class="navbar py-2">
        <div class="container-fluid navbar-branding px-3 px-md-4">
            <div class="navbar-branding-left d-flex align-items-center">
                <a href="{{ route('landing') }}" class="d-inline-block">
                    <img src="{{ asset('i3.svg') }}" alt="i3" class="navbar-logo" width="48" height="48">
                </a>
                @auth
                    @if (Auth::user()->isAdmin() && !request()->routeIs('admin.users.dashboard', 'admin.projects.show'))
                        @php
                            $navbarView = request()->query('view') === 'admin' ? 'admin' : 'user';
                        @endphp
                        <div class="dashboard-segment navbar-view-toggle" id="navbarViewToggle" role="group" aria-label="Switch between user and admin view">
                            <button type="button" class="dashboard-segment__btn{{ $navbarView === 'user' ? ' active' : '' }}" data-view="user" aria-pressed="{{ $navbarView === 'user' ? 'true' : 'false' }}">User</button>
                            <button type="button" class="dashboard-segment__btn{{ $navbarView === 'admin' ? ' active' : '' }}" data-view="admin" aria-pressed="{{ $navbarView === 'admin' ? 'true' : 'false' }}">Admin</button>
                        </div>
                    @endif
                @endauth
            </div>

            <div class="navbar-branding-center text-center">
                <div class="navbar-app-title">i3 Time Tracker</div>
                <div class="navbar-branding-meta d-inline-flex align-items-center justify-content-center gap-2 mt-1">
                    <span class="navbar-chip navbar-chip--pill d-none d-md-inline-flex">UNIVERSITY OF CONNECTICUT</span>
                    <button type="button" class="navbar-chip navbar-chip--toggle" id="themeToggle" aria-label="Toggle light and dark mode">
                        <i class="bi bi-moon-fill" id="themeToggleIcon" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <div class="navbar-branding-right d-flex align-items-center gap-2 gap-md-3">
                @auth
                    <span class="navbar-username text-nowrap d-none d-sm-inline">
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

            const resolveView = () => {
                const urlView = new URLSearchParams(window.location.search).get('view');
                return urlView === 'admin' ? 'admin' : 'user';
            };

            const syncViewButtons = (view) => {
                buttons.forEach((btn) => {
                    const active = btn.dataset.view === view;
                    btn.classList.toggle('active', active);
                    btn.setAttribute('aria-pressed', active ? 'true' : 'false');
                });
            };

            const navigateToView = (view) => {
                const url = new URL(window.location.href);
                if (view === 'admin') {
                    url.searchParams.set('view', 'admin');
                } else {
                    url.searchParams.delete('view');
                    url.searchParams.delete('date_from');
                    url.searchParams.delete('date_to');
                }

                const next = url.pathname + url.search + url.hash;
                const current = window.location.pathname + window.location.search + window.location.hash;

                if (next !== current) {
                    window.location.assign(next);
                }
            };

            window.navbarResolveView = resolveView;
            window.navbarSetView = navigateToView;

            syncViewButtons(resolveView());

            buttons.forEach((btn) => {
                btn.addEventListener('click', () => navigateToView(btn.dataset.view));
            });
        })();
    </script>
    @stack('scripts')
</body>

</html>
