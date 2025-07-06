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

                        <div class="mb-4">
                            <label for="netid" class="form-label">
                                <i class="bi bi-person me-1"></i>Employee Name *
                            </label>
                            <select class="form-select @error('netid') is-invalid @enderror" id="netid" name="netid" required>
                                <option value="">Select an employee...</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->netid }}" {{ old('netid') == $user->netid ? 'selected' : '' }}>
                                        {{ $user->name }} ({{ $user->netid }})
                                    </option>
                                @endforeach
                            </select>
                            @error('netid')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="proj_id" class="form-label">
                                <i class="bi bi-folder me-1"></i>Project *
                            </label>
                            <select name="proj_id" id="proj_id" class="form-select @error('proj_id') is-invalid @enderror" required>
                                <option value="">Select a project...</option>
                                @foreach($projects as $project)
                                    <option value="{{ $project->id }}" {{ old('proj_id') == $project->id ? 'selected' : '' }}>
                                        {{ $project->name }}{{ !$project->active ? ' (Inactive)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('proj_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="start_time" class="form-label">
                                    <i class="bi bi-play-circle me-1"></i>Start Time *
                                </label>
                                <input type="datetime-local" class="form-control @error('start_time') is-invalid @enderror" 
                                       id="start_time" name="start_time" value="{{ old('start_time') }}" required>
                                @error('start_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="end_time" class="form-label">
                                    <i class="bi bi-stop-circle me-1"></i>End Time *
                                </label>
                                <input type="datetime-local" class="form-control @error('end_time') is-invalid @enderror" 
                                       id="end_time" name="end_time" value="{{ old('end_time') }}" required>
                                @error('end_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
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

@endsection