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
            <p><strong>Created At:</strong> {{ $project->created_at->format('M d, Y') }}</p>
            <p><strong>Last Updated:</strong> {{ $project->updated_at->format('M d, Y') }}</p>

            <hr>
            <h4>Associated Shifts</h4>

            @if($project->shifts && $project->shifts->count() > 0)
                <ul class="list-group">

                    @foreach($project->shifts as $shift)
                        <li class="list-group-item">
                            User: {{ $shift->user->name ?? 'N/A (User ID: '.$shift->netid.')' }} | 
                            From: {{ \Carbon\Carbon::parse($shift->start_time)->format('Y-m-d H:i') }} | 
                            To: {{ \Carbon\Carbon::parse($shift->end_time)->format('Y-m-d H:i') }} |
                            Billed: {{ $shift->billed ? 'Yes' : 'No' }}
                            <a href="{{ route('shifts.show', $shift) }}" class="btn btn-sm btn-outline-info float-end">View Shift</a>
                        </li>
                    @endforeach

                </ul>
            @else
                <p>No shifts associated with this project yet.</p>
            @endif
        </div>

        <div class="card-footer">
            <a href="{{ route('projects.edit', $project) }}" class="btn btn-warning me-2">Edit Project</a>
            <a href="{{ route('projects.index') }}" class="btn btn-secondary">Back to Projects</a>
        </div>
    </div>
</div>
@endsection
