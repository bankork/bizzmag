<?php

namespace App\Domain\Orders\Models;

/**
 * An order item's lifecycle (SRS §6.2).
 */
enum ItemStatus: string
{
    case Requested = 'requested';
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    /**
     * Requested and Approved items are charged; nothing else is (SRS §1.3).
     */
    public function isBillable(): bool
    {
        return $this === self::Requested || $this === self::Approved;
    }

    /**
     * Work can be recorded against it (FR-EXE-003, FR-EXE-015).
     */
    public function acceptsWork(): bool
    {
        return $this->isBillable() || $this === self::PendingApproval;
    }

    /**
     * @return list<string>
     */
    public static function workable(): array
    {
        return [self::Requested->value, self::Approved->value, self::PendingApproval->value];
    }
}
