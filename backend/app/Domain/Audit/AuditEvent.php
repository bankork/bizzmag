<?php

namespace App\Domain\Audit;

use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * An append-only audit record (FR-AUD-006).
 *
 * Updating or deleting an event through the application is refused.
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
}
