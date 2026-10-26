<?php

use App\Http\Controllers\AuthController;
use App\Http\Middleware\AuthMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::view('/login', 'auth.login')->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1,auth-login:')->name('login.store');
    Route::view('/register', 'auth.register')->name('register');
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:10,1,auth-register:')->name('register.store');
});

Route::middleware(AuthMiddleware::class)->group(function () {
    Route::view('/', 'welcome')->name('home');
    Route::view('/page-a', 'page-a')->name('page-a');
    Route::view('/page-b', 'page-b')->name('page-b');

    Route::get('/download', function () {
        return response()->download(resource_path('downloads/sample.exe'), 'sample.exe', [
            'Content-Type' => 'application/octet-stream',
        ]);
    })->name('download');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
