(function () {
    const backdrop = document.getElementById('adminShiftModalBackdrop');
    const modal = document.getElementById('adminShiftModal');
    const form = document.getElementById('adminShiftModalForm');
    const closeBtn = document.getElementById('adminShiftModalClose');
    const deleteBtn = document.getElementById('adminShiftModalDelete');
    const durationInput = document.getElementById('adminShiftModalDuration');
    const hoursDisplay = document.getElementById('adminShiftModalHoursDisplay');
    const dateInput = document.getElementById('adminShiftModalDate');
    const projectSelect = document.getElementById('adminShiftModalProject');
    const employeeEl = document.getElementById('adminShiftModalEmployee');
    const shiftIdEl = document.getElementById('adminShiftModalId');
    const enteredCheckbox = document.getElementById('adminShiftModalEntered');
    const billedCheckbox = document.getElementById('adminShiftModalBilled');
    const errorsEl = document.getElementById('adminShiftModalErrors');
    const adjustButtons = document.querySelectorAll('[data-admin-adjust]');

    if (!modal || !form) {
        return;
    }

    let activeShift = null;
    let csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const setDurationMinutes = (minutes) => {
        const safeMinutes = Math.max(1, minutes);
        durationInput.value = safeMinutes;
        hoursDisplay.textContent = (safeMinutes / 60).toFixed(2);
    };

    const getDurationMinutes = () => parseInt(durationInput.value, 10) || 0;

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

    const closeModal = () => {
        backdrop?.classList.add('d-none');
        modal.classList.add('d-none');
        document.body.classList.remove('shift-modal-open');
        activeShift = null;
    };

    const openModal = (shift) => {
        activeShift = shift;
        showErrors([]);

        if (shiftIdEl) {
            shiftIdEl.textContent = `#${shift.id}`;
        }
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

        backdrop?.classList.remove('d-none');
        modal.classList.remove('d-none');
        document.body.classList.add('shift-modal-open');
    };

    window.openAdminShiftModal = openModal;

    closeBtn?.addEventListener('click', closeModal);
    backdrop?.addEventListener('click', closeModal);

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.classList.contains('d-none')) {
            closeModal();
        }
    });

    adjustButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const adjust = parseInt(button.dataset.adminAdjust, 10);
            setDurationMinutes(getDurationMinutes() + adjust);
        });
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        showErrors([]);

        if (!activeShift?.id) {
            return;
        }

        if (getDurationMinutes() < 1) {
            showErrors(['Duration must be at least 1 minute.']);
            return;
        }

        const formData = new FormData(form);
        formData.append('_method', 'PUT');

        try {
            const response = await fetch(`/shifts/${activeShift.id}`, {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
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
            }

            if (typeof window.onAdminShiftSaved === 'function' && data.shift) {
                window.onAdminShiftSaved(data.shift);
            }

            closeModal();
        } catch {
            showErrors(['Could not save shift. Please try again.']);
        }
    });

    deleteBtn?.addEventListener('click', async () => {
        if (!activeShift?.id) {
            return;
        }

        if (!window.confirm('Delete this shift?')) {
            return;
        }

        try {
            const response = await fetch(`/shifts/${activeShift.id}`, {
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

            if (typeof window.onAdminShiftDeleted === 'function') {
                window.onAdminShiftDeleted(activeShift.id);
            }

            closeModal();
        } catch {
            showErrors(['Could not delete shift. Please try again.']);
        }
    });
})();
