@extends('layouts.app')

@section('content')
<div class="container mt-4">

    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="mb-0">Edit Project Details</h4>
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
                            <label for="description" class="form-label">Project Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $project->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input type="hidden" name="active" value="0">
                            <input class="form-check-input" type="checkbox" id="active" name="active" value="1" {{ old('active', $project->active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="active">Active</label>
                        </div>  
                        
                        <button type="submit" class="btn btn-primary">Update Project</button>
                    </form>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Manage Users</h4>
                </div>
                <div class="card-body">
                    <h5 class="mb-3">Assigned Users</h5>
                    
                    @if($assignedUsers->count() > 0)
                        <div class="table-responsive mb-4">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>NetID</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($assignedUsers as $user)
                                        <tr>
                                            <td>{{ $user->name }}</td>
                                            <td>{{ $user->netid }}</td>
                                            <td class="text-end">
                                                <form action="{{ route('admin.projects.remove-user', [$project, $user->netid]) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to remove this user?')">
                                                        <i class="bi bi-person-dash"></i> Remove
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info">
                           No users assigned to this project yet.
                        </div>
                    @endif
                    
                    <hr>
                    
                    <h5 class="mb-3">Assign New Users</h5>
                    <form action="{{ route('admin.projects.assign-users', $project) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="user_ids" class="form-label">Select Users</label>
                            <select class="form-select" id="user_ids" name="user_ids[]" multiple>
                                @foreach($users as $user)
                                    @if(!$assignedUsers->contains('netid', $user->netid))
                                        <option value="{{ $user->netid }}">{{ $user->name }} ({{ $user->netid }})</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success">
                            Assign Selected Users
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection