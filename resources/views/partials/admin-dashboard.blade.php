@php
    $orgProjects = $adminDashboard['org_projects'] ?? [];
    $orgStats = $adminDashboard['org_stats'] ?? [];
    $activePeriod = $adminDashboard['active_period'] ?? [];
    $navbarView = $navbarView ?? 'user';
    $adminTable = request()->query('admin_table', 'shift');
    if (! in_array($adminTable, ['shift', 'employee', 'project'], true)) {
        $adminTable = 'shift';
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
                    <button type="button" class="dashboard-segment__btn{{ $adminTable === 'shift' ? ' active' : '' }}" data-admin-table="shift">Shift</button>
                    <button type="button" class="dashboard-segment__btn{{ $adminTable === 'employee' ? ' active' : '' }}" data-admin-table="employee">Employee</button>
                    <button type="button" class="dashboard-segment__btn{{ $adminTable === 'project' ? ' active' : '' }}" data-admin-table="project">Project</button>
                </div>
            </div>

            <div class="dashboard-toolbar-period text-center">
                <span class="dashboard-period-badge {{ ($activePeriod['is_current_week'] ?? false) ? '' : 'd-none' }}" id="adminCurrentPeriodBadge">Current Period</span>
                <div class="dashboard-period-select">
                    <button type="button" class="dashboard-period-toggle" id="adminPeriodToggle" aria-haspopup="listbox" aria-expanded="false">
                        View Period: <span id="adminPeriodLabel">{{ $activePeriod['label'] ?? '' }}</span>
                        <i class="bi bi-chevron-down ms-1"></i>
                    </button>
                    <ul class="dashboard-period-menu d-none" id="adminPeriodMenu" role="listbox"></ul>
                </div>
            </div>

            <div class="dashboard-toolbar-total">
                Total: <span class="dashboard-total-value wavy-underline" id="adminPeriodTotal">{{ number_format($activePeriod['hours_this_period'] ?? 0, 2) }}</span>
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

    <div class="i3-data-table i3-data-table--7col{{ $adminTable === 'shift' ? '' : ' d-none' }}" id="adminShiftPanel">
        <div class="i3-data-table__scroll">
            <div class="i3-data-table__scroll-inner">
                <div class="i3-data-table__head">
                    <span>Employee</span>
                    <span>Project</span>
                    <span>Date</span>
                    <span>Hours</span>
                    <span class="i3-data-table__check-col">Timecard</span>
                    <span class="i3-data-table__check-col">Honeycrisp</span>
                    <span></span>
                </div>
                <ul class="i3-data-table__body i3-data-table__body--lg list-unstyled mb-0" id="adminShiftList">
                    @forelse($activePeriod['shifts'] ?? [] as $row)
                    <li class="i3-data-table__row" data-search="{{ strtolower($row['employee_name'].' '.$row['project_name'].' '.$row['date_display']) }}">
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
                                    @disabled($row['billed'])
                                    aria-label="Mark shift as billed for {{ $row['employee_name'] }}">
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
                        <li class="i3-data-table__empty">No shifts for this period.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    <div class="i3-data-table i3-data-table--5col{{ $adminTable === 'employee' ? '' : ' d-none' }}" id="adminEmployeePanel">
        <div class="i3-data-table__scroll">
            <div class="i3-data-table__scroll-inner">
                <div class="i3-data-table__head">
                    <span>Employee Name</span>
                    <span>Unbilled Hours</span>
                    <span>Total Hours</span>
                    <span>Top Project</span>
                    <span>Last Shift Date</span>
                </div>
                <ul class="i3-data-table__body i3-data-table__body--lg list-unstyled mb-0" id="adminEmployeeList">
                    @forelse($activePeriod['employees'] ?? [] as $row)
                    <li class="i3-data-table__row" data-search="{{ strtolower($row['name'].' '.$row['top_project']) }}">
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
                        <li class="i3-data-table__empty">No employee shift data for this period.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    <div class="i3-data-table i3-data-table--5col{{ $adminTable === 'project' ? '' : ' d-none' }}" id="adminProjectPanel">
        <div class="i3-data-table__scroll">
            <div class="i3-data-table__scroll-inner">
                <div class="i3-data-table__head">
                    <span>Project Name</span>
                    <span>Hours Last Period</span>
                    <span>Total Hours</span>
                    <span>Top Employee</span>
                    <span>Last Shift Date</span>
                </div>
                <ul class="i3-data-table__body i3-data-table__body--lg list-unstyled mb-0" id="adminProjectList">
                    @forelse($activePeriod['project_rows'] ?? [] as $row)
                    <li class="i3-data-table__row" data-search="{{ strtolower($row['name'].' '.$row['top_employee']) }}">
                        <span class="i3-data-table__label" data-label="Project">
                            <span class="i3-hash">#</span>
                            <a href="{{ route('admin.projects.show', ['project' => $row['id']]) }}" class="i3-link admin-project-link">
                                {{ $row['name'] }}
                            </a>
                        </span>
                        <span data-label="Hrs Last Period">{{ number_format($row['hours_last_period'], 2) }}</span>
                        <span data-label="Total Hrs">{{ number_format($row['total_hours'], 2) }}</span>
                        <span data-label="Top Employee">{{ $row['top_employee'] }}</span>
                        <span data-label="Last Shift">{{ $row['last_shift_date'] ?? '—' }}</span>
                    </li>
                    @empty
                        <li class="i3-data-table__empty">No project shift data for this period.</li>
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
        'chartRanges' => [
            ['range' => 'period', 'label' => 'Period', 'active' => true],
            ['range' => 'week', 'label' => 'Week'],
            ['range' => 'month', 'label' => 'Month'],
            ['range' => 'year', 'label' => 'Year'],
        ],
        'rangeAriaLabel' => 'Admin hours chart range',
    ])
    </div>
</div>
