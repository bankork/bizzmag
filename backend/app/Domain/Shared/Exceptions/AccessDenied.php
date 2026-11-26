<?php

namespace App\Domain\Shared\Exceptions;

/**
 * The user lacks the permission for this operation (SEC-002). The exception
 * handler records it as a denied attempt (SEC-016). The response carries no
 * data.
 */
final class AccessDenied extends DomainError
{
    public ?string $permission = null;

    public static function missing(string $permission): self
    {
        $error = new self;
        $error->permission = $permission;

        return $error;
    }

    public function errorCode(): string
    {
        return 'FORBIDDEN';
    }

    public function httpStatus(): int
    {
        return 403;
    }

    protected function defaultMessage(): string
    {
        return 'You do not have permission to do this.';
    }
}
