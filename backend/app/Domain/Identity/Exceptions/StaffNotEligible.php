<?php

namespace App\Domain\Identity\Exceptions;

use App\Domain\Shared\Exceptions\DomainError;

/**
 * Someone chosen for a service cannot be credited: disabled, not doing
 * salon work, from another business, or listed twice (SRS §14.22).
 */
final class StaffNotEligible extends DomainError
{
    public function errorCode(): string
    {
        return 'STAFF_NOT_ELIGIBLE';
    }

    public function httpStatus(): int
    {
        return 422;
    }

    protected function defaultMessage(): string
    {
        return 'One of the people chosen cannot be added. They may be disabled or not doing salon work.';
    }
}
