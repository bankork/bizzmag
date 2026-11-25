<?php

namespace App\Domain\Shared\Idempotency;

/**
 * The stored outcome of an idempotent operation.
 *
 * The body is what the API returns as `data`. On a replay the same status
 * and body are returned without running the operation again.
 */
final readonly class IdempotentResult
{
    /**
     * @param  array<string, mixed>  $body
     */
    private function __construct(
        public int $status,
        public array $body,
        public bool $replayed,
    ) {}

    /**
     * @param  array<string, mixed>  $body
     */
    public static function fresh(int $status, array $body): self
    {
        return new self($status, $body, false);
    }

    /**
     * @param  array<string, mixed>  $body
     */
    public static function replay(int $status, array $body): self
    {
        return new self($status, $body, true);
    }
}
