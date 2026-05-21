(function () {
    const modal = document.getElementById('createProjectModal');
    const backdrop = document.getElementById('createProjectModalBackdrop');
    const form = document.getElementById('createProjectForm');
    const openBtn = document.getElementById('openCreateProjectModalBtn');
    const closeBtn = document.getElementById('createProjectModalClose');
    const errorsEl = document.getElementById('createProjectModalErrors');
    const storeUrl = window.adminDashboardConfig?.projectsStoreUrl;

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
        const activeCheckbox = document.getElementById('createProjectActive');
        if (activeCheckbox) {
            activeCheckbox.checked = true;
        }
        showErrors([]);
    };

    const openModal = () => {
        resetForm();
        backdrop?.classList.remove('d-none');
        modal.classList.remove('d-none');
        document.body.classList.add('shift-modal-open');
        document.getElementById('createProjectName')?.focus();
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
        return [data.message || 'Could not create project.'];
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
            showErrors(['Could not create project. Please try again.']);
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
