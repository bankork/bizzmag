<?php

namespace App\Domain\Shared\Exceptions;

/**
 * A record expected by a domain operation does not exist.
 */
final class RecordNotFound extends DomainError
{
    public function errorCode(): string
    {
        return 'NOT_FOUND';
    }

    public function httpStatus(): int
    {
        return 404;
    }

    protected function defaultMessage(): string
    {
        return 'The record was not found.';
    }
}
