@extends('layouts.app')

@section('content')

<div class="mt-4">
    <div class="page-header text-center">
        <div class="container">
            <h1 class="display-5">
                <i class="bi bi-folder-plus me-3"></i>
                Create New Project
            </h1>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="mb-0">
                        <i class="bi bi-pencil-square me-2"></i>
                        Project Details
                    </h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('projects.store') }}" method="POST">
                        @csrf
                        
                        <div class="mb-4">
                            <label for="name" class="form-label">
                                <i class="bi bi-type me-1"></i>Project Name *
                            </label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" value="{{ old('name') }}" 
                                   placeholder="Enter project name" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="desc" class="form-label">
                                <i class="bi bi-text-paragraph me-1"></i>Description
                            </label>
                            <textarea class="form-control @error('desc') is-invalid @enderror" 
                                      id="desc" name="desc" rows="4" 
                                      placeholder="Describe the project purpose and goals...">{{ old('desc') }}</textarea>
                            @error('desc')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Optional</small>
                        </div>

                        <div class="mb-4">
                            <div class="form-check form-switch">
                                <input type="hidden" name="active" value="0">
                                <input type="checkbox" class="form-check-input @error('active') is-invalid @enderror" 
                                       id="active" name="active" value="1" {{ old('active', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="active">
                                    <i class="bi bi-toggle-on me-1"></i>
                                    <strong>Active Project</strong>
                                    <br>
                                </label>
                                @error('active')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('projects.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle me-1"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-1"></i>Create Project
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection