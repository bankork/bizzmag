<?php

namespace App\Domain\Execution\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A record was disputed (FR-EXE-009, FR-EXE-011). Dispatched only after the change commits (SRS §7.7.1).
 */
final class ExecutionDisputed implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly int $executionId,
        public readonly int $actorId,
    ) {}
}
