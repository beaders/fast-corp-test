@extends('layouts.analytics')

@section('title', 'Reports')

@section('content')
    <h1>Reports</h1>
    <p class="subtitle">Daily page views and button clicks, grouped in {{ config('app.timezone') }}. Choose up to 366 days; the default range shows the most recent activity.</p>

    @if ($errors->any())
        <div class="errors" role="alert">
            Please check the selected date range.
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="card" aria-label="Report date range">
        <form class="filters" method="get" action="{{ route('page-reports') }}">
            <div class="field">
                <label for="from">From</label>
                <input type="date" id="from" name="from" value="{{ old('from', $filters['from'] ?? '') }}">
            </div>
            <div class="field">
                <label for="to">To</label>
                <input type="date" id="to" name="to" value="{{ old('to', $filters['to'] ?? '') }}">
            </div>
            <div class="filter-actions">
                <button class="primary" type="submit">Apply dates</button>
                <a class="button" href="{{ route('page-reports') }}">Reset</a>
            </div>
        </form>
    </section>

    <div class="summary" aria-label="Totals for the report date range">
        @foreach ($metrics as $key => $metric)
            <div class="summary-item">
                <p><span class="swatch" style="background: {{ $metric['color'] }}"></span>{{ $metric['label'] }}</p>
                <strong>{{ number_format($totals[$key]) }}</strong>
            </div>
        @endforeach
    </div>

    <section class="card" aria-labelledby="chart-heading">
        <div class="card-header">
            <h2 id="chart-heading">Activity over time</h2>
            @if ($days->isNotEmpty())
                <span class="muted">{{ $days->first()['date'] }} – {{ $days->last()['date'] }}</span>
            @endif
        </div>
        @if ($days->isNotEmpty())
            @php
                $maximum = max(1, ...$days->map(fn ($day) => max(array_intersect_key($day, $metrics)))->all());
                $step = max(1, (int) ceil($maximum / 5));
                $yMax = $step * 5;
                $x = fn ($index) => $days->count() === 1 ? 500 : 70 + ($index / ($days->count() - 1)) * 860;
                $y = fn ($count) => 260 - ($count / $yMax) * 220;
                $tickIndexes = array_unique(array_map(fn ($tick) => (int) round($tick * ($days->count() - 1) / 4), range(0, 4)));
            @endphp
            <div class="chart">
                <ul class="chart-legend" aria-label="Chart legend">
                    @foreach ($metrics as $metric)
                        <li><span class="swatch" style="background: {{ $metric['color'] }}"></span>{{ $metric['label'] }}</li>
                    @endforeach
                </ul>
                <div class="chart-plot">
                    <svg viewBox="0 0 1000 325" role="img" aria-labelledby="activity-chart-title activity-chart-description">
                        <title id="activity-chart-title">Daily activity counts</title>
                        <desc id="activity-chart-description">Dates on the horizontal axis and event counts on the vertical axis. Four series show Page view A, Page view B, Buy a cow clicks, and Download clicks. Exact counts are in the daily breakdown table below.</desc>
                        @for ($tick = 0; $tick <= 5; $tick++)
                            <line x1="70" x2="930" y1="{{ $y($tick * $step) }}" y2="{{ $y($tick * $step) }}" stroke="#e8ebf1" />
                            <text x="57" y="{{ $y($tick * $step) + 4 }}" text-anchor="end" font-size="12" fill="#59657a">{{ $tick * $step }}</text>
                        @endfor
                        <text x="18" y="150" transform="rotate(-90 18 150)" text-anchor="middle" font-size="12" fill="#59657a">Count</text>
                        @foreach ($tickIndexes as $index)
                            <text x="{{ $x($index) }}" y="284" text-anchor="middle" font-size="12" fill="#59657a">{{ $days[$index]['date'] }}</text>
                        @endforeach
                        <text x="500" y="316" text-anchor="middle" font-size="12" fill="#59657a">Date</text>
                        @foreach ($metrics as $key => $metric)
                            <polyline data-series="{{ $key }}" points="{{ $days->map(fn ($day, $index) => round($x($index), 2).','.round($y($day[$key]), 2))->implode(' ') }}" fill="none" stroke="{{ $metric['color'] }}" stroke-width="2.5" stroke-linejoin="round" />
                            @foreach ($days as $index => $day)
                                <circle cx="{{ $x($index) }}" cy="{{ $y($day[$key]) }}" r="3.5" fill="{{ $metric['color'] }}">
                                    <title>{{ $day['date'] }} · {{ $metric['label'] }}: {{ $day[$key] }}</title>
                                </circle>
                            @endforeach
                        @endforeach
                    </svg>
                </div>
            </div>
        @else
            <p class="empty">No page views or button clicks recorded for this date range.</p>
        @endif
    </section>

    <section class="card" aria-labelledby="breakdown-heading">
        <div class="card-header">
            <h2 id="breakdown-heading">Daily breakdown</h2>
            <span class="muted">{{ number_format($days->count()) }} {{ Str::plural('day', $days->count()) }}</span>
        </div>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th scope="col">Date</th>
                        @foreach ($metrics as $metric)
                            <th scope="col" class="numeric">{{ $metric['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $day)
                        <tr>
                            <td><time datetime="{{ $day['date'] }}">{{ $day['date'] }}</time></td>
                            @foreach ($metrics as $key => $metric)
                                <td class="numeric">{{ number_format($day[$key]) }}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty">No activity to report yet.</td></tr>
                    @endforelse
                </tbody>
                @if ($days->isNotEmpty())
                    <tfoot>
                        <tr>
                            <th scope="row">Total (all dates in range)</th>
                            @foreach ($metrics as $key => $metric)
                                <td class="numeric"><strong>{{ number_format($totals[$key]) }}</strong></td>
                            @endforeach
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
        @include('partials.analytics-pagination', ['paginator' => $rows, 'label' => 'days'])
    </section>
@endsection
