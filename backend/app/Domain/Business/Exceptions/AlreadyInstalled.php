<?php

namespace App\Domain\Business\Exceptions;

use App\Domain\Shared\Exceptions\DomainError;

/**
 * app:install was run on an installation that already has a business.
 */
final class AlreadyInstalled extends DomainError
{
    public function errorCode(): string
    {
        return 'ALREADY_INSTALLED';
    }

    public function httpStatus(): int
    {
        return 409;
    }

    protected function defaultMessage(): string
    {
        return 'This installation already has a business.';
    }
}
