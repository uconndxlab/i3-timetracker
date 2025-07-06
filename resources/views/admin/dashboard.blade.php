@extends('layouts.app')

@section('content')
<div class="mt-4">
    <div class="page-header text-center">
        <div class="container">
            <h1 class="display-5">
                <i class="bi bi-speedometer2 me-3"></i>
                Admin Dashboard
            </h1>
            <p class="lead mb-0">Monitor projects and track unbilled shifts</p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="d-flex align-items-center justify-content-between">
                <span>
                    <i class="bi bi-building me-2"></i>
                    Projects Overview
                </span>
                <span class="badge bg-light text-dark">{{ $projects->count() }} Total</span>
            </h2>
        </div>
        <div class="card-body">
            @if($projects->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                    <p class="text-muted mt-3 mb-0">No projects found.</p>
                </div>
            @else
                <div class="row">
                    @foreach ($projects as $project)
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="project-item h-100">
                                <a href="{{ route('admin.projects.unbilled_users', $project) }}" class="text-decoration-none">
                                    <div class="p-3">
                                        <div class="d-flex align-items-start justify-content-between mb-2">
                                            <h5 class="mb-1 text-dark fw-semibold">{{ $project->name }}</h5>
                                            <span class="badge {{ $project->active ? 'bg-success' : 'bg-secondary' }}">
                                                <i class="bi bi-{{ $project->active ? 'check-circle' : 'pause-circle' }} me-1"></i>
                                                {{ $project->active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </div>
                                        <p class="text-muted mb-3 small">
                                            {{ Str::limit($project->desc, 120) ?: 'No description available.' }}
                                        </p>
                                        <div class="d-flex align-items-center justify-content-between">
                                            <small class="text-muted">
                                                <i class="bi bi-people me-1"></i>
                                                View unbilled shifts
                                            </small>
                                            <i class="bi bi-arrow-right text-primary"></i>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection