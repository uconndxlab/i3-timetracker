@extends('layouts.app')

@section('content')
<div class="mt-4">
    <div class="page-header text-center">
        <div class="container">
            <h1 class="display-5">
                <i class="bi bi-collection me-3"></i>
                Projects
            </h1>
            <p class="lead mb-0">
                {{ auth()->user()->isAdmin() ? 'Manage i3 projects' : 'Your assigned projects' }}
            </p>
        </div>
    </div>

    @php
        $columns = [
            ['key' => 'name', 'label' => 'Project Name', 'sortable' => true],
            ['key' => 'billed_hours', 'label' => 'Billed Hours', 'sortable' => true],
            ['key' => 'unbilled_hours', 'label' => 'Unbilled Hours', 'sortable' => true],
            ['key' => 'active', 'label' => 'Status', 'sortable' => true, 'type' => 'boolean'],
        ];

        if (auth()->user()->isAdmin()) {
            array_splice($columns, 3, 0, [
                ['key' => 'assigned_users_count', 'label' => 'Staff Count', 'sortable' => true],
            ]);
        }

        $actions[] = [
            'key' => 'add_shift', 
            'label' => 'Add Shift', 
            'icon' => 'clock', 
            'route' => 'shifts.create',
            'color' => 'success',
            'params' => ['proj_id' => 'id']
        ];
        
        if (auth()->user()->isAdmin()) {
            $actions = array_merge($actions, [
                ['key' => 'edit', 'label' => 'Manage Project', 'icon' => 'gear', 'route' => 'admin.projects.manage'],
            ]);
        }
    @endphp

    @include('partials.table', [
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