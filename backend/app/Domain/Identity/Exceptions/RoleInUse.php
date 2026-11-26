<?php

namespace App\Domain\Identity\Exceptions;

use App\Domain\Shared\Exceptions\DomainError;

/**
 * A role that is still assigned to users cannot be deleted.
 */
final class RoleInUse extends DomainError
{
    public function errorCode(): string
    {
        return 'ROLE_IN_USE';
    }

    public function httpStatus(): int
    {
        return 409;
    }

    protected function defaultMessage(): string
    {
        return 'Remove this role from everyone who has it before deleting it.';
    }
}
