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

    let tableView = 'shift';

    const periodUrlFor = (startDate) => {
        const url = new URL(adminLandingUrl, window.location.origin);
        url.searchParams.set('period_start', startDate);
        return url.pathname + url.search;
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

    const shiftBaseUrl = config.shiftBaseUrl || '/shifts';
    let csrfToken = config.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content || '';

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

    shiftList?.querySelectorAll('.admin-shift-edit-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            try {
                const shift = JSON.parse(btn.dataset.shift || '{}');
                if (shift.id && typeof window.openShiftModal === 'function') {
                    window.openShiftModal(shift);
                }
            } catch {
                // ignore malformed shift payload
            }
        });
    });

    shiftList?.addEventListener('click', async (event) => {
        const toggleBtn = event.target.closest('.admin-shift-billed-toggle');
        if (!toggleBtn || toggleBtn.disabled) {
            return;
        }

        event.preventDefault();

        const shiftId = toggleBtn.dataset.shiftId;
        if (!shiftId) {
            return;
        }

        toggleBtn.disabled = true;

        try {
            await markShiftBilled(shiftId);
            toggleBtn.classList.add('is-checked');
            toggleBtn.setAttribute('aria-pressed', 'true');
            toggleBtn.disabled = true;
        } catch (error) {
            alert(error.message || 'Could not mark shift as billed. Please try again.');
            toggleBtn.disabled = false;
        }
    });

    tableToggle?.querySelectorAll('[data-admin-table]').forEach((btn) => {
        btn.addEventListener('click', () => setTableView(btn.dataset.adminTable));
    });

    searchInput?.addEventListener('input', filterRows);

    setTableView('shift');
})();
