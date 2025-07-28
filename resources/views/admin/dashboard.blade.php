@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="my-4">Admin Dashboard</h1>
    <p>No projects available.</p>

    <div class="row">
        @foreach($projects as $project)
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">{{ $project->name }}</h5>
                    <p class="card-text">
                        <span class="badge {{ $project->active ? 'bg-success' : 'bg-secondary' }}">
                            {{ $project->active ? 'Active' : 'Inactive' }}
                        </span>
                    </p>
                    <div class="d-flex">
                        <a href="{{ route('admin.projects.unbilled_users', $project->id) }}" class="btn btn-primary btn-sm me-2">
                            <i class="bi bi-clock"></i> View unbilled shifts
                        </a>
                        <a href="{{ route('admin.projects.users', $project->id) }}" class="btn btn-primary btn-sm me-2">
                            <i class="bi bi-people"></i> Manage Users
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection