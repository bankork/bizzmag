<?php

namespace App\Domain\Execution\Models;

/**
 * A recorded service's lifecycle (SRS §6.3).
 */
enum ExecutionStatus: string
{
    case Confirmed = 'confirmed';
    case Disputed = 'disputed';
    case Superseded = 'superseded';
    case Cancelled = 'cancelled';

    /**
     * Active executions count as the item's record of work; at most one per
     * item (FR-EXE-007), enforced by the database.
     */
    public function isActive(): bool
    {
        return $this === self::Confirmed || $this === self::Disputed;
    }
}
