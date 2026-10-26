<?php

namespace App\Services;

use App\Enums\ActivityEventType;
use App\Models\ActivityEvent;
use App\Models\User;
use Carbon\CarbonImmutable;

class StatisticsService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getData(array $filters): array
    {
        $snapshot = $filters['snapshot'] ?? ActivityEvent::max('id');
        $query = ActivityEvent::query()->with('user:id,name,email')->where('id', '<=', $snapshot);

        if (! empty($filters['date'])) {
            $date = CarbonImmutable::parse($filters['date']);
            $query->where('occurred_at', '>=', $date)->where('occurred_at', '<', $date->addDay());
        }

        if (isset($filters['user'])) {
            $filters['user'] === 'guest'
                ? $query->whereNull('user_id')
                : $query->where('user_id', $filters['user']);
        }

        if (! empty($filters['action'])) {
            if (in_array($filters['action'], ['login', 'logout', 'registration'], true)) {
                $query->where('type', ActivityEventType::Authentication)->where('action', $filters['action']);
            } else {
                $query->where('type', $filters['action']);
            }
        }

        return [
            'events' => $query->orderByDesc('occurred_at')->orderByDesc('id')->paginate(25)->withQueryString()->appends('snapshot', $snapshot),
            'users' => User::query()->orderBy('name')->orderBy('id')->get(['id', 'name', 'email']),
            'filters' => $filters,
        ];
    }
}
