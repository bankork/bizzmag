<?php

namespace App\Domain\Billing\Exceptions;

use App\Domain\Shared\Exceptions\DomainError;

/**
 * A discount would make a line or the invoice negative (FR-DIS-006).
 */
final class InvalidDiscount extends DomainError
{
    public function errorCode(): string
    {
        return 'DISCOUNT_INVALID';
    }

    public function httpStatus(): int
    {
        return 422;
    }

    protected function defaultMessage(): string
    {
        return 'The discount is larger than the amount it applies to.';
    }
}
