@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Admin Dashboard - Projects Overview</h1>
    </div>

    @if($projects->isEmpty())
        <p>No projects found.</p>
    @else
        <div class="list-group">
            @foreach ($projects as $project)
                <a href="{{ route('admin.projects.unbilled_users', $project) }}" class="list-group-item list-group-item-action">
                    <div class="d-flex w-100 justify-content-between">
                        <h5 class="mb-1">{{ $project->name }}</h5>
                        <small>Status: {{ $project->active ? 'Active' : 'Inactive' }}</small>
                    </div>
                    <p class="mb-1">{{ Str::limit($project->desc, 150) ?: 'No description available.' }}</p>
                    <small class="text-muted">See users with unbilled shifts.</small>
                </a>
            @endforeach
        </div>
    @endif
</div>
@endsection