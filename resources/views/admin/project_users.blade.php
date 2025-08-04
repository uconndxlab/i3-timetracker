{{-- @if(app()->environment('local'))
<div class="alert alert-info">
    <h5>Debug Info:</h5>
    <p>Assigned Users Count: {{ $assignedUsers->count() }}</p>
    <p>Assigned NetIDs: {{ implode(', ', $assignedUsers->pluck('netid')->toArray()) }}</p>
    <p>Unassigned Users Count: {{ $unassignedUsers->count() }}</p>
</div>
@endif --}}

@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="my-4">Manage Users ({{ $project->name }})</h1>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if(session('info'))
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        {{ session('info') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <div class="row">
        <div class="col-md-5">
            <div class="card h-100">
                <div class="card-header">
                    <h2 class="mb-0">Assign New Users</h2>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.projects.assign-users', $project->id) }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label for="user_ids">Select Users to Assign</label>
                            <select name="user_ids[]" id="user_ids" class="form-control" multiple style="min-height: 200px;">
                                @foreach($unassignedUsers as $user)
                                    <option value="{{ $user->netid }}"style="padding: 4px 8px;">{{ $user->name }} ({{ $user->netid }})</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary mt-3">Assign Selected Users</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card h-100">
                <div class="card-header">
                    <h2 class="mb-0">Currently Assigned Users</h2>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($assignedUsers->count() > 0)
                                @foreach($assignedUsers as $user)
                                <tr>
                                    <td>{{ $user->name }}</td>
                                    <td>{{ $user->pivot->active ? 'Active' : 'Inactive' }}</td>
                                    <td>
                                        <form action="{{ route('admin.projects.remove-user', [$project->id, $user->netid]) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm">Remove</button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="3" class="text-center">No users currently assigned to this project</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection