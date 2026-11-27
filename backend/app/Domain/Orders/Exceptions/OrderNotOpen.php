<?php

namespace App\Domain\Orders\Exceptions;

use App\Domain\Shared\Exceptions\DomainError;

/**
 * The visit is finalised or cancelled (FR-ORD-010).
 */
final class OrderNotOpen extends DomainError
{
    public function errorCode(): string
    {
        return 'ORDER_NOT_OPEN';
    }

    public function httpStatus(): int
    {
        return 409;
    }

    protected function defaultMessage(): string
    {
        return 'This visit is no longer open, so it cannot be changed this way.';
    }
}
