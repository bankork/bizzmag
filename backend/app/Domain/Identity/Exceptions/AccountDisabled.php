<?php

namespace App\Domain\Identity\Exceptions;

use App\Domain\Shared\Exceptions\DomainError;

/**
 * A disabled user cannot sign in or use the API (FR-AUTH-005).
 */
final class AccountDisabled extends DomainError
{
    public function errorCode(): string
    {
        return 'ACCOUNT_DISABLED';
    }

    public function httpStatus(): int
    {
        return 403;
    }

    protected function defaultMessage(): string
    {
        return 'This account has been disabled. Speak to the salon owner.';
    }
}
