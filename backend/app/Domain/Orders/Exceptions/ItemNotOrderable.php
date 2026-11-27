<?php

namespace App\Domain\Orders\Exceptions;

use App\Domain\Shared\Exceptions\DomainError;

/**
 * A catalogue item that cannot be put on a visit (FR-SVC-005).
 */
final class ItemNotOrderable extends DomainError
{
    public function errorCode(): string
    {
        return 'ITEM_NOT_ACTIVE';
    }

    public function httpStatus(): int
    {
        return 422;
    }

    protected function defaultMessage(): string
    {
        return 'This item cannot be chosen. It may be inactive, or a service whose variants must be chosen instead.';
    }
}
