<?php

namespace App\Domain\Execution\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A record was replaced by a corrected one (FR-EXE-010, FR-EXE-011). Dispatched only after the change commits (SRS §7.7.1).
 */
final class ExecutionCorrected implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly int $supersededId,
        public readonly int $replacementId,
        public readonly int $actorId,
    ) {}
}
