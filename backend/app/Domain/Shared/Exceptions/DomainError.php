<?php

namespace App\Domain\Shared\Exceptions;

use RuntimeException;

/**
 * Base class for business-rule failures.
 *
 * Each subclass maps to one stable API error code (SRS §9) and an HTTP status.
 * The exception handler renders it as the standard error envelope.
 */
abstract class DomainError extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $context  Safe, user-facing data returned in the error response.
     */
    public function __construct(string $message = '', protected array $context = [])
    {
        parent::__construct($message !== '' ? $message : $this->defaultMessage());
    }

    abstract public function errorCode(): string;

    abstract public function httpStatus(): int;

    abstract protected function defaultMessage(): string;

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return $this->context;
    }
}
