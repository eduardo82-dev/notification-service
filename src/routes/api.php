<?php

declare(strict_types=1);

use App\Http\Controllers\HealthController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Health
    Route::get('/health', HealthController::class)->name('health');

    // Notifications
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::post('/',        [NotificationController::class, 'store'])->name('store');
        Route::get('/{uuid}',  [NotificationController::class, 'show'])->name('show');
    });

    // User notifications
    Route::get('/users/{userId}/notifications', [NotificationController::class, 'userNotifications'])
        ->name('users.notifications.index')
        ->where('userId', '[0-9]+');
});
