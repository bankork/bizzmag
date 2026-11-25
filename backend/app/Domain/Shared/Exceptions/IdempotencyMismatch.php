<?php

namespace App\Domain\Shared\Exceptions;

/**
 * The same Idempotency-Key was reused with a different request payload.
 */
final class IdempotencyMismatch extends DomainError
{
    public function errorCode(): string
    {
        return 'IDEMPOTENCY_MISMATCH';
    }

    public function httpStatus(): int
    {
        return 422;
    }

    protected function defaultMessage(): string
    {
        return 'This request key was already used with different details. Start the action again.';
    }
}
