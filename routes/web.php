<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PagesController;
use App\Http\Middleware\AuthMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1,auth-login:')->name('login.store');
    Route::get('/register', [AuthController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:10,1,auth-register:')->name('register.store');
});

Route::middleware(AuthMiddleware::class)->group(function () {
    Route::redirect('/', '/page-a');
    Route::get('/page-a', [PagesController::class, 'pageA'])->name('page-a');
    Route::get('/page-b', [PagesController::class, 'pageB'])->name('page-b');

    Route::get('/download', function () {
        return response()->download(resource_path('downloads/sample.exe'), 'sample.exe', [
            'Content-Type' => 'application/octet-stream',
        ]);
    })->name('download');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
