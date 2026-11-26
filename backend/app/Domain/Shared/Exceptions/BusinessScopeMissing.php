<?php

namespace App\Domain\Shared\Exceptions;

/**
 * Code tried to work with business-owned data without a current business.
 * This is a programming error, so it is reported as a server error.
 */
final class BusinessScopeMissing extends DomainError
{
    public function errorCode(): string
    {
        return 'BUSINESS_SCOPE_MISSING';
    }

    public function httpStatus(): int
    {
        return 500;
    }

    protected function defaultMessage(): string
    {
        return 'No business is selected for this operation.';
    }
}
