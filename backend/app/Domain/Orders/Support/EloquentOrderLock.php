<?php

namespace App\Domain\Orders\Support;

use App\Domain\Orders\Models\ServiceOrder;
use App\Domain\Shared\Exceptions\NotInTransaction;
use App\Domain\Shared\Exceptions\RecordNotFound;
use App\Domain\Shared\Locking\OrderLock;
use Illuminate\Support\Facades\DB;

/**
 * The order lock (SRS §7.7.2): SELECT ... FOR UPDATE on the visit's row,
 * inside the business scope. Every command on a visit takes it first and
 * makes its checks afterwards, so commands on one visit run one at a time.
 *
 * Commands take the lock before reading anything else in their transaction:
 * InnoDB fixes a transaction's read snapshot at its first plain read, and a
 * snapshot older than the lock could miss work the previous holder just
 * committed.
 */
final class EloquentOrderLock implements OrderLock
{
    public function lock(int $orderId): ServiceOrder
    {
        if (DB::transactionLevel() === 0) {
            throw new NotInTransaction('Locking a visit requires a database transaction.');
        }

        return ServiceOrder::query()->whereKey($orderId)->lockForUpdate()->first() ?? throw new RecordNotFound;
    }
}
