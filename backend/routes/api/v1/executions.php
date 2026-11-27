<?php

use App\Http\Controllers\Api\V1\Execution\ExecutionController;
use Illuminate\Support\Facades\Route;

/*
| Recorded work (SRS §4.6, §4.7, §6.3). Submitting needs an
| Idempotency-Key. Editing contributors is limited to the person who
| recorded the work, and disputing to the people named on it unless the
| user may correct; the actions check both.
*/

Route::middleware('signed-in')->group(function (): void {
    Route::post('order-items/{orderItem}/executions', [ExecutionController::class, 'store'])->whereNumber('orderItem')->middleware(['permission:execution.submit', 'idempotent'])->name('executions.store');
    Route::get('executions/mine', [ExecutionController::class, 'mine'])->middleware('permission:execution.submit|execution.join')->name('executions.mine');
    Route::post('executions/{execution}/join', [ExecutionController::class, 'join'])->whereNumber('execution')->middleware('permission:execution.join')->name('executions.join');
    Route::put('executions/{execution}/contributors', [ExecutionController::class, 'contributors'])->whereNumber('execution')->middleware('permission:execution.submit')->name('executions.contributors');
    Route::post('executions/{execution}/allocation', [ExecutionController::class, 'allocation'])->whereNumber('execution')->middleware('permission:allocation.override')->name('executions.allocation');
    Route::post('executions/{execution}/dispute', [ExecutionController::class, 'dispute'])->whereNumber('execution')->middleware('permission:execution.dispute|execution.correct')->name('executions.dispute');
    Route::post('executions/{execution}/dismiss-dispute', [ExecutionController::class, 'dismissDispute'])->whereNumber('execution')->middleware('permission:execution.correct')->name('executions.dismiss-dispute');
    Route::post('executions/{execution}/correct', [ExecutionController::class, 'correct'])->whereNumber('execution')->middleware('permission:execution.correct')->name('executions.correct');
    Route::post('executions/{execution}/cancel', [ExecutionController::class, 'cancel'])->whereNumber('execution')->middleware('permission:execution.correct')->name('executions.cancel');
});
