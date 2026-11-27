<?php

namespace App\Domain\Notifications\Models;

use App\Domain\Shared\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * One in-app notification for one person (SRS §4.22). Never contains
 * amounts or contact details (FR-NOT-004). Removed after 90 days.
 *
 * @property int $id
 * @property int $business_id
 * @property int $user_id
 * @property string $type
 * @property string $title
 * @property ?string $body
 * @property ?array<string, mixed> $data
 * @property ?Carbon $read_at
 * @property Carbon $created_at
 */
class InAppNotification extends Model
{
    use BelongsToBusiness;
    use MassPrunable;

    public const UPDATED_AT = null;

    public const RETENTION_DAYS = 90;

    protected $table = 'notifications';

    protected $fillable = ['business_id', 'user_id', 'type', 'title', 'body', 'data', 'read_at'];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return ['data' => 'array', 'read_at' => 'datetime', 'created_at' => 'datetime'];
    }

    /**
     * Pruning runs from the scheduler, outside any business.
     *
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        return static::query()->withoutGlobalScopes()->where('created_at', '<', now()->subDays(self::RETENTION_DAYS));
    }
}
