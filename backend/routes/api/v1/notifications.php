<?php

use App\Http\Controllers\Api\V1\Notifications\NotificationController;
use Illuminate\Support\Facades\Route;

/*
| In-app notifications (SRS §4.22). Everyone signed in sees only their own.
*/

Route::middleware('signed-in')->group(function (): void {
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/read', [NotificationController::class, 'read'])->name('notifications.read');
});
