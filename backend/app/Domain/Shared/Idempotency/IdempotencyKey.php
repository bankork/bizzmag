<?php

namespace App\Domain\Shared\Idempotency;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

/**
 * Stored idempotency key. Written only through IdempotencyGuard; this model
 * exists so `model:prune` can remove keys older than the retention period.
 */
class IdempotencyKey extends Model
{
    use MassPrunable;

    public const UPDATED_AT = null;

    protected $guarded = ['*'];

    /**
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        return static::query()->where('created_at', '<', now()->subDays(config('bizzmag.idempotency.retention_days')));
    }
}
