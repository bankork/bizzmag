<?php

namespace App\Domain\Orders\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Employees were newly assigned to a service (FR-ORD-005). Dispatched only
 * after the change commits (SRS §7.7.1).
 */
final class StaffAssigned implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    /**
     * @param  list<int>  $employeeIds  Only the newly assigned employees.
     */
    public function __construct(
        public readonly int $orderItemId,
        public readonly array $employeeIds,
        public readonly int $actorId,
    ) {}
}
