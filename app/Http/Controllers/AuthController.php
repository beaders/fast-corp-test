<?php

namespace App\Http\Controllers;

use App\Enums\ActivityEventAction;
use App\Enums\ActivityEventType;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = DB::transaction(function () use ($request): User {
            $user = User::create($request->safe()->only(['name', 'email', 'password']));

            $this->recordActivity($user, ActivityEventAction::Registration);

            return $user->refresh();
        });

        Auth::guard('web')->login($user);

        return response()->json([
            'user' => $user->only(['id', 'name', 'email', 'role']),
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        if (! Auth::guard('web')->attempt($request->validated())) {
            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        $user = $request->user('web');

        $this->recordActivity($user, ActivityEventAction::Login);

        return response()->json([
            'user' => $user->only(['id', 'name', 'email', 'role']),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user('web')->only(['id', 'name', 'email', 'role']),
        ]);
    }

    public function logout(Request $request): Response
    {
        $this->recordActivity($request->user('web'), ActivityEventAction::Logout);

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }

    private function recordActivity(User $user, ActivityEventAction $action): void
    {
        $user->activityEvents()->create([
            'type' => ActivityEventType::Authentication,
            'action' => $action,
            'occurred_at' => now(),
        ]);
    }
}
