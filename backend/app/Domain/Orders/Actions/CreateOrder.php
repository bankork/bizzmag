<?php

namespace App\Domain\Orders\Actions;

use App\Domain\Business\Models\Business;
use App\Domain\Catalogue\Models\CatalogueItem;
use App\Domain\Customers\Exceptions\CustomerRemoved;
use App\Domain\Customers\Models\Customer;
use App\Domain\Orders\Events\StaffAssigned;
use App\Domain\Orders\Exceptions\ItemNotOrderable;
use App\Domain\Orders\Models\ItemOrigin;
use App\Domain\Orders\Models\ItemStatus;
use App\Domain\Orders\Models\OrderItem;
use App\Domain\Orders\Models\ServiceOrder;
use App\Domain\Orders\Support\Assignments;
use App\Domain\Orders\Support\ItemSnapshot;
use App\Domain\Orders\Support\OrderReference;
use App\Domain\Shared\Audit\AuditEntry;
use App\Domain\Shared\Audit\AuditRecorder;
use App\Domain\Shared\Exceptions\RecordNotFound;
use App\Domain\Shared\Phone\PhoneNumber;
use App\Domain\Shared\Tenancy\CurrentBusiness;
use App\Domain\Shared\Time\BusinessDay;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Opens a visit for a customer or a guest (FR-ORD-001 to 008, FR-CUS-003 to
 * 005, SRS §6.1 create). Each item is snapshotted from the catalogue, and
 * services may be assigned to the staff expected to do them.
 */
final class CreateOrder
{
    public function __construct(
        private readonly CurrentBusiness $business,
        private readonly OrderReference $references,
        private readonly Assignments $assignments,
        private readonly AuditRecorder $audit,
    ) {}

    /**
     * @param  array{customer_id?: ?int, guest_name?: ?string, guest_phone?: ?string}  $who
     * @param  list<array{catalogue_item_id: int, quantity?: int, employee_ids?: list<int>}>  $items
     */
    public function __invoke(User $actor, array $who, array $items): ServiceOrder
    {
        return DB::transaction(function () use ($actor, $who, $items): ServiceOrder {
            $business = Business::query()->findOrFail($this->business->require());
            $customer = $this->customer($who['customer_id'] ?? null);
            $now = CarbonImmutable::now();

            $order = ServiceOrder::query()->create([
                'reference' => $this->references->next($business->id, $now->setTimezone($business->timezone)),
                'customer_id' => $customer?->id,
                'guest_name' => $customer === null ? $this->guestName($who['guest_name'] ?? null) : null,
                'guest_phone_e164' => $customer === null ? PhoneNumber::normalise($who['guest_phone'] ?? null) : null,
                'opened_at' => $now,
                'opened_business_day' => (string) BusinessDay::fromInstant($now, $business->timezone),
                'created_by' => $actor->id,
            ]);

            $added = [];
            $assigned = [];

            foreach ($items as $spec) {
                $item = $this->addItem($order, $spec, $actor);
                $added[] = ['id' => $item->id, 'name' => $item->name_snapshot, 'quantity' => $item->quantity, 'unit_price_kobo' => $item->unit_price_kobo];

                if ($item->isService() && ($spec['employee_ids'] ?? []) !== []) {
                    $assigned[$item->id] = $this->assignments->replace($item, $spec['employee_ids'], $actor)['added'];
                }
            }

            $this->audit->record(new AuditEntry('order.created', 'order', $order->id, after: [
                'reference' => $order->reference,
                'customer_id' => $order->customer_id,
                'guest' => $customer === null,
                'items' => $added,
                'assignments' => $assigned,
            ], actorId: $actor->id, orderId: $order->id));

            foreach ($assigned as $itemId => $employeeIds) {
                StaffAssigned::dispatch($itemId, $employeeIds, $actor->id);
            }

            return $order;
        });
    }

    /**
     * @param  array{catalogue_item_id: int, quantity?: int}  $spec
     */
    private function addItem(ServiceOrder $order, array $spec, User $actor): OrderItem
    {
        $catalogueItem = CatalogueItem::query()->whereKey($spec['catalogue_item_id'])->first() ?? throw new ItemNotOrderable;

        return OrderItem::query()->create([
            ...ItemSnapshot::of($catalogueItem, $spec['quantity'] ?? 1),
            'service_order_id' => $order->id,
            'status' => ItemStatus::Requested,
            'origin' => ItemOrigin::Order,
            'requested_by' => $actor->id,
        ]);
    }

    private function customer(?int $customerId): ?Customer
    {
        if ($customerId === null) {
            return null;
        }

        $customer = Customer::query()->whereKey($customerId)->first() ?? throw new RecordNotFound('That customer was not found.');

        if ($customer->isRemoved()) {
            throw new CustomerRemoved;
        }

        return $customer;
    }

    private function guestName(?string $name): ?string
    {
        $name = $name === null ? '' : trim($name);

        return $name === '' ? null : $name;
    }
}
