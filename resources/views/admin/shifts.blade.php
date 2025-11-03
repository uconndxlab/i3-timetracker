@extends('layouts.app')

@section('content')
<div class="mt-4">
    <div class="page-header text-center">
        <div class="container">
            <h1 class="display-5">
                <i class="bi bi-clock-history me-3"></i>
                Time Shifts
            </h1>
            <p class="lead mb-0">
                All staff time entries
            </p>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.shifts.index') }}" class="mb-2">
        <div class="row g-2 align-items-center">
            <div class="col-md-2 ">
                <input type="text" name="search" class="form-control" placeholder="Enter staff name" value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <select name="entered_filter" class="form-select">
                    <option value="">Timecard Status</option>
                    <option value="1" {{ isset($enteredFilter) && $enteredFilter == '1' ? 'selected' : '' }}>Entered</option>
                    <option value="0" {{ isset($enteredFilter) && $enteredFilter == '0' ? 'selected' : '' }}>Not Entered</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="billed_filter" class="form-select">
                    <option value="">Honeycrisp Status</option>
                    <option value="1" {{ isset($billedFilter) && $billedFilter == '1' ? 'selected' : '' }}>Billed</option>
                    <option value="0" {{ isset($billedFilter) && $billedFilter == '0' ? 'selected' : '' }}>Not Billed</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="billed_filter" class="form-select">
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
                <a href="{{ route('admin.shifts.index') }}" class="btn btn-outline-secondary px-4 d-flex align-items-center justify-content-center gap-2">
                    <i class="bi bi-x-circle"></i>
                    <span>Clear Filters</span>
                </a>
            </div>
        </div>
    </form>

    @php
        $columns = [
            ['key' => 'project.name', 'label' => 'Project', 'sortable' => true],
            ['key' => 'user.name', 'label' => 'Staff Member', 'sortable' => true],
            ['key' => 'shift_date', 'label' => 'Shift Date', 'sortable' => true],
            ['key' => 'duration', 'label' => 'Duration', 'sortable' => true, 'type' => 'duration'],
            ['key' => 'entered', 'label' => 'Entered (Timecard)', 'sortable' => true, 'type' => 'boolean'],
            ['key' => 'billed', 'label' => 'Billed (Honeycrisp)', 'sortable' => true, 'type' => 'boolean'],
        ];
        
        $actions = [
            ['key' => 'edit', 'label' => 'Edit Shift', 'icon' => 'pencil-square', 'route' => 'shifts.edit', 
             'show_if' => 'can_edit'],
            ['key' => 'delete', 'label' => 'Delete Shift', 'icon' => 'trash', 'route' => 'shifts.destroy', 
             'show_if' => 'can_edit'],
        ];
    @endphp

    @include('partials.table', [
        'items' => $shifts,
        'columns' => $columns,
        'actions' => $actions,
        'title' => 'Shift',
        'empty_message' => 'No shifts found.',
        'empty_icon' => 'calendar-x',
        'create_route' => 'shifts.create',
        'create_label' => 'Add New Shift'
    ])
    <div class="d-flex justify-content-center mt-4">
        {{ $shifts->links('partials.pagination') }}
    </div>
</div>
@endsection