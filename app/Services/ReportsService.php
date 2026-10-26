<?php

namespace App\Services;

use App\Models\ActivityEvent;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriodImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ReportsService
{
    private const MAX_REPORT_DAYS = 366;

    private const DAYS_PER_PAGE = 31;

    private const METRICS = [
        'page_a' => ['label' => 'Page view A', 'type' => 'view-page', 'action' => 'page-a', 'color' => '#2563eb'],
        'page_b' => ['label' => 'Page view B', 'type' => 'view-page', 'action' => 'page-b', 'color' => '#7c3aed'],
        'buy_cow' => ['label' => 'Click “Buy a cow”', 'type' => 'button-click', 'action' => 'buy-cow', 'color' => '#059669'],
        'download' => ['label' => 'Click “Download”', 'type' => 'button-click', 'action' => 'download', 'color' => '#ea580c'],
    ];

    /**
     * @param  array<string, mixed>  $filters
     * @param  array{path: string, query: array<string, mixed>}  $paginationOptions
     * @return array<string, mixed>
     */
    public function getData(array $filters, array $paginationOptions): array
    {
        $eventsQuery = $this->buildEventsQuery($filters);
        [$startDate, $endDate] = $this->resolveDateRange($eventsQuery, $filters);

        if ($startDate !== null && $endDate !== null) {
            $filters = ['from' => $startDate->format('Y-m-d'), 'to' => $endDate->format('Y-m-d')];
            $eventsQuery->where('occurred_at', '>=', $startDate)->where('occurred_at', '<', $endDate->addDay());
        }

        $dailyCountsByDate = $this->getDailyCounts($eventsQuery);
        $dailyRows = $this->buildDailyRows($startDate, $endDate, $dailyCountsByDate);

        return [
            'metrics' => self::METRICS,
            'days' => $dailyRows,
            'rows' => $this->paginateDailyRows($dailyRows, $paginationOptions),
            'totals' => $this->calculateTotals($dailyRows),
            'filters' => $filters,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function buildEventsQuery(array $filters): Builder
    {
        $eventsQuery = ActivityEvent::query()->where(function (Builder $matchingMetricsQuery): void {
            foreach (self::METRICS as $metricDefinition) {
                $matchingMetricsQuery->orWhere(fn (Builder $metricQuery) => $metricQuery
                    ->where('type', $metricDefinition['type'])
                    ->where('action', $metricDefinition['action']));
            }
        });

        if (! empty($filters['from'])) {
            $eventsQuery->where('occurred_at', '>=', CarbonImmutable::parse($filters['from']));
        }

        if (! empty($filters['to'])) {
            $eventsQuery->where('occurred_at', '<', CarbonImmutable::parse($filters['to'])->addDay());
        }

        return $eventsQuery;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{CarbonImmutable|null, CarbonImmutable|null}
     */
    private function resolveDateRange(Builder $eventsQuery, array $filters): array
    {
        $eventDateBounds = (clone $eventsQuery)
            ->selectRaw('MIN(occurred_at) as first_event, MAX(occurred_at) as last_event')
            ->first();

        $startDateValue = $filters['from'] ?? $eventDateBounds->first_event;
        $endDateValue = $filters['to'] ?? $eventDateBounds->last_event;
        $startDate = $startDateValue ? CarbonImmutable::parse($startDateValue)->startOfDay() : null;
        $endDate = $endDateValue ? CarbonImmutable::parse($endDateValue)->startOfDay() : null;
        $maximumDateDifference = self::MAX_REPORT_DAYS - 1;

        if ($startDate === null || $endDate === null || $startDate->diffInDays($endDate) <= $maximumDateDifference) {
            return [$startDate, $endDate];
        }

        if (! empty($filters['from']) && empty($filters['to'])) {
            return [$startDate, $startDate->addDays($maximumDateDifference)];
        }

        return [$endDate->subDays($maximumDateDifference), $endDate];
    }

    private function getDailyCounts(Builder $eventsQuery): Collection
    {
        $eventsQuery->selectRaw('DATE(occurred_at) as date');

        foreach (self::METRICS as $metricKey => $metricDefinition) {
            $eventsQuery->selectRaw(
                "SUM(CASE WHEN type = ? AND action = ? THEN 1 ELSE 0 END) AS {$metricKey}",
                [$metricDefinition['type'], $metricDefinition['action']],
            );
        }

        return $eventsQuery->groupByRaw('DATE(occurred_at)')->orderBy('date')->get()->keyBy('date');
    }

    private function buildDailyRows(?CarbonImmutable $startDate, ?CarbonImmutable $endDate, Collection $dailyCountsByDate): Collection
    {
        $dailyRows = collect();

        if ($startDate === null || $endDate === null) {
            return $dailyRows;
        }

        foreach (CarbonPeriodImmutable::create($startDate, $endDate) as $reportDate) {
            $dailyRow = ['date' => $reportDate->format('Y-m-d')];
            $dailyCounts = $dailyCountsByDate->get($dailyRow['date']);

            foreach (array_keys(self::METRICS) as $metricKey) {
                $dailyRow[$metricKey] = (int) ($dailyCounts?->getAttribute($metricKey) ?? 0);
            }

            $dailyRows->push($dailyRow);
        }

        return $dailyRows;
    }

    private function calculateTotals(Collection $dailyRows): Collection
    {
        $totals = [];

        foreach (array_keys(self::METRICS) as $metricKey) {
            $totals[$metricKey] = $dailyRows->sum($metricKey);
        }

        return collect($totals);
    }

    /**
     * @param  array{path: string, query: array<string, mixed>}  $paginationOptions
     */
    private function paginateDailyRows(Collection $dailyRows, array $paginationOptions): LengthAwarePaginator
    {
        $currentPage = LengthAwarePaginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            $dailyRows->forPage($currentPage, self::DAYS_PER_PAGE)->values(),
            $dailyRows->count(),
            self::DAYS_PER_PAGE,
            $currentPage,
            $paginationOptions,
        );
    }
}
