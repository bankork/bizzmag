<?php

use App\Http\Controllers\Api\V1\Customers\CustomerController;
use Illuminate\Support\Facades\Route;

/*
| Registered customers (FR-CUS-001 to 009) and removal of their personal
| data (SEC-021), which is reserved for the owner.
*/

Route::middleware(['signed-in', 'permission:customer.view'])->group(function (): void {
    Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('customers/{customer}', [CustomerController::class, 'show'])->whereNumber('customer')->name('customers.show');
});

Route::middleware(['signed-in', 'permission:customer.manage'])->group(function (): void {
    Route::post('customers', [CustomerController::class, 'store'])->name('customers.store');
    Route::patch('customers/{customer}', [CustomerController::class, 'update'])->whereNumber('customer')->name('customers.update');
    Route::post('customers/{customer}/anonymise', [CustomerController::class, 'anonymise'])->whereNumber('customer')->middleware('owner')->name('customers.anonymise');
});
