@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="bi bi-pencil-square me-2"></i> Edit Shift
                    </h5>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger mb-3">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('shifts.update', $shift) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <input type="hidden" id="duration" name="duration" value="{{ old('duration', $shift->duration) }}">

                        <div class="row mb-3">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label for="proj_id" class="form-label">Project *</label>
                                <select name="proj_id" id="proj_id" class="form-select @error('proj_id') is-invalid @enderror" required>
                                    <option value="">Select a project...</option>
                                    @foreach($projects as $project)
                                        <option value="{{ $project->id }}" 
                                                {{ old('proj_id', $shift->proj_id) == $project->id ? 'selected' : '' }}>
                                            {{ $project->name }}{{ !$project->active ? ' (Inactive)' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('proj_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            @if(auth()->user()->isAdmin())
                            <div class="col-md-6">
                                <label for="netid" class="form-label">Staff Member *</label>
                                <input type="text" class="form-control @error('netid') is-invalid @enderror" 
                                    id="netid" name="netid" value="{{ old('netid', $shift->netid) }}" readonly>
                                @error('netid')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            @else
                                <input type="hidden" name="netid" value="{{ auth()->user()->netid }}">
                            @endif
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label for="date" class="form-label">
                                    <i class="bi bi-calendar me-1"></i>Date *
                                </label>
                                <input type="date" class="form-control @error('date') is-invalid @enderror" 
                                       id="date" name="date" 
                                       value="{{ old('date', $shift->date->format('Y-m-d')) }}" required>
                                @error('date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6">
                                <label for="duration-hours-input" class="form-label">
                                    <i class="bi bi-clock-history me-1"></i>Duration (hours) *
                                </label>
                                <input type="number" class="form-control @error('duration') is-invalid @enderror" 
                                       id="duration-hours-input" 
                                       value="{{ old('duration') ? number_format(old('duration') / 60, 2) : number_format($shift->duration / 60, 2) }}" 
                                       min="0" step="0.25" required>
                                @error('duration')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror

                                <div class="mt-2">
                                    <div class="btn-group btn-group-sm w-100" role="group" style="flex-wrap: wrap;">
                                        <button type="button" class="btn btn-outline-secondary" onclick="adjustDuration(-30)">-30min</button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="adjustDuration(-15)">-15min</button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="setDuration(0)">0min</button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="adjustDuration(15)">+15min</button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="adjustDuration(30)">+30min</button>
                                    </div>
                                    <div class="btn-group btn-group-sm w-100 mt-1" role="group">
                                        <button type="button" class="btn btn-outline-secondary" onclick="adjustDuration(-60)">-1hr</button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="adjustDuration(60)">+1hr</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6 mb-2 mb-md-0">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input @error('entered') is-invalid @enderror" 
                                           id="entered" name="entered" value="1" 
                                           {{ old('entered', $shift->entered) ? 'checked' : '' }}
                                           {{ !auth()->user()->isAdmin() && $shift->entered ? 'disabled' : '' }}>
                                    <label class="form-check-label" for="entered">
                                        Entered in University System (Timecard)
                                    </label>
                                    @if(!auth()->user()->isAdmin() && $shift->entered)
                                        <input type="hidden" name="entered" value="1">
                                    @endif
                                    @error('entered')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            @if(auth()->user()->isAdmin())
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input @error('billed') is-invalid @enderror" 
                                           id="billed" name="billed" value="1" 
                                           {{ old('billed', $shift->billed) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="billed">
                                        Billed in Honeycrisp
                                    </label>
                                    @error('billed')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            @else
                                <input type="hidden" name="billed" value="{{ $shift->billed ? '1' : '0' }}">
                            @endif
                        </div>

                        <hr class="my-3">

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('shifts.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Back to Shifts
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> Update Shift
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function adjustDuration(minutes) {
    const hiddenInput = document.getElementById('duration');
    const hoursInput = document.getElementById('duration-hours-input');
    
    let currentMinutes = parseInt(hiddenInput.value);
    if (isNaN(currentMinutes)) {
        currentMinutes = 0;
    }
    
    let newMinutes = currentMinutes + minutes;

    if (newMinutes < 0) {
        newMinutes = 0;
    }

    hiddenInput.value = newMinutes;
    hoursInput.value = (newMinutes / 60).toFixed(2);
}

function setDuration(minutes) {
    const hiddenInput = document.getElementById('duration');
    const hoursInput = document.getElementById('duration-hours-input');
    
    hiddenInput.value = minutes;
    hoursInput.value = (minutes / 60).toFixed(2);
}

function updateDurationFromHours() {
    const hoursInput = document.getElementById('duration-hours-input');
    const hiddenInput = document.getElementById('duration');
    
    let hours = parseFloat(hoursInput.value);
    if (isNaN(hours) || hours < 0) {
        hours = 0;
    }
    
    const minutes = Math.round(hours * 60);
    hiddenInput.value = minutes;
}

document.addEventListener('DOMContentLoaded', function() {
    const hoursInput = document.getElementById('duration-hours-input');
    hoursInput.addEventListener('input', updateDurationFromHours);
});
</script>
@endsection