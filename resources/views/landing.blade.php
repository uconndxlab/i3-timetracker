@extends('layouts.app')

@php
    $navbarView = ($adminDashboard ?? null) && request()->query('view') === 'admin' ? 'admin' : 'user';
@endphp

@section('content')
@if($dashboardReadOnly ?? false)
    <div class="dashboard-viewing-user mb-3">
        <span class="dashboard-viewing-user__name">
            <span class="i3-dot" aria-hidden="true"></span>
            {{ \Illuminate\Support\Str::title($subjectUser->name) }}
            <span class="dashboard-viewing-user__sep" aria-hidden="true">/</span>
            <a href="{{ route('landing', ['view' => 'admin']) }}" class="i3-link">Back To Admin</a>
        </span>
    </div>
@endif
<div class="dashboard mt-3 {{ $navbarView === 'admin' ? 'd-none' : '' }}" id="userDashboard">
    @unless($dashboardReadOnly ?? false)
    <div class="dashboard-actions d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <button type="button" class="dashboard-btn dashboard-btn--dark" id="openJoinProjectsModalBtn">
            <i class="bi bi-folder2-open me-2"></i>Join Projects
        </button>
        <button type="button" class="dashboard-btn dashboard-btn--primary" id="openShiftModalBtn">
            <i class="bi bi-plus-lg me-2"></i>Log New Shift
        </button>
    </div>
    @endunless

    <div class="dashboard-toolbar d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
        <div class="dashboard-toolbar-sort">
            <div class="dashboard-toolbar-label">Sort Shifts By:</div>
            <div class="d-flex align-items-center gap-2">
                <div class="dashboard-segment" role="group" aria-label="Sort shifts">
                    <button type="button" class="dashboard-segment__btn active" data-sort="date">Date</button>
                    <button type="button" class="dashboard-segment__btn" data-sort="duration">Duration</button>
                    <button type="button" class="dashboard-segment__btn" data-sort="timecard">Timecard</button>
                </div>
                <button type="button" class="dashboard-sort-dir" id="sortDirBtn" aria-label="Toggle sort direction">
                    <i class="bi bi-arrow-down" id="sortDirIcon"></i>
                </button>
            </div>
        </div>

        <div class="dashboard-toolbar-period text-center">
            <span class="dashboard-period-badge d-none" id="currentPeriodBadge">Current Period</span>
            <div class="dashboard-period-select">
                <button type="button" class="dashboard-period-toggle" id="periodToggle" aria-haspopup="listbox" aria-expanded="false">
                    View Period: <span id="periodLabel"></span>
                    <i class="bi bi-chevron-down ms-1"></i>
                </button>
                <ul class="dashboard-period-menu d-none" id="periodMenu" role="listbox"></ul>
            </div>
        </div>

        <div class="dashboard-toolbar-filter">
            <label class="dashboard-toolbar-label mb-1" for="projectFilter">Projects:</label>
            <select class="form-select dashboard-select" id="projectFilter">
                <option value="all">All</option>
                @foreach($userProjects as $project)
                    <option value="{{ $project['id'] }}">{{ $project['name'] }}</option>
                @endforeach
            </select>
        </div>

        <div class="dashboard-toolbar-total">
            Total: <span class="dashboard-total-value wavy-underline" id="periodTotal">0.00</span>
        </div>
    </div>

    <div id="shiftGrid" class="dashboard-shift-grid"></div>

    <hr class="dashboard-stats-divider">

    @include('partials.dashboard-stats', [
        'ariaLabel' => 'All-time statistics',
        'projects' => $allTimeStats['projects'] ?? [],
        'projectsEmpty' => 'No shifts logged yet.',
        'metrics' => [
            ['label' => 'Total Shifts', 'value' => $allTimeStats['total_shifts'] ?? 0, 'decimals' => 0],
            ['label' => 'Total Hours', 'value' => $allTimeStats['total_hours'] ?? 0, 'decimals' => 2],
            ['label' => 'Avg Per Week', 'value' => $allTimeStats['avg_hours_per_week'] ?? 0, 'decimals' => 2],
        ],
        'chartId' => 'weeklyHoursChart',
        'chartTotalId' => 'statsChartTotal',
        'chartRanges' => [
            ['range' => 'month', 'label' => 'Month', 'active' => true],
            ['range' => 'week', 'label' => 'Week'],
            ['range' => 'period', 'label' => 'Period'],
            ['range' => 'year', 'label' => 'Year'],
        ],
    ])
</div>

@if($isAdminViewer ?? false)
    @include('partials.admin-dashboard', ['adminDashboard' => $adminDashboard, 'navbarView' => $navbarView])
@endif

@unless($dashboardReadOnly ?? false)
    @include('partials.shift-modal')
    @include('partials.join-projects-modal')
@endunless
@include('partials.bootstrap-js')

@push('scripts')
<script src="{{ asset('js/i3-utils.js') }}"></script>
<script>
    window.userDashboardConfig = {
        dashboardReadOnly: @json($dashboardReadOnly ?? false),
        weeklyChartData: @json($weeklyChartData ?? []),
        hoursTimeline: @json($hoursTimeline ?? []),
        currentWeekIndex: {{ $currentWeekIndex ?? 0 }},
        editShiftBaseUrl: @json(url('/shifts')),
        destroyShiftBaseUrl: @json(url('/shifts')),
        bulkEnteredUrl: @json(route('shifts.bulk-update-entered')),
        editableProjects: @json($logShiftProjects->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])->values()),
        csrfToken: @json(csrf_token()),
        defaultShiftDate: @json($defaultShiftDate),
        joinableProjects: @json($joinableProjects ?? []),
        joinProjectsSyncUrl: @json(route('projects.sync-memberships')),
    };
    window.shiftModalConfig = {
        mode: @json(($isAdminViewer ?? false) && request()->query('view') === 'admin' ? 'admin' : 'user'),
        canDelete: @json(($isAdminViewer ?? false) && request()->query('view') === 'admin'),
        defaultShiftDate: @json($defaultShiftDate),
        storeUrl: @json(route('shifts.store')),
        shiftBaseUrl: @json(url('/shifts')),
    };
    @if($isAdminViewer ?? false)
    window.adminDashboardConfig = {
        landingUrl: @json(route('landing', ['view' => 'admin'])),
        projectsStoreUrl: @json(route('projects.store')),
        @if($adminDashboard ?? null)
        weeklyPeriods: @json($adminDashboard['weekly_periods'] ?? []),
        weekIndex: {{ $adminDashboard['current_week_index'] ?? 0 }},
        hoursTimeline: @json($adminDashboard['hours_timeline'] ?? []),
        activePeriod: @json($adminDashboard['active_period'] ?? []),
        @endif
    };
    @endif
</script>
<script defer src="{{ asset('js/user-dashboard.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="{{ asset('js/dashboard-chart.js') }}"></script>
@unless($dashboardReadOnly ?? false)
<script defer src="{{ asset('js/shift-modal.js') }}"></script>
<script defer src="{{ asset('js/join-projects-modal.js') }}"></script>
@endunless
@if($isAdminViewer ?? false)
<script defer src="{{ asset('js/admin-create-project-modal.js') }}"></script>
<script defer src="{{ asset('js/admin-dashboard.js') }}"></script>
@endif
@endpush
@endsection
