<?php

use App\Http\Controllers\Api\V1\Catalogue\CategoryController;
use App\Http\Controllers\Api\V1\Catalogue\ItemController;
use Illuminate\Support\Facades\Route;

/*
| Services, products, variants and categories (FR-SVC-001 to 009).
| Reading is open to everyone signed in, with prices removed for staff who
| may not see them (BR-041). Changes need catalogue.manage.
*/

Route::middleware('signed-in')->group(function (): void {
    Route::get('catalogue/categories', [CategoryController::class, 'index'])->name('catalogue.categories.index');
    Route::get('catalogue/items', [ItemController::class, 'index'])->name('catalogue.items.index');
    Route::get('catalogue/items/{item}', [ItemController::class, 'show'])->whereNumber('item')->name('catalogue.items.show');
});

Route::middleware(['signed-in', 'permission:catalogue.manage'])->group(function (): void {
    Route::post('catalogue/categories', [CategoryController::class, 'store'])->name('catalogue.categories.store');
    Route::post('catalogue/categories/reorder', [CategoryController::class, 'reorder'])->name('catalogue.categories.reorder');
    Route::patch('catalogue/categories/{category}', [CategoryController::class, 'update'])->whereNumber('category')->name('catalogue.categories.update');
    Route::post('catalogue/categories/{category}/deactivate', [CategoryController::class, 'deactivate'])->whereNumber('category')->name('catalogue.categories.deactivate');
    Route::post('catalogue/categories/{category}/activate', [CategoryController::class, 'activate'])->whereNumber('category')->name('catalogue.categories.activate');

    Route::post('catalogue/items', [ItemController::class, 'store'])->name('catalogue.items.store');
    Route::patch('catalogue/items/{item}', [ItemController::class, 'update'])->whereNumber('item')->name('catalogue.items.update');
    Route::post('catalogue/items/{item}/deactivate', [ItemController::class, 'deactivate'])->whereNumber('item')->name('catalogue.items.deactivate');
    Route::post('catalogue/items/{item}/activate', [ItemController::class, 'activate'])->whereNumber('item')->name('catalogue.items.activate');
    Route::get('catalogue/items/{item}/history', [ItemController::class, 'history'])->whereNumber('item')->name('catalogue.items.history');
});
