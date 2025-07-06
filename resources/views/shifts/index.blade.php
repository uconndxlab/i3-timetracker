@extends('layouts.app')

@section('content')

<div class="mt-4">
    <div class="page-header text-center">
        <div class="container">
            <h1 class="display-5">
                <i class="bi bi-clock-history me-3"></i>
                All Shifts
            </h1>
            <p class="lead mb-0">Track and manage all recorded work shifts</p>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <span class="text-muted">
                <i class="bi bi-info-circle me-1"></i>
                Showing {{ $shifts->count() }} shift{{ $shifts->count() !== 1 ? 's' : '' }}
            </span>
        </div>
        <a href="{{ route('shifts.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i>Log New Shift
        </a>
    </div>

    @if($shifts->isEmpty())
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-clock-history text-muted" style="font-size: 4rem;"></i>
                <h4 class="text-muted mt-3">No Shifts Recorded</h4>
            </div>
        </div>
    @else
        <div class="row">
            @foreach ($shifts as $shift)
                <div class="col-lg-6 mb-4">
                    <div class="shift-item">
                        <div class="d-flex w-100 justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="mb-2 fw-bold text-primary">
                                    <i class="bi bi-folder me-2"></i>
                                    {{ $shift->project->name ?? 'N/A' }}
                                </h5>
                                <div class="d-flex flex-wrap gap-2 mb-2">
                                    <span class="badge bg-light text-dark border">
                                        <i class="bi bi-calendar-event me-1"></i>
                                        {{ $shift->start_time ? $shift->start_time->format('D, M j, Y') : 'N/A' }}
                                    </span>
                                    <span class="badge bg-light text-dark border">
                                        <i class="bi bi-clock me-1"></i>
                                        {{ $shift->start_time ? $shift->start_time->format('g:i A') : 'N/A' }}
                                        &ndash;
                                        {{ $shift->end_time ? $shift->end_time->format('g:i A') : 'N/A' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="border-end">
                                    <span class="text-muted small d-block">University System</span>
                                    <span class="{{ $shift->entered ? 'text-success' : 'text-danger' }} fw-semibold">
                                        <i class="bi bi-{{ $shift->entered ? 'check-circle-fill' : 'x-circle-fill' }} me-1"></i>
                                        {{ $shift->entered ? 'Recorded' : 'Not Recorded' }}
                                    </span>
                                </div>
                            </div>
                            <div class="col-6">
                                <span class="text-muted small d-block">Billing Status</span>
                                <span class="{{ $shift->billed ? 'text-success' : 'text-danger' }} fw-semibold">
                                    <i class="bi bi-{{ $shift->billed ? 'currency-dollar' : 'clock-fill' }} me-1"></i>
                                    {{ $shift->billed ? 'Billed' : 'Unbilled' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection