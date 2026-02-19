@extends('layouts.app')

@section('content')
<div class="mt-4">
    <div class="card border-0 shadow-sm mb-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="row g-0">
                <div class="col-md-7">
                    <div class="p-5 h-100" style="background-color: var(--uconn-navy);">
                        <h1 class="display-5 fw-bold text-white mb-3">
                            i3 Time Tracker
                        </h1>
                        <p class="lead text-white opacity-90 mb-4">Track time spent working on projects</p>
                        <div class="d-flex gap-3 flex-wrap">
                            <a href="{{ route('shifts.create') }}" class="btn btn-outline-light mt-2">
                                <i class="bi bi-plus-circle me-2"></i>Log New Shift
                            </a>
                            @if(auth()->user()->isAdmin())
                            <a href="{{ route('projects.create') }}" class="btn btn-outline-light mt-2">
                                Create Project
                            </a>
                            @endif
                        </div>
                    </div>
                </div>
                
                <div class="col-md-5">
                    <div class="p-4 h-100 d-flex flex-column justify-content-center align-items-center bg-light border rounded shadow-sm position-relative">
                        <button id="prev" type="button" class="btn btn-link text-decoration-none position-absolute" style="left: 0.5rem; color: #6c757d; font-size: 2rem;">‹</button>

                        <button id="next" type="button" class="btn btn-link text-decoration-none position-absolute" style="right: 0.5rem; color: #6c757d; font-size: 2rem;">›</button>

                        <div class="text-center mb-2">
                            <div id="current" class="display-4 fw-bold mb-0" style="color: var(--uconn-navy);">{{ $hoursThisWeek }}</div>
                            <p id="selectedWeekLabel" class="text-muted mb-0" style="font-size: 0.75rem; letter-spacing: 0.05em;">Hours from</p>
                        </div>
                        <div style="height: 100px; position: relative; margin: 1rem -0.5rem 0.5rem;">
                            <canvas id="weeklyHoursChart"></canvas>
                        </div>
                        {{-- <div class="display-1 fw-bold text-primary mb-0">{{ $hoursThisWeek }}</div>
                        <p class="mb-2 text-uppercase fw-semibold text-muted small">Hours This Week</p> --}}
                        <div class="mt-3">
                            <a href="{{ route('shifts.index') }}" class="btn btn-sm btn-outline-secondary">
                                View All Shifts
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @php
        $columns = [
            ['key' => 'name', 'label' => 'Project Name', 'sortable' => true, 'route' => 'projects.show'],
            ['key' => 'billed_hours', 'label' => 'Billed Hours', 'sortable' => true],
            ['key' => 'unbilled_hours', 'label' => 'Unbilled Hours', 'sortable' => true],
            ['key' => 'active', 'label' => 'Active', 'sortable' => true, 'type' => 'boolean'],
        ];

        $actions = [
            ['key' => 'add_shift', 'label' => 'Add Shift', 'icon' => 'clock', 'route' => 'shifts.create', 'color' => 'success', 'params' => ['proj_id' => 'id']]
        ];
    @endphp

    @include('partials.table', [
        // 'filterable' => true,
        'items' => $activeProjects,
        'columns' => $columns,
        'actions' => $actions,
        'title' => 'Your Projects',
        'empty_message' => 'No active projects found.',
        'empty_icon' => 'folder-x',
        'create_route' => auth()->user()->isAdmin() ? 'projects.create' : null,
        'create_label' => 'Create Project'
    ])
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>

(function() {
    const initChart = () => {
        if (typeof Chart === 'undefined') {
            // console.error('Chart.js is not loaded.');
            setTimeout(initChart, 50);
            return;
        }

        const ctx = document.getElementById('weeklyHoursChart');
        const hoursValueElement = document.getElementById('current');
        const selectedWeekLabelElement = document.getElementById('selectedWeekLabel');
        const prev = document.getElementById('prev');
        const next = document.getElementById('next');
        const weeklyChartData = @json($weeklyChartData ?? []);
        const defaultDailyData = @json(array_values($dailyHours));
        const defaultDailyLabels = @json(array_keys($dailyHours));
        let currentWeekIndex = Math.max(weeklyChartData.length - 1, 0);

        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: defaultDailyLabels,
                datasets: [{
                    label: 'Hours',
                    data: defaultDailyData,
                    backgroundColor: 'rgba(0, 14, 47, 0.1)',
                    borderColor: 'rgba(0, 14, 47, 0.4)',
                    borderWidth: 1.5,
                    fill: true,
                    tension: 0.3, // smoothing
                    pointRadius: 0, // 0 no pt
                    pointHoverRadius: 4,
                    pointHoverBackgroundColor: 'rgb(0, 14, 47)',
                    pointHoverBorderColor: '#fff',
                    pointHoverBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                // layout: {
                //     padding: {
                //         top: 1,
                //         bottom: 0,
                //     }
                // },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.7)',
                        padding: 6,
                        titleFont: {
                            size: 11,
                            weight: 'normal'
                        },
                        bodyFont: {
                            size: 10
                        },
                        displayColors: false,
                        caretSize: 4,
                        callbacks: {
                            title: function(context) {
                                return context[0].label;
                            },
                            label: function(context) {
                                const hours = context.parsed.y;
                                return hours + 'h';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        display: false,
                        beginAtZero: true,
                        grace: '10%'
                    },
                    x: {
                        ticks: {
                            font: {
                                size: 9,
                                weight: '300'
                            },
                            color: '#bbb',
                            padding: 4
                        },
                        grid: {
                            display: false
                        },
                        border: {
                            display: false
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });

        const setNavState = () => {
            if (!prev || !next) {
                return;
            }

            prev.disabled = currentWeekIndex <= 0;
            next.disabled = currentWeekIndex >= weeklyChartData.length - 1;
        };

        const renderWeek = () => {
            if (!weeklyChartData.length) {
                if (selectedWeekLabelElement) {
                    selectedWeekLabelElement.textContent = 'Hours from selected week';
                }
                setNavState();
                return;
            }

            const week = weeklyChartData[currentWeekIndex];
            const labels = Object.keys(week.daily_hours || {});
            const data = Object.values(week.daily_hours || {});

            chart.data.labels = labels;
            chart.data.datasets[0].data = data;
            chart.update();

            if (hoursValueElement) {
                hoursValueElement.textContent = week.hours_this_week;
            }

            if (selectedWeekLabelElement) {
                selectedWeekLabelElement.textContent = week.label ? `Hours from ${week.label}` : 'Hours from selected week';
            }

            setNavState();
        };

        if (prev) {
            prev.addEventListener('click', function () {
                if (currentWeekIndex > 0) {
                    currentWeekIndex -= 1;
                    renderWeek();
                }
            });
        }

        if (next) {
            next.addEventListener('click', function () {
                if (currentWeekIndex < weeklyChartData.length - 1) {
                    currentWeekIndex += 1;
                    renderWeek();
                }
            });
        }

        renderWeek();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initChart);
    } else {
        initChart();
    }
})();

</script>
@endpush

@endsection