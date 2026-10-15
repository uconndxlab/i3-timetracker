<div class="shift-modal-backdrop d-none" id="editProjectModalBackdrop" aria-hidden="true"></div>
<div class="shift-modal d-none" id="editProjectModal" role="dialog" aria-modal="true" aria-labelledby="editProjectModalTitle">
    <div class="shift-modal__dialog">
        <div class="shift-modal__header">
            <div class="shift-modal__number" aria-hidden="true"></div>
            <h2 class="shift-modal__title" id="editProjectModalTitle">Edit Project</h2>
            <button type="button" class="shift-modal__close" id="editProjectModalClose" aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <form id="editProjectForm" novalidate>
            <div class="shift-modal__body">
                <div id="editProjectModalErrors" class="alert alert-danger d-none py-2 small"></div>

                <div class="shift-modal__field">
                    <label class="shift-modal__field-label" for="editProjectName">Name:</label>
                    <div class="shift-modal__field-control">
                        <input type="text"
                               class="form-control"
                               id="editProjectName"
                               name="name"
                               maxlength="255"
                               placeholder="Project name"
                               required>
                    </div>
                </div>

                <div class="shift-modal__field align-items-start">
                    <label class="shift-modal__field-label pt-1" for="editProjectDescription">About:</label>
                    <div class="shift-modal__field-control">
                        <textarea class="form-control"
                                  id="editProjectDescription"
                                  name="description"
                                  rows="3"
                                  maxlength="1000"
                                  placeholder="Optional description"></textarea>
                    </div>
                </div>

                <div class="shift-modal__footer-checks flex-column align-items-start gap-2 mt-2">
                    <label class="i3-check">
                        <input type="hidden" name="active" value="0">
                        <input type="checkbox" name="active" id="editProjectActive" value="1" class="i3-check__input">
                        <span class="i3-check__box"><i class="bi bi-check-lg"></i></span>
                        <span>Active project</span>
                    </label>
                </div>
            </div>

            <div class="shift-modal__footer">
                <button type="submit" class="shift-modal__submit w-100" id="editProjectSaveBtn">Save Changes</button>
                <button type="button"
                        class="btn btn-outline-danger w-100 mt-2 d-none"
                        id="editProjectDeleteBtn">
                    <i class="bi bi-trash me-2" aria-hidden="true"></i>Delete Project
                </button>
            </div>
        </form>
    </div>
</div>
