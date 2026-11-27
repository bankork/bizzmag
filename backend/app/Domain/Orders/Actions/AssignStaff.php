<?php

namespace App\Domain\Orders\Actions;

use App\Domain\Orders\Events\StaffAssigned;
use App\Domain\Orders\Exceptions\OrderNotOpen;
use App\Domain\Orders\Models\OrderItem;
use App\Domain\Orders\Support\Assignments;
use App\Domain\Orders\Support\EloquentOrderLock;
use App\Domain\Shared\Audit\AuditEntry;
use App\Domain\Shared\Audit\AuditRecorder;
use App\Domain\Shared\Exceptions\InvalidTransition;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Sets who is expected to do a service (FR-ORD-005, FR-ORD-006). An empty
 * list leaves it unassigned. Newly assigned staff are notified.
 */
final class AssignStaff
{
    public function __construct(
        private readonly EloquentOrderLock $orders,
        private readonly Assignments $assignments,
        private readonly AuditRecorder $audit,
    ) {}

    /**
     * @param  list<int>  $employeeIds
     */
    public function __invoke(User $actor, OrderItem $item, array $employeeIds): OrderItem
    {
        return DB::transaction(function () use ($actor, $item, $employeeIds): OrderItem {
            $order = $this->orders->lock($item->service_order_id);
            $item = OrderItem::query()->findOrFail($item->id);

            if (! $order->isOpen()) {
                throw new OrderNotOpen;
            }

            if (! $item->isService() || ! $item->status->acceptsWork()) {
                throw new InvalidTransition('Staff can be assigned only to a service that is still on the visit.');
            }

            $change = $this->assignments->replace($item, $employeeIds, $actor);

            if ($change['before'] !== $change['after']) {
                $this->audit->record(new AuditEntry('order.assigned', 'order_item', $item->id,
                    before: ['employee_ids' => $change['before']], after: ['employee_ids' => $change['after']],
                    actorId: $actor->id, orderId: $order->id));
            }

            if ($change['added'] !== []) {
                StaffAssigned::dispatch($item->id, $change['added'], $actor->id);
            }

            return $item;
        });
    }
}
