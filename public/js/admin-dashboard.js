(function () {
    const adminDashboard = document.getElementById('adminDashboard');
    if (!adminDashboard) {
        return;
    }

    const weeklyPeriods = window.adminWeeklyPeriods || [];
    let adminWeekIndex = window.adminWeekIndex ?? 0;

    const userDashboard = document.getElementById('userDashboard');
    const tableToggle = document.getElementById('adminTableViewToggle');
    const employeePanel = document.getElementById('adminEmployeePanel');
    const projectPanel = document.getElementById('adminProjectPanel');
    const shiftPanel = document.getElementById('adminShiftPanel');
    const searchInput = document.getElementById('adminTableSearch');
    const employeeList = document.getElementById('adminEmployeeList');
    const projectList = document.getElementById('adminProjectList');
    const shiftList = document.getElementById('adminShiftList');
    const periodLabelEl = document.getElementById('adminPeriodLabel');
    const periodTotalEl = document.getElementById('adminPeriodTotal');
    const currentPeriodBadgeEl = document.getElementById('adminCurrentPeriodBadge');
    const periodMenuEl = document.getElementById('adminPeriodMenu');
    const periodToggleEl = document.getElementById('adminPeriodToggle');

    let tableView = 'shift';

    const escapeHtml = (value) => String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');

    const formatHours = (hours) => Number(hours || 0).toFixed(2);

    const isStatusOn = (value) => value === true || value === 1 || value === '1';

    const renderStatusIcon = (value) => (
        `<span class="i3-check ${isStatusOn(value) ? 'is-checked' : ''}" aria-hidden="true">
            <i class="bi bi-check-lg"></i>
        </span>`
    );

    const getCurrentPeriod = () => weeklyPeriods[adminWeekIndex] || null;

    const applyMainView = (view) => {
        const isAdmin = view === 'admin';
        adminDashboard.classList.toggle('d-none', !isAdmin);
        if (userDashboard) {
            userDashboard.classList.toggle('d-none', isAdmin);
        }
    };

    const renderEmployeeRows = (employees) => {
        if (!employeeList) {
            return;
        }

        if (!employees.length) {
            employeeList.innerHTML = '<li class="i3-data-table__empty">No employee shift data for this period.</li>';
            return;
        }

        const employeeUrl = typeof window.adminEmployeeDashboardUrl === 'function'
            ? window.adminEmployeeDashboardUrl
            : null;

        employeeList.innerHTML = employees.map((row) => {
            const nameHtml = employeeUrl && row.netid
                ? `<a href="${escapeHtml(employeeUrl(row.netid))}" class="i3-link">${escapeHtml(row.name)}</a>`
                : escapeHtml(row.name);

            return `
            <li class="i3-data-table__row" data-search="${escapeHtml((row.name + ' ' + row.top_project).toLowerCase())}">
                <span class="i3-data-table__label" data-label="Employee">
                    <span class="i3-hash">#</span> ${nameHtml}
                </span>
                <span data-label="Unbilled Hrs">${formatHours(row.unbilled_hours)}</span>
                <span data-label="Total Hrs">${formatHours(row.total_hours)}</span>
                <span data-label="Top Project"><span class="i3-hash">#</span> ${escapeHtml(row.top_project)}</span>
                <span data-label="Last Shift">${escapeHtml(row.last_shift_date || '—')}</span>
            </li>
        `;
        }).join('');
    };

    const renderProjectRows = (projects) => {
        if (!projectList) {
            return;
        }

        if (!projects.length) {
            projectList.innerHTML = '<li class="i3-data-table__empty">No project shift data for this period.</li>';
            return;
        }

        projectList.innerHTML = projects.map((row) => `
            <li class="i3-data-table__row" data-search="${escapeHtml((row.name + ' ' + row.top_employee).toLowerCase())}">
                <span class="i3-data-table__label" data-label="Project">
                    <span class="i3-hash">#</span> ${escapeHtml(row.name)}
                </span>
                <span data-label="Hrs Last Period">${formatHours(row.hours_last_period)}</span>
                <span data-label="Total Hrs">${formatHours(row.total_hours)}</span>
                <span data-label="Top Employee">${escapeHtml(row.top_employee)}</span>
                <span data-label="Last Shift">${escapeHtml(row.last_shift_date || '—')}</span>
            </li>
        `).join('');
    };

    const renderShiftRows = (shifts) => {
        if (!shiftList) {
            return;
        }

        if (!shifts.length) {
            shiftList.innerHTML = '<li class="i3-data-table__empty">No shifts for this period.</li>';
            return;
        }

        shiftList.innerHTML = shifts.map((row) => `
            <li class="i3-data-table__row"
                data-search="${escapeHtml((row.employee_name + ' ' + row.project_name + ' ' + row.date_display).toLowerCase())}">
                <span class="i3-data-table__label" data-label="Employee">
                    <span class="i3-hash">#</span> ${escapeHtml(row.employee_name)}
                </span>
                <span data-label="Project"><span class="i3-hash">#</span> ${escapeHtml(row.project_name)}</span>
                <span data-label="Date">${escapeHtml(row.date_display)}</span>
                <span data-label="Hours">${formatHours(row.hours)}</span>
                <span data-label="Timecard">${renderStatusIcon(row.entered)}</span>
                <span data-label="Honeycrisp">${renderStatusIcon(row.billed)}</span>
                <span class="i3-data-table__actions" data-label="Actions">
                    <button type="button"
                            class="btn btn-sm btn-outline-secondary admin-shift-edit-btn"
                            data-shift-id="${row.id}"
                            aria-label="Edit shift for ${escapeHtml(row.employee_name)}">
                        Edit
                    </button>
                </span>
            </li>
        `).join('');

        shiftList.querySelectorAll('.admin-shift-edit-btn').forEach((btn) => {
            btn.addEventListener('click', () => {
                const shiftId = parseInt(btn.dataset.shiftId, 10);
                const shift = shifts.find((entry) => entry.id === shiftId);
                if (shift && typeof window.openAdminShiftModal === 'function') {
                    window.openAdminShiftModal(shift);
                }
            });
        });
    };

    const recalcPeriodHours = (period) => {
        if (!period?.shifts) {
            return;
        }

        period.hours_this_period = period.shifts.reduce((sum, shift) => sum + Number(shift.hours || 0), 0);
    };

    const shiftInPeriod = (shift, period) => {
        if (!shift?.date || !period?.start_date || !period?.end_date) {
            return false;
        }

        return shift.date >= period.start_date && shift.date <= period.end_date;
    };

    const upsertShiftInPeriod = (period, updatedShift) => {
        if (!period?.shifts) {
            return;
        }

        const index = period.shifts.findIndex((shift) => shift.id === updatedShift.id);
        const inPeriod = shiftInPeriod(updatedShift, period);

        if (inPeriod) {
            if (index === -1) {
                period.shifts.push(updatedShift);
            } else {
                period.shifts[index] = updatedShift;
            }
        } else if (index !== -1) {
            period.shifts.splice(index, 1);
        }

        recalcPeriodHours(period);
    };

    const removeShiftFromPeriod = (period, shiftId) => {
        if (!period?.shifts) {
            return;
        }

        period.shifts = period.shifts.filter((shift) => shift.id !== shiftId);
        recalcPeriodHours(period);
    };

    window.onAdminShiftSaved = (updatedShift) => {
        const period = getCurrentPeriod();
        if (period) {
            upsertShiftInPeriod(period, updatedShift);
        }
        renderPeriod();
    };

    window.onAdminShiftDeleted = (shiftId) => {
        const period = getCurrentPeriod();
        if (period) {
            removeShiftFromPeriod(period, shiftId);
        }
        renderPeriod();
    };

    const renderPeriod = () => {
        const period = getCurrentPeriod();

        renderEmployeeRows(period?.employees || []);
        renderProjectRows(period?.project_rows || []);
        renderShiftRows(period?.shifts || []);

        if (periodLabelEl) {
            periodLabelEl.textContent = period?.label || '';
        }
        if (periodTotalEl) {
            periodTotalEl.textContent = formatHours(period?.hours_this_period);
        }
        if (currentPeriodBadgeEl) {
            currentPeriodBadgeEl.classList.toggle('d-none', !(period?.is_current_week));
        }

        filterRows();

        window.refreshDashboardChart('adminHoursChart');
    };

    const renderPeriodMenu = () => {
        if (!periodMenuEl) {
            return;
        }

        periodMenuEl.innerHTML = weeklyPeriods.map((week, index) => `
            <li>
                <button type="button" role="option" data-admin-week-index="${index}">
                    ${escapeHtml(week.label)}
                </button>
            </li>
        `).join('');

        periodMenuEl.querySelectorAll('[data-admin-week-index]').forEach((btn) => {
            btn.addEventListener('click', () => {
                adminWeekIndex = parseInt(btn.dataset.adminWeekIndex, 10);
                window.adminWeekIndex = adminWeekIndex;
                periodMenuEl.classList.add('d-none');
                periodToggleEl?.setAttribute('aria-expanded', 'false');
                renderPeriod();
            });
        });
    };

    const searchPlaceholders = {
        employee: 'Search through employees . . .',
        project: 'Search through projects . . .',
        shift: 'Search through shifts . . .',
    };

    const setTableView = (view) => {
        tableView = view;
        tableToggle?.querySelectorAll('.dashboard-segment__btn').forEach((btn) => {
            btn.classList.toggle('active', btn.dataset.adminTable === view);
        });
        employeePanel?.classList.toggle('d-none', view !== 'employee');
        projectPanel?.classList.toggle('d-none', view !== 'project');
        shiftPanel?.classList.toggle('d-none', view !== 'shift');
        if (searchInput) {
            searchInput.placeholder = searchPlaceholders[view] || searchPlaceholders.shift;
        }
        filterRows();
    };

    const getActiveList = () => {
        if (tableView === 'project') {
            return projectList;
        }
        if (tableView === 'shift') {
            return shiftList;
        }
        return employeeList;
    };

    const filterRows = () => {
        const term = (searchInput?.value || '').trim().toLowerCase();
        const list = getActiveList();
        if (!list) {
            return;
        }

        list.querySelectorAll('.i3-data-table__row').forEach((row) => {
            const haystack = row.dataset.search || '';
            row.classList.toggle('d-none', term !== '' && !haystack.includes(term));
        });
    };

    tableToggle?.querySelectorAll('[data-admin-table]').forEach((btn) => {
        btn.addEventListener('click', () => setTableView(btn.dataset.adminTable));
    });

    searchInput?.addEventListener('input', filterRows);

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

    const handleNavbarView = (view) => {
        applyMainView(view);
        if (view === 'admin') {
            setTableView('shift');
        }
    };

    document.addEventListener('navbar-view-change', (event) => {
        handleNavbarView(event.detail?.view || 'user');
    });

    const storedView = localStorage.getItem('navbarViewMode');
    if (storedView === 'admin' || storedView === 'user') {
        handleNavbarView(storedView);
    }

    renderPeriodMenu();
    setTableView('shift');
    renderPeriod();
})();
