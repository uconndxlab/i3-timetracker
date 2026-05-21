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
    const searchInput = document.getElementById('adminTableSearch');
    const employeeList = document.getElementById('adminEmployeeList');
    const projectList = document.getElementById('adminProjectList');
    const periodLabelEl = document.getElementById('adminPeriodLabel');
    const periodTotalEl = document.getElementById('adminPeriodTotal');
    const currentPeriodBadgeEl = document.getElementById('adminCurrentPeriodBadge');
    const periodMenuEl = document.getElementById('adminPeriodMenu');
    const periodToggleEl = document.getElementById('adminPeriodToggle');

    let tableView = 'employee';

    const escapeHtml = (value) => String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');

    const formatHours = (hours) => Number(hours || 0).toFixed(2);

    const getCurrentPeriod = () => weeklyPeriods[adminWeekIndex] || null;

    const applyMainView = (view) => {
        const isAdmin = view === 'admin';
        adminDashboard.classList.toggle('d-none', !isAdmin);
        if (userDashboard) {
            userDashboard.classList.toggle('d-none', isAdmin);
        }
    };

    document.addEventListener('navbar-view-change', (event) => {
        applyMainView(event.detail?.view || 'user');
    });

    const storedView = localStorage.getItem('navbarViewMode');
    if (storedView === 'admin' || storedView === 'user') {
        applyMainView(storedView);
    }

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
                ? `<a href="${escapeHtml(employeeUrl(row.netid))}" class="i3-data-table__name-link">${escapeHtml(row.name)}</a>`
                : escapeHtml(row.name);

            return `
            <li class="i3-data-table__row" data-search="${escapeHtml((row.name + ' ' + row.top_project).toLowerCase())}">
                <span class="i3-data-table__label">
                    <span class="i3-data-table__dot" aria-hidden="true"></span>
                    ${nameHtml}
                </span>
                <span>${formatHours(row.unbilled_hours)}</span>
                <span>${formatHours(row.total_hours)}</span>
                <span><span class="i3-hash">#</span> ${escapeHtml(row.top_project)}</span>
                <span>${escapeHtml(row.last_shift_date || '—')}</span>
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
                <span class="i3-data-table__label">
                    <span class="i3-data-table__dot" aria-hidden="true"></span>
                    <span class="i3-hash">#</span> ${escapeHtml(row.name)}
                </span>
                <span>${formatHours(row.hours_last_period)}</span>
                <span>${formatHours(row.total_hours)}</span>
                <span>${escapeHtml(row.top_employee)}</span>
                <span>${escapeHtml(row.last_shift_date || '—')}</span>
            </li>
        `).join('');
    };

    const renderPeriod = () => {
        const period = getCurrentPeriod();

        renderEmployeeRows(period?.employees || []);
        renderProjectRows(period?.project_rows || []);

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

    const setTableView = (view) => {
        tableView = view;
        tableToggle?.querySelectorAll('.dashboard-segment__btn').forEach((btn) => {
            btn.classList.toggle('active', btn.dataset.adminTable === view);
        });
        employeePanel?.classList.toggle('d-none', view !== 'employee');
        projectPanel?.classList.toggle('d-none', view !== 'project');
        if (searchInput) {
            searchInput.placeholder = view === 'project'
                ? 'Search through projects . . .'
                : 'Search through employees . . .';
        }
        filterRows();
    };

    const filterRows = () => {
        const term = (searchInput?.value || '').trim().toLowerCase();
        const list = tableView === 'project' ? projectList : employeeList;
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

    renderPeriodMenu();
    renderPeriod();
})();
