<?php

namespace App\Domain\Orders\Actions;

use App\Domain\Catalogue\Models\CatalogueItem;
use App\Domain\Orders\Events\StaffAssigned;
use App\Domain\Orders\Exceptions\ItemNotOrderable;
use App\Domain\Orders\Exceptions\OrderNotOpen;
use App\Domain\Orders\Models\ItemOrigin;
use App\Domain\Orders\Models\ItemStatus;
use App\Domain\Orders\Models\OrderItem;
use App\Domain\Orders\Models\ServiceOrder;
use App\Domain\Orders\Support\Assignments;
use App\Domain\Orders\Support\EloquentOrderLock;
use App\Domain\Orders\Support\ItemSnapshot;
use App\Domain\Shared\Audit\AuditEntry;
use App\Domain\Shared\Audit\AuditRecorder;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * The desk adds a service or product to an open visit (FR-ORD-012, §6.2).
 */
final class AddItem
{
    public function __construct(
        private readonly EloquentOrderLock $orders,
        private readonly Assignments $assignments,
        private readonly AuditRecorder $audit,
    ) {}

    /**
     * @param  array{catalogue_item_id: int, quantity?: int, employee_ids?: list<int>}  $spec
     */
    public function __invoke(User $actor, ServiceOrder $order, array $spec): OrderItem
    {
        return DB::transaction(function () use ($actor, $order, $spec): OrderItem {
            $order = $this->orders->lock($order->id);

            if (! $order->isOpen()) {
                throw new OrderNotOpen;
            }

            $catalogueItem = CatalogueItem::query()->whereKey($spec['catalogue_item_id'])->first() ?? throw new ItemNotOrderable;

            $item = OrderItem::query()->create([
                ...ItemSnapshot::of($catalogueItem, $spec['quantity'] ?? 1),
                'service_order_id' => $order->id,
                'status' => ItemStatus::Requested,
                'origin' => ItemOrigin::DeskAdd,
                'requested_by' => $actor->id,
            ]);

            $assigned = $item->isService() && ($spec['employee_ids'] ?? []) !== []
                ? $this->assignments->replace($item, $spec['employee_ids'], $actor)['added']
                : [];

            $this->audit->record(new AuditEntry('order.item_added', 'order_item', $item->id, after: [
                'name' => $item->name_snapshot,
                'quantity' => $item->quantity,
                'unit_price_kobo' => $item->unit_price_kobo,
                'assignments' => $assigned,
            ], actorId: $actor->id, orderId: $order->id));

            if ($assigned !== []) {
                StaffAssigned::dispatch($item->id, $assigned, $actor->id);
            }

            return $item;
        });
    }
}
