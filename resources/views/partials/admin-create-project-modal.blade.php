<div class="shift-modal-backdrop d-none" id="createProjectModalBackdrop" aria-hidden="true"></div>
<div class="shift-modal d-none" id="createProjectModal" role="dialog" aria-modal="true" aria-labelledby="createProjectModalTitle">
    <div class="shift-modal__dialog">
        <div class="shift-modal__header">
            <div class="shift-modal__number" aria-hidden="true"></div>
            <h2 class="shift-modal__title" id="createProjectModalTitle">New Project</h2>
            <button type="button" class="shift-modal__close" id="createProjectModalClose" aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <form id="createProjectForm" novalidate>
            <div class="shift-modal__body">
                <div id="createProjectModalErrors" class="alert alert-danger d-none py-2 small"></div>

                <div class="shift-modal__field">
                    <label class="shift-modal__field-label" for="createProjectName">Name:</label>
                    <div class="shift-modal__field-control">
                        <input type="text"
                               class="form-control"
                               id="createProjectName"
                               name="name"
                               maxlength="255"
                               placeholder="Project name"
                               required>
                    </div>
                </div>

                <div class="shift-modal__field align-items-start">
                    <label class="shift-modal__field-label pt-1" for="createProjectDescription">About:</label>
                    <div class="shift-modal__field-control">
                        <textarea class="form-control"
                                  id="createProjectDescription"
                                  name="description"
                                  rows="3"
                                  maxlength="1000"
                                  placeholder="Optional description"></textarea>
                    </div>
                </div>

                <div class="shift-modal__field">
                    <label class="shift-modal__field-label" for="createProjectHoneycrisp">Honeycrisp:</label>
                    <div class="shift-modal__field-control">
                        <select class="form-select shift-modal__select"
                                id="createProjectHoneycrisp"
                                name="honeycrisp_project_id">
                            <option value="">—</option>
                            @foreach($honeycrispProjects as $honeycrispProject)
                                <option value="{{ $honeycrispProject['id'] }}">{{ $honeycrispProject['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="shift-modal__footer-checks flex-column align-items-start gap-2 mt-2">
                    <label class="i3-check">
                        <input type="hidden" name="active" value="0">
                        <input type="checkbox" name="active" id="createProjectActive" value="1" class="i3-check__input" checked>
                        <span class="i3-check__box"><i class="bi bi-check-lg"></i></span>
                        <span>Active project</span>
                    </label>

                    <label class="i3-check">
                        <input type="hidden" name="assign_all_users" value="0">
                        <input type="checkbox" name="assign_all_users" id="createProjectAssignAll" value="1" class="i3-check__input">
                        <span class="i3-check__box"><i class="bi bi-check-lg"></i></span>
                        <span>Assign all users</span>
                    </label>
                </div>
            </div>

            <div class="shift-modal__footer">
                <button type="submit" class="shift-modal__submit w-100">Create Project</button>
            </div>
        </form>
    </div>
</div>
