@extends('layouts.app')

@section('content')

<div class="container mt-4">
    <h1>Record New Project</h1>

    <form action="{{ route('projects.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label for="name" class="form-label">Project Name</label>
            <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" required>
            @error('name')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="desc" class="form-label">Description (Optional)</label>
            <textarea class="form-control" id="desc" name="desc" rows="3">{{ old('desc') }}</textarea>
            @error('desc')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3 form-check">
            <input type="hidden" name="active" value="0">
            <input type="checkbox" class="form-check-input" id="active" name="active" value="1" {{ old('active', true) ? 'checked' : '' }}>
            <label class="form-check-label" for="active">Active</label>
            @error('active')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary">Create Project</button>
        <a href="{{ route('projects.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection