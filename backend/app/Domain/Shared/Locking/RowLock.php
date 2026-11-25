<?php

namespace App\Domain\Shared\Locking;

use App\Domain\Shared\Exceptions\NotInTransaction;
use App\Domain\Shared\Exceptions\RecordNotFound;
use Illuminate\Database\ConnectionInterface;

/**
 * Pessimistic row locking for aggregate roots (TDR-08).
 *
 * Every command that changes an order locks the order row first, then makes
 * its checks. Commands on the same aggregate therefore run one after
 * another; commands on different aggregates never wait for each other.
 */
final class RowLock
{
    public function __construct(private readonly ConnectionInterface $db) {}

    /**
     * Lock one row with SELECT ... FOR UPDATE and return it.
     */
    public function forUpdate(string $table, int $id): object
    {
        if ($this->db->transactionLevel() === 0) {
            throw new NotInTransaction("Locking {$table} requires a database transaction.");
        }

        $row = $this->db->table($table)->where('id', $id)->lockForUpdate()->first();

        if ($row === null) {
            throw new RecordNotFound;
        }

        return $row;
    }
}
