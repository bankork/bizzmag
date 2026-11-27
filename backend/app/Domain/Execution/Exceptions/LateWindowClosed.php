<?php

namespace App\Domain\Execution\Exceptions;

use App\Domain\Shared\Exceptions\DomainError;

/**
 * The late window after finalisation has ended (FR-EXE-013).
 */
final class LateWindowClosed extends DomainError
{
    public function errorCode(): string
    {
        return 'LATE_WINDOW_CLOSED';
    }

    public function httpStatus(): int
    {
        return 403;
    }

    protected function defaultMessage(): string
    {
        return 'It is too late to record work on this visit. Ask a supervisor.';
    }
}
