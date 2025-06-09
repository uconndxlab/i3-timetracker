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
            <p><strong>User:</strong> {{ $shift->user->name ?? 'N/A' }} (ID: {{ $shift->netid }})</p>
            <p><strong>Project:</strong> {{ $shift->project->name ?? 'N/A' }} (ID: {{ $shift->proj_id }})</p>
            <p><strong>Start Time:</strong> {{ $shift->start_time ? $shift->start_time->format('M d, Y H:i A') : 'N/A' }}</p>
            <p><strong>End Time:</strong> {{ $shift->end_time ? $shift->end_time->format('M d, Y H:i A') : 'N/A' }}</p>
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
            <p><strong>Created At:</strong> {{ $shift->created_at ? $shift->created_at->format('M d, Y H:i A') : 'N/A' }}</p>
            <p><strong>Last Updated:</strong> {{ $shift->updated_at ? $shift->updated_at->format('M d, Y H:i A') : 'N/A' }}</p>
        </div>

        <div class="card-footer">
            <a href="{{ route('shifts.index') }}" class="btn btn-secondary">Back to Shifts</a>
        </div>
    </div>
</div>
@endsection