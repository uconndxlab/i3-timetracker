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
                            {{-- <p><strong>Created At:</strong> {{ $project->created_at->format('M d, Y') }}</p> --}}
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


                                    @if(auth()->user()->is_admin && $unbilledShiftCount > 0)
                                        <button class="btn btn-warning mt-3 w-100" data-bs-toggle="modal" data-bs-target="#markBilledModal">
                                            Mark Remaining Hours as Billed
                                        </button>
                                    @endif
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

<!-- Mark Remaining as Billed Confirmation Modal -->
<div class="modal fade" id="markBilledModal" tabindex="-1" aria-labelledby="markBilledModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="markBilledModalLabel">
                    <i class="bi bi-exclamation-triangle me-2"></i>Confirm Mark as Billed
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning" role="alert">
                    <i class="bi bi-exclamation-circle me-2"></i>
                    <strong>Warning:</strong> This action cannot be undone!
                </div>
                
                <h6 class="mb-3">You are about to mark the following as billed:</h6>
                
                <div class="card bg-light mb-3">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6">
                                <div class="text-center">
                                    <div class="text-muted small">Shifts</div>
                                    <div class="h3 mb-0 text-warning">{{ $unbilledShiftCount }}</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center">
                                    <div class="text-muted small">Hours</div>
                                    <div class="h3 mb-0 text-warning">{{ number_format($unbilledHours, 2) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <p class="mb-2"><strong>Project:</strong> {{ $project->name }}</p>
                <p class="text-muted small mb-0">
                    Once marked as billed, these shifts cannot be automatically reverted. 
                    You would need to manually update each shift individually to mark them as unbilled again.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i>Cancel
                </button>
                <form action="{{ route('admin.projects.mark-remaining-billed', $project) }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-check-circle me-1"></i>Mark as Billed
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection