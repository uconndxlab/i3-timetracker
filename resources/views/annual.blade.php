@extends('layouts.app')

@use(Carbon\Carbon)
@php
    $dailyTotals       = $annualData['dailyTotals'];
    $selectedDayShifts = $annualData['selectedDayShifts'];
    $totalShiftsInYear = $annualData['totalShiftsInYear'];
    $totalHoursInYear  = $annualData['totalHoursInYear'];
@endphp

@section('content')

@if($dashboardReadOnly)
    <div class="dashboard-viewing-user mb-3">
        <span class="dashboard-viewing-user__name">
            <span class="i3-dot" aria-hidden="true"></span>
            {{ \Illuminate\Support\Str::title($subjectUser->name) }}
            <span class="dashboard-viewing-user__sep" aria-hidden="true">/</span>
            <a href="{{ route('admin.users.dashboard', $subjectUser->netid) }}" class="i3-link">Dashboard</a>
            <span class="dashboard-viewing-user__sep" aria-hidden="true">/</span>
            <a href="{{ route('landing', ['view' => 'admin']) }}" class="i3-link">Back To Admin</a>
        </span>
    </div>
@endif

<div class="annual-view mt-3">

    {{-- Top bar --}}
    <div class="annual-topbar d-flex justify-content-between align-items-center mb-4">
        <div class="annual-topbar__left">
            <a href="{{ route('landing') }}" class="i3-link annual-back-link">
                <i class="bi bi-arrow-left me-1"></i>Dashboard
            </a>
        </div>
        @unless($dashboardReadOnly)
        <button type="button" class="dashboard-btn dashboard-btn--primary" id="openShiftModalBtn">
            <i class="bi bi-plus-lg me-2"></i>Log New Shift
        </button>
        @endunless
    </div>

    {{-- Year navigation --}}
    <div class="annual-year-nav d-flex align-items-center justify-content-center gap-3 mb-4">
        <a href="{{ request()->fullUrlWithQuery(['date' => $prevDate]) }}"
           class="annual-year-nav__arrow"
           aria-label="Previous year ({{ $year - 1 }})">
            <i class="bi bi-chevron-left"></i>
        </a>
        <h2 class="annual-year-nav__year mb-0">{{ $year }}</h2>
        <a href="{{ request()->fullUrlWithQuery(['date' => $nextDate]) }}"
           class="annual-year-nav__arrow"
           aria-label="Next year ({{ $year + 1 }})">
            <i class="bi bi-chevron-right"></i>
        </a>
    </div>

    {{-- View toggle: Full / Compact --}}
    <div class="d-flex justify-content-center mb-3">
        <form method="POST" action="{{ route('annual.view-preference') }}" class="d-flex">
            @csrf
            <div class="dashboard-segment" role="group" aria-label="Calendar view">
                <button type="submit" name="view" value="full"
                    class="dashboard-segment__btn{{ $calView === 'full' ? ' active' : '' }}"
                    aria-pressed="{{ $calView === 'full' ? 'true' : 'false' }}">Full</button>
                <button type="submit" name="view" value="compact"
                    class="dashboard-segment__btn{{ $calView === 'compact' ? ' active' : '' }}"
                    aria-pressed="{{ $calView === 'compact' ? 'true' : 'false' }}">Compact</button>
            </div>
        </form>
    </div>

    {{-- Summary pill --}}
    <div class="d-flex justify-content-center mb-4">
        <span class="annual-summary-pill">
            {{ number_format($totalShiftsInYear) }} {{ Str::plural('shift', $totalShiftsInYear) }}
            &middot;
            {{ number_format($totalHoursInYear, 2) }} hrs in {{ $year }}
        </span>
    </div>

    {{-- 12-month calendar grid --}}
    <div id="annualCalFull" class="{{ $calView === 'compact' ? 'd-none' : '' }}">
    <div class="annual-cal" aria-label="{{ $year }} calendar">
        @for($m = 1; $m <= 12; $m++)
            @php
                $firstDay  = Carbon::createFromDate($year, $m, 1);
                $daysInMonth = $firstDay->daysInMonth;
                $startDow  = (int) $firstDay->dayOfWeek; // 0=Sun
            @endphp
            <div class="annual-cal__month">
                <div class="annual-cal__month-name">{{ $monthNames[$m - 1] }}</div>
                <div class="cal-days" role="grid" aria-label="{{ $monthNames[$m - 1] }} {{ $year }}">
                    {{-- Day-of-week headers --}}
                    @foreach($dayLabels as $dl)
                        <div class="cal-day cal-day--header" aria-hidden="true">{{ $dl }}</div>
                    @endforeach

                    {{-- Blank offset cells --}}
                    @for($b = 0; $b < $startDow; $b++)
                        <div class="cal-day cal-day--blank" aria-hidden="true"></div>
                    @endfor

                    {{-- Day cells --}}
                    @for($d = 1; $d <= $daysInMonth; $d++)
                        @php
                            $dateStr = sprintf('%04d-%02d-%02d', $year, $m, $d);
                            $shade   = $dailyTotals[$dateStr]['shade'] ?? 'empty';
                            $isSelected = $dateStr === $selectedDate;
                            $isTodayCell = $dateStr === $today;
                            $cellUrl = request()->fullUrlWithQuery(['date' => $dateStr]);
                            $minutes = $dailyTotals[$dateStr]['minutes'] ?? 0;
                            $hrs = $minutes > 0 ? number_format($minutes / 60, 1) . ' hrs' : 'No shifts';
                            $ariaLabel = Carbon::createFromFormat('Y-m-d', $dateStr)->format('F j, Y') . ': ' . $hrs;
                        @endphp
                        <a href="{{ $cellUrl }}"
                           class="cal-day cal-day--{{ $shade }}{{ $isSelected ? ' cal-day--selected' : '' }}{{ $isTodayCell ? ' cal-day--today' : '' }}"
                           aria-label="{{ $ariaLabel }}"
                           aria-current="{{ $isSelected ? 'date' : 'false' }}">{{ $d }}</a>
                    @endfor
                </div>
            </div>
        @endfor
    </div>
    </div>{{-- /#annualCalFull --}}

    {{-- Compact (GitHub-style) calendar --}}
    <div id="annualCalCompact" class="annual-cal-compact{{ $calView === 'compact' ? '' : ' d-none' }}" aria-label="{{ $year }} compact calendar">
        <div class="compact-inner">
            {{-- Month labels row --}}
            <div class="compact-header-row">
                <div class="compact-dow-spacer"></div>
                <div class="compact-months-track" style="width: {{ $compactTotalWeeks * 15 }}px">
                    @foreach($compactMonthLabels as $ml)
                        <span class="compact-month-label" style="left: {{ $ml['weekIndex'] * 15 }}px">{{ $ml['label'] }}</span>
                    @endforeach
                </div>
            </div>
            {{-- Day grid --}}
            <div class="compact-body-row">
                <div class="compact-dow-labels">
                    <div class="compact-dow-slot" aria-hidden="true"></div>
                    <div class="compact-dow-slot">Mon</div>
                    <div class="compact-dow-slot" aria-hidden="true"></div>
                    <div class="compact-dow-slot">Wed</div>
                    <div class="compact-dow-slot" aria-hidden="true"></div>
                    <div class="compact-dow-slot">Fri</div>
                    <div class="compact-dow-slot" aria-hidden="true"></div>
                </div>
                <div class="compact-weeks-track">
                    @foreach($compactWeeks as $week)
                        <div class="compact-week">
                            @foreach($week as $ds)
                                @if($ds === null)
                                    <div class="compact-day compact-day--blank" aria-hidden="true"></div>
                                @else
                                    @php
                                        $cShade = $dailyTotals[$ds]['shade'] ?? 'empty';
                                        $cMins  = $dailyTotals[$ds]['minutes'] ?? 0;
                                        $cHrs   = $cMins > 0 ? number_format($cMins / 60, 2) : '0';
                                        $cSel   = $ds === $selectedDate;
                                        $cToday = $ds === $today;
                                        $cUrl   = request()->fullUrlWithQuery(['date' => $ds]);
                                        $cLabel = Carbon::createFromFormat('Y-m-d', $ds)->format('F j, Y')
                                                  . ': ' . ($cMins > 0 ? $cHrs . ' hrs' : 'No shifts');
                                    @endphp
                                    <a href="{{ $cUrl }}"
                                       class="compact-day compact-day--{{ $cShade }}{{ $cSel ? ' compact-day--selected' : '' }}{{ $cToday ? ' compact-day--today' : '' }}"
                                       data-date="{{ $ds }}"
                                       data-hours="{{ $cHrs }}"
                                       aria-label="{{ $cLabel }}"
                                       aria-current="{{ $cSel ? 'date' : 'false' }}"></a>
                                @endif
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Legend --}}
    <div class="annual-legend d-flex align-items-center justify-content-center gap-3 mt-4 mb-4" aria-label="Shade legend">
        <span class="annual-legend__item">
            <span class="annual-legend__swatch cal-day--empty" aria-hidden="true"></span>
            <span>No shifts</span>
        </span>
        <span class="annual-legend__item">
            <span class="annual-legend__swatch cal-day--light" aria-hidden="true"></span>
            <span>&gt;0 hrs</span>
        </span>
        <span class="annual-legend__item">
            <span class="annual-legend__swatch cal-day--medium" aria-hidden="true"></span>
            <span>4–6 hrs</span>
        </span>
        <span class="annual-legend__item">
            <span class="annual-legend__swatch cal-day--dark" aria-hidden="true"></span>
            <span>7+ hrs</span>
        </span>
    </div>

    {{-- Selected day detail panel --}}
    <div class="annual-selected-day mt-2">
        <h3 class="annual-selected-day__heading">
            @if($isToday)
                Today,
            @endif
            {{ $selectedCarbon->format('F j, Y') }}
        </h3>

        @if(count($selectedDayShifts) === 0)
            <p class="annual-selected-day__empty">No shifts recorded for this day.</p>
        @else
            <ul class="annual-selected-day__list">
                @foreach($selectedDayShifts as $shift)
                    <li class="annual-selected-day__row">
                        <span class="annual-selected-day__project">{{ $shift['project_name'] }}</span>
                        <span class="annual-selected-day__dots" aria-hidden="true"></span>
                        <span class="annual-selected-day__hours">{{ number_format($shift['duration_hours'], 2) }} hrs</span>
                        <span class="annual-selected-day__badges">
                            @if($shift['entered'])
                                <span class="annual-badge annual-badge--entered">Entered</span>
                            @endif
                            @if($shift['billed'])
                                <span class="annual-badge annual-badge--billed">Billed</span>
                            @endif
                        </span>
                        @if(! $dashboardReadOnly && $shift['can_edit'])
                            <button type="button"
                                class="annual-selected-day__edit"
                                data-shift-id="{{ $shift['id'] }}"
                                aria-label="Edit shift for {{ $shift['project_name'] }}">
                                <i class="bi bi-pencil" aria-hidden="true"></i>
                            </button>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    {{-- Floating tooltip for compact calendar --}}
    <div id="calTooltip" class="annual-cal-tooltip" role="tooltip" aria-hidden="true"></div>

</div>

@unless($dashboardReadOnly)
    @include('partials.shift-modal')
    @include('partials.bootstrap-js')

    @push('scripts')
    <script src="{{ asset('js/i3-utils.js') }}"></script>
    <script>
        window.shiftModalConfig = {
            mode: @json($isAdminViewer ? 'admin' : 'user'),
            canDelete: @json((bool) $isAdminViewer),
            defaultShiftDate: @json($defaultShiftDate),
            storeUrl: @json(route('shifts.store')),
            shiftBaseUrl: @json(url('/shifts')),
        };
        window.annualDayShifts = @json(
            collect($selectedDayShifts)->keyBy('id')->all()
        );
    </script>
    <script defer src="{{ asset('js/shift-modal.js') }}"></script>
    @endpush
@endunless

@push('scripts')
<script>
(function () {
    // ── Shift edit buttons ───────────────────────────────────────────
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.annual-selected-day__edit[data-shift-id]');
        if (!btn) return;
        var shift = (window.annualDayShifts || {})[btn.dataset.shiftId];
        if (shift && typeof window.openShiftModal === 'function') {
            window.openShiftModal(shift);
        }
    });

    // ── Compact day tooltip ──────────────────────────────────────────
    var tooltip = document.getElementById('calTooltip');
    if (!tooltip) return;

    var hovered = null;

    document.addEventListener('mouseover', function (e) {
        var day = e.target.closest('.compact-day[data-date]');
        if (day === hovered) return;
        hovered = day;

        if (!day) {
            tooltip.setAttribute('aria-hidden', 'true');
            tooltip.classList.remove('is-visible');
            return;
        }

        var hours = parseFloat(day.dataset.hours);
        var parts = day.dataset.date.split('-');
        var d     = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
        var label = d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        var hText = hours > 0 ? hours.toFixed(2) + ' hrs' : 'No shifts';

        tooltip.textContent = label + ' · ' + hText;

        var rect    = day.getBoundingClientRect();
        var scrollY = window.scrollY || document.documentElement.scrollTop;
        var scrollX = window.scrollX || document.documentElement.scrollLeft;
        tooltip.style.left      = (rect.left + rect.width / 2 + scrollX) + 'px';
        tooltip.style.top       = (rect.bottom + scrollY + 6) + 'px';
        tooltip.style.transform = 'translateX(-50%)';

        tooltip.removeAttribute('aria-hidden');
        tooltip.classList.add('is-visible');
    });

    document.addEventListener('mouseleave', function () {
        hovered = null;
        tooltip.setAttribute('aria-hidden', 'true');
        tooltip.classList.remove('is-visible');
    });
})();
</script>
@endpush

@endsection
