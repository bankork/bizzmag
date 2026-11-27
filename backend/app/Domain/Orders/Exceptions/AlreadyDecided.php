<?php

namespace App\Domain\Orders\Exceptions;

use App\Domain\Shared\Exceptions\DomainError;

/**
 * An additional-service request that is no longer waiting for a decision (FR-ADD-003).
 */
final class AlreadyDecided extends DomainError
{
    public function errorCode(): string
    {
        return 'ALREADY_DECIDED';
    }

    public function httpStatus(): int
    {
        return 409;
    }

    protected function defaultMessage(): string
    {
        return 'This request has already been decided or withdrawn.';
    }
}
