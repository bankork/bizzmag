<?php

use App\Http\Controllers\Api\V1\Business\LogoController;
use App\Http\Controllers\Api\V1\Business\SettingsController;
use Illuminate\Support\Facades\Route;

/*
| Business profile, settings and tax (FR-BIZ-001 to 006, FR-TAX-001).
*/

Route::get('businesses/{business}/logo', LogoController::class)->whereNumber('business')->name('business.logo');

Route::middleware(['signed-in', 'permission:settings.manage'])->group(function (): void {
    Route::get('settings', [SettingsController::class, 'show'])->name('settings.show');
    Route::patch('settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('settings/logo', [SettingsController::class, 'uploadLogo'])->name('settings.logo');
    Route::get('settings/tax', [SettingsController::class, 'showTax'])->name('settings.tax.show');
    Route::patch('settings/tax', [SettingsController::class, 'updateTax'])->name('settings.tax.update');
    Route::get('settings/history', [SettingsController::class, 'history'])->name('settings.history');
});
