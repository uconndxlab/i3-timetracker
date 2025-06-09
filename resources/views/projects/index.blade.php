@extends('layouts.app')

@section('content')

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>All Projects</h1>
        <a href="{{ route('projects.create') }}" class="btn btn-primary">Add New Project</a>
    </div>

    <div class="list-group">
        @foreach ($projects as $project)
            <div class="list-group-item list-group-item-action">
                <a href="{{ route('projects.show', $project) }}" class="stretched-link" aria-label="View details for {{ $project->name }}"></a>

                <div class="d-flex w-100 justify-content-between">
                    <h5 class="mb-1">{{ $project->name }}</h5>
                    <small>Status: {{ $project->active ? 'Active' : 'Inactive' }}</small>
                </div>
                
                <p class="mb-1">{{ Str::limit($project->desc, 150) ?: 'No description available.' }}</p>
                {{-- <div class="mt-2">
                    <a href="{{ route('projects.edit', $project) }}" class="btn btn-sm btn-outline-warning position-relative">Edit</a>
                    
                    -- add delete button (?) -- 

                </div> --}}

                <small class="text-muted">Created: {{ $project->created_at ? $project->created_at->format('M d, Y') : 'N/A' }}</small>
                
            </div>
        @endforeach
    </div>
</div>

@endsection
