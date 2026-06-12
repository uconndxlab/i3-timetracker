@extends('layouts.app')

@php
    $project = $overview['project'];
    $allTime = $overview['all_time'];
    $employees = $overview['employees'] ?? [];
    $shifts = $overview['shifts'] ?? [];
    $backUrl = route('landing', ['view' => 'admin', 'admin_table' => 'project']);
@endphp

@section('content')
<div class="dashboard mt-3" id="projectOverview">
    <div class="dashboard-viewing-user mb-4">
        <span class="dashboard-viewing-user__name">
            <span class="i3-dot" aria-hidden="true"></span>
            {{ $project['name'] }}
            @if(! $project['active'])
                <span class="project-overview__status">Inactive</span>
            @endif
            <span class="dashboard-viewing-user__sep" aria-hidden="true">/</span>
            <a href="{{ $backUrl }}" class="i3-link" data-admin-back>Back To Admin</a>
        </span>
    </div>

    @if(session('success'))
        <p class="project-overview__flash project-overview__flash--success">{{ session('success') }}</p>
    @endif
    @if(session('info'))
        <p class="project-overview__flash">{{ session('info') }}</p>
    @endif

    <section class="project-overview-billing" aria-label="All-time billing summary">
        <div class="dashboard-stats__all-time-pill">All Time</div>
        <dl class="dashboard-stats__metrics project-overview-billing__metrics">
            <div class="dashboard-stats__metric">
                <dt>Billed Hrs</dt>
                <dd class="wavy-underline">{{ number_format($allTime['billed_hours'], 2) }}</dd>
            </div>
            <div class="dashboard-stats__metric">
                <dt>Unbilled Hrs</dt>
                <dd class="wavy-underline">{{ number_format($allTime['unbilled_hours'], 2) }}</dd>
            </div>
            <div class="dashboard-stats__metric">
                <dt>Total Hrs</dt>
                <dd class="wavy-underline">{{ number_format($allTime['total_hours'], 2) }}</dd>
            </div>
            <div class="dashboard-stats__metric">
                <dt>Shifts</dt>
                <dd>{{ number_format($allTime['shift_count']) }}</dd>
            </div>
        </dl>

        <form method="post" action="{{ route('admin.projects.mark-remaining-billed', $project['id']) }}" class="project-overview-billing__action" id="markBilledForm">
            @csrf
            <button type="submit" class="dashboard-btn dashboard-btn--primary dashboard-btn--sm">
                Mark all unbilled shifts as billed
            </button>
        </form>
    </section>

    <hr class="dashboard-stats-divider">

    <div class="project-overview-panels">
        <section class="project-overview-panel" aria-label="Hours by employee">
            <div class="dashboard-toolbar-label mb-2">By Employee (All Time)</div>
            <div class="i3-data-table i3-data-table--5col">
                <div class="i3-data-table__scroll">
                    <div class="i3-data-table__scroll-inner">
                        <div class="i3-data-table__head">
                            <span>Employee</span>
                            <span>Billed Hrs</span>
                            <span>Unbilled Hrs</span>
                            <span>Total Hrs</span>
                            <span>Last Shift</span>
                        </div>
                        <ul class="i3-data-table__body i3-data-table__body--lg list-unstyled mb-0">
                            @forelse($employees as $row)
                            <li class="i3-data-table__row">
                                <span class="i3-data-table__label" data-label="Employee">
                                    <span class="i3-hash">#</span>
                                    <a href="{{ route('admin.users.dashboard', ['user' => $row['netid']]) }}" class="i3-link">{{ $row['name'] }}</a>
                                </span>
                                <span data-label="Billed Hrs">{{ number_format($row['billed_hours'], 2) }}</span>
                                <span data-label="Unbilled Hrs">{{ number_format($row['unbilled_hours'], 2) }}</span>
                                <span data-label="Total Hrs">{{ number_format($row['total_hours'], 2) }}</span>
                                <span data-label="Last Shift">{{ $row['last_shift_date'] ?? '—' }}</span>
                            </li>
                            @empty
                            <li class="i3-data-table__empty">No hours logged on this project.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <section class="project-overview-panel project-overview-panel--members" aria-label="Assigned members">
            <div class="dashboard-toolbar-label mb-2">Assigned Members ({{ count($overview['assigned_users']) }})</div>
            @if(count($overview['assigned_users']) > 0)
                <ul class="project-overview-members list-unstyled mb-0">
                    @foreach($overview['assigned_users'] as $user)
                    <li class="project-overview-members__item">
                        <span class="i3-hash">#</span>
                        <a href="{{ route('admin.users.dashboard', ['user' => $user['netid']]) }}" class="i3-link">{{ $user['name'] }}</a>
                    </li>
                    @endforeach
                </ul>
            @else
                <p class="dashboard-stats__empty">No members assigned.</p>
            @endif
        </section>
    </div>

    <hr class="dashboard-stats-divider">

    <section aria-label="Shifts all time">
        <div class="dashboard-toolbar-label mb-2">Shifts (All Time)</div>
        <div class="i3-data-table i3-data-table--7col">
            <div class="i3-data-table__scroll">
                <div class="i3-data-table__scroll-inner">
                    <div class="i3-data-table__head">
                        <span>Employee</span>
                        <span>Date</span>
                        <span>Hours</span>
                        <span class="i3-data-table__check-col">Timecard</span>
                        <span class="i3-data-table__check-col">Honeycrisp</span>
                        <span></span>
                        <span></span>
                    </div>
                    <ul class="i3-data-table__body i3-data-table__body--lg list-unstyled mb-0" id="projectShiftList">
                        @forelse($shifts as $row)
                        <li class="i3-data-table__row">
                            <span class="i3-data-table__label" data-label="Employee">
                                <span class="i3-hash">#</span>
                                <a href="{{ route('admin.users.dashboard', ['user' => $row['netid']]) }}" class="i3-link">{{ $row['employee_name'] }}</a>
                            </span>
                            <span data-label="Date">{{ $row['date_display'] }}</span>
                            <span data-label="Hours">{{ number_format($row['hours'], 2) }}</span>
                            <span class="i3-data-table__check-col" data-label="Timecard">
                                <span class="i3-check {{ $row['entered'] ? 'is-checked' : '' }}" aria-hidden="true">
                                    <i class="bi bi-check-lg"></i>
                                </span>
                            </span>
                            <span class="i3-data-table__check-col" data-label="Honeycrisp">
                                <button type="button"
                                        class="i3-check admin-shift-billed-toggle {{ $row['billed'] ? 'is-checked' : '' }}"
                                        data-shift-id="{{ $row['id'] }}"
                                        aria-pressed="{{ $row['billed'] ? 'true' : 'false' }}"
                                        @disabled($row['billed'])
                                        aria-label="Mark shift as billed for {{ $row['employee_name'] }}">
                                    <i class="bi bi-check-lg"></i>
                                </button>
                            </span>
                            <span aria-hidden="true"></span>
                            <span class="i3-data-table__actions" data-label="Actions">
                                <button type="button"
                                        class="dashboard-shift-card__edit-btn project-shift-edit-btn"
                                        data-shift='@json($row)'
                                        aria-label="Edit shift for {{ $row['employee_name'] }}">
                                    Edit
                                </button>
                            </span>
                        </li>
                        @empty
                        <li class="i3-data-table__empty">No shifts logged for this project.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </section>
</div>

@php $shiftModalMode = 'admin'; @endphp
@include('partials.shift-modal')

<div class="shift-modal-backdrop d-none" id="markBilledBackdrop" aria-hidden="true"></div>
<div class="shift-modal d-none" id="markBilledModal" role="dialog" aria-modal="true" aria-labelledby="markBilledTitle">
    <div class="shift-modal__dialog">
        <div class="shift-modal__header">
            <span id="markBilledTitle" class="flex-grow-1">Confirm Billing</span>
            <button type="button" class="shift-modal__close" id="markBilledClose" aria-label="Cancel">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
        </div>
        <div class="shift-modal__body">
            <p class="mb-2">Mark <strong id="markBilledTotal"></strong> unbilled hrs as billed for <strong>{{ $project['name'] }}</strong>?</p>
            <ul class="list-unstyled mb-0" id="markBilledEmployeeList"></ul>
        </div>
        <div class="shift-modal__footer">
            <div></div>
            <div class="d-flex gap-2">
                <button type="button" class="dashboard-btn dashboard-btn--sm" id="markBilledCancel">Cancel</button>
                <button type="button" class="dashboard-btn dashboard-btn--primary dashboard-btn--sm" id="markBilledConfirm">Confirm</button>
            </div>
        </div>
    </div>
</div>

@include('partials.bootstrap-js')

@push('scripts')
<script src="{{ asset('js/i3-utils.js') }}"></script>
<script>
    (function () {
        const back = document.querySelector('[data-admin-back]');
        if (back && sessionStorage.getItem('admin_return') === '1') {
            back.addEventListener('click', (event) => {
                event.preventDefault();
                sessionStorage.removeItem('admin_return');
                history.back();
            });
        }
    })();
</script>
<script>
    (function () {
        const shiftBaseUrl = @json(url('/shifts'));
        let csrfToken = @json(csrf_token());
        const shiftList = document.getElementById('projectShiftList');

        const markShiftBilled = async (shiftId) => {
            const formData = new FormData();
            formData.append('_token', csrfToken);
            formData.append('billed', '1');

            const response = await fetch(`${shiftBaseUrl}/${shiftId}/billed`, {
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
                throw new Error(data.message || `Request failed (${response.status})`);
            }

            if (data.csrf_token) {
                csrfToken = data.csrf_token;
            }

            return data;
        };

        document.querySelectorAll('.project-shift-edit-btn').forEach((btn) => {
            btn.addEventListener('click', () => {
                try {
                    const shift = JSON.parse(btn.dataset.shift || '{}');
                    if (shift.id && typeof window.openShiftModal === 'function') {
                        window.openShiftModal(shift);
                    }
                } catch {
                    // ignore malformed shift payload
                }
            });
        });

        shiftList?.addEventListener('click', async (event) => {
            const toggleBtn = event.target.closest('.admin-shift-billed-toggle');
            if (!toggleBtn || toggleBtn.disabled) {
                return;
            }

            event.preventDefault();

            const shiftId = toggleBtn.dataset.shiftId;
            if (!shiftId) {
                return;
            }

        toggleBtn.disabled = true;
        toggleBtn.classList.add('is-checked');
        toggleBtn.setAttribute('aria-pressed', 'true');

        try {
            await markShiftBilled(shiftId);
        } catch (error) {
            toggleBtn.classList.remove('is-checked');
            toggleBtn.setAttribute('aria-pressed', 'false');
            toggleBtn.disabled = false;
            alert(error.message || 'Could not mark shift as billed. Please try again.');
        }
        });
    })();
    window.shiftModalConfig = {
        mode: 'admin',
        canDelete: true,
        storeUrl: @json(route('shifts.store')),
        shiftBaseUrl: @json(url('/shifts')),
    };
</script>
<script defer src="{{ asset('js/shift-modal.js') }}"></script>
<script>
    (function () {
        const allTime = @json($allTime);
        const employees = @json($employees);
        const form = document.getElementById('markBilledForm');
        const modal = document.getElementById('markBilledModal');
        const backdrop = document.getElementById('markBilledBackdrop');

        function openModal() {
            document.getElementById('markBilledTotal').textContent =
                Number(allTime.unbilled_hours).toFixed(2);

            const list = document.getElementById('markBilledEmployeeList');
            list.innerHTML = '';
            employees
                .filter(e => Number(e.unbilled_hours) > 0)
                .forEach(e => {
                    const li = document.createElement('li');
                    li.textContent = `${e.name}: ${Number(e.unbilled_hours).toFixed(2)} hrs`;
                    list.appendChild(li);
                });

            modal.classList.remove('d-none');
            backdrop.classList.remove('d-none');
            document.body.classList.add('shift-modal-open');
            document.getElementById('markBilledConfirm').focus();
        }

        function closeModal() {
            modal.classList.add('d-none');
            backdrop.classList.add('d-none');
            document.body.classList.remove('shift-modal-open');
        }

        form?.addEventListener('submit', (event) => {
            event.preventDefault();
            openModal();
        });

        document.getElementById('markBilledConfirm')?.addEventListener('click', () => {
            closeModal();
            form.submit();
        });

        document.getElementById('markBilledCancel')?.addEventListener('click', closeModal);
        document.getElementById('markBilledClose')?.addEventListener('click', closeModal);
        backdrop?.addEventListener('click', closeModal);

        modal?.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeModal();
            }
        });
    })();
</script>
@endpush
@endsection
