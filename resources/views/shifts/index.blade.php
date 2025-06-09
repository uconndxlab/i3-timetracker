@extends('layouts.app')

@section('content')

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>All Shifts</h1>
        <a href="{{ route('shifts.create') }}" class="btn btn-primary">Add Shift</a>
    </div>

    @if($shifts->isEmpty())
        <p>No shifts found.</p>
    @else
        <div class="list-group">

            @foreach ($shifts as $shift)
                <a href="{{ route('shifts.show', $shift) }}" class="list-group-item list-group-item-action">
                    <div class="d-flex w-100 justify-content-between">
                        <h5 class="mb-1">Shift for: {{ $shift->project->name ?? 'N/A' }}</h5>
                        <small>User: {{ $shift->user->name ?? 'N/A (User ID: '.$shift->netid.')' }}</small>
                    </div>
                    
                    <p class="mb-1">
                        From: {{ \Carbon\Carbon::parse($shift->start_time)->format('Y-m-d H:i') }}
                        To: {{ \Carbon\Carbon::parse($shift->end_time)->format('Y-m-d H:i') }}
                    </p>

                    <small>Entered in University System: {{ $shift->entered ? 'Yes' : 'No' }}</small>
                    <small>Billed in Cider: {{ $shift->billed ? 'Yes' : 'No' }}</small>
                </a>
            @endforeach

        </div>
    @endif
</div>
@endsection