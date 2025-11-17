@extends('layouts.app')

@section('content')
<div class="mt-4">
    <div class="page-header text-center">
        <div class="container">
            <h1 class="display-5 mb-3">
                Manage Projects
            </h1>
            <p class="lead mb-1">
                Join or leave projects you're working on
            </p>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            {{-- @if(session('message'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i>{{ session('message') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif --}}

            <div class="card shadow-sm">
                <div class="card-header">
                    <h3 class="mb-0">
                        All Projects
                    </h3>
                </div>
                <div class="card-body">
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
        </div>
    </div>
</div>
@endsection

