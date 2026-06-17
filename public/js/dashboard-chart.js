(function () {
    const instances = {};

    const getChartColors = () => ({
        border: '#4869FD',
        tick: '#9aa5b1',
        hover: '#4869FD',
    });

    const buildGradient = (chartInstance) => {
        if (!chartInstance?.chart?.ctx) {
            return 'rgba(72, 105, 253, 0.2)';
        }

        const { ctx, chartArea } = chartInstance.chart;
        if (!chartArea) {
            return 'rgba(72, 105, 253, 0.2)';
        }

        const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
        gradient.addColorStop(0, 'rgba(72, 105, 253, 0.35)');
        gradient.addColorStop(1, 'rgba(72, 105, 253, 0)');

        return gradient;
    };

    const getActiveSeries = (instance) => {
        if (instance.chartRange === 'period' && typeof instance.getPeriodSeries === 'function') {
            return instance.getPeriodSeries();
        }

        return instance.hoursTimeline[instance.chartRange] || { labels: [], data: [], total: 0 };
    };

    const applyChartSeries = (instance) => {
        if (!instance.chart) {
            return;
        }

        const series = getActiveSeries(instance);
        const colors = getChartColors();

        instance.chart.data.labels = series.labels;
        instance.chart.data.datasets[0].data = series.data;
        instance.chart.data.datasets[0].borderColor = colors.border;
        instance.chart.data.datasets[0].pointHoverBackgroundColor = colors.hover;
        instance.chart.options.scales.x.ticks.color = colors.tick;

        if (instance.totalEl) {
            instance.totalEl.textContent = Number(series.total || 0).toFixed(2);
        }

        instance.chart.data.datasets[0].backgroundColor = buildGradient(instance.chart);
        instance.chart.update('none');
    };

    const setChartRange = (instance, range) => {
        instance.chartRange = range;
        instance.rangeButtons.forEach((btn) => {
            btn.classList.toggle('active', btn.dataset.chartRange === range);
        });
        applyChartSeries(instance);
    };

    const initChart = (instance) => {
        if (!instance.canvas || typeof Chart === 'undefined') {
            setTimeout(() => initChart(instance), 50);
            return;
        }

        const series = getActiveSeries(instance);
        const colors = getChartColors();

        instance.chart = new Chart(instance.canvas, {
            type: 'line',
            data: {
                labels: series.labels,
                datasets: [{
                    label: 'Hours',
                    data: series.data,
                    backgroundColor: 'rgba(72, 105, 253, 0.2)',
                    borderColor: colors.border,
                    borderWidth: 1.5,
                    fill: true,
                    tension: 0.35,
                    pointRadius: 0,
                    pointHoverRadius: 4,
                    pointHoverBackgroundColor: colors.hover,
                    pointHoverBorderColor: '#fff',
                    pointHoverBorderWidth: 2,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.75)',
                        padding: 6,
                        displayColors: false,
                        callbacks: {
                            label(context) {
                                return `${context.parsed.y} hr`;
                            },
                        },
                    },
                },
                scales: {
                    y: {
                        display: false,
                        beginAtZero: true,
                        grace: '10%',
                    },
                    x: {
                        ticks: {
                            font: { size: 9, weight: '500' },
                            color: colors.tick,
                            padding: 4,
                            maxTicksLimit: instance.maxTicksLimit,
                            autoSkip: true,
                        },
                        grid: { display: false },
                        border: { display: false },
                    },
                },
                interaction: {
                    intersect: false,
                    mode: 'index',
                },
            },
        });

        applyChartSeries(instance);

        instance.rangeButtons.forEach((btn) => {
            btn.addEventListener('click', () => setChartRange(instance, btn.dataset.chartRange));
        });

        if (!window._dashboardChartThemeBound) {
            window._dashboardChartThemeBound = true;
            document.getElementById('themeToggle')?.addEventListener('click', () => {
                Object.values(instances).forEach((entry) => {
                    setTimeout(() => applyChartSeries(entry), 0);
                });
            });
        }
    };

    window.initDashboardChart = (config) => {
        const root = config.rootId ? document.getElementById(config.rootId) : document;
        const canvas = document.getElementById(config.canvasId);

        if (!canvas) {
            return null;
        }

        if (instances[config.canvasId]?.chart) {
            instances[config.canvasId].chart.destroy();
        }

        const instance = {
            canvas,
            canvasId: config.canvasId,
            totalEl: document.getElementById(config.totalId),
            hoursTimeline: config.hoursTimeline || {},
            chartRange: config.defaultRange || 'month',
            maxTicksLimit: config.maxTicksLimit ?? 7,
            getPeriodSeries: config.getPeriodSeries || null,
            rangeButtons: root
                ? root.querySelectorAll('[data-chart-range]')
                : canvas.closest('.dashboard-stats')?.querySelectorAll('[data-chart-range]') || [],
            chart: null,
        };

        instances[config.canvasId] = instance;

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => initChart(instance));
        } else {
            initChart(instance);
        }

        return instance;
    };

    window.refreshDashboardChart = (canvasId) => {
        const instance = instances[canvasId];
        if (!instance?.chart) {
            return;
        }

        applyChartSeries(instance);
    };

    const bootLandingCharts = () => {
        const bootChart = (chartConfig, retries = 0) => {
            if (typeof window.initDashboardChart !== 'function') {
                if (retries < 40) {
                    setTimeout(() => bootChart(chartConfig, retries + 1), 50);
                }
                return;
            }

            window.initDashboardChart(chartConfig);
        };

        const userConfig = window.userDashboardConfig || {};
        if (document.getElementById('weeklyHoursChart')) {
            bootChart({
                rootId: 'userDashboard',
                canvasId: 'weeklyHoursChart',
                totalId: 'statsChartTotal',
                hoursTimeline: userConfig.hoursTimeline || {},
                defaultRange: 'month',
                maxTicksLimit: 4,
                getPeriodSeries: () => {
                    const week = userConfig.weeklyChartData?.[window.dashboardWeekIndex ?? 0];
                    if (!week?.days?.length) {
                        return { labels: [], data: [], total: 0 };
                    }

                    return {
                        labels: week.days.map((day) => day.key || window.I3.formatChartDayLabel(day)),
                        data: week.days.map((day) => day.hours ?? 0),
                        total: week.hours_this_week ?? 0,
                    };
                },
            });

            window.updateWeeklyChart = () => window.refreshDashboardChart('weeklyHoursChart');

            window.dashboardSetWeekIndex = (index) => {
                window.dashboardWeekIndex = index;
                window.updateWeeklyChart();
            };
        }

        const adminConfig = window.adminDashboardConfig || {};
        if (document.getElementById('adminHoursChart') && adminConfig.hoursTimeline) {
            bootChart({
                rootId: 'adminDashboard',
                canvasId: 'adminHoursChart',
                totalId: 'adminStatsChartTotal',
                hoursTimeline: adminConfig.hoursTimeline || {},
                defaultRange: adminConfig.hasDateFilter ? 'period' : 'month',
                maxTicksLimit: 7,
                getPeriodSeries: () => {
                    const range = adminConfig.activeRange || {};
                    const days = range.days || [];

                    return {
                        labels: days.map((day) => day.key || window.I3.formatChartDayLabel(day)),
                        data: days.map((day) => day.hours ?? 0),
                        total: range.hours_in_range ?? 0,
                    };
                },
            });
        }
    };

    window.bootLandingCharts = bootLandingCharts;

    if (document.getElementById('weeklyHoursChart') || document.getElementById('adminHoursChart')) {
        bootLandingCharts();
    }
})();
