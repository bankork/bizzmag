<?php

namespace App\Domain\Shared\Exceptions;

/**
 * A configuration record was changed by someone else after it was loaded (SRS §9).
 */
final class StaleVersion extends DomainError
{
    public function errorCode(): string
    {
        return 'STALE';
    }

    public function httpStatus(): int
    {
        return 409;
    }

    protected function defaultMessage(): string
    {
        return 'Someone else saved changes here first. Reload to see them, then try again.';
    }
}
