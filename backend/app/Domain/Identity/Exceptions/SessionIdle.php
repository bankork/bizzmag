<?php

namespace App\Domain\Identity\Exceptions;

use App\Domain\Shared\Exceptions\DomainError;

/**
 * The session passed its idle limit (SEC-018) and was ended.
 */
final class SessionIdle extends DomainError
{
    public function errorCode(): string
    {
        return 'SESSION_IDLE';
    }

    public function httpStatus(): int
    {
        return 401;
    }

    protected function defaultMessage(): string
    {
        return 'You were signed out after a period of inactivity. Please sign in again.';
    }
}
