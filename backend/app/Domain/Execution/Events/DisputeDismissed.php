<?php

namespace App\Domain\Execution\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A supervisor dismissed a dispute; the record stands (FR-EXE-009). Dispatched only after the change commits (SRS §7.7.1).
 */
final class DisputeDismissed implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly int $executionId,
        public readonly int $actorId,
    ) {}
}
