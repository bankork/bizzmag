<?php

namespace App\Domain\Execution\Exceptions;

use App\Domain\Shared\Exceptions\DomainError;

/**
 * Someone already recorded this service (FR-EXE-007). The response offers joining instead.
 */
final class ExecutionExists extends DomainError
{
    public function errorCode(): string
    {
        return 'EXECUTION_EXISTS';
    }

    public function httpStatus(): int
    {
        return 409;
    }

    protected function defaultMessage(): string
    {
        return 'This service has already been recorded.';
    }
}
