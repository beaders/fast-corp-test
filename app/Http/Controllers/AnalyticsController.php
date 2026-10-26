<?php

namespace App\Http\Controllers;

use App\Enums\ActivityEventAction;
use App\Http\Requests\ReportsRequest;
use App\Http\Requests\StatisticsRequest;
use App\Services\ReportsService;
use App\Services\StatisticsService;
use Illuminate\Contracts\View\View;

class AnalyticsController extends Controller
{
    public function stats(StatisticsRequest $request, StatisticsService $statistics): View
    {
        return $this->viewPage('page-stats', ActivityEventAction::PageStats, $request->user('web'))
            ->with($statistics->getData($request->validated()));
    }

    public function reports(ReportsRequest $request, ReportsService $reports): View
    {
        return $this->viewPage('page-reports', ActivityEventAction::PageReports, $request->user('web'))
            ->with($reports->getData($request->validated(), [
                'path' => $request->url(),
                'query' => $request->query(),
            ]));
    }
}
