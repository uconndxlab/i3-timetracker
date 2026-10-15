(function () {
    const modal = document.getElementById('editProjectModal');
    const backdrop = document.getElementById('editProjectModalBackdrop');
    const form = document.getElementById('editProjectForm');
    const closeBtn = document.getElementById('editProjectModalClose');
    const errorsEl = document.getElementById('editProjectModalErrors');
    const projectList = document.getElementById('adminProjectList');
    const updateBaseUrl = window.adminDashboardConfig?.projectsUpdateBaseUrl;
    const deleteBaseUrl = window.adminDashboardConfig?.projectsDeleteBaseUrl;

    if (!modal || !form || !projectList || !updateBaseUrl) {
        return;
    }

    const nameInput = document.getElementById('editProjectName');
    const descriptionInput = document.getElementById('editProjectDescription');
    const activeCheckbox = document.getElementById('editProjectActive');
    const deleteBtn = document.getElementById('editProjectDeleteBtn');
    let projectId = null;

    const showErrors = (messages) => {
        if (!errorsEl) {
            return;
        }
        if (!messages.length) {
            errorsEl.classList.add('d-none');
            errorsEl.replaceChildren();
            return;
        }

        const list = document.createElement('ul');
        list.className = 'mb-0 ps-3';
        messages.forEach((message) => {
            const item = document.createElement('li');
            item.textContent = message;
            list.appendChild(item);
        });
        errorsEl.replaceChildren(list);
        errorsEl.classList.remove('d-none');
    };

    const openModal = (button) => {
        projectId = button.dataset.projectId;
        nameInput.value = button.dataset.projectName || '';
        descriptionInput.value = button.dataset.projectDescription || '';
        activeCheckbox.checked = button.dataset.projectActive === '1';
        deleteBtn?.classList.toggle('d-none', button.dataset.projectHasShifts !== '0');
        showErrors([]);
        backdrop?.classList.remove('d-none');
        modal.classList.remove('d-none');
        document.body.classList.add('shift-modal-open');
        nameInput.focus();
    };

    const closeModal = () => {
        backdrop?.classList.add('d-none');
        modal.classList.add('d-none');
        document.body.classList.remove('shift-modal-open');
        projectId = null;
    };

    const collectValidationErrors = (data) => {
        if (data.errors && typeof data.errors === 'object') {
            return Object.values(data.errors).flat();
        }
        return [data.message || 'Could not update project.'];
    };

    const deleteProject = async () => {
        if (!projectId || !deleteBaseUrl || !window.confirm('Delete this project? This cannot be undone.')) {
            return;
        }

        deleteBtn.disabled = true;
        showErrors([]);

        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.content;
            const response = await fetch(`${deleteBaseUrl}/${encodeURIComponent(projectId)}`, {
                method: 'DELETE',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(token ? { 'X-CSRF-TOKEN': token } : {}),
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

            window.location.reload();
        } catch {
            showErrors(['Could not delete project. Please try again.']);
        } finally {
            deleteBtn.disabled = false;
        }
    };

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        showErrors([]);

        if (!projectId) {
            showErrors(['Could not update project. Please close this dialog and try again.']);
            return;
        }

        const submitBtn = form.querySelector('[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
        }

        const formData = new FormData(form);
        formData.set('_method', 'PUT');
        const token = document.querySelector('meta[name="csrf-token"]')?.content;
        if (token) {
            formData.set('_token', token);
        }

        try {
            const response = await fetch(`${updateBaseUrl}/${encodeURIComponent(projectId)}`, {
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

            window.location.reload();
        } catch {
            showErrors(['Could not update project. Please try again.']);
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
            }
        }
    });

    projectList.addEventListener('click', (event) => {
        const button = event.target.closest('.admin-project-edit-btn');
        if (button) {
            openModal(button);
        }
    });

    closeBtn?.addEventListener('click', closeModal);
    backdrop?.addEventListener('click', closeModal);
    deleteBtn?.addEventListener('click', deleteProject);

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.classList.contains('d-none')) {
            closeModal();
        }
    });
})();
