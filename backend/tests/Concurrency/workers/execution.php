<?php

/**
 * One person's action on one service, started at the shared instant.
 *
 * Arguments: business ID, order item ID, user ID, and "submit" or "cancel".
 * Prints "created", "exists", "cancelled" or "refused:<code>". Any other
 * failure (deadlock, lock timeout) makes the worker fail, so the test
 * cannot hide it.
 */

use App\Domain\Execution\Actions\CancelItem;
use App\Domain\Execution\Actions\SubmitExecution;
use App\Domain\Execution\Exceptions\ExecutionExists;
use App\Domain\Orders\Models\OrderItem;
use App\Domain\Shared\Exceptions\DomainError;
use App\Domain\Shared\Tenancy\CurrentBusiness;
use App\Models\User;

$app = require __DIR__.'/bootstrap.php';

[, $businessId, $itemId, $userId, $command] = $argv;

$app->make(CurrentBusiness::class)->set((int) $businessId);
$user = User::query()->findOrFail((int) $userId);
$item = OrderItem::query()->findOrFail((int) $itemId);

waitForStart();

try {
    if ($command === 'cancel') {
        $app->make(CancelItem::class)($user, $item, 'Customer changed her mind');
        echo 'cancelled';
    } else {
        $app->make(SubmitExecution::class)($user, $item);
        echo 'created';
    }
} catch (ExecutionExists) {
    echo 'exists';
} catch (DomainError $error) {
    echo 'refused:'.$error->errorCode();
}
