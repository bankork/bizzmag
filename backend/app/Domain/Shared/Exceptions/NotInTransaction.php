<?php

namespace App\Domain\Shared\Exceptions;

/**
 * Raised when code that relies on row locks or gap-free numbering is called
 * outside a database transaction. This is a programming error, not a user error.
 */
final class NotInTransaction extends DomainError
{
    public function errorCode(): string
    {
        return 'INTERNAL_INVARIANT';
    }

    public function httpStatus(): int
    {
        return 500;
    }

    protected function defaultMessage(): string
    {
        return 'This operation must run inside a database transaction.';
    }
}
