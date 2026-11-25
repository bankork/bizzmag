<?php

namespace App\Domain\Shared\Locking;

/**
 * Locks a service order for the duration of the current transaction.
 *
 * Implemented in Phase 3 by the Orders module on top of RowLock. Declared
 * here so Billing, Payments and Execution depend on the contract, not on
 * the Orders implementation.
 */
interface OrderLock
{
    /**
     * Lock the order row FOR UPDATE and return the locked order.
     */
    public function lock(int $orderId): object;
}
