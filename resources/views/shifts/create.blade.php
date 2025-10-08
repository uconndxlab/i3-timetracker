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
                                <label for="start_time" class="form-label">
                                    <i class="bi bi-play-circle me-1"></i>Start Time *
                                </label>
                                <input type="datetime-local" class="form-control @error('start_time') is-invalid @enderror" 
                                       id="start_time" name="start_time" value="{{ old('start_time', $defaultStartTime) }}" required>
                                @error('start_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror

                                
                            </div>

                            <div class="col-md-6">
                                <label for="end_time" class="form-label">
                                    <i class="bi bi-stop-circle me-1"></i>End Time *
                                </label>
                                <input type="datetime-local" class="form-control @error('end_time') is-invalid @enderror" 
                                       id="end_time" name="end_time" value="{{ old('end_time', $defaultEndTime) }}" required>
                                @error('end_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12 d-flex justify-content-end">
                                <div class="mt-2">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-outline-secondary" onclick="adjustTime('end_time', -60)">-1hr</button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="adjustTime('end_time', -30)">-30min</button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="adjustTime('end_time', -15)">-15min</button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="setCurrentTime('end_time')">Now</button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="adjustTime('end_time', 15)">+15min</button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="adjustTime('end_time', 30)">+30min</button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="adjustTime('end_time', 60)">+1hr</button>
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
function adjustTime(inputId, minutes) {
    const input = document.getElementById(inputId);
    if (!input.value) {
        setCurrentTime(inputId);
        return;
    }
    const currentTime = new Date(input.value);
    currentTime.setMinutes(currentTime.getMinutes() + minutes);
    const year = currentTime.getFullYear();
    const month = String(currentTime.getMonth() + 1).padStart(2, '0');
    const day = String(currentTime.getDate()).padStart(2, '0');
    const hours = String(currentTime.getHours()).padStart(2, '0');
    const mins = String(currentTime.getMinutes()).padStart(2, '0');
    input.value = `${year}-${month}-${day}T${hours}:${mins}`;
}

function setCurrentTime(inputId) {
    const input = document.getElementById(inputId);
    const now = new Date();
    const easternTime = new Date(now.toLocaleString("en-US", {timeZone: "America/New_York"}));
    const year = easternTime.getFullYear();
    const month = String(easternTime.getMonth() + 1).padStart(2, '0');
    const day = String(easternTime.getDate()).padStart(2, '0');
    const hours = String(easternTime.getHours()).padStart(2, '0');
    const mins = String(easternTime.getMinutes()).padStart(2, '0');
    
    input.value = `${year}-${month}-${day}T${hours}:${mins}`;
}
</script>

@endsection