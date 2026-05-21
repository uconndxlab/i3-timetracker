@php
    $orgProjects = $adminDashboard['org_projects'] ?? [];
    $orgStats = $adminDashboard['org_stats'] ?? [];
@endphp

<div class="dashboard d-none" id="adminDashboard">
    <div class="mb-4">
        <div class="dashboard-toolbar d-flex flex-wrap align-items-end justify-content-between gap-3 mb-3">
            <div class="dashboard-toolbar-sort">
                <div class="dashboard-toolbar-label">View Table By:</div>
                <div class="dashboard-segment" id="adminTableViewToggle" role="group" aria-label="View table by">
                    <button type="button" class="dashboard-segment__btn active" data-admin-table="shift">Shift</button>
                    <button type="button" class="dashboard-segment__btn" data-admin-table="employee">Employee</button>
                    <button type="button" class="dashboard-segment__btn" data-admin-table="project">Project</button>
                </div>
            </div>

            <div class="dashboard-toolbar-period text-center">
                <span class="dashboard-period-badge d-none" id="adminCurrentPeriodBadge">Current Period</span>
                <div class="dashboard-period-select">
                    <button type="button" class="dashboard-period-toggle" id="adminPeriodToggle" aria-haspopup="listbox" aria-expanded="false">
                        View Period: <span id="adminPeriodLabel"></span>
                        <i class="bi bi-chevron-down ms-1"></i>
                    </button>
                    <ul class="dashboard-period-menu d-none" id="adminPeriodMenu" role="listbox"></ul>
                </div>
            </div>

            <div class="dashboard-toolbar-total">
                Total: <span class="dashboard-total-value wavy-underline" id="adminPeriodTotal">0.00</span>
            </div>
        </div>

        <div class="dashboard-toolbar-filter w-100">
            <label class="dashboard-toolbar-label mb-1" for="adminTableSearch">Search:</label>
            <input type="search"
                   class="form-control dashboard-search-input w-100"
                   id="adminTableSearch"
                   placeholder="Search through shifts . . ."
                   autocomplete="off">
        </div>
    </div>

    <div class="i3-data-table i3-data-table--7col" id="adminShiftPanel">
        <div class="i3-data-table__head">
            <span>Employee</span>
            <span>Project</span>
            <span>Date</span>
            <span>Hours</span>
            <span>Timecard</span>
            <span>Honeycrisp</span>
            <span></span>
        </div>
        <ul class="i3-data-table__body i3-data-table__body--lg list-unstyled mb-0" id="adminShiftList"></ul>
    </div>

    <div class="i3-data-table i3-data-table--5col d-none" id="adminEmployeePanel">
        <div class="i3-data-table__head">
            <span>Employee Name</span>
            <span>Unbilled Hours</span>
            <span>Total Hours</span>
            <span>Top Project</span>
            <span>Last Shift Date</span>
        </div>
        <ul class="i3-data-table__body i3-data-table__body--lg list-unstyled mb-0" id="adminEmployeeList"></ul>
    </div>

    <div class="i3-data-table i3-data-table--5col d-none" id="adminProjectPanel">
        <div class="i3-data-table__head">
            <span>Project Name</span>
            <span>Hours Last Period</span>
            <span>Total Hours</span>
            <span>Top Employee</span>
            <span>Last Shift Date</span>
        </div>
        <ul class="i3-data-table__body i3-data-table__body--lg list-unstyled mb-0" id="adminProjectList"></ul>
    </div>

    @include('partials.admin-shift-modal')

    <hr class="dashboard-stats-divider">

    @include('partials.dashboard-stats', [
        'ariaLabel' => 'Organization statistics',
        'projects' => $orgProjects,
        'projectsEmpty' => 'No project hours logged yet.',
        'metrics' => [
            ['label' => 'Total Hours', 'value' => $orgStats['total_hours'] ?? 0, 'decimals' => 2],
            ['label' => 'Avg Per Week', 'value' => $orgStats['avg_hours_per_week'] ?? 0, 'decimals' => 2],
        ],
        'chartId' => 'adminHoursChart',
        'chartTotalId' => 'adminStatsChartTotal',
        'chartRanges' => [
            ['range' => 'period', 'label' => 'Period', 'active' => true],
            ['range' => 'week', 'label' => 'Week'],
            ['range' => 'month', 'label' => 'Month'],
            ['range' => 'year', 'label' => 'Year'],
        ],
        'rangeAriaLabel' => 'Admin hours chart range',
    ])
</div>
