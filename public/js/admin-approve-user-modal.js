(function () {
    const modal = document.getElementById('approveUserModal');
    const backdrop = document.getElementById('approveUserModalBackdrop');
    const form = document.getElementById('approveUserForm');
    const openBtn = document.getElementById('openApproveUserModalBtn');
    const closeBtn = document.getElementById('approveUserModalClose');
    const errorsEl = document.getElementById('approveUserModalErrors');
    const storeUrl = window.adminDashboardConfig?.usersApproveUrl;

    if (!modal || !form || !storeUrl) {
        return;
    }

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

    const resetForm = () => {
        form.reset();
        showErrors([]);
    };

    const openModal = () => {
        resetForm();
        backdrop?.classList.remove('d-none');
        modal.classList.remove('d-none');
        document.body.classList.add('shift-modal-open');
        document.getElementById('approveUserNetid')?.focus();
    };

    const closeModal = () => {
        backdrop?.classList.add('d-none');
        modal.classList.add('d-none');
        document.body.classList.remove('shift-modal-open');
    };

    const collectValidationErrors = (data) => {
        if (data.errors && typeof data.errors === 'object') {
            return Object.values(data.errors).flat();
        }
        return [data.message || 'Could not approve user.'];
    };

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        showErrors([]);

        const submitBtn = form.querySelector('[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
        }

        const formData = new FormData(form);
        const token = document.querySelector('meta[name="csrf-token"]')?.content;
        if (token) {
            formData.set('_token', token);
        }

        try {
            const response = await fetch(storeUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                showErrors(collectValidationErrors(data));
                return;
            }

            if (data.csrf_token) {
                document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', data.csrf_token);
            }

            closeModal();
            window.location.href = data.redirect_url || window.location.pathname + '?view=admin';
        } catch {
            showErrors(['Could not approve user. Please try again.']);
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
            }
        }
    });

    openBtn?.addEventListener('click', openModal);
    closeBtn?.addEventListener('click', closeModal);
    backdrop?.addEventListener('click', closeModal);

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.classList.contains('d-none')) {
            closeModal();
        }
    });
})();
