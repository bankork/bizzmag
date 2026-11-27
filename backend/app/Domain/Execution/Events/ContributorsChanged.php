<?php

namespace App\Domain\Execution\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * People were added to or removed from a record (FR-ATTR-013, FR-NOT-002). Dispatched only after the change commits (SRS §7.7.1).
 */
final class ContributorsChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    /**
     * @param  list<int>  $addedEmployeeIds
     * @param  list<int>  $removedEmployeeIds
     */
    public function __construct(
        public readonly int $executionId,
        public readonly array $addedEmployeeIds,
        public readonly array $removedEmployeeIds,
        public readonly int $actorId,
    ) {}
}
