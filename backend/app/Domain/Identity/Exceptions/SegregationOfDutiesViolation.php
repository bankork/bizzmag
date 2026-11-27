<?php

namespace App\Domain\Identity\Exceptions;

use App\Domain\Shared\Exceptions\DomainError;

/**
 * BR-040: a person cannot approve, correct or resolve their own request or work.
 */
final class SegregationOfDutiesViolation extends DomainError
{
    public function errorCode(): string
    {
        return 'SEGREGATION_OF_DUTIES';
    }

    public function httpStatus(): int
    {
        return 403;
    }

    protected function defaultMessage(): string
    {
        return 'Someone else must do this, because it concerns your own request or work.';
    }
}
