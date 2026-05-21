(function () {
    const backdrop = document.getElementById('shiftModalBackdrop');
    const modal = document.getElementById('shiftModal');
    const form = document.getElementById('shiftModalForm');
    const openBtn = document.getElementById('openShiftModalBtn');
    const closeBtn = document.getElementById('shiftModalClose');
    const durationInput = document.getElementById('shiftModalDuration');
    const hoursDisplay = document.getElementById('shiftModalHoursDisplay');
    const dateInput = document.getElementById('shiftModalDate');
    const errorsEl = document.getElementById('shiftModalErrors');
    const adjustButtons = document.querySelectorAll('[data-adjust]');

    if (!modal || !form) {
        return;
    }

    const setDurationMinutes = (minutes) => {
        const safeMinutes = Math.max(0, minutes);
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

    const openModal = (date) => {
        if (date && dateInput) {
            dateInput.value = date;
        }
        setDurationMinutes(60);
        showErrors([]);
        const enteredCheckbox = document.getElementById('shiftModalEntered');
        if (enteredCheckbox) {
            enteredCheckbox.checked = false;
        }
        backdrop?.classList.remove('d-none');
        modal.classList.remove('d-none');
        document.body.classList.add('shift-modal-open');
    };

    const closeModal = () => {
        backdrop?.classList.add('d-none');
        modal.classList.add('d-none');
        document.body.classList.remove('shift-modal-open');
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
            const adjust = parseInt(button.dataset.adjust, 10);
            setDurationMinutes(getDurationMinutes() + adjust);
        });
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        showErrors([]);

        if (getDurationMinutes() < 1) {
            showErrors(['Duration must be at least 1 minute.']);
            return;
        }

        const formData = new FormData(form);

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                const messages = data.errors
                    ? Object.values(data.errors).flat()
                    : [data.message || 'Could not log shift.'];
                showErrors(messages);
                return;
            }

            closeModal();
            window.location.reload();
        } catch {
            showErrors(['Could not log shift. Please try again.']);
        }
    });
})();
