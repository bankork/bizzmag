<?php

use App\Http\Controllers\Api\V1\Identity\RoleController;
use App\Http\Controllers\Api\V1\Identity\UserController;
use Illuminate\Support\Facades\Route;

/*
| Staff accounts, roles and permissions (FR-AUTH-003 to 005, FR-IAM-003 to 009).
*/

Route::middleware(['signed-in', 'permission:user.manage'])->group(function (): void {
    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::post('users', [UserController::class, 'store'])->name('users.store');
    Route::get('users/{user}', [UserController::class, 'show'])->whereNumber('user')->name('users.show');
    Route::patch('users/{user}', [UserController::class, 'update'])->whereNumber('user')->name('users.update');
    Route::post('users/{user}/disable', [UserController::class, 'disable'])->whereNumber('user')->name('users.disable');
    Route::post('users/{user}/enable', [UserController::class, 'enable'])->whereNumber('user')->name('users.enable');
    Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->whereNumber('user')->name('users.reset-password');
});

Route::middleware(['signed-in', 'permission:role.manage'])->group(function (): void {
    Route::put('users/{user}/roles', [UserController::class, 'roles'])->whereNumber('user')->name('users.roles');
    Route::get('permissions', [RoleController::class, 'permissions'])->name('permissions.index');
    Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
    Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
    Route::patch('roles/{role}', [RoleController::class, 'update'])->whereNumber('role')->name('roles.update');
    Route::delete('roles/{role}', [RoleController::class, 'destroy'])->whereNumber('role')->name('roles.destroy');
});
