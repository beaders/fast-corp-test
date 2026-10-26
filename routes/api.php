<?php

use App\Http\Controllers\AuthController;
use App\Http\Middleware\AuthMiddleware;
use Illuminate\Support\Facades\Route;

// These endpoints use cookie sessions, so they also need web middleware and CSRF protection.
Route::middleware('web')->name('auth.')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:10,1,auth-register:')->name('register');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1,auth-login:')->name('login');

    Route::middleware(AuthMiddleware::class)->group(function () {
        Route::get('/me', [AuthController::class, 'me'])->name('me');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    });
});
