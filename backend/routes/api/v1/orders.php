<?php

use App\Http\Controllers\Api\V1\Orders\ApprovalController;
use App\Http\Controllers\Api\V1\Orders\CustomerOrdersController;
use App\Http\Controllers\Api\V1\Orders\OrderController;
use App\Http\Controllers\Api\V1\Orders\OrderItemController;
use App\Http\Controllers\Api\V1\Orders\StaffDirectoryController;
use Illuminate\Support\Facades\Route;

/*
| Visits and their items (SRS §4.5, §4.8, §6.1, §6.2). Staff with only
| order.view_open see open visits without prices or contact details
| (BR-041). Cancelling an item that has recorded work needs
| execution.correct, checked by the action.
*/

Route::middleware(['signed-in', 'permission:order.view_open|order.view_all'])->group(function (): void {
    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [OrderController::class, 'show'])->whereNumber('order')->name('orders.show');
    Route::get('staff-directory', StaffDirectoryController::class)->name('staff-directory');
});

Route::middleware('signed-in')->group(function (): void {
    Route::post('orders', [OrderController::class, 'store'])->middleware('permission:order.create')->name('orders.store');
    Route::post('orders/{order}/items', [OrderController::class, 'addItem'])->whereNumber('order')->middleware('permission:order.item.add')->name('orders.items.store');
    Route::post('orders/{order}/additional-requests', [OrderController::class, 'requestAdditional'])->whereNumber('order')->middleware('permission:additional.request')->name('orders.additional-requests.store');
    Route::post('orders/{order}/customer', [OrderController::class, 'associateCustomer'])->whereNumber('order')->middleware('permission:customer.associate')->name('orders.customer');
    Route::post('orders/{order}/cancel', [OrderController::class, 'cancel'])->whereNumber('order')->middleware('permission:order.cancel')->name('orders.cancel');

    Route::put('order-items/{orderItem}/assignments', [OrderItemController::class, 'assignments'])->whereNumber('orderItem')->middleware('permission:order.assign')->name('order-items.assignments');
    Route::post('order-items/{orderItem}/cancel', [OrderItemController::class, 'cancel'])->whereNumber('orderItem')->middleware('permission:order.item.cancel_unexecuted|execution.correct')->name('order-items.cancel');
    Route::post('order-items/{orderItem}/price-override', [OrderItemController::class, 'priceOverride'])->whereNumber('orderItem')->middleware('permission:order.item.price_override')->name('order-items.price-override');
    Route::post('order-items/{orderItem}/approve', [OrderItemController::class, 'approve'])->whereNumber('orderItem')->middleware('permission:additional.decide')->name('order-items.approve');
    Route::post('order-items/{orderItem}/reject', [OrderItemController::class, 'reject'])->whereNumber('orderItem')->middleware('permission:additional.decide')->name('order-items.reject');
    Route::post('order-items/{orderItem}/withdraw', [OrderItemController::class, 'withdraw'])->whereNumber('orderItem')->middleware('permission:additional.request|order.item.cancel_unexecuted')->name('order-items.withdraw');

    Route::get('approvals', [ApprovalController::class, 'index'])->middleware('permission:additional.decide')->name('approvals.index');
    Route::get('customers/{customer}/orders', CustomerOrdersController::class)->whereNumber('customer')->middleware('permission:order.view_all')->name('customers.orders');
});
