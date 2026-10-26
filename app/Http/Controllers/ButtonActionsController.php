<?php

namespace App\Http\Controllers;

use App\Enums\ActivityEventAction;
use App\Enums\ActivityEventType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ButtonActionsController extends Controller
{
    public function buyCow(Request $request): Response
    {
        $this->recordActivity($request->user('web'), ActivityEventAction::BuyCow);

        return response()->noContent();
    }

    public function download(Request $request): BinaryFileResponse
    {
        $response = response()->download(resource_path('downloads/sample.exe'), 'sample.exe', [
            'Content-Type' => 'application/octet-stream',
        ]);

        $this->recordActivity($request->user('web'), ActivityEventAction::Download);

        return $response;
    }

    private function recordActivity(User $user, ActivityEventAction $action): void
    {
        $user->activityEvents()->create([
            'type' => ActivityEventType::ButtonClick,
            'action' => $action,
            'occurred_at' => now(),
        ]);
    }
}
