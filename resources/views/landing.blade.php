@extends('layouts.app')

@section('content')
<div class="mt-4">
    <div class="card border-0 shadow-sm mb-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="row g-0">
                <div class="col-md-7">
                    <div class="p-5" style="background-color: var(--uconn-navy);">
                        <h1 class="display-5 fw-bold text-white mb-3">
                            i3 Time Tracker
                        </h1>
                        <p class="lead text-white opacity-90 mb-4">Track time spent working on projects</p>
                        <div class="d-flex gap-3 flex-wrap">
                            <a href="{{ route('shifts.create') }}" class="btn btn-outline-light">
                                <i class="bi bi-plus-circle me-2"></i>Log New Shift
                            </a>
                            @if(auth()->user()->isAdmin())
                            <a href="{{ route('projects.create') }}" class="btn btn-outline-light">
                                Create Project
                            </a>
                            @endif
                        </div>
                    </div>
                </div>
                
                <div class="col-md-5">
                    <div class="p-4 h-100 d-flex flex-column justify-content-center align-items-center bg-light border rounded shadow-sm">
                        <div class="display-1 fw-bold text-primary mb-0">{{ $hoursThisWeek }}</div>
                        <p class="mb-2 text-uppercase fw-semibold text-muted small">Hours This Week</p>
                        <div class="mt-3">
                            <a href="{{ route('shifts.index') }}" class="btn btn-sm btn-outline-secondary">
                                View All Shifts
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- show projects in a clean list with hours you have contributed split into billed / unbilled, and then also add shift form here --}}

    <div class="row">
        {{-- <div class="col-lg-8"> --}}
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h2 class="h5 mb-0 d-flex align-items-center">
                            <i class="text-primary"></i>
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
                                            <h5 class="card-title text-primary mb-0">
                                                <a href="{{ route('shifts.create', ['proj_id' => $project->id]) }}" class="text-primary text-decoration-none">
                                                    {{ $project->name }}

                                                    {{-- show hours contributed into billed / unbilled --}}
                                                    <a href="{{ route('shifts.create', ['proj_id' => $project->id]) }}" class="text-decoration-none">
                                                        <span class="badge bg-secondary ms-2">
                                                            <i class="bi"></i>
                                                            @php
                                                                $billedHours = $project->shifts()
                                                                    ->where('netid', auth()->user()->netid)
                                                                    ->where('billed', true)
                                                                    ->sum('duration');
                                                                $unbilledHours = $project->shifts()
                                                                    ->where('netid', auth()->user()->netid)
                                                                    ->where('billed', false)
                                                                    ->sum('duration');
                                                            @endphp
                                                            {{ $billedHours }} billed / {{ $unbilledHours }} unbilled
                                                        </span>
                                                    </a>
                                                </a>

                                            </h5>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div class="text-muted small">
                                                    @if($project->users_count ?? false)
                                                    <span><i class="bi"></i>{{ $project->users_count }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-footer bg-white border-top-0 text-muted small">
                                            @if ($project->created_at)
                                                <i class="bi"></i>Updated {{ $project->updated_at->diffForHumans() }}
                                            @else
                                                <span>Date not available</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        {{-- </div> --}}
    </div>
</div>
@endsection