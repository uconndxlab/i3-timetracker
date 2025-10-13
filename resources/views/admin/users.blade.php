@extends('layouts.app')

@section('content')
<div class="mt-4">
    <div class="page-header text-center">
        <div class="container">
            <h1 class="display-5">
                <i class="bi bi-people-fill me-3"></i>
                Staff Management
            </h1>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.users.index') }}" class="mb-2">
        <div class="row g-2 align-items-center">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" placeholder="Search by name, NetID, or email" value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <select name="admin_filter" class="form-select">
                    <option value="">Admin Status</option>
                    <option value="1" {{ isset($adminFilter) && $adminFilter == '1' ? 'selected' : '' }}>Admin</option>
                    <option value="0" {{ isset($adminFilter) && $adminFilter == '0' ? 'selected' : '' }}>Not Admin</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="active_filter" class="form-select">
                    <option value="">Active Status</option>
                    <option value="1" {{ isset($activeFilter) && $activeFilter == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ isset($activeFilter) && $activeFilter == '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="col-md-auto">
                <button type="submit" class="btn btn-primary px-4 d-flex align-items-center justify-content-center gap-2">
                    <i class="bi bi-funnel-fill"></i>
                    <span>Apply Filters</span>
                </button>
            </div>
            <div class="col-md-auto">
                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary px-4 d-flex align-items-center justify-content-center gap-2">
                    <i class="bi bi-x-circle"></i>
                    <span>Clear Filters</span>
                </a>
            </div>
        </div>
    </form>

    @php
        $columns = [
            ['key' => 'name', 'label' => 'Name', 'sortable' => true],
            ['key' => 'netid', 'label' => 'NetID', 'sortable' => true],
            ['key' => 'total_shifts', 'label' => 'Total Shifts', 'sortable' => false],
            ['key' => 'total_hours', 'label' => 'Total Hours', 'sortable' => false],
            ['key' => 'is_admin', 'label' => 'Admin', 'sortable' => true, 'type' => 'boolean'],
            ['key' => 'active', 'label' => 'Active', 'sortable' => true, 'type' => 'boolean'],
        ];
        
        $actions = [
            ['key' => 'toggle_admin', 'label' => 'Toggle Admin', 'icon' => 'shield-lock', 'route' => 'admin.users.toggle-admin', 'color' => 'warning', 'method' => 'post'],
        ];
    @endphp

    @include('partials.table', [
        'items' => $users,
        'columns' => $columns,
        'actions' => $actions,
        'title' => 'User',
        'empty_message' => 'No users found.',
        'empty_icon' => 'people-fill',
    ])
    
    <div class="d-flex justify-content-center mt-4">
        {{ $users->links('partials.pagination') }}
    </div>
</div>
@endsection