@extends('layouts.app')

@section('content')
<div class="landing-page landing-page-web mt-3">
    <section class="landing-hero-band mb-4">
        <div class="landing-hero-grid">
            <div class="landing-hero-content">
                <p class="landing-kicker mb-3">Institutional Insights & Innovation</p>
                <h1 class="landing-title mb-3">Time Tracker</h1>
                <div class="d-flex gap-3 flex-wrap align-items-center">
                    <a href="{{ route('shifts.create') }}" class="btn landing-btn-primary">
                        Log New Shift
                    </a>
                    <a href="{{ route('shifts.index') }}" class="btn landing-btn-secondary">
                        View All Shifts
                    </a>
                    @if(auth()->user()->isAdmin())
                    <a href="{{ route('projects.create') }}" class="btn landing-btn-secondary">
                        Create Project
                    </a>
                    @endif
                </div>
            </div>

            <div class="landing-weekly-panel">
                <button id="prev" type="button" class="btn btn-link text-decoration-none landing-kpi-nav landing-kpi-prev">‹</button>
                <button id="next" type="button" class="btn btn-link text-decoration-none landing-kpi-nav landing-kpi-next">›</button>
                <div id="current" class="landing-kpi-value mb-0">{{ $hoursThisWeek }}</div>
                <p id="selectedWeekLabel" class="landing-kpi-subtext mb-0">Hours from</p>

                <div class="landing-kpi-chart-wrap mt-3">
                    <canvas id="weeklyHoursChart"></canvas>
                </div>
            </div>
        </div>
    </section>

    <section class="landing-projects-section">
        <div class="landing-section-head mb-3">
            <h2 class="landing-section-title mb-1">Assigned Projects</h2>
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
        'items' => $activeProjects,
        'columns' => $columns,
        'actions' => $actions,
        'title' => null,
        'empty_message' => 'No active projects found.',
        'empty_icon' => 'folder-x',
        'create_route' => auth()->user()->isAdmin() ? 'projects.create' : null,
        'create_label' => 'Create Project'
    ])
    </section>
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
                    backgroundColor: 'rgba(145, 217, 255, 0.2)',
                    borderColor: 'rgba(170, 228, 255, 0.95)',
                    borderWidth: 1.8,
                    fill: true,
                    tension: 0.42, // smoothing
                    pointRadius: 0, // 0 no pt
                    pointHoverRadius: 0,
                    pointHoverBackgroundColor: 'rgb(230, 247, 255)',
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
                        backgroundColor: 'rgba(2, 22, 62, 0.92)',
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
                            color: 'rgba(232, 241, 255, 0.7)',
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