<?php

namespace App\Domain\Execution\Actions;

use App\Domain\Execution\Support\WorkRecords;
use App\Domain\Identity\Support\SegregationOfDuties;
use App\Domain\Orders\Exceptions\OrderNotOpen;
use App\Domain\Orders\Models\ItemStatus;
use App\Domain\Orders\Models\OrderItem;
use App\Domain\Shared\Audit\AuditEntry;
use App\Domain\Shared\Audit\AuditRecorder;
use App\Domain\Shared\Exceptions\AccessDenied;
use App\Domain\Shared\Exceptions\InvalidTransition;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Removes an item from an open visit, with a reason (FR-ORD-013, §6.2).
 *
 * Nobody has recorded it: order.item.cancel_unexecuted is enough. Someone
 * has: only execution.correct, which cancels the record as well, and not
 * by the person who recorded it (BR-040). A cashier therefore cannot remove
 * recorded work, in the screens or through the API (§14.2).
 *
 * Lives in Execution because it needs to know about recorded work, and the
 * Orders module may not depend on Execution.
 */
final class CancelItem
{
    public function __construct(
        private readonly WorkRecords $work,
        private readonly CancelExecution $executions,
        private readonly SegregationOfDuties $duties,
        private readonly AuditRecorder $audit,
    ) {}

    public function __invoke(User $actor, OrderItem $item, string $reason): OrderItem
    {
        return DB::transaction(function () use ($actor, $item, $reason): OrderItem {
            [$order, $item] = $this->work->lockItem($item);

            if (! $order->isOpen()) {
                throw new OrderNotOpen;
            }

            if (! $item->status->acceptsWork()) {
                throw new InvalidTransition('This item is already off the visit.');
            }

            $active = $this->work->activeFor($item);
            $exempt = false;

            if ($active !== null) {
                if (! $actor->checkPermissionTo('execution.correct')) {
                    throw AccessDenied::missing('execution.correct');
                }

                $exempt = $this->duties->check($actor, [$active->submitted_by, $active->disputed_by]);
                $this->executions->cancel($active, $actor, $reason);
            } elseif (! $actor->checkPermissionTo('order.item.cancel_unexecuted')) {
                throw AccessDenied::missing('order.item.cancel_unexecuted');
            }

            $before = $item->status->value;
            $item->forceFill([
                'status' => ItemStatus::Cancelled,
                'cancelled_by' => $actor->id,
                'cancelled_at' => now(),
                'cancel_reason' => $reason,
            ])->save();

            $this->audit->record(new AuditEntry(
                $active !== null ? 'order.item_cancelled_executed' : 'order.item_cancelled',
                'order_item', $item->id,
                before: ['status' => $before],
                after: ['status' => ItemStatus::Cancelled->value, 'execution_id' => $active?->id],
                reason: $reason, actorId: $actor->id, orderId: $order->id, sodExempt: $exempt,
            ));

            return $item;
        });
    }
}
