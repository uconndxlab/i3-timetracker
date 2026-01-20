@extends('layouts.app')

@section('content')
<div class="mt-4">
    <div class="page-header text-center">
        <div class="container">
            <h1 class="display-5">
                Time Shifts
            </h1>
        
            {{-- <div class="mt-4 d-flex justify-content-center align-items-center gap-3">
                <a href="{{ route('shifts.index', ['week' => $prev]) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-chevron-left"></i> Previous Week
                </a>
                <h5 class="mb-0 fw-bold">{{ $start }} to {{ $end }}</h5>
                @if($next <= 0)
                <a href="{{ route('shifts.index', ['week' => $next]) }}" class="btn btn-outline-secondary">
                    Next Week <i class="bi bi-chevron-right"></i>
                </a>
                @endif
                @if($currOffset != 0)
                    <a href="{{ route('shifts.index') }}" class="btn btn-secondary">
                        Current Week
                    </a>
                @endif
            </div> --}}
        </div>
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

    <div class="mt-4 d-flex justify-content-center">
        {{ $shifts->links('partials.pagination') }}
    </div>
    
</div>
@endsection