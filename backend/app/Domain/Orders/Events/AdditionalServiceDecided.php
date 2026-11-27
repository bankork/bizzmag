<?php

namespace App\Domain\Orders\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * An extra-service request was approved or rejected (FR-ADD-005, FR-ADD-009).
 * Dispatched only after the change commits.
 */
final class AdditionalServiceDecided implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly int $orderItemId,
        public readonly bool $approved,
        public readonly int $actorId,
    ) {}
}
