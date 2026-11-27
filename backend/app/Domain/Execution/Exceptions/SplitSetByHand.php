<?php

namespace App\Domain\Execution\Exceptions;

use App\Domain\Shared\Exceptions\DomainError;

/**
 * People cannot join or leave a record whose split a supervisor set by hand, because it cannot be recalculated.
 */
final class SplitSetByHand extends DomainError
{
    public function errorCode(): string
    {
        return 'SPLIT_SET_BY_HAND';
    }

    public function httpStatus(): int
    {
        return 409;
    }

    protected function defaultMessage(): string
    {
        return 'A supervisor set this split by hand. Ask a supervisor to change who is on it.';
    }
}
