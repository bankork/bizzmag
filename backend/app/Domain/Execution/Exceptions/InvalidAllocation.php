<?php

namespace App\Domain\Execution\Exceptions;

use App\Domain\Shared\Exceptions\DomainError;

/**
 * The contributors or shares for a service break the allocation rules
 * (BR-010, FR-ATTR-002/003, DR-008).
 */
final class InvalidAllocation extends DomainError
{
    public function errorCode(): string
    {
        return 'ALLOCATION_INVALID';
    }

    public function httpStatus(): int
    {
        return 422;
    }

    protected function defaultMessage(): string
    {
        return 'The staff credit split is not valid.';
    }
}
