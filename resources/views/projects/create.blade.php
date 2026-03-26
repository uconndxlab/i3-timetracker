@extends('layouts.app')

@section('content')
<div class="app-page">
    <div class="app-page-hero">
        <h1 class="app-page-title">Create New Project</h1>
    </div>

    <div class="app-panel">
                <h3 class="app-panel-title">Project Details</h3>
                    <form action="{{ route('projects.store') }}" method="POST">
                        @csrf
                        
                        <div class="mb-4">
                            <label for="name" class="form-label">
                                Project Name
                            </label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" value="{{ old('name') }}" 
                                   placeholder="Enter project name" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- add desc field --}}
                        <div class="mb-4">
                            <label for="description" class="form-label">
                                Project Description
                            </label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <div class="form-check form-switch">
                                <input type="hidden" name="active" value="0">
                                <input type="checkbox" class="form-check-input" id="active" name="active" value="1" {{ old('active', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="active">
                                    <strong>Active Project</strong>
                                    <br>
                                </label>
                                @error('active')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="form-check form-switch">
                                <input type="hidden" name="assign_all_users" value="0">
                                <input type="checkbox" class="form-check-input" id="assign_all_users" name="assign_all_users" value="1" {{ old('assign_all_users', false) ? 'checked' : '' }}>
                                <label class="form-check-label" for="assign_all_users">
                                    <strong>Assign to All Users</strong>
                                </label>
                                @error('assign_all_users')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('projects.index') }}" class="btn btn-outline-secondary">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                Create Project
                            </button>
                        </div>
                    </form>
    </div>
</div>
@endsection