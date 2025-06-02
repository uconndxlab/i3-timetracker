@extends('layouts.app')
@section('content')

<div class="row">
    <a> Current Projects </a>
    @foreach ($projects as $project)
        <div class="card mb-3">
            <div class="card-body">
                <h5 class="card-title">{{ $project->name }}</h5>
    @if ($project->description)
                <p class="card-text">{{ $project->description }}</p>
    @endif
                <p class="card-text"><small class="text-muted">Created at: {{ $project->created_at->format('Y-m-d H:i') }}</small></p>
                <a href="{{ route('projects.show', ['project' => $project->id]) }}" class="btn btn-primary">View Project</a>
            </div>
        </div>
    @endforeach
</div>


        

@endsection