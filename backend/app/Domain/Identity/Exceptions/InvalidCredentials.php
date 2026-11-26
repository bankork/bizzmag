<?php

namespace App\Domain\Identity\Exceptions;

use App\Domain\Shared\Exceptions\DomainError;

/**
 * Wrong sign-in details. The message never says whether the account exists.
 */
final class InvalidCredentials extends DomainError
{
    public function errorCode(): string
    {
        return 'INVALID_CREDENTIALS';
    }

    public function httpStatus(): int
    {
        return 422;
    }

    protected function defaultMessage(): string
    {
        return 'Those details are not correct. Check them and try again.';
    }
}
