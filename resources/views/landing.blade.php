@extends('layouts.app')

@section('content')
<div class="mt-4">
    <div class="card border-0 shadow-sm mb-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="row g-0">
                <div class="col-md-7">
                    <div class="p-5" style="background-color: var(--uconn-navy);">
                        <h1 class="display-5 fw-bold text-white mb-3">
                            <i class="bi bi-clock-history me-2"></i>i3 Time Tracker
                        </h1>
                        <p class="lead text-white opacity-90 mb-4">Track time spent working on projects</p>
                        <div class="d-flex gap-3 flex-wrap">
                            <a href="{{ route('shifts.create') }}" class="btn btn-outline-light">
                                <i class="bi bi-plus-circle me-2"></i>Log New Shift
                            </a>
                            <a href="{{ route('projects.create') }}" class="btn btn-outline-light">
                                <i class="bi bi-folder-plus me-2"></i>Create Project
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-5">
                    <div class="p-4 h-100 d-flex flex-column justify-content-center align-items-center bg-light border rounded shadow-sm">
                        <div class="display-1 fw-bold text-primary mb-0">{{ $activeProjects->count() }}</div>
                        <p class="mb-2 text-uppercase fw-semibold text-muted small">Assigned Project</p>
                        <div class="mt-3">
                            <a href="{{ route('projects.index') }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-grid me-1"></i>View All Projects
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-header bg-white border-bottom">
                    <h2 class="h5 mb-0 d-flex align-items-center">
                        <i class="bi bi-activity me-2 text-primary"></i>
                        <span class="text-dark">Recent Activity</span>
                    </h2>
                </div>
                <div class="card-body">
                    @if($activeShifts->count() > 0)
                        @php
                            $recentShifts = $activeShifts->sortByDesc('updated_at')->take(5);
                        @endphp
                        <div class="list-group list-group-flush">
                            @foreach($recentShifts as $shift)
                                <a href="/shifts/{{ $shift->id }}" class="list-group-item list-group-item-action px-0 py-3 border-0 border-bottom">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1 text-primary">{{ $shift->project->name }}</h6>
                                            <p class="text-muted mb-0 small">
                                                <i class="bi bi-clock me-1"></i>{{ $shift->updated_at->diffForHumans() }}
                                            </p>
                                        </div>
                                        <i class="bi bi-chevron-right text-muted"></i>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="bi bi-activity text-muted" style="font-size: 2rem;"></i>
                            <p class="text-muted mt-3 mb-0">No recent activity</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h2 class="h5 mb-0 d-flex align-items-center">
                            <i class="bi bi-activity me-2 text-primary"></i>
                            <span class="text-dark">Your Projects</span>
                        </h2>
                    </div>
                </div>
                <div class="card-body"> 
                    @if($activeProjects->isEmpty())
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="bi bi-folder-x text-muted" style="font-size: 3rem;"></i>
                            </div>
                            <h3 class="h5 text-muted">No active projects</h3>
                        </div>
                    @else
                        <div class="row g-3">
                            @foreach ($activeProjects as $project)
                                <div class="col-md-6">
                                    <div class="card h-100 border shadow-sm">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between mb-3">
                                                <h5 class="card-title text-primary mb-0">{{ $project->name }}</h5>
                                            </div>
                                            <p class="card-text text-muted small mb-3">
                                                {{ Str::limit($project->desc, 80) ?: 'No description available.' }}
                                            </p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div class="text-muted small">
                                                    @if($project->users_count ?? false)
                                                    <span><i class="bi bi-people me-1"></i>{{ $project->users_count }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-footer bg-white border-top-0 text-muted small">
                                            <i class="bi bi-clock-history me-1"></i>Updated {{ $project->updated_at->diffForHumans() }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection