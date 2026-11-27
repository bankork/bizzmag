<?php

namespace App\Domain\Execution\Actions;

use App\Domain\Execution\Models\Execution;
use App\Domain\Orders\Exceptions\OrderNotOpen;
use App\Domain\Orders\Models\ItemStatus;
use App\Domain\Orders\Models\OrderItem;
use App\Domain\Orders\Models\OrderStatus;
use App\Domain\Orders\Models\ServiceOrder;
use App\Domain\Orders\Support\EloquentOrderLock;
use App\Domain\Shared\Audit\AuditEntry;
use App\Domain\Shared\Audit\AuditRecorder;
use App\Domain\Shared\Exceptions\AccessDenied;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Cancels an abandoned visit with a reason (FR-ORD-014, SRS §6.1 → Cancelled).
 * Its items are cancelled; if any service was recorded, execution.correct
 * is needed as well and those records are cancelled with the same reason.
 *
 * A visit with money paid cannot be cancelled until it is refunded; that
 * check joins this action when payments arrive in P5.
 */
final class CancelOrder
{
    public function __construct(
        private readonly EloquentOrderLock $orders,
        private readonly CancelExecution $executions,
        private readonly AuditRecorder $audit,
    ) {}

    public function __invoke(User $actor, ServiceOrder $order, string $reason): ServiceOrder
    {
        return DB::transaction(function () use ($actor, $order, $reason): ServiceOrder {
            $order = $this->orders->lock($order->id);

            if (! $order->isOpen()) {
                throw new OrderNotOpen;
            }

            $itemIds = OrderItem::query()->where('service_order_id', $order->id)->pluck('id');
            $active = Execution::query()->whereIn('order_item_id', $itemIds)->active()->lockForUpdate()->get();

            if ($active->isNotEmpty() && ! $actor->checkPermissionTo('execution.correct')) {
                throw AccessDenied::missing('execution.correct');
            }

            foreach ($active as $execution) {
                $this->executions->cancel($execution, $actor, $reason);
            }

            $cancelledItems = OrderItem::query()
                ->where('service_order_id', $order->id)
                ->whereIn('status', ItemStatus::workable())
                ->pluck('id')->all();

            OrderItem::query()->whereKey($cancelledItems)->update([
                'status' => ItemStatus::Cancelled->value,
                'cancelled_by' => $actor->id,
                'cancelled_at' => now(),
                'cancel_reason' => $reason,
                'updated_at' => now(),
            ]);

            $order->forceFill([
                'status' => OrderStatus::Cancelled,
                'cancelled_at' => now(),
                'cancelled_by' => $actor->id,
                'cancel_reason' => $reason,
            ])->save();

            $this->audit->record(new AuditEntry('order.cancelled', 'order', $order->id,
                before: ['status' => OrderStatus::Open->value],
                after: ['status' => OrderStatus::Cancelled->value, 'items' => $cancelledItems, 'executions' => $active->pluck('id')->all()],
                reason: $reason, actorId: $actor->id, orderId: $order->id));

            return $order;
        });
    }
}
