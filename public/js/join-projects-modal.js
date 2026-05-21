(function () {
    const backdrop = document.getElementById('joinProjectsModalBackdrop');
    const modal = document.getElementById('joinProjectsModal');
    const openBtn = document.getElementById('openJoinProjectsModalBtn');
    const closeBtn = document.getElementById('joinProjectsModalClose');
    const searchInput = document.getElementById('joinProjectsSearch');
    const listEl = document.getElementById('joinProjectsList');
    const submitBtn = document.getElementById('joinProjectsSubmit');
    const joinCountEl = document.getElementById('joinProjectsJoinCount');
    const leaveCountEl = document.getElementById('joinProjectsLeaveCount');
    const leavingEl = document.getElementById('joinProjectsLeaving');
    const errorsEl = document.getElementById('joinProjectsModalErrors');

    if (!modal || !listEl || !window.joinableProjects) {
        return;
    }

    const syncUrl = window.joinProjectsSyncUrl;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    let initialJoined = new Set();
    let selected = new Set();
    let searchTerm = '';

    const escapeHtml = (value) => String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');

    const getFilteredProjects = () => window.joinableProjects.filter((project) => {
        if (!searchTerm) {
            return true;
        }

        return project.name.toLowerCase().includes(searchTerm);
    });

    const getChangeCounts = () => {
        let joinCount = 0;
        let leaveCount = 0;

        selected.forEach((id) => {
            if (!initialJoined.has(id)) {
                joinCount += 1;
            }
        });

        initialJoined.forEach((id) => {
            if (!selected.has(id)) {
                leaveCount += 1;
            }
        });

        return { joinCount, leaveCount };
    };

    const updateFooter = () => {
        const { joinCount, leaveCount } = getChangeCounts();
        const hasChanges = joinCount > 0 || leaveCount > 0;

        joinCountEl.textContent = `[${joinCount}]`;
        leaveCountEl.textContent = `[${leaveCount}]`;
        submitBtn.disabled = !hasChanges;

        if (leaveCount > 0) {
            leavingEl.classList.remove('d-none');
        } else {
            leavingEl.classList.add('d-none');
        }
    };

    const renderList = () => {
        const projects = getFilteredProjects();

        if (projects.length === 0) {
            listEl.innerHTML = '<p class="join-modal__empty">No projects match your search.</p>';
            return;
        }

        listEl.innerHTML = projects.map((project) => {
            const id = String(project.id);
            const checked = selected.has(id) ? 'checked' : '';

            return `
                <label class="join-modal__item">
                    <input type="checkbox"
                           class="i3-check__input"
                           value="${id}"
                           ${checked}>
                    <span class="i3-check__box"><i class="bi bi-check-lg"></i></span>
                    <span class="join-modal__project-name"><span class="join-modal__hash">#</span> ${escapeHtml(project.name)}</span>
                </label>
            `;
        }).join('');

        listEl.querySelectorAll('.i3-check__input').forEach((checkbox) => {
            checkbox.addEventListener('change', () => {
                const id = String(checkbox.value);

                if (checkbox.checked) {
                    selected.add(id);
                } else {
                    selected.delete(id);
                }

                updateFooter();
            });
        });
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
        errorsEl.innerHTML = '<ul class="mb-0 ps-3">' + messages.map((m) => `<li>${escapeHtml(m)}</li>`).join('') + '</ul>';
    };

    const openModal = () => {
        initialJoined = new Set(
            window.joinableProjects
                .filter((project) => project.joined)
                .map((project) => String(project.id))
        );
        selected = new Set(initialJoined);
        searchTerm = '';

        if (searchInput) {
            searchInput.value = '';
        }

        showErrors([]);
        renderList();
        updateFooter();

        backdrop?.classList.remove('d-none');
        modal.classList.remove('d-none');
        document.body.classList.add('join-modal-open');
        searchInput?.focus();
    };

    const closeModal = () => {
        backdrop?.classList.add('d-none');
        modal.classList.add('d-none');
        document.body.classList.remove('join-modal-open');
    };

    const saveMemberships = async () => {
        const formData = new FormData();
        const token = document.querySelector('meta[name="csrf-token"]')?.content || csrfToken;
        formData.append('_token', token);
        selected.forEach((id) => formData.append('project_ids[]', id));

        const response = await fetch(syncUrl, {
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
            throw new Error(data.message || 'Could not update projects.');
        }

        if (data.csrf_token) {
            document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', data.csrf_token);
        }

        return data;
    };

    openBtn?.addEventListener('click', openModal);
    closeBtn?.addEventListener('click', closeModal);
    backdrop?.addEventListener('click', closeModal);

    searchInput?.addEventListener('input', () => {
        searchTerm = searchInput.value.trim().toLowerCase();
        renderList();
    });

    submitBtn?.addEventListener('click', async () => {
        submitBtn.disabled = true;

        try {
            await saveMemberships();
            closeModal();
            window.location.reload();
        } catch (error) {
            showErrors([error.message || 'Could not update projects. Please try again.']);
            updateFooter();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.classList.contains('d-none')) {
            closeModal();
        }
    });
})();
