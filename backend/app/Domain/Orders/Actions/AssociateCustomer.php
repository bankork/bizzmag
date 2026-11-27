<?php

namespace App\Domain\Orders\Actions;

use App\Domain\Customers\Exceptions\CustomerRemoved;
use App\Domain\Customers\Models\Customer;
use App\Domain\Orders\Models\OrderStatus;
use App\Domain\Orders\Models\ServiceOrder;
use App\Domain\Orders\Support\EloquentOrderLock;
use App\Domain\Shared\Audit\AuditEntry;
use App\Domain\Shared\Audit\AuditRecorder;
use App\Domain\Shared\Exceptions\InvalidTransition;
use App\Domain\Shared\Exceptions\RecordNotFound;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Links a guest visit to a registered customer (FR-CUS-006, FR-CUS-007).
 * The guest details typed at the desk stay on the visit as they were.
 */
final class AssociateCustomer
{
    public function __construct(
        private readonly EloquentOrderLock $orders,
        private readonly AuditRecorder $audit,
    ) {}

    public function __invoke(User $actor, ServiceOrder $order, int $customerId): ServiceOrder
    {
        return DB::transaction(function () use ($actor, $order, $customerId): ServiceOrder {
            $order = $this->orders->lock($order->id);

            if ($order->status === OrderStatus::Cancelled) {
                throw new InvalidTransition('A cancelled visit cannot be linked to a customer.');
            }

            if ($order->customer_id !== null) {
                throw new InvalidTransition('This visit already belongs to a customer.');
            }

            $customer = Customer::query()->whereKey($customerId)->first() ?? throw new RecordNotFound('That customer was not found.');

            if ($customer->isRemoved()) {
                throw new CustomerRemoved;
            }

            $order->forceFill(['customer_id' => $customer->id])->save();

            $this->audit->record(new AuditEntry('order.customer_associated', 'order', $order->id,
                before: ['customer_id' => null], after: ['customer_id' => $customer->id],
                actorId: $actor->id, orderId: $order->id));

            return $order;
        });
    }
}
