<?php

namespace App\Domain\Orders\Actions;

use App\Domain\Catalogue\Models\CatalogueItem;
use App\Domain\Catalogue\Models\ItemType;
use App\Domain\Orders\Events\AdditionalServiceRequested;
use App\Domain\Orders\Exceptions\ItemNotOrderable;
use App\Domain\Orders\Exceptions\OrderNotOpen;
use App\Domain\Orders\Models\ItemOrigin;
use App\Domain\Orders\Models\ItemStatus;
use App\Domain\Orders\Models\OrderItem;
use App\Domain\Orders\Models\ServiceOrder;
use App\Domain\Orders\Support\EloquentOrderLock;
use App\Domain\Orders\Support\ItemSnapshot;
use App\Domain\Shared\Audit\AuditEntry;
use App\Domain\Shared\Audit\AuditRecorder;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Staff ask for an extra catalogue service on an open visit (FR-ADD-001 to
 * 003). It is added at the catalogue price, waits for approval and is not
 * billable until approved. Approvers are notified.
 *
 * Requests on a finalised visit within the late window arrive with
 * amendments in P4 (FR-ADD-011).
 */
final class RequestAdditional
{
    public function __construct(
        private readonly EloquentOrderLock $orders,
        private readonly AuditRecorder $audit,
    ) {}

    public function __invoke(User $actor, ServiceOrder $order, int $catalogueItemId, int $quantity = 1, ?string $note = null): OrderItem
    {
        return DB::transaction(function () use ($actor, $order, $catalogueItemId, $quantity, $note): OrderItem {
            $order = $this->orders->lock($order->id);

            if (! $order->isOpen()) {
                throw new OrderNotOpen;
            }

            $catalogueItem = CatalogueItem::query()->whereKey($catalogueItemId)->first() ?? throw new ItemNotOrderable;

            $item = OrderItem::query()->create([
                ...ItemSnapshot::of($catalogueItem, $quantity, onlyType: ItemType::Service),
                'service_order_id' => $order->id,
                'status' => ItemStatus::PendingApproval,
                'origin' => ItemOrigin::AdditionalRequest,
                'requested_by' => $actor->id,
                'request_note' => $note,
            ]);

            $this->audit->record(new AuditEntry('additional.requested', 'order_item', $item->id, after: [
                'name' => $item->name_snapshot,
                'quantity' => $item->quantity,
                'unit_price_kobo' => $item->unit_price_kobo,
                'note' => $note,
            ], actorId: $actor->id, orderId: $order->id));

            AdditionalServiceRequested::dispatch($item->id, $actor->id);

            return $item;
        });
    }
}
