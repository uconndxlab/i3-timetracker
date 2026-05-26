(function () {
    const config = window.adminDashboardConfig || {};
    const adminDashboard = document.getElementById('adminDashboard');
    if (!adminDashboard || !config.weeklyPeriods?.length) {
        return;
    }

    const weeklyPeriods = config.weeklyPeriods;
    const adminWeekIndex = config.weekIndex ?? 0;
    const adminLandingUrl = config.landingUrl || '/?view=admin';

    const tableToggle = document.getElementById('adminTableViewToggle');
    const employeePanel = document.getElementById('adminEmployeePanel');
    const projectPanel = document.getElementById('adminProjectPanel');
    const shiftPanel = document.getElementById('adminShiftPanel');
    const searchInput = document.getElementById('adminTableSearch');
    const employeeList = document.getElementById('adminEmployeeList');
    const projectList = document.getElementById('adminProjectList');
    const shiftList = document.getElementById('adminShiftList');
    const periodMenuEl = document.getElementById('adminPeriodMenu');
    const periodToggleEl = document.getElementById('adminPeriodToggle');

    const urlTableView = new URLSearchParams(window.location.search).get('admin_table');
    let tableView = ['shift', 'employee', 'project'].includes(urlTableView) ? urlTableView : 'shift';

    let csrfToken = config.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content || '';
    const shiftBaseUrl = config.shiftBaseUrl || '/shifts';

    const searchPlaceholders = {
        employee: 'Search through employees . . .',
        project: 'Search through projects . . .',
        shift: 'Search through shifts . . .',
    };

    const periodUrlFor = (startDate) => {
        const url = new URL(adminLandingUrl, window.location.origin);
        url.searchParams.set('period_start', startDate);
        if (tableView !== 'shift') {
            url.searchParams.set('admin_table', tableView);
        }
        return url.pathname + url.search;
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

    const markShiftBilled = async (shiftId) => {
        const formData = new FormData();
        formData.append('_token', csrfToken);
        formData.append('billed', '1');

        const response = await fetch(`${shiftBaseUrl}/${shiftId}/billed`, {
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
            csrfToken = data.csrf_token;
        }

        return data;
    };

    I3.initPeriodPicker({
        toggleEl: periodToggleEl,
        menuEl: periodMenuEl,
        periods: weeklyPeriods,
        activeIndex: adminWeekIndex,
        onSelect: (index) => {
            if (index === adminWeekIndex) {
                return;
            }

            const period = weeklyPeriods[index];
            if (!period) {
                return;
            }

            window.location.assign(periodUrlFor(period.start_date));
        },
    });

    adminDashboard.addEventListener('click', (event) => {
        const editBtn = event.target.closest('.admin-shift-edit-btn');
        if (editBtn) {
            try {
                const shift = JSON.parse(editBtn.dataset.shift || '{}');
                if (shift.id && typeof window.openShiftModal === 'function') {
                    window.openShiftModal(shift);
                }
            } catch {
                // ignore malformed shift payload
            }
            return;
        }

        const billedBtn = event.target.closest('.admin-shift-billed-toggle');
        if (!billedBtn || billedBtn.disabled) {
            return;
        }

        const shiftId = billedBtn.dataset.shiftId;
        if (!shiftId) {
            return;
        }

        event.preventDefault();
        billedBtn.disabled = true;
        billedBtn.classList.add('is-checked');
        billedBtn.setAttribute('aria-pressed', 'true');

        markShiftBilled(shiftId).catch((error) => {
            billedBtn.classList.remove('is-checked');
            billedBtn.setAttribute('aria-pressed', 'false');
            billedBtn.disabled = false;
            alert(error.message || 'Could not mark shift as billed. Please try again.');
        });
    });

    projectList?.addEventListener('click', (event) => {
        if (!event.target.closest('.admin-project-link')) {
            return;
        }

        sessionStorage.setItem('admin_return', '1');
        sessionStorage.setItem('admin_table', 'project');

        const period = weeklyPeriods[adminWeekIndex];
        if (period?.start_date) {
            sessionStorage.setItem('admin_period_start', period.start_date);
        }
    });

    tableToggle?.querySelectorAll('[data-admin-table]').forEach((btn) => {
        btn.addEventListener('click', () => setTableView(btn.dataset.adminTable));
    });

    searchInput?.addEventListener('input', filterRows);

    setTableView(tableView);
})();
