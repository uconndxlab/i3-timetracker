@extends('layouts.app')

@section('content')

<div class="container mt-4">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h2>Shift Details</h2>
            </div>
        </div>

        <div class="card-body">
            <p><strong>Name:</strong> {{ $shift->user->name ?? 'N/A' }} </p>
            <p><strong>Project:</strong> {{ $shift->project->name ?? 'N/A' }} </p>
            <p><strong>Start Time:</strong> {{ $shift->start_time->format('M d, Y h:i A') }}</p>
            <p><strong>End Time:</strong> {{ $shift->end_time->format('M d, Y h:i A') }}</p>

            @php
                $durationDisplay = 'N/A';
                if ($shift->start_time && $shift->end_time) {
                    $duration = $shift->start_time->diff($shift->end_time);
                    $durationDisplay = $duration->format('%h hours %i minutes');
                } elseif ($shift->start_time) {
                    $durationDisplay = 'End time missing';
                }
            @endphp
            <p><strong>Duration:</strong> {{ $durationDisplay }}</p>
            <p><strong>Billed:</strong> {{ $shift->billed ? 'Yes' : 'No' }}</p>
        </div>

        <div class="card-footer">
            <a href="{{ route('shifts.index') }}" class="btn btn-secondary">Back to Shifts</a>
        </div>
    </div>
</div>
@endsection