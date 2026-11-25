<?php

namespace App\Domain\Shared\Sequences;

use App\Domain\Shared\Exceptions\NotInTransaction;
use Illuminate\Database\ConnectionInterface;

/**
 * Gap-free numbers for invoices, receipts and order references (TDR-10).
 *
 * The sequence row is locked and incremented inside the caller's
 * transaction. If the business operation rolls back, so does the number,
 * which is what keeps invoice and receipt numbers free of gaps.
 */
final class Sequence
{
    public function __construct(private readonly ConnectionInterface $db) {}

    /**
     * Take the next number for a sequence.
     *
     * @param  string  $sequenceKey  For example "invoice", "receipt" or "order".
     * @param  string  $period  For example "2026" (yearly reset) or "261125" (daily). Empty for no reset.
     */
    public function next(string $sequenceKey, string $period = '', int $businessId = 0): int
    {
        if ($this->db->transactionLevel() === 0) {
            throw new NotInTransaction('Sequence numbers must be taken inside a database transaction.');
        }

        $identity = [
            'business_id' => $businessId,
            'sequence_key' => $sequenceKey,
            'period' => $period,
        ];

        // Lock first. Only create the row when it is missing: running INSERT IGNORE
        // on every call takes a shared lock that deadlocks concurrent callers.
        $row = $this->db->table('number_sequences')->where($identity)->lockForUpdate()->first();

        if ($row === null) {
            $this->db->table('number_sequences')->insertOrIgnore($identity + [
                'next_value' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $row = $this->db->table('number_sequences')->where($identity)->lockForUpdate()->first();
        }

        $value = (int) $row->next_value;

        $this->db->table('number_sequences')->where('id', $row->id)->update([
            'next_value' => $value + 1,
            'updated_at' => now(),
        ]);

        return $value;
    }
}
