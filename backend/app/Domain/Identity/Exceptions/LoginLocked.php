<?php

namespace App\Domain\Identity\Exceptions;

use App\Domain\Shared\Exceptions\DomainError;

/**
 * Too many failed sign-ins (SEC-017). Context carries retry_after_seconds.
 */
final class LoginLocked extends DomainError
{
    public function errorCode(): string
    {
        return 'LOGIN_LOCKED';
    }

    public function httpStatus(): int
    {
        return 429;
    }

    protected function defaultMessage(): string
    {
        return 'Too many attempts. Try again later.';
    }
}
