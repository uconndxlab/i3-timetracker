@extends('layouts.app')

@section('content')

<div class="container mt-4">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h2>{{ $project->name }}</h2>
            </div>
        </div>

        <div class="card-body">
            <p><strong>Description:</strong> {{ $project->desc ?: 'N/A' }}</p>
            <p><strong>Status:</strong> {{ $project->active ? 'Active' : 'Inactive' }}</p>
            <p><strong>Started On:</strong> {{ $project->created_at->format('M d, Y') }}</p>
            <p><strong>Last Updated:</strong> {{ $project->updated_at->format('M d, Y') }}</p>

            <hr>
            <h4>Associated Shifts</h4>

            @if($project->shifts && $project->shifts->count() > 0)
                <ul class="list-group">

                @foreach($project->shifts->take(5) as $shift)
                    <li class="list-group-item">
                        <strong>{{ $shift->user->name }}</strong> - 
                        <span class="text-muted">{{ $shift->start_time->format('M d, Y h:i A') }} to {{ $shift->end_time->format('h:i A') }}</span>
                        <span class="badge bg-secondary float-end">{{ $shift->status }}</span>
                        <a href="{{ route('shifts.show', $shift) }}" class="btn btn-sm btn-outline-info float-end">View Shift</a>
                    </li>
                @endforeach

                </ul>
            @else
                <p>No shifts associated with this project yet.</p>
            @endif
        </div>

        <div class="card-footer">
            {{-- appropriate and appealing edit and back to project buttons --}}
            <div class="d-flex justify-content-between">
                <a href="{{ route('projects.edit', $project) }}" class="btn btn-secondary">
                    <i class="bi bi-pencil-square me-1"></i>Edit Project
                </a>
                <a href="{{ route('projects.index') }}" class="btn btn-primary">
                    <i class="bi bi-arrow-left-short me-1"></i>Back to Projects
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
