<?php

namespace App\Domain\Orders\Exceptions;

use App\Domain\Shared\Exceptions\DomainError;

/**
 * A quantity above 1 on an item that does not allow it (FR-ORD-004).
 */
final class QuantityNotAllowed extends DomainError
{
    public function errorCode(): string
    {
        return 'QUANTITY_NOT_ALLOWED';
    }

    public function httpStatus(): int
    {
        return 422;
    }

    protected function defaultMessage(): string
    {
        return 'This item is done once per visit, so the quantity must be 1.';
    }
}
