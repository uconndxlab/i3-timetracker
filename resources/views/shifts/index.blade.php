@extends('layouts.app')

@section('content')
<div class="app-page">
    <div class="app-page-hero">
        <h1 class="app-page-title">Your Shifts</h1>
    </div>

    @php
        $columns = [
            ['key' => 'project.name', 'label' => 'Project', 'sortable' => true],
            ['key' => 'shift_date', 'label' => 'Shift Date', 'sortable' => true],
            ['key' => 'duration', 'label' => 'Duration', 'sortable' => true, 'type' => 'duration'],
            ['key' => 'entered', 'label' => 'Entered (Timecard)', 'sortable' => true, 'type' => 'boolean'],
            ['key' => 'billed', 'label' => 'Billed (Honeycrisp)', 'sortable' => true, 'type' => 'boolean'],
        ];
        
        if (auth()->user()->isAdmin()) {
            array_splice($columns, 1, 0, [
                ['key' => 'user.name', 'label' => 'Staff Member', 'sortable' => true],
            ]);
        }
        
        $actions = [
            ['key' => 'edit', 'label' => 'Edit Shift', 'icon' => 'pencil-square', 'route' => 'shifts.edit', 
             'show_if' => 'can_edit'],
            ['key' => 'delete', 'label' => 'Delete Shift', 'icon' => 'trash', 'route' => 'shifts.destroy', 
             'show_if' => 'can_edit'],
        ];
    @endphp

    <div class="app-panel app-table-wrap">
        @include('partials.table', [
            'items' => $shifts,
            'columns' => $columns,
            'actions' => $actions,
            'title' => 'Shift',
            'empty_message' => 'No shifts found for this week.',
            'empty_icon' => 'calendar-x',
            'create_route' => 'shifts.create',
            'create_label' => 'Add New Shift'
        ])
    </div>

    {{-- <div class="mt-4 d-flex justify-content-center">
        {{ $shifts->links('partials.pagination') }}
    </div> --}}
    
</div>
@endsection