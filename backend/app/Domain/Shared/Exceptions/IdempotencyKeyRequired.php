<?php

namespace App\Domain\Shared\Exceptions;

/**
 * A protected financial or operational action was sent without an Idempotency-Key.
 */
final class IdempotencyKeyRequired extends DomainError
{
    public function errorCode(): string
    {
        return 'IDEMPOTENCY_KEY_REQUIRED';
    }

    public function httpStatus(): int
    {
        return 422;
    }

    protected function defaultMessage(): string
    {
        return 'This action needs an Idempotency-Key header.';
    }
}
