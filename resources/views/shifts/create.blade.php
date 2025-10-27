@extends('layouts.app')

@section('content')

<div class="mt-4">
    <div class="page-header text-center">
        <div class="container">
            <h1 class="display-5">
                <i class="bi bi-plus-square me-3"></i>
                Log New Shift
            </h1>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="mb-0">
                        <i class="bi bi-clock me-2"></i>
                        Shift Information
                    </h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('shifts.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="netid" value="{{ cas()->user() }}">
                        <input type="hidden" id="duration" name="duration" value="{{ old('duration', 60) }}">

                        <div class="mb-4">
                            <label for="proj_id" class="block text-gray-700 text-sm font-bold mb-2">Project:</label>
                            <select name="proj_id" id="proj_id" class="form-select" required>
                                <option value="">Select a project</option>
                                @foreach($projects as $project)
                                    <option value="{{ $project->id }}" {{ (old('proj_id') == $project->id || (isset($selectedProject) && $selectedProject->id == $project->id)) ? 'selected' : '' }}>
                                        {{ $project->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('proj_id')
                                <p class="text-red-500 text-xs italic">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="date" class="form-label">
                                    <i class="bi bi-calendar me-1"></i>Date *
                                </label>
                                <input type="date" class="form-control @error('date') is-invalid @enderror" 
                                       id="date" name="date" value="{{ old('date', $date) }}" required>
                                @error('date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="duration-hours-input" class="form-label">
                                    <i class="bi bi-clock-history me-1"></i>Duration (hours) *
                                </label>
                                <input type="number" class="form-control @error('duration') is-invalid @enderror" 
                                       id="duration-hours-input" value="{{ old('duration') ? number_format(old('duration') / 60, 2) : '1.00' }}" 
                                       min="0" step="0.25" required>
                                @error('duration')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror

                                <div class="mt-2">
                                    <div class="btn-group btn-group-sm" role="group" style="flex-wrap: wrap;">
                                        <button type="button" class="btn btn-outline-secondary" onclick="adjustDuration(-30)">-30min</button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="adjustDuration(-15)">-15min</button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="setDuration(0)">0min</button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="adjustDuration(15)">+15min</button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="adjustDuration(30)">+30min</button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="adjustDuration(-60)">-1hr</button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="adjustDuration(60)">+1hr</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <div class="form-check form-switch">
                                        <input type="hidden" name="entered" value="0">
                                        <input type="checkbox" class="form-check-input @error('entered') is-invalid @enderror" 
                                               id="entered" name="entered" value="1" {{ old('entered', false) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="entered">
                                            <i class="bi bi-building me-1"></i>
                                            <strong>Recorded in University Employee Portal</strong>
                                            <br>
                                            <small class="text-muted">Check if this shift has been recorded in the UConn Employee Portal</small>
                                        </label>
                                        @error('entered')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4"> 

                        <div class="d-flex justify-content-end gap-2"> 
                            <a href="{{ route('shifts.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle me-1"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-1"></i>Log Shift
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

    if (newMinutes < 0) { // just in case someone tries something funny
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