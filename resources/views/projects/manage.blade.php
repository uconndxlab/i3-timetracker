@extends('layouts.app')

@section('content')
<div class="app-page">
    <div class="app-page-hero">
        <h1 class="app-page-title">Manage Projects</h1>
    </div>

    <div class="app-panel">
                <h3 class="app-panel-title">All Projects</h3>
                    <form method="GET" action="{{ route('projects.manage') }}" class="mb-4">
                        <div class="input-group">
                            <input type="text" class="form-control" name="search" placeholder="Search projects by name or description..." value="{{ request('search') }}">
                            <button class="btn btn-outline-secondary" type="submit">
                                <i class="bi bi-search me-1"></i>Search
                            </button>
                        </div>
                    </form>

                    @if($projects->count() > 0)
                        <div class="list-group">
                            @foreach($projects as $project)
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div class="flex-grow-1">
                                        <h5 class="mb-1">
                                            <i class="bi bi-folder me-2"></i>
                                            {{ $project->name }}
                                        </h5>
                                        @if($project->description)
                                            <p class="mb-1 text-muted small">{{ $project->description }}</p>
                                        @endif
                                    </div>
                                    <div class="ms-3">
                                        @if(in_array($project->id, $userProjectIds))
                                            <form action="{{ route('projects.leave', $project) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                @if(request('search'))
                                                    <input type="hidden" name="search" value="{{ request('search') }}">
                                                @endif
                                                <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to leave {{ $project->name }}?')">Leave</button>
                                            </form>
                                        @else
                                            <form action="{{ route('projects.join', $project) }}" method="POST" class="d-inline">
                                                @csrf
                                                @if(request('search'))
                                                    <input type="hidden" name="search" value="{{ request('search') }}">
                                                @endif
                                                <button type="submit" class="btn btn-success">Join</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-4 d-flex justify-content-center">
                            {{ $projects->links('partials.pagination') }}
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            @if(request('search'))
                                No projects found matching your search.
                            @else
                                No projects available.
                            @endif
                        </div>
                    @endif
    </div>
</div>
@endsection

