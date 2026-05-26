@php
    $shiftModalMode = $shiftModalMode ?? (
        ($isAdminViewer ?? false) && (request()->query('view') === 'admin' || request()->routeIs('admin.projects.show'))
            ? 'admin'
            : 'user'
    );
    $shiftModalProjects = $shiftModalMode === 'admin'
        ? ($adminDashboard['projects'] ?? [])
        : $logShiftProjects->map(fn ($project) => ['id' => $project->id, 'name' => $project->name])->values()->all();
@endphp

<div class="shift-modal-backdrop d-none" id="shiftModalBackdrop" aria-hidden="true"></div>
<div class="shift-modal d-none" id="shiftModal" role="dialog" aria-modal="true" aria-labelledby="shiftModalTitle">
    <div class="shift-modal__dialog">
        <div class="shift-modal__header">
            <div class="shift-modal__number">
                <span id="shiftModalNumberPrefix">No.</span>
                <span class="shift-modal__number-value" id="shiftModalNumberValue">[{{ $nextShiftNumber }}]</span>
            </div>
            <h2 class="shift-modal__title" id="shiftModalTitle">New Shift</h2>
            <button type="button" class="shift-modal__close" id="shiftModalClose" aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <form id="shiftModalForm" action="{{ route('shifts.store') }}" method="POST" novalidate>
            @csrf
            <input type="hidden" name="netid" id="shiftModalNetid" value="{{ auth()->user()->netid }}">
            <input type="hidden" name="duration" id="shiftModalDuration" value="60">

            <div class="shift-modal__body">
                <div id="shiftModalErrors" class="alert alert-danger d-none py-2 small"></div>

                <div class="shift-modal__field d-none" id="shiftModalEmployeeField">
                    <span class="shift-modal__field-label">Employee:</span>
                    <div class="shift-modal__field-control fw-semibold" id="shiftModalEmployee"></div>
                </div>

                <div class="shift-modal__field">
                    <label class="shift-modal__field-label" for="shiftModalProject">Project:</label>
                    <div class="shift-modal__field-control">
                        <select name="proj_id" id="shiftModalProject" class="form-select shift-modal__select" required>
                            <option value="">Select a project</option>
                            @foreach($shiftModalProjects as $project)
                                <option value="{{ is_array($project) ? $project['id'] : $project->id }}">
                                    {{ is_array($project) ? $project['name'] : $project->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="shift-modal__field">
                    <label class="shift-modal__field-label" for="shiftModalDate">Date:</label>
                    <div class="shift-modal__field-control">
                        <input type="date" name="date" id="shiftModalDate" class="shift-modal__date"
                               value="{{ $defaultShiftDate }}" required>
                    </div>
                </div>

                <div class="shift-modal__duration">
                    <div class="shift-modal__duration-col">
                        <div class="shift-modal__duration-row">
                            <button type="button" class="shift-modal__dur-btn shift-modal__dur-btn--muted" data-adjust="-30">- 30 min</button>
                            <button type="button" class="shift-modal__dur-btn shift-modal__dur-btn--muted" data-adjust="-15">- 15 min</button>
                        </div>
                        <button type="button" class="shift-modal__dur-btn shift-modal__dur-btn--dark shift-modal__dur-btn--wide" data-adjust="-60">- 1 hour</button>
                    </div>
                    <div class="shift-modal__duration-center">
                        <div class="shift-modal__duration-value" id="shiftModalHoursDisplay">1.00</div>
                        <div class="shift-modal__duration-label">Duration (hours)</div>
                    </div>
                    <div class="shift-modal__duration-col shift-modal__duration-col--right">
                        <div class="shift-modal__duration-row">
                            <button type="button" class="shift-modal__dur-btn shift-modal__dur-btn--muted" data-adjust="15">+ 15 min</button>
                            <button type="button" class="shift-modal__dur-btn shift-modal__dur-btn--muted" data-adjust="30">+ 30 min</button>
                        </div>
                        <button type="button" class="shift-modal__dur-btn shift-modal__dur-btn--primary shift-modal__dur-btn--wide" data-adjust="60">+ 1 hour</button>
                    </div>
                </div>
            </div>

            <div class="shift-modal__footer">
                <div class="shift-modal__footer-checks">
                    <label class="i3-check">
                        <input type="hidden" name="entered" value="0">
                        <input type="checkbox" name="entered" id="shiftModalEntered" value="1" class="i3-check__input">
                        <span class="i3-check__box"><i class="bi bi-check-lg"></i></span>
                        <span>Entered in Timecard</span>
                    </label>

                    <label class="i3-check {{ $shiftModalMode === 'admin' || auth()->user()->isAdmin() ? '' : 'd-none' }}" id="shiftModalBilledWrap">
                        <input type="hidden" name="billed" value="0">
                        <input type="checkbox" name="billed" id="shiftModalBilled" value="1" class="i3-check__input">
                        <span class="i3-check__box"><i class="bi bi-check-lg"></i></span>
                        <span id="shiftModalBilledLabel">{{ $shiftModalMode === 'admin' ? 'Billed (Honeycrisp)' : 'Billed (Admin)' }}</span>
                    </label>
                </div>

                <div class="d-flex flex-wrap gap-2 justify-content-end w-100">
                    <button type="button" class="btn btn-outline-danger btn-sm d-none" id="shiftModalDelete">Delete</button>
                    <button type="submit" class="shift-modal__submit" id="shiftModalSubmit">Log Shift</button>
                </div>
            </div>
        </form>
    </div>
</div>
