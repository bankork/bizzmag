<?php

namespace App\Domain\Execution\Models;

use App\Domain\Execution\Allocation\AllocationMethod;
use App\Domain\Orders\Models\OrderItem;
use App\Domain\Shared\Tenancy\BelongsToBusiness;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * The confirmed record that a service was done (SRS §1.3, §4.6). Never
 * deleted or edited in place (BR-005): only its status, contributors and
 * split change through the §6.3 transitions, and a correction supersedes it.
 *
 * @property int $id
 * @property int $business_id
 * @property int $order_item_id
 * @property ExecutionStatus $status
 * @property ?int $active_item_key
 * @property int $submitted_by
 * @property Carbon $submitted_at
 * @property bool $late
 * @property ?int $supersedes_id
 * @property AllocationMethod $allocation_method
 * @property ?int $primary_share_bp
 * @property ?string $override_reason
 * @property ?int $overridden_by
 * @property ?Carbon $overridden_at
 * @property ?string $dispute_reason
 * @property ?int $disputed_by
 * @property ?Carbon $disputed_at
 * @property ?string $resolution_reason
 * @property ?int $resolved_by
 * @property ?Carbon $resolved_at
 * @property-read OrderItem $item
 * @property-read User $submitter
 * @property-read ?User $disputer
 * @property-read Collection<int, ExecutionContributor> $contributors
 */
class Execution extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id', 'order_item_id', 'status', 'submitted_by', 'submitted_at', 'late', 'supersedes_id',
        'allocation_method', 'primary_share_bp', 'override_reason', 'overridden_by', 'overridden_at',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'status' => ExecutionStatus::class,
            'submitted_at' => 'datetime',
            'late' => 'boolean',
            'allocation_method' => AllocationMethod::class,
            'primary_share_bp' => 'integer',
            'overridden_at' => 'datetime',
            'disputed_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<OrderItem, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function disputer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disputed_by');
    }

    /**
     * Primary first, then in the order they were added.
     *
     * @return HasMany<ExecutionContributor, $this>
     */
    public function contributors(): HasMany
    {
        return $this->hasMany(ExecutionContributor::class)->orderByDesc('is_primary')->orderBy('id');
    }

    /**
     * @param  Builder<Execution>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereNotNull('active_item_key');
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    public function primaryEmployeeId(): int
    {
        return (int) $this->contributors->firstWhere('is_primary', true)?->employee_id;
    }

    /**
     * @return list<int>
     */
    public function contributorIds(): array
    {
        return $this->contributors->pluck('employee_id')->map(fn ($id) => (int) $id)->values()->all();
    }

    /**
     * Shares by employee, for audit records.
     *
     * @return array<int, int>
     */
    public function shareMap(): array
    {
        return $this->contributors->mapWithKeys(fn (ExecutionContributor $contributor) => [$contributor->employee_id => $contributor->share_bp])->all();
    }
}
