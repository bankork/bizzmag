<?php

namespace App\Domain\Orders\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A member of staff asked for an extra service, now waiting for approval
 * (FR-ADD-003). Dispatched only after the change commits.
 */
final class AdditionalServiceRequested implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly int $orderItemId,
        public readonly int $actorId,
    ) {}
}
