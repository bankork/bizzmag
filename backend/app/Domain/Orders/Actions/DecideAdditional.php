<?php

namespace App\Domain\Orders\Actions;

use App\Domain\Identity\Support\SegregationOfDuties;
use App\Domain\Orders\Events\AdditionalServiceDecided;
use App\Domain\Orders\Exceptions\AlreadyDecided;
use App\Domain\Orders\Exceptions\OrderNotOpen;
use App\Domain\Orders\Models\ItemStatus;
use App\Domain\Orders\Models\OrderItem;
use App\Domain\Orders\Support\EloquentOrderLock;
use App\Domain\Shared\Audit\AuditEntry;
use App\Domain\Shared\Audit\AuditRecorder;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Approves or rejects an extra-service request (FR-ADD-003 to 006, §6.2).
 * The decider must not be the requester, unless they are the owner, which
 * is flagged (BR-040). A rejection needs a reason. The requester is told.
 */
final class DecideAdditional
{
    public function __construct(
        private readonly EloquentOrderLock $orders,
        private readonly SegregationOfDuties $duties,
        private readonly AuditRecorder $audit,
    ) {}

    public function approve(User $actor, OrderItem $item, ?string $reason = null): OrderItem
    {
        return $this->decide($actor, $item, ItemStatus::Approved, $reason);
    }

    public function reject(User $actor, OrderItem $item, string $reason): OrderItem
    {
        return $this->decide($actor, $item, ItemStatus::Rejected, $reason);
    }

    private function decide(User $actor, OrderItem $item, ItemStatus $decision, ?string $reason): OrderItem
    {
        return DB::transaction(function () use ($actor, $item, $decision, $reason): OrderItem {
            $order = $this->orders->lock($item->service_order_id);
            $item = OrderItem::query()->findOrFail($item->id);

            if ($item->status !== ItemStatus::PendingApproval) {
                throw new AlreadyDecided;
            }

            if (! $order->isOpen()) {
                throw new OrderNotOpen;
            }

            $exempt = $this->duties->check($actor, [$item->requested_by]);

            $item->forceFill([
                'status' => $decision,
                'decided_by' => $actor->id,
                'decided_at' => now(),
                'decision_reason' => $reason,
            ])->save();

            $action = $decision === ItemStatus::Approved ? 'additional.approved' : 'additional.rejected';

            $this->audit->record(new AuditEntry($action, 'order_item', $item->id,
                before: ['status' => ItemStatus::PendingApproval->value], after: ['status' => $decision->value],
                reason: $reason, actorId: $actor->id, orderId: $order->id, sodExempt: $exempt));

            AdditionalServiceDecided::dispatch($item->id, $decision === ItemStatus::Approved, $actor->id);

            return $item;
        });
    }
}
