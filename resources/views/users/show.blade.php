@extends('layouts.app')
@section('content')
<div class="container mt-4">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h2>{{ $user->name }}</h2>
            </div>
        </div>
        <div class="card-body">
            <p><strong>Email:</strong> {{ $user->email }}</p>
            <p><strong>NetID:</strong> {{ $user->netid }}</p>

            <hr>
            <h4>Activity</h4>

            @if($user->projects && $user->projects->count() > 0)
                <ul class="list-group">
                    @foreach($user->projects as $project)
                        <li class="list-group-item">
                            <strong>{{ $project->name }}</strong> - 
                            <span class="text-muted">{{ $project->desc ?: 'No description available.' }}</span>
                            <span class="badge bg-secondary float-end">{{ $project->active ? 'Active' : 'Inactive' }}</span>
                            <a href="{{ route('projects.show', $project) }}" class="btn btn-sm btn-outline-info float-end">View Project</a>
                        </li>
                    @endforeach
                </ul>
            @else
                <p>No projects associated with this user yet.</p>
            @endif
            <hr>
            <h4>Recent Shifts</h4>
            @if($user->shifts && $user->shifts->count() > 0)
                <ul class="list-group">
                    @foreach($user->shifts as $shift)
                        <li class="list-group-item">
                            <strong>{{ $shift->name }}</strong> - 
                            <span class="text-muted">{{ $shift->desc ?: 'No description available.' }}</span>
                            <span class="badge bg-secondary float-end">{{ $shift->active ? 'Active' : 'Inactive' }}</span>
                            <a href="{{ route('shifts.show', $shift) }}" class="btn btn-sm btn-outline-info float-end">View Shift</a>
                        </li>
                    @endforeach
                </ul>
            @else
                <p>No shifts logged by this user yet.</p>
            @endif
        </div>
    </div>
</div>
@endsection