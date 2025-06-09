@extends('layouts.app')

@section('content')

<div class="container mt-4">
    <div class="card">
        <div class="card-header">
            <h2>Edit Project: {{ $project->name }}</h2>
        </div>
        <div class="card-body">
            <form action="{{ route('projects.update', $project) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="name" class="form-label">Project Name</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $project->name) }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="desc" class="form-label">Description (Optional)</label>
                    <textarea class="form-control @error('desc') is-invalid @enderror" id="desc" name="desc" rows="3">{{ old('desc', $project->desc) }}</textarea>
                    @error('desc')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3 form-check">
                    <input type="hidden" name="active" value="0">
                    <input type="checkbox" class="form-check-input @error('active') is-invalid @enderror" id="active" name="active" value="1" {{ old('active', $project->active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="active">Active</label>
                    @error('active')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <hr class="my-4">

                <div class="d-flex justify-content-end">
                    <a href="{{ route('projects.show', $project) }}" class="btn btn-outline-secondary me-2">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Project</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection