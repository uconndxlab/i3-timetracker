@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>{{ $project->name }} Unbilled Shifts</h2>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">Back to Admin Dashboard</a>
    </div>

    @if($usersWithUnbilledShifts->isEmpty())
        <div class="alert alert-info" role="alert">
            No users found with unbilled shifts for this project.
        </div>
    @else
        @foreach ($usersWithUnbilledShifts as $user)
            <div class="card mb-3">
                <div class="card-header">
                    <strong>User: {{ $user->name }}</strong> ({{ $user->netid ?? $user->email }})
                </div>
                <div class="card-body">
                    @if($user->shifts->isEmpty())
                        <p>No unbilled shifts found for this user on this project.</p>
                    @else
                        <h5 class="card-title">Unbilled Shifts:</h5>
                        <ul class="list-group list-group-flush">
                            @foreach ($user->shifts as $shift)
                                <li class="list-group-item">
                                    Shift ID: {{ $shift->id }} |
                                    From: {{ \Carbon\Carbon::parse($shift->start_time)->format('Y-m-d H:i') }} |
                                    To: {{ \Carbon\Carbon::parse($shift->end_time)->format('Y-m-d H:i') }}
                                    <a href="{{ route('shifts.show', $shift) }}" class="btn btn-sm btn-outline-info float-end ms-2">View Shift</a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        @endforeach
    @endif
</div>
@endsection