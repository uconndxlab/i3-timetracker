@extends('layouts.app')

@section('content')
<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Unbilled Shifts: {{ $project->name }}</h1>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($usersWithUnbilledShifts->isEmpty())
        <div class="alert alert-info">No unbilled shifts found for this project.</div>
    @else
        @foreach($usersWithUnbilledShifts as $user)
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">{{ $user->name }} ({{ $user->netid }})</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Start Time</th>
                                    <th>End Time</th>
                                    <th>Duration</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($user->shifts as $shift)
                                    <tr>
                                        <td>{{ $shift->start_time->format('Y-m-d') }}</td>
                                        <td>{{ $shift->start_time->format('H:i') }}</td>
                                        <td>{{ $shift->end_time->format('H:i') }}</td>
                                        <td>{{ $shift->start_time->diffInHours($shift->end_time) }} hrs</td>
                                        <td>
                                            <form action="{{ route('admin.shifts.mark-entered', $shift) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <button type="submit" class="btn btn-sm btn-success">
                                                    <i class="bi bi-check-circle"></i> Mark as Entered
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach
    @endif
</div>
@endsection
