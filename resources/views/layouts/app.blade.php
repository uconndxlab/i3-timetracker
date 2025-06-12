<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>i3 Time Tracker</title>
    <script src="https://unpkg.com/htmx.org@2.0.4" integrity="sha384-HGfztofotfshcF7+8n44JQL2oJmowVChPTg48S+jvZoztPfvwD79OC/LTtG6dMp+" crossorigin="anonymous"></script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-info mb-4">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="{{ route('landing') }}">i3 Time Tracker</a>
    
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
    
            <div class="collapse navbar-collapse" id="mainNavbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('projects.index') }}">Projects</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('shifts.index') }}">Shifts</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('projects.create') }}">Add Project</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('shifts.create') }}">Add Shift</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('admin.dashboard') }}">Admin Dashboard</a>
                    </li>

                </ul>

                <div class="d-flex align-items-center ms-auto">
                    @if ( Auth::check() )
                        <span class="navbar-text text-white text-mono login-hud me-3">
                            Logged in as: {{ Auth::user()->netid ?? Auth::user()->name }}
                            <span class="badge bg-success">{{ Str::headline(Auth::user()->role ?? 'User')}}</span>
                        </span>
                        <ul class="navbar-nav">
                            <li class="nav-item">
                                <a class="nav-link text-white" href="{{ route('logout') }}">Logout</a>
                            </li>
                        </ul>
                    @else
                        <span class="navbar-text text-white text-mono login-hud me-3">(Not currently logged in.)</span>
                        <ul class="navbar-nav">
                            <li class="nav-item">
                                <a href="{{ route('login') }}" class="nav-link text-white">Login</a>
                            </li>
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </nav>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('message'))
        <div class="alert alert-success">
            {{ session('message') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    <div class="container">
        @yield('content')
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
