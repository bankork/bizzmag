<?php

namespace App\Domain\Identity\Exceptions;

use App\Domain\Shared\Exceptions\DomainError;

/**
 * The change would leave no active user who can manage users and roles.
 */
final class LastAdministrator extends DomainError
{
    public function errorCode(): string
    {
        return 'LAST_ADMINISTRATOR';
    }

    public function httpStatus(): int
    {
        return 409;
    }

    protected function defaultMessage(): string
    {
        return 'At least one active person must be able to manage staff and roles.';
    }
}
