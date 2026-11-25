<?php

namespace App\Domain\Shared\Audit;

/**
 * One auditable action, as described by the Action that performed it.
 */
final readonly class AuditEntry
{
    /**
     * @param  string  $action  Dotted action name, for example "invoice.finalised".
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public function __construct(
        public string $action,
        public ?string $subjectType = null,
        public ?int $subjectId = null,
        public ?array $before = null,
        public ?array $after = null,
        public ?string $reason = null,
        public ?int $actorId = null,
        public ?int $businessId = null,
        public ?int $orderId = null,
        public ?int $deviceId = null,
        public bool $sodExempt = false,
    ) {}
}
