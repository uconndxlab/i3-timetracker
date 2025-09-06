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

    @php
        $columns = [
            ['key' => 'project.name', 'label' => 'Project', 'sortable' => true],
            ['key' => 'user.name', 'label' => 'Staff Member', 'sortable' => true],
            ['key' => 'shift_date', 'label' => 'Date', 'sortable' => true],
            ['key' => 'time_range', 'label' => 'Hours', 'sortable' => true],
            ['key' => 'duration', 'label' => 'Duration', 'sortable' => true],
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
        {{ $shifts->links() }}
    </div>
</div>
@endsection