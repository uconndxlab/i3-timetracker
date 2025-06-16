@extends('layouts.app')

@section('content')

<div class="container mt-4">
    <div class="card">
        <div class="card-header">
            <h2>Log Shift</h2>
        </div>
        <div class="card-body">
            <form action="{{ route('shifts.store') }}" method="POST">
                @csrf

                {{-- <div class="mb-3">
                    <label for="user-search" class="form-label">Name</label>
                    <div class="dropdown">
                        <input type="text" class="form-control" id="user-search" name="user_search_term"
                            value="{{ old('user_search_term') }}"
                            placeholder="Search for a user..."
                            autocomplete="on">

                        <input type="hidden" name="netid" id="netid" value="{{ old('netid') }}" required>
                        <div class="dropdown-menu w-100" id="user-results">
                            @foreach($users as $user)
                                <a class="dropdown-item" href="#" data-id="{{ $user->netid }}" data-name="{{ $user->name }}">{{ $user->name }} (NetID: {{ $user->netid }})</a>
                            @endforeach
                        </div>
                    </div>

                    <div id="selected-user-display" class="form-text mt-1">
                        @if(old('netid') && $users->count())
                            @php
                                $selectedUserId = old('netid');
                                $selectedUser = $users->firstWhere('id', $selectedUserId);
                            @endphp
                        @endif
                    </div>
                </div> --}}

                <div class="form-group">
                    <label for="netid">Name</label>
                    <select class="form-control" id="netid" name="netid" required>
                        <option value="">Select a user...</option>
                        @foreach($users as $user)
                            <option value="{{ $user->netid }}" {{ old('netid') == $user->netid ? 'selected' : '' }}>
                                {{ $user->name }} ({{ $user->netid }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label for="proj_id" class="form-label">Project</label>
                    <div>
                        <select name="proj_id" id="proj_id" class="form-select" required>
                            <option value="">Select a project</option>
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}" {{ old('proj_id') == $project->id ? 'selected' : '' }}>
                                    {{ $project->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div id="selected-project-display" class="form-text mt-1">
                        @if(old('proj_id') && $projects->count())
                            @php
                                $selectedProjectId = old('proj_id');
                                $selectedProject = $projects->firstWhere('id', (int)$selectedProjectId);
                            @endphp
                        @endif
                    </div>
                </div>

                <div class="mb-3">
                    <label for="start_time" class="form-label">Start Time</label>
                    <input type="datetime-local" class="form-control" id="start_time" name="start_time" value="{{ old('start_time') }}" required>
                </div>

                <div class="mb-3">
                    <label for="end_time" class="form-label">End Time</label>
                    <input type="datetime-local" class="form-control" id="end_time" name="end_time" value="{{ old('end_time') }}" required>
                </div>

                <div class="mb-3 form-check">
                    <input type="hidden" name="entered" value="0">
                    <input type="checkbox" class="form-check-input" id="entered" name="entered" value="1" {{ old('entered', false) ? 'checked' : '' }}>
                    <label class="form-check-label" for="entered">Recorded in Employee Portal</label>
                </div>

                {{-- <div class="mb-3 form-check">
                    <input type="hidden" name="billed" value="0">
                    <input type="checkbox" class="form-check-input" id="billed" name="billed" value="1" {{ old('billed', false) ? 'checked' : '' }}>
                    <label class="form-check-label" for="billed">Billed in Cider</label>
                </div> --}}

                <hr class="my-4"> 

                <div class="d-flex justify-content-end"> 
                    <a href="{{ route('shifts.index') }}" class="btn btn-outline-secondary me-2">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create Shift</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection