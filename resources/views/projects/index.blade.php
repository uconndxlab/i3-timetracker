@extends('layouts.app')

@section('content')

<div class="mt-4">
    <div class="page-header text-center">
        <div class="container">
            <h1 class="display-5">
                <i class="bi bi-collection me-3"></i>
                All Projects
            </h1>
            <p class="lead mb-0">Manage and track all i3 projects</p>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <span class="text-muted">
                <i class="bi bi-info-circle me-1"></i>
                Showing {{ $projects->count() }} project{{ $projects->count() !== 1 ? 's' : '' }}
            </span>
        </div>
        <a href="{{ route('projects.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i>Add New Project
        </a>
    </div>

    @if($projects->isEmpty())
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-folder-x text-muted" style="font-size: 4rem;"></i>
                <h4 class="text-muted mt-3">No Projects Found</h4>
            </div>
        </div>
    @else
        <div class="row">
            @foreach ($projects as $project)
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="project-item h-100">
                        <a href="{{ route('projects.show', $project) }}" class="text-decoration-none">
                            <div class="p-4">
                                <div class="d-flex align-items-start justify-content-between mb-3">
                                    <h5 class="mb-1 text-dark fw-semibold">{{ $project->name }}</h5>
                                    <span class="badge {{ $project->active ? 'bg-success' : 'bg-secondary' }}">
                                        <i class="bi bi-{{ $project->active ? 'check-circle' : 'pause-circle' }} me-1"></i>
                                        {{ $project->active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                                
                                <p class="text-muted mb-3">
                                    {{ Str::limit($project->desc, 150) ?: 'No description available.' }}
                                </p>
                                
                                <div class="d-flex align-items-center justify-content-between">
                                    <small class="text-muted">
                                        <i class="bi bi-calendar-plus me-1"></i>
                                        Created: {{ $project->created_at ? $project->created_at->format('M d, Y') : 'N/A' }}
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

@endsection
