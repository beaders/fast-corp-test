<?php

namespace App\Services;

use App\Models\ActivityEvent;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriodImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class ReportsService
{
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
        $query = ActivityEvent::query()->where(function (Builder $query): void {
            foreach (self::METRICS as $metric) {
                $query->orWhere(fn (Builder $query) => $query->where('type', $metric['type'])->where('action', $metric['action']));
            }
        });

        if (! empty($filters['from'])) {
            $query->where('occurred_at', '>=', CarbonImmutable::parse($filters['from']));
        }

        if (! empty($filters['to'])) {
            $query->where('occurred_at', '<', CarbonImmutable::parse($filters['to'])->addDay());
        }

        $bounds = (clone $query)->selectRaw('MIN(occurred_at) as first_event, MAX(occurred_at) as last_event')->first();
        $from = $filters['from'] ?? ($bounds->first_event ? CarbonImmutable::parse($bounds->first_event)->format('Y-m-d') : null);
        $to = $filters['to'] ?? ($bounds->last_event ? CarbonImmutable::parse($bounds->last_event)->format('Y-m-d') : null);

        if ($from && $to) {
            $from = CarbonImmutable::parse($from);
            $to = CarbonImmutable::parse($to);

            if ($from->diffInDays($to) > 365) {
                if (! empty($filters['from']) && empty($filters['to'])) {
                    $to = $from->addDays(365);
                } else {
                    $from = $to->subDays(365);
                }
            }

            $filters = ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')];
            $query->where('occurred_at', '>=', $from)->where('occurred_at', '<', $to->addDay());
        }

        $query->selectRaw('DATE(occurred_at) as date');

        foreach (self::METRICS as $key => $metric) {
            $query->selectRaw("SUM(CASE WHEN type = ? AND action = ? THEN 1 ELSE 0 END) AS {$key}", [$metric['type'], $metric['action']]);
        }

        $counts = $query->groupByRaw('DATE(occurred_at)')->orderBy('date')->get()->keyBy('date');
        $days = collect();

        if ($from && $to) {
            foreach (CarbonPeriodImmutable::create($from, $to) as $date) {
                $day = ['date' => $date->format('Y-m-d')];

                foreach (self::METRICS as $key => $metric) {
                    $day[$key] = (int) ($counts->get($day['date'])?->getAttribute($key) ?? 0);
                }

                $days->push($day);
            }
        }

        $totals = collect(self::METRICS)->map(fn (array $metric, string $key) => $days->sum($key));

        return [
            'metrics' => self::METRICS,
            'days' => $days,
            'rows' => new LengthAwarePaginator(
                $days->forPage(LengthAwarePaginator::resolveCurrentPage(), 31)->values(),
                $days->count(),
                31,
                null,
                $paginationOptions,
            ),
            'totals' => $totals,
            'filters' => $filters,
        ];
    }
}
