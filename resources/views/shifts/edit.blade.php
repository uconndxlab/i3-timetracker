@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="bi bi-pencil-square me-2"></i> Edit Shift
                    </h5>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger mb-4">
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

                        <div class="mb-3">
                            <label for="proj_id" class="form-label">Project</label>
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
                        <div class="mb-3">
                            <label for="netid" class="form-label">Staff Member</label>
                            <input type="text" class="form-control @error('netid') is-invalid @enderror" 
                                id="netid" name="netid" value="{{ old('netid', $shift->netid) }}" readonly>
                            @error('netid')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        @else
                            <input type="hidden" name="netid" value="{{ auth()->user()->netid }}">
                        @endif

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="start_time" class="form-label">Start Time</label>
                                    <input type="datetime-local" class="form-control @error('start_time') is-invalid @enderror" 
                                           id="start_time" name="start_time" 
                                           value="{{ old('start_time', $shift->start_time->format('Y-m-d\TH:i')) }}" required>
                                    @error('start_time')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="end_time" class="form-label">End Time</label>
                                    <input type="datetime-local" class="form-control @error('end_time') is-invalid @enderror" 
                                           id="end_time" name="end_time" 
                                           value="{{ old('end_time', $shift->end_time->format('Y-m-d\TH:i')) }}" required>
                                    @error('end_time')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
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
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input @error('billed') is-invalid @enderror" 
                                       id="billed" name="billed" value="1" 
                                       {{ old('billed', $shift->billed) ? 'checked' : '' }}>
                                <label class="form-check-label" for="billed">
                                    Billed in Cider
                                </label>
                                @error('billed')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        @else
                            <input type="hidden" name="billed" value="{{ $shift->billed ? '1' : '0' }}">
                        @endif

                        <div class="d-flex justify-content-between mt-4">
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
@endsection