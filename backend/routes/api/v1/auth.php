<?php

use App\Http\Controllers\Api\V1\Auth\MeController;
use App\Http\Controllers\Api\V1\Auth\SessionController;
use Illuminate\Support\Facades\Route;

/*
| Sign-in and the session bootstrap (SEC-023). The web app first calls
| GET /sanctum/csrf-cookie, then POST /api/v1/auth/login.
*/

Route::post('auth/login', [SessionController::class, 'store'])
    ->middleware('throttle:login')
    ->name('auth.login');

Route::middleware('signed-in')->group(function (): void {
    Route::post('auth/logout', [SessionController::class, 'destroy'])->name('auth.logout');
    Route::get('me', [MeController::class, 'show'])->name('me');
    Route::put('me/password', [MeController::class, 'updatePassword'])->name('me.password');
});
