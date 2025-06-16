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
                <div class="card-header bg-light">
                    <strong>{{ $user->name }}</strong>
                </div>
                <div class="card-body p-0">
                    @if($user->shifts->isEmpty())
                        <p class="m-3">No unbilled shifts found for this user on this project.</p>
                    @else
                        <ul class="list-group list-group-flush">
                            @foreach ($user->shifts as $shift)
                                @php
                                    $from = \Carbon\Carbon::parse($shift->start_time);
                                    $to = \Carbon\Carbon::parse($shift->end_time);
                                    $duration = $from && $to ? $from->diff($to)->format('%h hr %i min') : 'N/A';
                                @endphp
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="fw-semibold">{{ $from->format('M d, Y') }}</span>
                                        &nbsp;|&nbsp;
                                        <span>From: {{ $from->format('g:i A') }}</span>
                                        &ndash;
                                        <span>To: {{ $to->format('g:i A') }}</span>
                                        &nbsp;|&nbsp;
                                        <span>Duration: {{ $duration }}</span>
                                    </div>
                                    <a href="{{ route('shifts.show', $shift) }}" class="btn btn-sm btn-outline-info ms-2">View Shift</a>
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