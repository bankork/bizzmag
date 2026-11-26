<?php

namespace App\Domain\Identity\Exceptions;

use App\Domain\Shared\Exceptions\DomainError;

/**
 * The user is not an active member of any business (FR-IAM-007).
 */
final class NoBusinessAccess extends DomainError
{
    public function errorCode(): string
    {
        return 'NO_BUSINESS_ACCESS';
    }

    public function httpStatus(): int
    {
        return 403;
    }

    protected function defaultMessage(): string
    {
        return 'Your account is not linked to a salon. Speak to the salon owner.';
    }
}
