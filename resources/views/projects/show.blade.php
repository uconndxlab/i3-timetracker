@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2>{{ $project->name }}</h2>
                    <div>
                        <a href="{{ route('shifts.create', ['proj_id' => $project->id]) }}" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-2"></i>Add New Shift
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <p><strong>Description:</strong> {{ $project->desc ?: 'N/A' }}</p>
                            <p><strong>Status:</strong> {{ $project->active ? 'Active' : 'Inactive' }}</p>
                            <p><strong>Created At:</strong> {{ $project->created_at->format('M d, Y') }}</p>
                            <p><strong>Last Updated:</strong> {{ $project->updated_at->format('M d, Y') }}</p>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5 class="card-title">Project Hours</h5>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Total Hours:</span>
                                        <span class="fw-bold">{{ number_format($totalHours, 2) }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Billed Hours:</span>
                                        <span class="text-success fw-bold">{{ number_format($billedHours, 2) }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Unbilled Hours:</span>
                                        <span class="text-warning fw-bold">{{ number_format($unbilledHours, 2) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h4 class="mb-3">Project Shifts</h4>

                    @if($project->shifts->count() > 0)
                        @include('partials.table', [
                            'items' => $shifts,
                            'columns' => $shiftColumns,
                            'actions' => $shiftActions,
                            'title' => 'Shift',
                            'empty_message' => 'No shifts found for this project.',
                            'create_route' => 'shifts.create',
                            'create_params' => ['project_id' => $project->id],
                            'create_label' => 'Add New Shift'
                        ])
                    @else
                        <div class="alert alert-info">
                            No shifts associated with this project yet.
                            <a href="{{ route('shifts.create', ['project_id' => $project->id]) }}" class="alert-link">Add your first shift</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection