@php
    $ariaLabel = $ariaLabel ?? 'Statistics';
    $projects = $projects ?? [];
    $projectsEmpty = $projectsEmpty ?? 'No data yet.';
    $metrics = $metrics ?? [];
    $chartId = $chartId ?? 'dashboardHoursChart';
    $chartTotalId = $chartTotalId ?? 'dashboardChartTotal';
    $chartRanges = $chartRanges ?? [
        ['range' => 'month', 'label' => 'Month', 'active' => true],
        ['range' => 'week', 'label' => 'Week'],
        ['range' => 'period', 'label' => 'Period'],
        ['range' => 'year', 'label' => 'Year'],
    ];
    $rangeAriaLabel = $rangeAriaLabel ?? 'Hours chart range';
@endphp

<section class="dashboard-stats" aria-label="{{ $ariaLabel }}">
    <div class="dashboard-stats__grid">
        <div class="dashboard-stats__projects">
            <div class="i3-data-table i3-data-table--3col">
                <div class="i3-data-table__head">
                    <span>Projects</span>
                    <span>Billed Hrs</span>
                    <span>Unbilled Hrs</span>
                </div>
                @if(count($projects) > 0)
                    <ul class="i3-data-table__body i3-data-table__body--sm list-unstyled mb-0">
                        @foreach($projects as $project)
                            <li class="i3-data-table__row">
                                <span class="i3-data-table__label">
                                    <span class="i3-hash">#</span> {{ $project['name'] }}
                                </span>
                                <span>{{ number_format($project['billed_hours'], 2) }}</span>
                                <span>{{ number_format($project['unbilled_hours'], 2) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="dashboard-stats__empty">{{ $projectsEmpty }}</p>
                @endif
            </div>
        </div>

        <div class="dashboard-stats__totals">
            <div class="dashboard-stats__all-time-pill">All Time</div>
            <dl class="dashboard-stats__metrics">
                @foreach($metrics as $metric)
                    <div class="dashboard-stats__metric">
                        <dt>{{ $metric['label'] }}</dt>
                        <dd class="wavy-underline">
                            {{ number_format($metric['value'] ?? 0, $metric['decimals'] ?? 0) }}
                        </dd>
                    </div>
                @endforeach
            </dl>
        </div>

        <div class="dashboard-stats__chart-card">
            <div class="dashboard-stats__chart-head">
                <span class="dashboard-stats__chart-title">Hours This</span>
                <div class="dashboard-stats__range" role="group" aria-label="{{ $rangeAriaLabel }}">
                    @foreach($chartRanges as $range)
                        <button type="button"
                                class="dashboard-stats__range-btn{{ !empty($range['active']) ? ' active' : '' }}"
                                data-chart-range="{{ $range['range'] }}">
                            {{ $range['label'] }}
                        </button>
                    @endforeach
                </div>
            </div>
            <div class="dashboard-stats__hours-display">
                <span class="dashboard-stats__hours-value" id="{{ $chartTotalId }}">0.00</span>
            </div>
            <div class="dashboard-stats__chart-wrap">
                <canvas id="{{ $chartId }}" aria-label="Hours over time"></canvas>
            </div>
        </div>
    </div>
</section>
