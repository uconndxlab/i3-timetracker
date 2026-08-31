(function () {
    const config = window.adminDashboardConfig || {};
    const adminDashboard = document.getElementById('adminDashboard');
    if (!adminDashboard) {
        return;
    }

    const adminLandingUrl = config.landingUrl || '/?view=admin';

    const tableToggle = document.getElementById('adminTableViewToggle');
    const employeePanel = document.getElementById('adminEmployeePanel');
    const projectPanel = document.getElementById('adminProjectPanel');
    const shiftPanel = document.getElementById('adminShiftPanel');
    const searchInput = document.getElementById('adminTableSearch');
    const employeeList = document.getElementById('adminEmployeeList');
    const projectList = document.getElementById('adminProjectList');
    const shiftList = document.getElementById('adminShiftList');
    const dateFromInput = document.getElementById('adminDateFrom');
    const dateToInput = document.getElementById('adminDateTo');
    const dateApplyBtn = document.getElementById('adminDateApply');
    const dateClearBtn = document.getElementById('adminDateClear');

    const urlTableView = new URLSearchParams(window.location.search).get('admin_table');
    let tableView = ['shift', 'employee', 'project'].includes(urlTableView) ? urlTableView : 'project';

    let csrfToken = config.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content || '';
    const shiftBaseUrl = config.shiftBaseUrl || '/shifts';
    const productUrlTemplate = config.productUrlTemplate || '/admin/users/__NETID__/product';

    const searchPlaceholders = {
        employee: 'Search through employees . . .',
        project: 'Search through projects . . .',
        shift: 'Search through shifts . . .',
    };

    const sortState = {
        project: { key: 'name', dir: 'asc', type: 'text' },
        employee: { key: 'name', dir: 'asc', type: 'text' },
        shift: { key: 'date', dir: 'desc', type: 'date' },
    };

    const getSortPanel = (view) => adminDashboard.querySelector(`[data-admin-sort-panel="${view}"]`);

    const sortDatasetKey = (key) => `sort-${key.replace(/_/g, '-')}`.replace(/-([a-z])/g, (_, char) => char.toUpperCase());

    const getSortValue = (row, key, type) => {
        const raw = row.getAttribute(`data-sort-${key.replace(/_/g, '-')}`) ?? row.dataset[sortDatasetKey(key)] ?? '';

        if (type === 'number' || type === 'boolean') {
            return Number(raw) || 0;
        }

        return String(raw).toLowerCase();
    };

    const compareSortValues = (left, right, type) => {
        if (type === 'number' || type === 'boolean') {
            return left - right;
        }

        if (type === 'date') {
            return String(left).localeCompare(String(right));
        }

        return String(left).localeCompare(String(right), undefined, { sensitivity: 'base' });
    };

    const syncSortHeaders = (view) => {
        const panel = getSortPanel(view);
        if (!panel) {
            return;
        }

        const state = sortState[view];
        panel.querySelectorAll('.i3-data-table__sort-btn').forEach((btn) => {
            const isActive = btn.dataset.adminSort === state.key;
            btn.classList.toggle('is-sorted', isActive);
            btn.dataset.sortDir = isActive ? state.dir : '';
            btn.setAttribute('aria-sort', isActive ? (state.dir === 'asc' ? 'ascending' : 'descending') : 'none');
        });
    };

    const sortRows = (view) => {
        const list = view === 'project' ? projectList : view === 'shift' ? shiftList : employeeList;
        const state = sortState[view];
        if (!list || !state?.key) {
            return;
        }

        const rows = [...list.querySelectorAll('.i3-data-table__row')];
        if (rows.length < 2) {
            syncSortHeaders(view);
            return;
        }

        const multiplier = state.dir === 'asc' ? 1 : -1;
        rows.sort((rowA, rowB) => {
            const left = getSortValue(rowA, state.key, state.type);
            const right = getSortValue(rowB, state.key, state.type);
            return compareSortValues(left, right, state.type) * multiplier;
        });

        rows.forEach((row) => list.appendChild(row));
        syncSortHeaders(view);
    };

    const setSort = (view, key, type, dir = null) => {
        const state = sortState[view];
        if (state.key === key && dir === null) {
            state.dir = state.dir === 'asc' ? 'desc' : 'asc';
        } else {
            state.key = key;
            state.type = type;
            state.dir = dir ?? (type === 'text' ? 'asc' : 'desc');
        }

        sortRows(view);
        filterRows();
    };

    const filterUrlFor = ({ dateFrom = null, dateTo = null, clearDates = false } = {}) => {
        const url = new URL(adminLandingUrl, window.location.origin);

        if (clearDates) {
            url.searchParams.delete('date_from');
            url.searchParams.delete('date_to');
        } else {
            if (dateFrom) {
                url.searchParams.set('date_from', dateFrom);
            } else {
                url.searchParams.delete('date_from');
            }

            if (dateTo) {
                url.searchParams.set('date_to', dateTo);
            } else {
                url.searchParams.delete('date_to');
            }
        }

        if (tableView !== 'project') {
            url.searchParams.set('admin_table', tableView);
        } else {
            url.searchParams.delete('admin_table');
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
            searchInput.placeholder = searchPlaceholders[view] || searchPlaceholders.project;
        }
        sortRows(view);
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

    const setShiftBilled = async (shiftId, billed) => {
        const formData = new FormData();
        formData.append('_token', csrfToken);
        formData.append('billed', billed ? '1' : '0');

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

    const setProduct = async (netid, productId) => {
        const formData = new FormData();
        formData.append('_token', csrfToken);
        formData.append('product_id', productId);

        const response = await fetch(productUrlTemplate.replace('__NETID__', encodeURIComponent(netid)), {
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

    const applyDateFilter = () => {
        const dateFrom = dateFromInput?.value || '';
        const dateTo = dateToInput?.value || '';

        if (!dateFrom || !dateTo) {
            alert('Please select both a start date and an end date.');
            return;
        }

        if (dateFrom > dateTo) {
            alert('Start date must be on or before the end date.');
            return;
        }

        window.location.assign(filterUrlFor({ dateFrom, dateTo }));
    };

    const clearDateFilter = () => {
        window.location.assign(filterUrlFor({ clearDates: true }));
    };

    dateApplyBtn?.addEventListener('click', applyDateFilter);
    dateClearBtn?.addEventListener('click', clearDateFilter);

    [dateFromInput, dateToInput].forEach((input) => {
        input?.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                applyDateFilter();
            }
        });
    });

    adminDashboard.addEventListener('click', (event) => {
        const sortBtn = event.target.closest('.i3-data-table__sort-btn');
        if (sortBtn) {
            const panel = sortBtn.closest('[data-admin-sort-panel]');
            const view = panel?.dataset.adminSortPanel;
            if (view && sortBtn.dataset.adminSort) {
                setSort(view, sortBtn.dataset.adminSort, sortBtn.dataset.sortType || 'text');
            }
            return;
        }

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
        const nextBilled = !billedBtn.classList.contains('is-checked');
        billedBtn.disabled = true;
        billedBtn.classList.toggle('is-checked', nextBilled);
        billedBtn.setAttribute('aria-pressed', nextBilled ? 'true' : 'false');

        setShiftBilled(shiftId, nextBilled).catch((error) => {
            billedBtn.classList.toggle('is-checked', !nextBilled);
            billedBtn.setAttribute('aria-pressed', nextBilled ? 'false' : 'true');
            alert(error.message || 'Could not update billed status. Please try again.');
        }).finally(() => {
            billedBtn.disabled = false;
        });
    });

    employeeList?.addEventListener('change', (event) => {
        const select = event.target.closest('.admin-product-select');
        if (!select || select.disabled) {
            return;
        }

        const netid = select.dataset.netid;
        if (!netid) {
            return;
        }

        const previous = select.dataset.previous || '';
        const next = select.value;
        select.disabled = true;

        setProduct(netid, next).then(() => {
            select.dataset.previous = next;
        }).catch((error) => {
            select.value = previous;
            alert(error.message || 'Could not update product. Please try again.');
        }).finally(() => {
            select.disabled = false;
        });
    });

    projectList?.addEventListener('click', (event) => {
        if (!event.target.closest('.admin-project-link')) {
            return;
        }

        sessionStorage.setItem('admin_return', '1');
    });

    tableToggle?.querySelectorAll('[data-admin-table]').forEach((btn) => {
        btn.addEventListener('click', () => setTableView(btn.dataset.adminTable));
    });

    searchInput?.addEventListener('input', filterRows);

    setTableView(tableView);
    ['project', 'employee', 'shift'].forEach((view) => sortRows(view));
})();
