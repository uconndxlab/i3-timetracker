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

            <h2>Activity</h2>
            <div class="activity-summary">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Projects Summary</h5>
                    </div>

                    <div class="card-body">
                        @if($projects->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Project Name</th>
                                            <th>Description</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($projects as $project)
                                            <tr>
                                                <td>
                                                    <a href="{{ route('projects.show', $project->id) }}">{{ $project->name }}</a>
                                                </td>
                                                <td>{{ $project->desc }}</td>
                                                <td>
                                                    @if($project->active)
                                                        <span class="badge bg-success">Active</span>
                                                    @else
                                                        <span class="badge bg-secondary">Inactive</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p>No projects yet.</p>
                        @endif
                    </div>

                </div>
                
                <h2>Recent Shifts</h2>
                @if($user->shifts->count() > 0)
                    @foreach($user->shifts as $shift)
                        <div class="shift-item">
                            <p>
                                <strong>
                                    {{ $shift->project ? $shift->project->name : 'If assignment works you should see this' }}
                                </strong>
                                
                                @if($shift->start_time)
                                    {{ $shift->start_time->format('M d, Y') }} 
                                    {{ $shift->start_time->format('g:i A') }} - 
                                    {{ $shift->end_time ? $shift->end_time->format('g:i A') : 'In progress' }}
                                @else
                                    No date
                                @endif
                            </p>
                            <a href="{{ route('shifts.show', $shift) }}" class="btn btn-primary">View Shift</a>
                        </div>
                    @endforeach
                @else
                    <p>No shifts logged yet.</p>
                @endif
        </div>
    </div>
</div>
@endsection