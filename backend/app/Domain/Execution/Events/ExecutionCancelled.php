<?php

namespace App\Domain\Execution\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A record was cancelled (FR-EXE-011). Dispatched only after the change commits (SRS §7.7.1).
 */
final class ExecutionCancelled implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly int $executionId,
        public readonly int $actorId,
    ) {}
}
