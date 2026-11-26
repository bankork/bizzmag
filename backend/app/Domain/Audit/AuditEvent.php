<?php

namespace App\Domain\Audit;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * An append-only audit record (FR-AUD-006).
 *
 * Updating or deleting an event through the application is refused.
 *
 * @property int $id
 * @property ?int $business_id
 * @property ?int $actor_id
 * @property string $action
 * @property ?array<string, mixed> $before
 * @property ?array<string, mixed> $after
 * @property ?CarbonImmutable $created_at
 * @property-read ?User $actor
 */
class AuditEvent extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Audit events cannot be changed.'));
        static::deleting(fn () => throw new LogicException('Audit events cannot be deleted.'));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'before' => 'array',
            'after' => 'array',
            'sod_exempt' => 'boolean',
            'created_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
