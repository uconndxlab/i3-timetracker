@extends('layouts.app')

@section('content')
<div class="pt-4 pb-5">
    <div class="page-header text-center">
        <div class="container">
            <h1 class="display-5 mb-3">
                Projects
            </h1>
            <div class="d-flex align-items-center gap-2">
                @if (auth()->user()->isAdmin())
                    <a href="{{ route('projects.create') }}" class="btn btn-secondary">Add New Project</a>
                @endif
                <a href="{{ route('projects.manage') }}" class="btn btn-primary">Manage Project Assignments</a>
            </div>
        </div>
    </div>

    @php
        $columns = [
            ['key' => 'name', 'label' => 'Project Name', 'sortable' => true, 'route' => 'projects.show'],
            ['key' => 'billed_hours', 'label' => 'Billed Hours', 'sortable' => true],
            ['key' => 'unbilled_hours', 'label' => 'Unbilled Hours', 'sortable' => true],
            ['key' => 'active', 'label' => 'Status', 'sortable' => true, 'type' => 'boolean'],
        ];

        if (auth()->user()->isAdmin()) {
            array_splice($columns, 3, 0, [
                ['key' => 'assigned_users_count', 'label' => 'Staff Count', 'sortable' => true],
            ]);
        }


        $actions = [
            // ['key' => 'view_details', 'label' => 'View Details', 'icon' => 'eye', 'route' => 'projects.show', 'color' => 'primary'],
            ['key' => 'add_shift', 'label' => 'Add Shift', 'icon' => 'clock', 'route' => 'shifts.create', 'color' => 'success', 'params' => ['proj_id' => 'id']]
        ];
        
        if (auth()->user()->isAdmin()) {
            $actions = array_merge($actions, [
                ['key' => 'edit', 'label' => 'Manage Project', 'icon' => 'gear', 'route' => 'admin.projects.manage'],
            ]);
        }
    @endphp

    @include('partials.table', [
        'filterable' => true,
        'items' => $projects,
        'columns' => $columns,
        'actions' => $actions,
        'title' => 'Project',
        'empty_message' => 'No projects found.',
        'empty_icon' => 'folder-x',
        'create_route' => auth()->user()->isAdmin() ? 'projects.create' : null,
        'create_label' => 'Add New Project'
    ])
</div>
@endsection