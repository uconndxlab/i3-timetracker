@extends('layouts.app')

@section('content')
<div class="app-page">
    <div class="app-page-hero">
        <h1 class="app-page-title">Your Projects</h1>
        <div class="app-page-tools">
            <a href="{{ route('projects.manage') }}" class="btn btn-light">
                Manage Project Assignments
            </a>
        </div>
    </div>

    @php
        $columns = [
            ['key' => 'name', 'label' => 'Project Name', 'sortable' => true, 'route' => 'projects.show'],
            ['key' => 'billed_hours', 'label' => 'Billed Hours', 'sortable' => true],
            ['key' => 'unbilled_hours', 'label' => 'Unbilled Hours', 'sortable' => true],
            ['key' => 'active', 'label' => 'Active', 'sortable' => true, 'type' => 'boolean'],
        ];

        if (auth()->user()->isAdmin()) {
            array_splice($columns, 3, 0, [
                ['key' => 'assigned_users_count', 'label' => 'Staff Count', 'sortable' => true],
            ]);
        }

        
        $actions = [
            // ['key' => 'view_details', 'label' => 'View Details', 'icon' => 'eye', 'route' => 'projects.show', 'color' => 'primary'],
            ['key' => 'add_shift', 'label' => 'Add Shift', 'icon' => 'clock', 'route' => 'shifts.create', 'color' => 'success', 'params' => ['proj_id' => 'id'], 'condition' => 'is_user_assigned']
        ];
        
        if (auth()->user()->isAdmin()) {
            $actions = array_merge($actions, [
                ['key' => 'edit', 'label' => 'Manage Project', 'icon' => 'gear', 'route' => 'admin.projects.manage'],
            ]);
        }
    @endphp

    <div class="app-panel app-table-wrap">
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
</div>
@endsection