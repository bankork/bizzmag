<?php

namespace App\Domain\Execution\Actions;

use App\Domain\Execution\Exceptions\ItemRecorded;
use App\Domain\Execution\Support\WorkRecords;
use App\Domain\Orders\Exceptions\AlreadyDecided;
use App\Domain\Orders\Exceptions\OrderNotOpen;
use App\Domain\Orders\Models\ItemStatus;
use App\Domain\Orders\Models\OrderItem;
use App\Domain\Shared\Audit\AuditEntry;
use App\Domain\Shared\Audit\AuditRecorder;
use App\Domain\Shared\Exceptions\AccessDenied;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * The person who asked for an extra service takes the request back before
 * anyone decides (FR-ADD-003, §6.2 Pending → Cancelled). The desk can too,
 * with order.item.cancel_unexecuted. Once work has been recorded on it, only
 * a supervisor can remove it.
 */
final class WithdrawAdditional
{
    public function __construct(
        private readonly WorkRecords $work,
        private readonly AuditRecorder $audit,
    ) {}

    public function __invoke(User $actor, OrderItem $item, ?string $reason = null): OrderItem
    {
        return DB::transaction(function () use ($actor, $item, $reason): OrderItem {
            [$order, $item] = $this->work->lockItem($item);

            if ($item->requested_by !== $actor->id && ! $actor->checkPermissionTo('order.item.cancel_unexecuted')) {
                throw AccessDenied::missing('order.item.cancel_unexecuted');
            }

            if ($item->status !== ItemStatus::PendingApproval) {
                throw new AlreadyDecided;
            }

            if (! $order->isOpen()) {
                throw new OrderNotOpen;
            }

            if ($this->work->activeFor($item) !== null) {
                throw new ItemRecorded;
            }

            $item->forceFill([
                'status' => ItemStatus::Cancelled,
                'cancelled_by' => $actor->id,
                'cancelled_at' => now(),
                'cancel_reason' => $reason ?? 'Withdrawn before a decision.',
            ])->save();

            $this->audit->record(new AuditEntry('additional.withdrawn', 'order_item', $item->id,
                before: ['status' => ItemStatus::PendingApproval->value], after: ['status' => ItemStatus::Cancelled->value],
                reason: $reason, actorId: $actor->id, orderId: $order->id));

            return $item;
        });
    }
}
