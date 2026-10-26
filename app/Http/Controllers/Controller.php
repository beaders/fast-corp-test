<?php

namespace App\Http\Controllers;

use App\Enums\ActivityEventAction;
use App\Enums\ActivityEventType;
use App\Models\ActivityEvent;
use App\Models\User;
use Illuminate\Contracts\View\View;

abstract class Controller
{
    protected function viewPage(string $view, ActivityEventAction $action, ?User $user): View
    {
        ActivityEvent::create([
            'user_id' => $user?->id,
            'type' => ActivityEventType::ViewPage,
            'action' => $action,
            'occurred_at' => now(),
        ]);

        return view($view);
    }
}
