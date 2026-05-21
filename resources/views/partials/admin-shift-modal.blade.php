<div class="shift-modal-backdrop d-none" id="adminShiftModalBackdrop" aria-hidden="true"></div>
<div class="shift-modal d-none" id="adminShiftModal" role="dialog" aria-modal="true" aria-labelledby="adminShiftModalTitle">
    <div class="shift-modal__dialog">
        <div class="shift-modal__header">
            <div class="shift-modal__number">Shift <span class="shift-modal__number-value" id="adminShiftModalId">—</span></div>
            <h2 class="shift-modal__title" id="adminShiftModalTitle">Edit Shift</h2>
            <button type="button" class="shift-modal__close" id="adminShiftModalClose" aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <form id="adminShiftModalForm" novalidate>
            <input type="hidden" name="duration" id="adminShiftModalDuration" value="60">

            <div class="shift-modal__body">
                <div id="adminShiftModalErrors" class="alert alert-danger d-none py-2 small"></div>

                <div class="shift-modal__field">
                    <span class="shift-modal__field-label">Employee:</span>
                    <div class="shift-modal__field-control fw-semibold" id="adminShiftModalEmployee"></div>
                </div>

                <div class="shift-modal__field">
                    <label class="shift-modal__field-label" for="adminShiftModalProject">Project:</label>
                    <div class="shift-modal__field-control">
                        <select name="proj_id" id="adminShiftModalProject" class="form-select shift-modal__select" required>
                            <option value="">Select a project</option>
                            @foreach(($adminDashboard['projects'] ?? []) as $project)
                                <option value="{{ $project['id'] }}">{{ $project['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="shift-modal__field">
                    <label class="shift-modal__field-label" for="adminShiftModalDate">Date:</label>
                    <div class="shift-modal__field-control">
                        <input type="date" name="date" id="adminShiftModalDate" class="shift-modal__date" required>
                    </div>
                </div>

                <div class="shift-modal__duration">
                    <div class="shift-modal__duration-col">
                        <div class="shift-modal__duration-row">
                            <button type="button" class="shift-modal__dur-btn shift-modal__dur-btn--muted" data-admin-adjust="-30">- 30 min</button>
                            <button type="button" class="shift-modal__dur-btn shift-modal__dur-btn--muted" data-admin-adjust="-15">- 15 min</button>
                        </div>
                        <button type="button" class="shift-modal__dur-btn shift-modal__dur-btn--dark shift-modal__dur-btn--wide" data-admin-adjust="-60">- 1 hour</button>
                    </div>
                    <div class="shift-modal__duration-center">
                        <div class="shift-modal__duration-value" id="adminShiftModalHoursDisplay">1.00</div>
                        <div class="shift-modal__duration-label">Duration (hours)</div>
                    </div>
                    <div class="shift-modal__duration-col shift-modal__duration-col--right">
                        <div class="shift-modal__duration-row">
                            <button type="button" class="shift-modal__dur-btn shift-modal__dur-btn--muted" data-admin-adjust="15">+ 15 min</button>
                            <button type="button" class="shift-modal__dur-btn shift-modal__dur-btn--muted" data-admin-adjust="30">+ 30 min</button>
                        </div>
                        <button type="button" class="shift-modal__dur-btn shift-modal__dur-btn--primary shift-modal__dur-btn--wide" data-admin-adjust="60">+ 1 hour</button>
                    </div>
                </div>
            </div>

            <div class="shift-modal__footer">
                <div class="shift-modal__footer-checks">
                    <label class="i3-check">
                        <input type="hidden" name="entered" value="0">
                        <input type="checkbox" name="entered" id="adminShiftModalEntered" value="1" class="i3-check__input">
                        <span class="i3-check__box"><i class="bi bi-check-lg"></i></span>
                        <span>Entered in Timecard</span>
                    </label>

                    <label class="i3-check">
                        <input type="hidden" name="billed" value="0">
                        <input type="checkbox" name="billed" id="adminShiftModalBilled" value="1" class="i3-check__input">
                        <span class="i3-check__box"><i class="bi bi-check-lg"></i></span>
                        <span>Billed (Honeycrisp)</span>
                    </label>
                </div>

                <div class="d-flex flex-wrap gap-2 justify-content-end w-100">
                    <button type="button" class="btn btn-outline-danger btn-sm" id="adminShiftModalDelete">Delete</button>
                    <button type="submit" class="shift-modal__submit">Save Shift</button>
                </div>
            </div>
        </form>
    </div>
</div>
