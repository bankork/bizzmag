<?php

namespace App\Domain\Orders\Models;

use App\Domain\Catalogue\Models\ItemType;
use App\Domain\Catalogue\Models\SplitMethod;
use App\Domain\Shared\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * One line on a visit with its catalogue snapshot (FR-ORD-003, §7.4). The
 * snapshot never changes; a price change is a recorded override kept next
 * to the original (FR-ORD-011).
 *
 * @property int $id
 * @property int $business_id
 * @property int $service_order_id
 * @property int $catalogue_item_id
 * @property ItemType $type
 * @property string $name_snapshot
 * @property int $unit_price_kobo
 * @property bool $taxable_snapshot
 * @property int $quantity
 * @property ?SplitMethod $allocation_method_snapshot
 * @property ?int $primary_share_bp_snapshot
 * @property ?int $override_price_kobo
 * @property ?string $override_reason
 * @property ?int $override_by
 * @property ?Carbon $override_at
 * @property ItemStatus $status
 * @property ItemOrigin $origin
 * @property int $requested_by
 * @property ?string $request_note
 * @property ?int $decided_by
 * @property ?Carbon $decided_at
 * @property ?string $decision_reason
 * @property ?int $cancelled_by
 * @property ?Carbon $cancelled_at
 * @property ?string $cancel_reason
 * @property ?Carbon $created_at
 * @property-read ServiceOrder $order
 * @property-read Collection<int, OrderItemAssignment> $assignments
 */
class OrderItem extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id', 'service_order_id', 'catalogue_item_id', 'type', 'name_snapshot', 'unit_price_kobo',
        'taxable_snapshot', 'quantity', 'allocation_method_snapshot', 'primary_share_bp_snapshot', 'status', 'origin',
        'requested_by', 'request_note',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'type' => ItemType::class,
            'unit_price_kobo' => 'integer',
            'taxable_snapshot' => 'boolean',
            'quantity' => 'integer',
            'allocation_method_snapshot' => SplitMethod::class,
            'primary_share_bp_snapshot' => 'integer',
            'override_price_kobo' => 'integer',
            'override_at' => 'datetime',
            'status' => ItemStatus::class,
            'origin' => ItemOrigin::class,
            'decided_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ServiceOrder, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(ServiceOrder::class, 'service_order_id');
    }

    /**
     * @return HasMany<OrderItemAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(OrderItemAssignment::class)->orderBy('id');
    }

    public function isService(): bool
    {
        return $this->type === ItemType::Service;
    }

    /**
     * The price charged per unit: the override when there is one.
     */
    public function effectiveUnitPriceKobo(): int
    {
        return $this->override_price_kobo ?? $this->unit_price_kobo;
    }

    /**
     * Unit price × quantity (FR-ORD-004). Discounts and tax arrive in P4.
     */
    public function lineValueKobo(): int
    {
        return $this->effectiveUnitPriceKobo() * $this->quantity;
    }
}
