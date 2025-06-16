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
                <div class="list-group-item py-4 px-4 mb-3 shadow-sm rounded" style="border: 1.5px solid #e5e7eb;">
                    <div class="d-flex w-100 justify-content-between align-items-center mb-2">
                        <div>
                            <h5 class="mb-1 fw-bold" style="font-size: 1.25rem;">
                                {{ $shift->project->name ?? 'N/A' }}
                            </h5>
                        </div>
                        {{-- <div>
                            <span class="fw-semibold" style="font-size: 1rem;">Name:</span>
                            <span style="font-size: 1rem;">{{ $shift->user->name ?? 'N/A (User ID: '.$shift->netid.')' }}</span>
                        </div> --}}
                    </div>
                    <div class="mb-2">
                        <span class="badge bg-light text-dark border me-2" style="font-size: 1rem;">
                            <i class="bi bi-calendar-event me-1"></i>
                            {{ $shift->start_time ? $shift->start_time->format('D, M j, Y') : 'N/A' }}
                        </span>
                        <span class="badge bg-light text-dark border" style="font-size: 1rem;">
                            <i class="bi bi-clock me-1"></i>
                            {{ $shift->start_time ? $shift->start_time->format('g:i A') : 'N/A' }}
                            &ndash;
                            {{ $shift->end_time ? $shift->end_time->format('g:i A') : 'N/A' }}
                        </span>
                    </div>
                    <div class="mb-2">
                        <span class="text-muted" style="font-size: 0.98rem;">
                            <i class="bi bi-building"></i>
                            Entered in University System:
                            <span class="{{ $shift->entered ? 'text-success' : 'text-danger' }}">
                                {{ $shift->entered ? 'Yes' : 'No' }}
                            </span>
                        </span>
                        <br>
                        <span class="text-muted" style="font-size: 0.98rem;">
                            <i class="bi bi-cash-coin"></i>
                            Billed in Cider:
                            <span class="{{ $shift->billed ? 'text-success' : 'text-danger' }}">
                                {{ $shift->billed ? 'Yes' : 'No' }}
                            </span>
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection