<?php

namespace App\Domain\Shared\Tenancy;

use App\Domain\Shared\Exceptions\BusinessScopeMissing;
use Illuminate\Support\Facades\Context;
use Spatie\Permission\PermissionRegistrar;

/**
 * The business the current request or command works in (FR-IAM-007).
 *
 * Set once per request by EnsureBusinessScope, or explicitly by console
 * commands. It also sets the spatie/laravel-permission team, because a
 * role always belongs to one business. Business-owned models read it
 * through BusinessScope, so a query can never return another business's
 * rows.
 */
final class CurrentBusiness
{
    private ?int $id = null;

    public function set(?int $businessId): void
    {
        $this->id = $businessId;
        app(PermissionRegistrar::class)->setPermissionsTeamId($businessId);

        if ($businessId === null) {
            Context::forget('business_id');
        } else {
            Context::add('business_id', $businessId);
        }
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function require(): int
    {
        return $this->id ?? throw new BusinessScopeMissing;
    }

    /**
     * Run a callback inside another business scope, then restore the
     * previous one. Used by console commands and tests.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public function within(int $businessId, callable $callback): mixed
    {
        $previous = $this->id;
        $this->set($businessId);

        try {
            return $callback();
        } finally {
            $this->set($previous);
        }
    }
}
