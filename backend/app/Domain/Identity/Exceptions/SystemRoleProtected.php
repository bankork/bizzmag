<?php

namespace App\Domain\Identity\Exceptions;

use App\Domain\Shared\Exceptions\DomainError;

/**
 * Default roles cannot be deleted, and the Owner role's permissions are fixed.
 */
final class SystemRoleProtected extends DomainError
{
    public function errorCode(): string
    {
        return 'SYSTEM_ROLE_PROTECTED';
    }

    public function httpStatus(): int
    {
        return 409;
    }

    protected function defaultMessage(): string
    {
        return 'This default role cannot be changed in that way.';
    }
}
