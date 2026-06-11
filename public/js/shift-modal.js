(function () {
    const config = window.shiftModalConfig || { mode: 'user' };
    const backdrop = document.getElementById('shiftModalBackdrop');
    const modal = document.getElementById('shiftModal');
    const form = document.getElementById('shiftModalForm');
    const openBtn = document.getElementById('openShiftModalBtn');
    const closeBtn = document.getElementById('shiftModalClose');
    const deleteBtn = document.getElementById('shiftModalDelete');
    const durationInput = document.getElementById('shiftModalDuration');
    const hoursDisplay = document.getElementById('shiftModalHoursDisplay');
    const dateInput = document.getElementById('shiftModalDate');
    const projectSelect = document.getElementById('shiftModalProject');
    const employeeField = document.getElementById('shiftModalEmployeeField');
    const employeeEl = document.getElementById('shiftModalEmployee');
    const numberPrefixEl = document.getElementById('shiftModalNumberPrefix');
    const numberValueEl = document.getElementById('shiftModalNumberValue');
    const titleEl = document.getElementById('shiftModalTitle');
    const submitBtn = document.getElementById('shiftModalSubmit');
    const enteredCheckbox = document.getElementById('shiftModalEntered');
    const billedCheckbox = document.getElementById('shiftModalBilled');
    const errorsEl = document.getElementById('shiftModalErrors');
    const adjustButtons = document.querySelectorAll('[data-adjust]');

    if (!modal || !form) {
        return;
    }

    let activeShift = null;
    let csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const isEditMode = () => Boolean(activeShift?.id);

    const setDurationMinutes = (minutes) => {
        const safeMinutes = I3.snapMinutesToQuarter(minutes);
        durationInput.value = String(safeMinutes);
        hoursDisplay.textContent = I3.formatHours(safeMinutes / 60);
    };

    const getDurationMinutes = () => {
        const raw = String(durationInput.value ?? '').trim();
        if (!raw) {
            return 0;
        }

        const numeric = Number(raw);
        if (Number.isNaN(numeric)) {
            return 0;
        }

        // Hidden field must store whole minutes; decimal values are hours (e.g. "1.00").
        if (raw.includes('.')) {
            return Math.round(numeric * 60);
        }

        return Math.round(numeric);
    };

    const showErrors = (messages) => {
        if (!errorsEl) {
            return;
        }
        if (!messages.length) {
            errorsEl.classList.add('d-none');
            errorsEl.innerHTML = '';
            return;
        }
        errorsEl.classList.remove('d-none');
        errorsEl.innerHTML = '<ul class="mb-0 ps-3">' + messages.map((m) => `<li>${m}</li>`).join('') + '</ul>';
    };

    const setCreateMode = (date) => {
        activeShift = null;
        showErrors([]);

        if (numberPrefixEl) {
            numberPrefixEl.textContent = 'No.';
        }
        if (titleEl) {
            titleEl.textContent = 'New Shift';
        }
        if (submitBtn) {
            submitBtn.textContent = 'Log Shift';
        }
        employeeField?.classList.add('d-none');
        deleteBtn?.classList.add('d-none');
        form.action = config.storeUrl || form.action;

        if (date && dateInput) {
            dateInput.value = date;
        } else if (dateInput && config.defaultShiftDate) {
            dateInput.value = config.defaultShiftDate;
        }

        if (projectSelect) {
            projectSelect.value = '';
        }

        setDurationMinutes(60);

        if (enteredCheckbox) {
            enteredCheckbox.checked = false;
        }
        if (billedCheckbox) {
            billedCheckbox.checked = false;
        }
    };

    const setEditMode = (shift) => {
        activeShift = shift;
        showErrors([]);

        if (numberPrefixEl) {
            numberPrefixEl.textContent = 'Shift';
        }
        if (numberValueEl) {
            numberValueEl.textContent = `#${shift.id}`;
        }
        if (titleEl) {
            titleEl.textContent = 'Edit Shift';
        }
        if (submitBtn) {
            submitBtn.textContent = 'Save Shift';
        }
        employeeField?.classList.remove('d-none');
        deleteBtn?.classList.toggle('d-none', !config.canDelete);

        if (employeeEl) {
            employeeEl.textContent = shift.employee_name || shift.netid || '—';
        }
        if (projectSelect) {
            projectSelect.value = String(shift.proj_id || '');
        }
        if (dateInput) {
            dateInput.value = shift.date || '';
        }
        setDurationMinutes(shift.duration_minutes || 60);
        if (enteredCheckbox) {
            enteredCheckbox.checked = Boolean(shift.entered);
        }
        if (billedCheckbox) {
            billedCheckbox.checked = Boolean(shift.billed);
        }
    };

    const openModal = (shiftOrDate) => {
        if (shiftOrDate && typeof shiftOrDate === 'object' && shiftOrDate.id) {
            setEditMode(shiftOrDate);
        } else {
            setCreateMode(typeof shiftOrDate === 'string' ? shiftOrDate : null);
        }

        backdrop?.classList.remove('d-none');
        modal.classList.remove('d-none');
        document.body.classList.add('shift-modal-open');
    };

    const closeModal = () => {
        backdrop?.classList.add('d-none');
        modal.classList.add('d-none');
        document.body.classList.remove('shift-modal-open');
        activeShift = null;
    };

    window.openShiftModal = openModal;

    openBtn?.addEventListener('click', () => openModal());
    closeBtn?.addEventListener('click', closeModal);
    backdrop?.addEventListener('click', closeModal);

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.classList.contains('d-none')) {
            closeModal();
        }
    });

    adjustButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const adjust = Number.parseInt(button.dataset.adjust, 10);
            if (Number.isNaN(adjust)) {
                return;
            }

            setDurationMinutes(getDurationMinutes() + adjust);
        });
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        showErrors([]);

        const durationMinutes = getDurationMinutes();
        if (!I3.isQuarterHourMinutes(durationMinutes)) {
            showErrors(['Duration must be in 15-minute increments.']);
            return;
        }

        const token = document.querySelector('meta[name="csrf-token"]')?.content || csrfToken;
        const formData = new FormData(form);
        formData.set('_token', token);
        let url = form.action;
        let method = 'POST';

        if (isEditMode()) {
            url = `${config.shiftBaseUrl || '/shifts'}/${activeShift.id}`;
            formData.append('_method', 'PUT');
        }

        try {
            const response = await fetch(url, {
                method,
                body: formData,
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': token,
                },
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                const messages = data.errors
                    ? Object.values(data.errors).flat()
                    : [data.message || 'Could not save shift.'];
                showErrors(messages);
                return;
            }

            if (data.csrf_token) {
                csrfToken = data.csrf_token;
                document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', data.csrf_token);
                form.querySelector('input[name="_token"]')?.setAttribute('value', data.csrf_token);
            }

            closeModal();

            if (config.mode !== 'admin' && typeof window.applyUserDashboardPayload === 'function') {
                window.applyUserDashboardPayload(data);
                window.refreshUserDashboard?.({
                    dayDate: data.day?.date,
                });
                return;
            }

            window.location.reload();
        } catch {
            showErrors(['Could not save shift. Please try again.']);
        }
    });

    deleteBtn?.addEventListener('click', async () => {
        if (!activeShift?.id || !config.canDelete) {
            return;
        }

        if (!window.confirm('Delete this shift?')) {
            return;
        }

        try {
            const response = await fetch(`${config.shiftBaseUrl || '/shifts'}/${activeShift.id}`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    _method: 'DELETE',
                    _token: csrfToken,
                }),
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                showErrors([data.message || 'Could not delete shift.']);
                return;
            }

            closeModal();
            window.location.reload();
        } catch {
            showErrors(['Could not delete shift. Please try again.']);
        }
    });
})();
