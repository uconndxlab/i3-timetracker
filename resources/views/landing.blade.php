@extends('layouts.app')

@section('content')

<div class="mt-4">
    <div class="welcome-section text-center py-5">
        <div class="container">
            <h1 class="display-4 fw-bold text-primary mb-3">
                <i class="bi bi-stopwatch me-3"></i>
                Welcome to i3 Time Tracker
            </h1>
            <p class="lead mb-4">Track time spent working on projects</p>
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <a href="{{ route('shifts.create') }}" class="btn btn-primary btn-lg">
                    <i class="bi bi-plus-circle me-2"></i>Log New Shift
                </a>
                <a href="{{ route('projects.create') }}" class="btn btn-outline-primary btn-lg">
                    <i class="bi bi-folder-plus me-2"></i>Create Project
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h2 class="d-flex align-items-center">
                        <i class="bi bi-folder-open me-2"></i>
                        Active Projects
                    </h2>
                </div>
                <div class="card-body">
                    @if($activeProjects->isEmpty())
                        <div class="text-center py-4">
                            <i class="bi bi-folder-x text-muted" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-3 mb-0">No active projects at the moment.</p>
                            <a href="{{ route('projects.create') }}" class="btn btn-outline-primary mt-3">
                                <i class="bi bi-plus-circle me-1"></i>Create Project
                            </a>
                        </div>
                    @else
                        <div class="row">
                            @foreach ($activeProjects as $project)
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="project-item h-100">
                                        <a href="/projects/{{ $project->id }}" class="text-decoration-none">
                                            <div class="p-3">
                                                <div class="d-flex align-items-start justify-content-between mb-2">
                                                    <h5 class="mb-1 text-dark fw-semibold">{{ $project->name }}</h5>
                                                    <span class="badge bg-success">
                                                        <i class="bi bi-check-circle me-1"></i>Active
                                                    </span>
                                                </div>
                                                <p class="text-muted mb-2 small">
                                                    {{ Str::limit($project->desc, 100) ?: 'No description available.' }}
                                                </p>
                                                <small class="text-muted">
                                                    <i class="bi bi-calendar me-1"></i>
                                                    Last updated: {{ $project->updated_at->diffForHumans() }}
                                                </small>
                                            </div>
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    
                    <div class="mt-4 text-center">
                        <a href="{{ route('projects.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-collection me-1"></i>View All Projects
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection