@php
    $adminDashboard = $adminDashboard ?? [];
    $orgProjects = $adminDashboard['org_projects'] ?? [];
    $orgStats = $adminDashboard['org_stats'] ?? [];
    $activeRange = $adminDashboard['active_range'] ?? [];
    $hasDateFilter = $adminDashboard['has_date_filter'] ?? false;
    $dateFrom = $adminDashboard['date_from'] ?? null;
    $dateTo = $adminDashboard['date_to'] ?? null;
    $navbarView = $navbarView ?? 'user';
    $adminTable = request()->query('admin_table', 'project');
    if (! in_array($adminTable, ['shift', 'employee', 'project'], true)) {
        $adminTable = 'project';
    }
@endphp

<div class="dashboard {{ $navbarView === 'admin' ? '' : 'd-none' }}" id="adminDashboard">
    <div class="dashboard-actions d-flex flex-wrap justify-content-end gap-2 mb-3">
        <button type="button" class="dashboard-btn dashboard-btn--primary" id="openCreateProjectModalBtn">
            <i class="bi bi-plus-lg me-2"></i>New Project
        </button>
    </div>

    <div class="mb-4">
        <div class="dashboard-toolbar d-flex flex-wrap align-items-end justify-content-between gap-3 mb-3">
            <div class="dashboard-toolbar-sort">
                <div class="dashboard-toolbar-label">View Table By:</div>
                <div class="dashboard-segment" id="adminTableViewToggle" role="group" aria-label="View table by">
                    <button type="button" class="dashboard-segment__btn{{ $adminTable === 'project' ? ' active' : '' }}" data-admin-table="project">Project</button>
                    <button type="button" class="dashboard-segment__btn{{ $adminTable === 'employee' ? ' active' : '' }}" data-admin-table="employee">Employee</button>
                    <button type="button" class="dashboard-segment__btn{{ $adminTable === 'shift' ? ' active' : '' }}" data-admin-table="shift">Shift</button>
                </div>
            </div>

            <div class="dashboard-toolbar-period">
                <div class="dashboard-toolbar-label mb-1">Date Range:</div>
                <div class="dashboard-date-range d-flex align-items-center gap-2 flex-wrap">
                    <input type="date"
                           class="form-control dashboard-date-input"
                           id="adminDateFrom"
                           value="{{ $dateFrom }}"
                           aria-label="Start date">
                    <span class="dashboard-date-range__sep">to</span>
                    <input type="date"
                           class="form-control dashboard-date-input"
                           id="adminDateTo"
                           value="{{ $dateTo }}"
                           aria-label="End date">
                    <button type="button" class="dashboard-btn dashboard-btn--sm dashboard-btn--dark" id="adminDateApply">Apply</button>
                    @if($hasDateFilter)
                        <button type="button" class="dashboard-btn dashboard-btn--sm dashboard-btn--dark" id="adminDateClear">Clear</button>
                    @endif
                </div>
            </div>

            <div class="dashboard-toolbar-total">
                Total: <span class="dashboard-total-value wavy-underline" id="adminRangeTotal">{{ number_format($activeRange['hours_in_range'] ?? 0, 2) }}</span>
            </div>
        </div>

        <div class="dashboard-toolbar-filter w-100">
            <label class="dashboard-toolbar-label mb-1" for="adminTableSearch">Search:</label>
            <input type="search"
                   class="form-control dashboard-search-input w-100"
                   id="adminTableSearch"
                   placeholder="Search through projects . . ."
                   autocomplete="off">
        </div>
    </div>

    <div class="i3-data-table i3-data-table--7col{{ $adminTable === 'shift' ? '' : ' d-none' }}" id="adminShiftPanel">
        <div class="i3-data-table__scroll">
            <div class="i3-data-table__scroll-inner">
                <div class="i3-data-table__head" data-admin-sort-panel="shift">
                    <button type="button" class="i3-data-table__sort-btn" data-admin-sort="employee" data-sort-type="text">Employee</button>
                    <button type="button" class="i3-data-table__sort-btn" data-admin-sort="project" data-sort-type="text">Project</button>
                    <button type="button" class="i3-data-table__sort-btn is-sorted" data-admin-sort="date" data-sort-type="date" data-sort-dir="desc">Date</button>
                    <button type="button" class="i3-data-table__sort-btn" data-admin-sort="hours" data-sort-type="number">Hours</button>
                    <button type="button" class="i3-data-table__sort-btn i3-data-table__check-col" data-admin-sort="entered" data-sort-type="boolean">Timecard</button>
                    <button type="button" class="i3-data-table__sort-btn i3-data-table__check-col" data-admin-sort="billed" data-sort-type="boolean">Honeycrisp</button>
                    <span></span>
                </div>
                <ul class="i3-data-table__body i3-data-table__body--lg list-unstyled mb-0" id="adminShiftList">
                    @forelse($activeRange['shifts'] ?? [] as $row)
                    <li class="i3-data-table__row"
                        data-search="{{ strtolower($row['employee_name'].' '.$row['project_name'].' '.$row['date_display']) }}"
                        data-sort-employee="{{ strtolower($row['employee_name']) }}"
                        data-sort-project="{{ strtolower($row['project_name']) }}"
                        data-sort-date="{{ $row['date'] }}"
                        data-sort-hours="{{ $row['hours'] }}"
                        data-sort-entered="{{ $row['entered'] ? '1' : '0' }}"
                        data-sort-billed="{{ $row['billed'] ? '1' : '0' }}">
                        <span class="i3-data-table__label" data-label="Employee">
                            <span class="i3-hash">#</span>
                            <a href="{{ route('admin.users.dashboard', ['user' => $row['netid']]) }}" class="i3-link">{{ $row['employee_name'] }}</a>
                        </span>
                        <span data-label="Project"><span class="i3-hash">#</span> {{ $row['project_name'] }}</span>
                        <span data-label="Date">{{ $row['date_display'] }}</span>
                        <span data-label="Hours">{{ number_format($row['hours'], 2) }}</span>
                        <span class="i3-data-table__check-col" data-label="Timecard">
                            <span class="i3-check {{ $row['entered'] ? 'is-checked' : '' }}" aria-hidden="true">
                                <i class="bi bi-check-lg"></i>
                            </span>
                        </span>
                        <span class="i3-data-table__check-col" data-label="Honeycrisp">
                            <button type="button"
                                    class="i3-check admin-shift-billed-toggle {{ $row['billed'] ? 'is-checked' : '' }}"
                                    data-shift-id="{{ $row['id'] }}"
                                    aria-pressed="{{ $row['billed'] ? 'true' : 'false' }}"
                                    aria-label="Toggle billed status for {{ $row['employee_name'] }}">
                                <i class="bi bi-check-lg"></i>
                            </button>
                        </span>
                        <span class="i3-data-table__actions" data-label="Actions">
                            <button type="button"
                                    class="btn btn-sm btn-outline-secondary admin-shift-edit-btn"
                                    data-shift='@json($row)'
                                    aria-label="Edit shift for {{ $row['employee_name'] }}">
                                Edit
                            </button>
                        </span>
                    </li>
                    @empty
                        <li class="i3-data-table__empty">{{ $hasDateFilter ? 'No shifts in this date range.' : 'No shifts logged yet.' }}</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    <div class="i3-data-table i3-data-table--5col{{ $adminTable === 'employee' ? '' : ' d-none' }}" id="adminEmployeePanel">
        <div class="i3-data-table__scroll">
            <div class="i3-data-table__scroll-inner">
                <div class="i3-data-table__head" data-admin-sort-panel="employee">
                    <button type="button" class="i3-data-table__sort-btn is-sorted" data-admin-sort="name" data-sort-type="text" data-sort-dir="asc">Employee Name</button>
                    <button type="button" class="i3-data-table__sort-btn" data-admin-sort="unbilled" data-sort-type="number">Unbilled Hours</button>
                    <button type="button" class="i3-data-table__sort-btn" data-admin-sort="total" data-sort-type="number">Total Hours</button>
                    <button type="button" class="i3-data-table__sort-btn" data-admin-sort="top_project" data-sort-type="text">Top Project</button>
                    <button type="button" class="i3-data-table__sort-btn" data-admin-sort="last_shift" data-sort-type="date">Last Shift Date</button>
                </div>
                <ul class="i3-data-table__body i3-data-table__body--lg list-unstyled mb-0" id="adminEmployeeList">
                    @forelse($activeRange['employees'] ?? [] as $row)
                    <li class="i3-data-table__row"
                        data-search="{{ strtolower($row['name'].' '.$row['top_project']) }}"
                        data-sort-name="{{ strtolower($row['name']) }}"
                        data-sort-unbilled="{{ $row['unbilled_hours'] }}"
                        data-sort-total="{{ $row['total_hours'] }}"
                        data-sort-top-project="{{ strtolower($row['top_project']) }}"
                        data-sort-last-shift="{{ $row['last_shift_date_sort'] ?? '' }}">
                        <span class="i3-data-table__label" data-label="Employee">
                            <span class="i3-hash">#</span>
                            <a href="{{ route('admin.users.dashboard', ['user' => $row['netid']]) }}" class="i3-link">{{ $row['name'] }}</a>
                        </span>
                        <span data-label="Unbilled Hrs">{{ number_format($row['unbilled_hours'], 2) }}</span>
                        <span data-label="Total Hrs">{{ number_format($row['total_hours'], 2) }}</span>
                        <span data-label="Top Project"><span class="i3-hash">#</span> {{ $row['top_project'] }}</span>
                        <span data-label="Last Shift">{{ $row['last_shift_date'] ?? '—' }}</span>
                    </li>
                    @empty
                        <li class="i3-data-table__empty">{{ $hasDateFilter ? 'No employee shift data in this date range.' : 'No employee shift data yet.' }}</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    <div class="i3-data-table i3-data-table--5col{{ $adminTable === 'project' ? '' : ' d-none' }}" id="adminProjectPanel">
        <div class="i3-data-table__scroll">
            <div class="i3-data-table__scroll-inner">
                <div class="i3-data-table__head" data-admin-sort-panel="project">
                    <button type="button" class="i3-data-table__sort-btn is-sorted" data-admin-sort="name" data-sort-type="text" data-sort-dir="asc">Project Name</button>
                    <button type="button" class="i3-data-table__sort-btn" data-admin-sort="unbilled" data-sort-type="number">Unbilled Hours</button>
                    <button type="button" class="i3-data-table__sort-btn" data-admin-sort="total" data-sort-type="number">Total Hours</button>
                    <button type="button" class="i3-data-table__sort-btn" data-admin-sort="top_employee" data-sort-type="text">Top Employee</button>
                    <button type="button" class="i3-data-table__sort-btn" data-admin-sort="last_shift" data-sort-type="date">Last Shift Date</button>
                </div>
                <ul class="i3-data-table__body i3-data-table__body--lg list-unstyled mb-0" id="adminProjectList">
                    @forelse($activeRange['project_rows'] ?? [] as $row)
                    <li class="i3-data-table__row"
                        data-search="{{ strtolower($row['name'].' '.$row['top_employee']) }}"
                        data-sort-name="{{ strtolower($row['name']) }}"
                        data-sort-unbilled="{{ $row['unbilled_hours'] }}"
                        data-sort-total="{{ $row['total_hours'] }}"
                        data-sort-top-employee="{{ strtolower($row['top_employee']) }}"
                        data-sort-last-shift="{{ $row['last_shift_date_sort'] ?? '' }}">
                        <span class="i3-data-table__label" data-label="Project">
                            <span class="i3-hash">#</span>
                            <a href="{{ route('admin.projects.show', ['project' => $row['id']]) }}" class="i3-link admin-project-link">
                                {{ $row['name'] }}
                            </a>
                        </span>
                        <span data-label="Unbilled Hrs">{{ number_format($row['unbilled_hours'], 2) }}</span>
                        <span data-label="Total Hrs">{{ number_format($row['total_hours'], 2) }}</span>
                        <span data-label="Top Employee">{{ $row['top_employee'] }}</span>
                        <span data-label="Last Shift">{{ $row['last_shift_date'] ?? '—' }}</span>
                    </li>
                    @empty
                        <li class="i3-data-table__empty">{{ $hasDateFilter ? 'No project shift data in this date range.' : 'No project shift data yet.' }}</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    @include('partials.admin-create-project-modal')

    <hr class="dashboard-stats-divider">

    <div id="adminOrgStats">
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
        'chartRanges' => $hasDateFilter
            ? [
                ['range' => 'period', 'label' => 'Range', 'active' => true],
                ['range' => 'week', 'label' => 'Week'],
                ['range' => 'month', 'label' => 'Month'],
                ['range' => 'year', 'label' => 'Year'],
            ]
            : [
                ['range' => 'month', 'label' => 'Month', 'active' => true],
                ['range' => 'week', 'label' => 'Week'],
                ['range' => 'year', 'label' => 'Year'],
            ],
        'rangeAriaLabel' => 'Admin hours chart range',
    ])
    </div>
</div>
