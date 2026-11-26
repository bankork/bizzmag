<?php

namespace App\Domain\Customers\Exceptions;

use App\Domain\Shared\Exceptions\DomainError;

/**
 * The customer's personal data was removed (SEC-021), so the record can no
 * longer be edited.
 */
final class CustomerRemoved extends DomainError
{
    public function errorCode(): string
    {
        return 'CUSTOMER_REMOVED';
    }

    public function httpStatus(): int
    {
        return 409;
    }

    protected function defaultMessage(): string
    {
        return 'This customer\'s personal data was removed, so the record cannot be changed.';
    }
}
