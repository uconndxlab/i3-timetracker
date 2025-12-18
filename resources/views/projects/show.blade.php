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

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="mb-0">Project Shifts</h4>
                        
                        @if(auth()->user()->is_admin)
                            <div id="billingModeControls">
                                <button id="startBillingMode" class="btn btn-outline-primary">
                                    <i class="bi bi-pencil-square me-1"></i>Start Billing Mode
                                </button>
                                <div id="billingModeActions" style="display: none;">
                                    <button id="cancelBillingMode" class="btn btn-outline-secondary me-2">
                                        <i class="bi bi-x-circle me-1"></i>Cancel
                                    </button>
                                    <button id="completeBillingMode" class="btn btn-success">
                                        <i class="bi bi-check-circle me-1"></i>Complete Billing
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>

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

<!-- Complete Billing Mode Confirmation Modal -->
<div class="modal fade" id="completeBillingModal" tabindex="-1" aria-labelledby="completeBillingModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="completeBillingModalLabel">
                    <i class="bi bi-check-circle me-2"></i>Complete Billing Update
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6 class="mb-3">Summary of Changes:</h6>
                
                <div class="card bg-light mb-3">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6">
                                <div class="text-center">
                                    <div class="text-muted small">Shifts Modified</div>
                                    <div class="h3 mb-0 text-primary" id="modifiedShiftsCount">0</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center">
                                    <div class="text-muted small">Hours Newly Billed</div>
                                    <div class="h3 mb-0 text-success" id="newlyBilledHours">0.00</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <h6 class="mb-2">Breakdown by User:</h6>
                <div id="userBreakdown" class="mb-3" style="max-height: 250px; overflow-y: auto;">
                    <!-- Per-user breakdown will be listed here -->
                </div>
                
                <h6 class="mb-2">Detailed Changes:</h6>
                <div id="changesDetail" class="mb-3" style="max-height: 200px; overflow-y: auto;">
                    <!-- Changes will be listed here -->
                </div>
                
                <p class="mb-2"><strong>Project:</strong> {{ $project->name }}</p>
                <p class="text-muted small mb-0">
                    These changes will be applied immediately.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i>Cancel
                </button>
                <button type="button" id="confirmBillingUpdate" class="btn btn-primary">
                    <i class="bi bi-check-circle me-1"></i>Apply Changes
                </button>
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

@push('scripts')
<script>
(function() {
    let billingModeActive = false;
    let changedShifts = new Map(); // Map of shift_id -> {field: value}
    
    const startBtn = document.getElementById('startBillingMode');
    const cancelBtn = document.getElementById('cancelBillingMode');
    const completeBtn = document.getElementById('completeBillingMode');
    const billingModeActions = document.getElementById('billingModeActions');
    const checkboxes = document.querySelectorAll('.billing-checkbox');
    const table = document.querySelector('#dynamic-table-container .table');
    
    if (startBtn && cancelBtn && completeBtn) {
        startBtn.addEventListener('click', function() {
            billingModeActive = true;
            startBtn.style.display = 'none';
            billingModeActions.style.display = 'block';
            
            // Add danger border to table
            if (table) {
                table.classList.add('border', 'border-danger', 'border-3');
            }
            
            // Enable checkboxes for billed and entered columns
            checkboxes.forEach(checkbox => {
                const field = checkbox.dataset.field;
                if (field === 'billed' || field === 'entered') {
                    checkbox.disabled = false;
                    checkbox.style.cursor = 'pointer';
                }
            });
        });
        
        cancelBtn.addEventListener('click', function() {
            exitBillingMode();
        });
        
        completeBtn.addEventListener('click', function() {
            showCompletionModal();
        });
        
        // Track checkbox changes
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                if (!billingModeActive) return;
                
                const shiftId = this.dataset.shiftId;
                const field = this.dataset.field;
                const originalValue = this.dataset.originalValue;
                const newValue = this.checked ? '1' : '0';
                
                if (!changedShifts.has(shiftId)) {
                    changedShifts.set(shiftId, {});
                }
                
                if (originalValue !== newValue) {
                    changedShifts.get(shiftId)[field] = this.checked;
                } else {
                    // If reverted to original, remove from changes
                    delete changedShifts.get(shiftId)[field];
                    if (Object.keys(changedShifts.get(shiftId)).length === 0) {
                        changedShifts.delete(shiftId);
                    }
                }
                
                // Update button state
                completeBtn.disabled = changedShifts.size === 0;
            });
        });
    }
    
    function exitBillingMode() {
        billingModeActive = false;
        startBtn.style.display = 'block';
        billingModeActions.style.display = 'none';
        
        // Remove danger border from table
        if (table) {
            table.classList.remove('border', 'border-danger', 'border-3');
        }
        
        // Reset all checkboxes to original state
        checkboxes.forEach(checkbox => {
            const field = checkbox.dataset.field;
            if (field === 'billed' || field === 'entered') {
                checkbox.disabled = true;
                checkbox.style.cursor = 'default';
                checkbox.checked = checkbox.dataset.originalValue === '1';
            }
        });
        
        changedShifts.clear();
    }
    
    function showCompletionModal() {
        if (changedShifts.size === 0) {
            alert('No changes to apply.');
            return;
        }
        
        // Calculate summary and per-user breakdown
        let newlyBilledHours = 0;
        let changesHtml = '<ul class="list-unstyled">';
        let userStats = new Map(); // userName -> { shifts: count, hours: total, newlyBilledHours: total }
        
        changedShifts.forEach((changes, shiftId) => {
            const row = document.querySelector(`[data-shift-id="${shiftId}"]`).closest('tr');
            const dateCell = row.cells[0].textContent.trim();
            const nameCell = row.cells[1].textContent.trim();
            const durationText = row.cells[2].textContent.trim();
            const hours = parseFloat(durationText.replace(' hr', '')) || 0;
            
            // Initialize user stats if not exists
            if (!userStats.has(nameCell)) {
                userStats.set(nameCell, { shifts: 0, hours: 0, newlyBilledHours: 0 });
            }
            
            // Update user stats
            const stats = userStats.get(nameCell);
            stats.shifts += 1;
            stats.hours += hours;
            
            let changeText = '';
            if (changes.hasOwnProperty('billed')) {
                changeText += changes.billed ? 'Marked as billed' : 'Unmarked as billed';
                if (changes.billed) {
                    // Check if it wasn't already billed
                    const billedCheckbox = row.querySelector('[data-field="billed"]');
                    if (billedCheckbox.dataset.originalValue === '0') {
                        newlyBilledHours += hours;
                        stats.newlyBilledHours += hours;
                    }
                }
            }
            if (changes.hasOwnProperty('entered')) {
                if (changeText) changeText += ', ';
                changeText += changes.entered ? 'Marked as entered' : 'Unmarked as entered';
            }
            
            changesHtml += `<li class="mb-2">
                <strong>${dateCell}</strong> - ${nameCell} (${durationText})<br>
                <small class="text-muted">${changeText}</small>
            </li>`;
        });
        
        changesHtml += '</ul>';
        
        // Build user breakdown HTML
        let userBreakdownHtml = '<div class="list-group">';
        userStats.forEach((stats, userName) => {
            userBreakdownHtml += `
                <div class="list-group-item noani">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${userName}</strong>
                        </div>
                        <div class="text-end">
                            <small class="text-muted d-block">${stats.shifts} shift${stats.shifts !== 1 ? 's' : ''} modified</small>
                            <small class="text-muted d-block">${stats.hours.toFixed(2)} hr total</small>
                            ${stats.newlyBilledHours > 0 ? `<small class="text-success d-block"><strong>${stats.newlyBilledHours.toFixed(2)} hr newly billed</strong></small>` : ''}
                        </div>
                    </div>
                </div>
            `;
        });
        userBreakdownHtml += '</div>';
        
        document.getElementById('modifiedShiftsCount').textContent = changedShifts.size;
        document.getElementById('newlyBilledHours').textContent = newlyBilledHours.toFixed(2);
        document.getElementById('userBreakdown').innerHTML = userBreakdownHtml;
        document.getElementById('changesDetail').innerHTML = changesHtml;
        
        new bootstrap.Modal(document.getElementById('completeBillingModal')).show();
    }
    
    // Handle confirmation
    const confirmBtn = document.getElementById('confirmBillingUpdate');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function() {
            // Convert changedShifts Map to object for submission
            const updates = {};
            changedShifts.forEach((changes, shiftId) => {
                updates[shiftId] = changes;
            });
            
            // Submit via fetch
            fetch('{{ route("admin.projects.batch-update-shifts", $project) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ updates: updates })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Close modal and reload page
                    bootstrap.Modal.getInstance(document.getElementById('completeBillingModal')).hide();
                    window.location.reload();
                } else {
                    alert('Error updating shifts: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating shifts.');
            });
        });
    }
})();
</script>
@endpush