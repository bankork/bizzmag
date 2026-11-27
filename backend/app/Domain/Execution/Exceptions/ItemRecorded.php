<?php

namespace App\Domain\Execution\Exceptions;

use App\Domain\Shared\Exceptions\DomainError;

/**
 * The item has a recorded service, so only a supervisor's correction can remove it (FR-ORD-013).
 */
final class ItemRecorded extends DomainError
{
    public function errorCode(): string
    {
        return 'ITEM_RECORDED';
    }

    public function httpStatus(): int
    {
        return 409;
    }

    protected function defaultMessage(): string
    {
        return 'Someone has already recorded this service. Ask a supervisor.';
    }
}
