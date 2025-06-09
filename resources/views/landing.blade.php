@extends('layouts.app')

@section('content')

<div class="container mt-4">
    <div class="p-5 mb-4 bg-light rounded-3">
        <div class="container-fluid py">
            <h1 class="display-5 fw-bold">Welcome to i3 Time Tracker</h1>
            <p class="col-md-8 fs-4">Track time spent working on various projects.</p>
            <a href="{{ route('shifts.create') }}" class="btn btn-primary btn-md" type="button">Log Shift</a>
            <a href="{{ route('projects.create') }}" class="btn btn-secondary btn-md" type="button">Record New Project</a>
        </div>
    </div>

    <div class="row align-items-md-stretch">
        <div class="col-md-12">
            <div class="h-100 p-5 border rounded-3">
                <h2>Currently Active Projects</h2>
                @if($activeProjects->isEmpty())
                    <p>No active projects at the moment.</p>
                @else
                    <div class="list-group mt-3">
                        @foreach ($activeProjects as $project)
                            <a href="/projects/{{ $project->id }}" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mb-1">{{ $project->name }}</h5>
                                    <small>Last updated: {{ $project->updated_at->diffForHumans() }}</small>
                                </div>
                                <p class="mb-1">{{ Str::limit($project->desc, 100) ?: 'No description available.' }}</p>
                            </a>
                        @endforeach
                    </div>
                @endif
                 <div class="mt-4">
                    <a href="{{ route('projects.index') }}" class="btn btn-outline-secondary">View All Projects</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection