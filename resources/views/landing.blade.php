@extends('layouts.app')

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
<div class="dashboard mt-3" id="userDashboard">
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

@if($adminDashboard ?? null)
    @include('partials.admin-dashboard', ['adminDashboard' => $adminDashboard])
@endif

@unless($dashboardReadOnly ?? false)
    @include('partials.shift-modal')
    @include('partials.join-projects-modal')
@endunless
@include('partials.bootstrap-js')

@push('scripts')
<script>
    window.joinableProjects = @json($joinableProjects ?? []);
    window.joinProjectsSyncUrl = @json(route('projects.sync-memberships'));
    @if($adminDashboard ?? null)
    window.adminWeeklyPeriods = @json($adminDashboard['weekly_periods'] ?? []);
    window.adminWeekIndex = {{ $adminDashboard['current_week_index'] ?? 0 }};
    window.adminHoursTimeline = @json($adminDashboard['hours_timeline'] ?? []);
    window.adminEmployeeDashboardUrl = (netid) => @json(route('admin.users.dashboard', ['user' => '__NETID__'])).replace('__NETID__', encodeURIComponent(netid));
    @endif
</script>
<script>
(function() {
    const dashboardReadOnly = @json($dashboardReadOnly ?? false);
    const weeklyChartData = @json($weeklyChartData ?? []);
    let currentWeekIndex = {{ $currentWeekIndex ?? 0 }};
    window.dashboardWeekIndex = currentWeekIndex;
    const editShiftBaseUrl = @json(url('/shifts'));
    const destroyShiftBaseUrl = @json(url('/shifts'));
    const bulkEnteredUrl = @json(route('shifts.bulk-update-entered'));
    const editableProjects = @json($logShiftProjects->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])->values());
    const csrfToken = @json(csrf_token());

    let sortBy = 'date';
    let sortDir = 'desc';
    let projectFilter = 'all';
    let editingDayDate = null;

    const shiftGridEl = document.getElementById('shiftGrid');
    const defaultShiftDate = @json($defaultShiftDate);
    const periodLabelEl = document.getElementById('periodLabel');
    const periodTotalEl = document.getElementById('periodTotal');
    const currentPeriodBadgeEl = document.getElementById('currentPeriodBadge');
    const periodMenuEl = document.getElementById('periodMenu');
    const periodToggleEl = document.getElementById('periodToggle');
    const projectFilterEl = document.getElementById('projectFilter');
    const sortDirBtn = document.getElementById('sortDirBtn');
    const sortDirIcon = document.getElementById('sortDirIcon');
    const sortButtons = document.querySelectorAll('[data-sort]');

    const formatHours = (hours) => Number(hours || 0).toFixed(2);

    const renderEnteredCheck = (isChecked, shiftIds, extraAttrs = '') => {
        if (dashboardReadOnly) {
            return `<span class="i3-check ${isChecked ? 'is-checked' : ''}" aria-hidden="true">
                        <i class="bi bi-check-lg"></i>
                    </span>`;
        }

        const ids = (Array.isArray(shiftIds) ? shiftIds : [shiftIds]).filter(Boolean).join(',');
        return `<button type="button"
                    class="i3-check ${isChecked ? 'is-checked' : ''}"
                    data-toggle-entered
                    data-stop-card-toggle
                    data-shift-ids="${ids}"
                    aria-pressed="${isChecked ? 'true' : 'false'}"
                    aria-label="Toggle entered in timecard"
                    ${extraAttrs}>
                <i class="bi bi-check-lg"></i>
            </button>`;
    };

    const mergeProjectHours = (shifts) => {
        const byProject = {};

        shifts.forEach((shift) => {
            const key = String(shift.proj_id);
            if (!byProject[key]) {
                byProject[key] = {
                    proj_id: shift.proj_id,
                    project_name: shift.project_name,
                    hours: 0,
                    entered: true,
                    shift_ids: [],
                };
            }
            byProject[key].hours += (shift.duration_minutes || 0) / 60;
            byProject[key].entered = byProject[key].entered && Boolean(shift.entered);
            byProject[key].shift_ids.push(shift.id);
        });

        return Object.values(byProject)
            .map((row) => ({
                ...row,
                hours: Math.round(row.hours * 100) / 100,
                entered: row.entered !== false,
            }))
            .sort((a, b) => b.hours - a.hours);
    };

    const getShiftsInDisplayOrder = (day) => {
        const projectHours = day.project_hours || mergeProjectHours(day.shifts || []);
        const shiftsByProject = {};

        (day.shifts || []).forEach((shift) => {
            const key = String(shift.proj_id);
            if (!shiftsByProject[key]) {
                shiftsByProject[key] = [];
            }
            shiftsByProject[key].push(shift);
        });

        const orderedShifts = [];

        projectHours.forEach((row) => {
            const projectShifts = (shiftsByProject[String(row.proj_id)] || [])
                .sort((a, b) => Number(a.id) - Number(b.id));
            orderedShifts.push(...projectShifts);
        });

        return orderedShifts;
    };

    const setShiftsEnteredInData = (shiftIds, entered) => {
        const idSet = new Set(shiftIds.map(String));

        weeklyChartData.forEach((week) => {
            (week.days || []).forEach((day) => {
                (day.shifts || []).forEach((shift) => {
                    if (idSet.has(String(shift.id))) {
                        shift.entered = entered;
                    }
                });
            });
        });
    };

    const getShiftEntered = (shiftId) => {
        for (const week of weeklyChartData) {
            for (const day of week.days || []) {
                for (const shift of day.shifts || []) {
                    if (String(shift.id) === String(shiftId)) {
                        return Boolean(shift.entered);
                    }
                }
            }
        }

        return false;
    };

    const syncEnteredCheckboxes = () => {
        if (!shiftGridEl) {
            return;
        }

        shiftGridEl.querySelectorAll('[data-toggle-entered]').forEach((btn) => {
            const ids = (btn.dataset.shiftIds || '').split(',').filter(Boolean);
            const allEntered = ids.length > 0 && ids.every((id) => getShiftEntered(id));

            btn.classList.toggle('is-checked', allEntered);
            btn.setAttribute('aria-pressed', allEntered ? 'true' : 'false');
        });
    };

    const refreshCsrfToken = (token) => {
        if (!token) {
            return;
        }

        document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', token);
    };

    const findShiftById = (shiftId) => {
        for (const week of weeklyChartData) {
            for (const day of week.days || []) {
                for (const shift of day.shifts || []) {
                    if (String(shift.id) === String(shiftId)) {
                        return { shift, day, week };
                    }
                }
            }
        }

        return null;
    };

    const applyShiftUpdateToData = (updatedShift) => {
        const match = findShiftById(updatedShift.id);
        if (!match) {
            return;
        }

        Object.assign(match.shift, updatedShift);
    };

    const persistShiftUpdate = async (shiftId, fields) => {
        const token = document.querySelector('meta[name="csrf-token"]')?.content || csrfToken;
        const formData = new FormData();
        formData.append('_token', token);
        formData.append('_method', 'PUT');

        if (fields.proj_id !== undefined) {
            formData.append('proj_id', fields.proj_id);
        }

        if (fields.duration !== undefined) {
            formData.append('duration', fields.duration);
        }

        const response = await fetch(`${editShiftBaseUrl}/${shiftId}`, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            throw new Error(data.message || `Request failed (${response.status})`);
        }

        if (data.csrf_token) {
            document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', data.csrf_token);
        }

        if (data.shift) {
            applyShiftUpdateToData(data.shift);
        }

        return data;
    };

    const renderShiftReadOnlyRow = (row) => `
        <li>
            <div class="dashboard-shift-card__project-row">
                <span class="dashboard-shift-card__project-name">${row.project_name}</span>
                <span class="dashboard-shift-card__project-sep">|</span>
                <span class="dashboard-shift-card__project-hours">${formatHours(row.hours)}</span>
                <span class="dashboard-shift-card__project-sep">|</span>
                ${renderEnteredCheck(row.entered, row.shift_ids || [])}
            </div>
        </li>
    `;

    const renderShiftEditorRow = (shift) => {
        const disabled = shift.can_edit ? '' : 'disabled';
        const projectOptions = editableProjects.map((project) => {
            const selected = String(project.id) === String(shift.proj_id) ? 'selected' : '';
            return `<option value="${project.id}" ${selected}>${project.name}</option>`;
        }).join('');

        return `
            <li data-shift-id="${shift.id}">
                <div class="dashboard-shift-card__shift-row" data-stop-card-toggle>
                    <select class="form-select dashboard-shift-card__inline-select"
                            data-shift-id="${shift.id}"
                            data-shift-field="proj_id"
                            ${disabled}
                            aria-label="Project for shift ${shift.id}">
                        ${projectOptions}
                    </select>
                    <div class="dashboard-shift-card__inline-hours-wrap">
                        <input type="number"
                               class="dashboard-shift-card__inline-hours"
                               data-shift-id="${shift.id}"
                               data-shift-field="duration_hours"
                               value="${formatHours(shift.duration_hours)}"
                               min="0.25"
                               step="0.25"
                               ${disabled}
                               aria-label="Hours for shift ${shift.id}">
                        <span class="dashboard-shift-card__inline-hours-suffix">hr</span>
                    </div>
                    ${renderEnteredCheck(shift.entered, [shift.id])}
                </div>
            </li>
        `;
    };

    const persistShiftsEntered = async (shiftIds, entered) => {
        const uniqueIds = [...new Set(shiftIds.map(String))];
        const token = document.querySelector('meta[name="csrf-token"]')?.content || csrfToken;
        const formData = new FormData();

        formData.append('_token', token);
        formData.append('entered', entered ? '1' : '0');
        uniqueIds.forEach((id) => formData.append('shift_ids[]', id));

        const response = await fetch(bulkEnteredUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            throw new Error(data.message || `Request failed (${response.status})`);
        }

        refreshCsrfToken(data.csrf_token);
        setShiftsEnteredInData(uniqueIds, entered);
        syncEnteredCheckboxes();

        return data;
    };

    const getCurrentWeek = () => weeklyChartData[window.dashboardWeekIndex ?? 0] || null;

    const getFilteredDays = () => {
        const week = getCurrentWeek();
        if (!week) {
            return [];
        }

        const days = (week.days || []).map((day) => {
            let shifts = [...(day.shifts || [])];

            if (projectFilter !== 'all') {
                shifts = shifts.filter((shift) => String(shift.proj_id) === String(projectFilter));
            }

            const durationMinutes = shifts.reduce((sum, shift) => sum + (shift.duration_minutes || 0), 0);
            const durationHours = durationMinutes / 60;

            return {
                ...day,
                shifts,
                project_hours: mergeProjectHours(shifts),
                duration_minutes: durationMinutes,
                duration_hours: durationHours,
                duration_display: formatHours(durationHours) + ' hr',
                entered: shifts.length > 0 && shifts.every((shift) => shift.entered),
                is_empty: shifts.length === 0,
            };
        });

        days.sort((a, b) => {
            let cmp = 0;
            if (sortBy === 'duration') {
                cmp = (a.duration_hours || 0) - (b.duration_hours || 0);
            } else if (sortBy === 'timecard') {
                cmp = Number(a.entered) - Number(b.entered);
            } else {
                cmp = a.date.localeCompare(b.date);
            }
            return sortDir === 'asc' ? cmp : -cmp;
        });

        return days;
    };

    const renderPeriodMenu = () => {
        if (!periodMenuEl) {
            return;
        }

        periodMenuEl.innerHTML = weeklyChartData.map((week, index) => `
            <li>
                <button type="button" role="option" data-week-index="${index}" ${index === currentWeekIndex ? 'aria-selected="true"' : ''}>
                    ${week.label}
                </button>
            </li>
        `).join('');

        periodMenuEl.querySelectorAll('[data-week-index]').forEach((btn) => {
            btn.addEventListener('click', () => {
                currentWeekIndex = Number(btn.dataset.weekIndex);
                window.dashboardWeekIndex = currentWeekIndex;
                window.dashboardSetWeekIndex?.(currentWeekIndex);
                editingDayDate = null;
                periodMenuEl.classList.add('d-none');
                periodToggleEl?.setAttribute('aria-expanded', 'false');
                render();
            });
        });
    };

    const renderDayCard = (day) => {
        const collapseId = `day-shifts-${day.date}`;

        const displayShifts = getShiftsInDisplayOrder(day);
        const projectHours = day.project_hours || mergeProjectHours(day.shifts || []);
        const isEditing = !dashboardReadOnly && editingDayDate === day.date;
        const hasEditableShifts = !dashboardReadOnly && displayShifts.some((shift) => shift.can_edit);

        const shiftsListHtml = isEditing
            ? displayShifts.map((shift) => renderShiftEditorRow(shift)).join('')
            : projectHours.map((row) => renderShiftReadOnlyRow(row)).join('');

        const actionBtnHtml = hasEditableShifts
            ? `<button type="button"
                      class="dashboard-shift-card__edit-btn ${isEditing ? 'dashboard-shift-card__edit-btn--save' : ''}"
                      data-day-date="${day.date}"
                      data-edit-mode="${isEditing ? 'save' : 'edit'}"
                      data-stop-card-toggle>
                    ${isEditing ? 'SAVE SHIFTS' : 'EDIT SHIFTS'}
               </button>`
            : '';

        const tagsHtml = (day.project_hours || []).map((row) => `
            <div class="dashboard-shift-card__tag"># ${row.project_name}</div>
        `).join('');

        const innerExpandHtml = day.is_empty ? '' : `
            <div class="collapse dashboard-shift-card__expand" id="${collapseId}">
                <hr class="dashboard-shift-card__divider">
                <ul class="dashboard-shift-card__shift-list">
                    ${shiftsListHtml}
                </ul>
                ${actionBtnHtml}
            </div>
        `;

        const menuHtml = day.is_empty || dashboardReadOnly
            ? ''
            : `<div class="dropdown">
                    <button class="dashboard-shift-card__menu" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-three-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        ${day.shifts.map((shift, index) => `
                            ${index > 0 ? '<li><hr class="dropdown-divider"></li>' : ''}
                            <li>
                                <div class="dashboard-shift-menu-item dropdown-item-text">
                                    <span class="dashboard-shift-menu-item__label">${shift.project_name}</span>
                                    ${renderEnteredCheck(shift.entered, [shift.id])}
                                </div>
                            </li>
                            <li>
                                <form method="POST" action="${destroyShiftBaseUrl}/${shift.id}" onsubmit="return confirm('Delete this shift?');">
                                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                    <input type="hidden" name="_method" value="DELETE">
                                    <button type="submit" class="dropdown-item text-danger">Delete ${shift.project_name}</button>
                                </form>
                            </li>
                        `).join('')}
                    </ul>
               </div>`;

        const timecardHtml = day.is_empty
            ? '<span class="dashboard-shift-card__timecard-empty">No timecard entry</span>'
            : `<div class="dashboard-shift-card__timecard">
                    <span>Entered in Timecard</span>
                    <span class="dashboard-shift-card__dots"></span>
                    ${renderEnteredCheck(day.entered, day.shifts.map((shift) => shift.id))}
               </div>`;

        const innerHtml = `
            <div class="dashboard-shift-card__inner">
                <div class="dashboard-shift-card__summary">
                    <div class="dashboard-shift-card__body-top">
                        <span class="dashboard-shift-card__total-label">Total:</span>
                        ${menuHtml}
                    </div>
                    <div class="dashboard-shift-card__hours">${day.duration_display}</div>
                    ${timecardHtml}
                </div>
                ${innerExpandHtml}
            </div>
        `;

        return `
            <article class="dashboard-shift-card ${day.is_empty ? 'is-empty' : 'dashboard-shift-card--expandable'}"
                     data-day-date="${day.date}"
                     ${day.is_empty ? '' : `data-day-expandable data-collapse-id="${collapseId}" role="button" tabindex="0" aria-expanded="false" aria-controls="${collapseId}"`}>
                <div class="dashboard-shift-card__header">
                    <span class="dashboard-shift-card__date-badge">${day.date_badge}</span>
                    <span class="dashboard-shift-card__weekday wavy-underline">${day.weekday}</span>
                </div>
                ${innerHtml}
                ${day.is_empty ? '' : `<div class="dashboard-shift-card__tags">${tagsHtml}</div>`}
            </article>
        `;
    };

    const render = () => {
        const week = getCurrentWeek();
        const days = getFilteredDays();

        if (periodLabelEl) {
            periodLabelEl.textContent = week?.label || '';
        }
        if (periodTotalEl) {
            const total = days.reduce((sum, day) => sum + (day.duration_hours || 0), 0);
            periodTotalEl.textContent = formatHours(total);
        }
        if (currentPeriodBadgeEl) {
            currentPeriodBadgeEl.classList.toggle('d-none', !(week?.is_current_week));
        }

        if (!shiftGridEl) {
            return;
        }

        const expandedCollapseIds = [...shiftGridEl.querySelectorAll('.dashboard-shift-card.is-expanded')]
            .map((card) => card.getAttribute('aria-controls'))
            .filter(Boolean);
        const activeEditingDayDate = editingDayDate;

        shiftGridEl.innerHTML = days.map(renderDayCard).join('');
        editingDayDate = activeEditingDayDate;
        bindDayCardExpand();
        bindEditSaveButtons();

        if (typeof bootstrap !== 'undefined') {
            expandedCollapseIds.forEach((collapseId) => {
                const card = shiftGridEl.querySelector(`[aria-controls="${collapseId}"]`);
                const collapseEl = document.getElementById(collapseId);
                if (!card || !collapseEl) {
                    return;
                }

                const collapse = bootstrap.Collapse.getOrCreateInstance(collapseEl, { toggle: false });
                collapse.show();
                card.classList.add('is-expanded');
                card.setAttribute('aria-expanded', 'true');
            });
        }

        window.updateWeeklyChart?.();
    };

    const saveDayShifts = async (dayDate) => {
        const card = shiftGridEl?.querySelector(`[data-day-date="${dayDate}"]`)?.closest('.dashboard-shift-card');
        if (!card) {
            return;
        }

        const updates = [];

        card.querySelectorAll('[data-shift-id]').forEach((row) => {
            const shiftId = row.dataset.shiftId;
            const match = findShiftById(shiftId);
            if (!match?.shift?.can_edit) {
                return;
            }

            const select = row.querySelector('.dashboard-shift-card__inline-select');
            const input = row.querySelector('.dashboard-shift-card__inline-hours');
            if (!select || !input) {
                return;
            }

            const hours = parseFloat(input.value);
            if (Number.isNaN(hours) || hours < 0.25) {
                throw new Error('Each shift must be at least 0.25 hours.');
            }

            const fields = {};
            const minutes = Math.round(hours * 60);

            if (String(select.value) !== String(match.shift.proj_id)) {
                fields.proj_id = select.value;
            }

            if (minutes !== Number(match.shift.duration_minutes)) {
                fields.duration = minutes;
            }

            if (Object.keys(fields).length > 0) {
                updates.push(persistShiftUpdate(shiftId, fields));
            }
        });

        await Promise.all(updates);
        editingDayDate = null;
        render();
    };

    const bindEditSaveButtons = () => {
        if (!shiftGridEl) {
            return;
        }

        shiftGridEl.querySelectorAll('.dashboard-shift-card__edit-btn').forEach((btn) => {
            btn.addEventListener('click', async (event) => {
                event.preventDefault();
                event.stopPropagation();

                const dayDate = btn.dataset.dayDate;
                if (!dayDate) {
                    return;
                }

                if (btn.dataset.editMode === 'save') {
                    btn.disabled = true;

                    try {
                        await saveDayShifts(dayDate);
                    } catch (error) {
                        alert(error.message || 'Could not save shifts. Please try again.');
                        btn.disabled = false;
                    }

                    return;
                }

                editingDayDate = dayDate;
                render();
            });
        });
    };

    const bindDayCardExpand = () => {
        if (!shiftGridEl || typeof bootstrap === 'undefined') {
            return;
        }

        shiftGridEl.querySelectorAll('[data-day-expandable]').forEach((card) => {
            const collapseEl = card.querySelector('.dashboard-shift-card__expand');
            if (!collapseEl) {
                return;
            }

            const collapse = bootstrap.Collapse.getOrCreateInstance(collapseEl, { toggle: false });

            collapseEl.addEventListener('shown.bs.collapse', () => {
                card.classList.add('is-expanded');
                card.setAttribute('aria-expanded', 'true');
            });

            collapseEl.addEventListener('hidden.bs.collapse', () => {
                card.classList.remove('is-expanded');
                card.setAttribute('aria-expanded', 'false');
            });

            const toggleCard = () => {
                collapse.toggle();
            };

            card.addEventListener('click', (event) => {
                if (event.target.closest('[data-stop-card-toggle], [data-toggle-entered], .dropdown, .dropdown-menu')) {
                    return;
                }
                toggleCard();
            });

            card.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    toggleCard();
                }
            });
        });
    };

    sortButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            sortBy = btn.dataset.sort;
            sortButtons.forEach((b) => b.classList.toggle('active', b === btn));
            editingDayDate = null;
            render();
        });
    });

    sortDirBtn?.addEventListener('click', () => {
        sortDir = sortDir === 'asc' ? 'desc' : 'asc';
        sortDirIcon.className = sortDir === 'asc' ? 'bi bi-arrow-up' : 'bi bi-arrow-down';
        editingDayDate = null;
        render();
    });

    projectFilterEl?.addEventListener('change', () => {
        projectFilter = projectFilterEl.value;
        editingDayDate = null;
        render();
    });

    periodToggleEl?.addEventListener('click', () => {
        const isOpen = !periodMenuEl.classList.contains('d-none');
        periodMenuEl.classList.toggle('d-none', isOpen);
        periodToggleEl.setAttribute('aria-expanded', String(!isOpen));
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('.dashboard-period-select')) {
            periodMenuEl?.classList.add('d-none');
            periodToggleEl?.setAttribute('aria-expanded', 'false');
        }
    });

    shiftGridEl?.addEventListener('click', async (event) => {
        const toggleBtn = event.target.closest('[data-toggle-entered]');
        if (!toggleBtn || toggleBtn.disabled) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        const shiftIds = (toggleBtn.dataset.shiftIds || '').split(',').filter(Boolean);
        if (shiftIds.length === 0) {
            return;
        }

        const nextEntered = !toggleBtn.classList.contains('is-checked');
        toggleBtn.disabled = true;

        try {
            await persistShiftsEntered(shiftIds, nextEntered);
        } catch (error) {
            syncEnteredCheckboxes();
            alert(error.message || 'Could not update timecard status. Please try again.');
        } finally {
            toggleBtn.disabled = false;
        }
    });

    renderPeriodMenu();
    render();
})();
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="{{ asset('js/dashboard-chart.js') }}"></script>
<script>
(function() {
    const userWeeklyChartData = @json($weeklyChartData ?? []);
    const userHoursTimeline = @json($hoursTimeline ?? []);

    const formatChartDayLabel = (day) => {
        if (!day?.date) {
            return day?.key || day?.label || '';
        }

        const parts = day.date.split('-');
        return `${Number(parts[1])}/${Number(parts[2])}`;
    };

    const bootDashboardCharts = () => {
        if (typeof window.initDashboardChart !== 'function') {
            setTimeout(bootDashboardCharts, 50);
            return;
        }

    window.initDashboardChart({
        rootId: 'userDashboard',
        canvasId: 'weeklyHoursChart',
        totalId: 'statsChartTotal',
        hoursTimeline: userHoursTimeline,
        defaultRange: 'month',
        maxTicksLimit: 4,
        getPeriodSeries: () => {
            const week = userWeeklyChartData[window.dashboardWeekIndex ?? 0];
            if (!week?.days?.length) {
                return { labels: [], data: [], total: 0 };
            }

            return {
                labels: week.days.map((day) => day.key || formatChartDayLabel(day)),
                data: week.days.map((day) => day.hours ?? 0),
                total: week.hours_this_week ?? 0,
            };
        },
    });

    window.updateWeeklyChart = () => window.refreshDashboardChart('weeklyHoursChart');

    window.dashboardSetWeekIndex = (index) => {
        window.dashboardWeekIndex = index;
        window.updateWeeklyChart();
    };

    @if($adminDashboard ?? null)
    const adminWeeklyPeriods = window.adminWeeklyPeriods || [];

    window.initDashboardChart({
        rootId: 'adminDashboard',
        canvasId: 'adminHoursChart',
        totalId: 'adminStatsChartTotal',
        hoursTimeline: window.adminHoursTimeline || {},
        defaultRange: 'period',
        maxTicksLimit: 7,
        getPeriodSeries: () => {
            const period = adminWeeklyPeriods[window.adminWeekIndex ?? 0];
            const days = period?.days || [];

            return {
                labels: days.map((day) => day.label || ''),
                data: days.map((day) => day.hours ?? 0),
                total: period?.hours_this_period ?? 0,
            };
        },
    });
    @endif
    };

    bootDashboardCharts();
})();
</script>
@unless($dashboardReadOnly ?? false)
<script defer src="{{ asset('js/shift-modal.js') }}"></script>
<script defer src="{{ asset('js/join-projects-modal.js') }}"></script>
@endunless
@if($adminDashboard ?? null)
<script defer src="{{ asset('js/admin-shift-modal.js') }}"></script>
<script defer src="{{ asset('js/admin-dashboard.js') }}"></script>
@endif
@endpush
@endsection
