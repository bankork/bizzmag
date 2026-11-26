<?php

namespace App\Domain\Identity\Exceptions;

use App\Domain\Shared\Exceptions\DomainError;

/**
 * A user may not disable themselves or reset their own password through user management.
 */
final class SelfActionNotAllowed extends DomainError
{
    public function errorCode(): string
    {
        return 'SELF_ACTION_NOT_ALLOWED';
    }

    public function httpStatus(): int
    {
        return 422;
    }

    protected function defaultMessage(): string
    {
        return 'You cannot do this to your own account.';
    }
}
