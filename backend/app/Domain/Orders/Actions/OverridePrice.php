<?php

namespace App\Domain\Orders\Actions;

use App\Domain\Orders\Exceptions\OrderNotOpen;
use App\Domain\Orders\Models\OrderItem;
use App\Domain\Orders\Support\EloquentOrderLock;
use App\Domain\Shared\Audit\AuditEntry;
use App\Domain\Shared\Audit\AuditRecorder;
use App\Domain\Shared\Exceptions\InvalidTransition;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Changes the price of one item on a visit, with a reason (FR-ORD-011).
 * The catalogue snapshot stays next to the override; reconciliation shows
 * it as R8 from P4.
 */
final class OverridePrice
{
    public function __construct(
        private readonly EloquentOrderLock $orders,
        private readonly AuditRecorder $audit,
    ) {}

    public function __invoke(User $actor, OrderItem $item, int $priceKobo, string $reason): OrderItem
    {
        return DB::transaction(function () use ($actor, $item, $priceKobo, $reason): OrderItem {
            $order = $this->orders->lock($item->service_order_id);
            $item = OrderItem::query()->findOrFail($item->id);

            if (! $order->isOpen()) {
                throw new OrderNotOpen;
            }

            if (! $item->status->acceptsWork()) {
                throw new InvalidTransition('This item is no longer on the visit, so its price cannot change.');
            }

            $before = ['unit_price_kobo' => $item->unit_price_kobo, 'override_price_kobo' => $item->override_price_kobo];

            $item->forceFill([
                'override_price_kobo' => $priceKobo,
                'override_reason' => $reason,
                'override_by' => $actor->id,
                'override_at' => now(),
            ])->save();

            $this->audit->record(new AuditEntry('order.item_price_overridden', 'order_item', $item->id,
                before: $before, after: ['unit_price_kobo' => $item->unit_price_kobo, 'override_price_kobo' => $priceKobo],
                reason: $reason, actorId: $actor->id, orderId: $order->id));

            return $item;
        });
    }
}
