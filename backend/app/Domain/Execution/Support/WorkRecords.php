<?php

namespace App\Domain\Execution\Support;

use App\Domain\Execution\Allocation\AllocationMethod;
use App\Domain\Execution\Exceptions\SplitSetByHand;
use App\Domain\Execution\Models\Execution;
use App\Domain\Execution\Models\ExecutionContributor;
use App\Domain\Execution\Models\JoinedVia;
use App\Domain\Orders\Models\OrderItem;
use App\Domain\Orders\Models\ServiceOrder;
use App\Domain\Orders\Support\EloquentOrderLock;

/**
 * Locking and contributor bookkeeping shared by the execution commands.
 *
 * Callers pass records loaded before their transaction began. Inside it the
 * visit is locked first, then everything is read again, so every check sees
 * the latest committed state (SRS §7.7.2).
 */
final class WorkRecords
{
    public function __construct(
        private readonly EloquentOrderLock $orders,
        private readonly ShareCalculator $calculator,
    ) {}

    /**
     * @return array{0: ServiceOrder, 1: OrderItem}
     */
    public function lockItem(OrderItem $item): array
    {
        $order = $this->orders->lock($item->service_order_id);

        return [$order, OrderItem::query()->findOrFail($item->id)];
    }

    /**
     * @return array{0: ServiceOrder, 1: OrderItem, 2: Execution}
     */
    public function lockExecution(Execution $execution, int $orderId): array
    {
        $order = $this->orders->lock($orderId);
        $fresh = Execution::query()->with('contributors')->findOrFail($execution->id);

        return [$order, OrderItem::query()->findOrFail($fresh->order_item_id), $fresh];
    }

    /**
     * The visit an execution belongs to. Call before the transaction starts.
     */
    public static function orderIdOf(Execution $execution): int
    {
        return $execution->loadMissing('item')->item->service_order_id;
    }

    /**
     * The active record for an item, read with a lock so it is never stale.
     */
    public function activeFor(OrderItem $item): ?Execution
    {
        return Execution::query()->where('order_item_id', $item->id)->active()->lockForUpdate()->first();
    }

    /**
     * Recalculate shares with the record's method after people join or
     * leave (FR-ATTR-008). A split a supervisor set by hand cannot be
     * recalculated, so changes to it go through a supervisor.
     *
     * @param  list<int>  $employeeIds  The new set of people, including the primary.
     */
    public function resplit(Execution $execution, array $employeeIds, int $primaryId, JoinedVia $viaForNew): void
    {
        if ($execution->allocation_method === AllocationMethod::Custom) {
            throw new SplitSetByHand;
        }

        $this->write($execution, $this->calculator->shares($execution->allocation_method, $execution->primary_share_bp, $employeeIds, $primaryId), $primaryId, $viaForNew);
    }

    /**
     * Make the record's contributors exactly the given shares.
     *
     * @param  array<int, int>  $shares  Employee ID => basis points.
     */
    public function write(Execution $execution, array $shares, int $primaryId, JoinedVia $viaForNew): void
    {
        $existing = $execution->contributors()->get()->keyBy('employee_id');
        $removed = $existing->keys()->diff(array_keys($shares))->all();

        if ($removed !== []) {
            $execution->contributors()->whereIn('employee_id', $removed)->delete();
        }

        foreach ($shares as $employeeId => $share) {
            $row = $existing->get($employeeId);

            if ($row instanceof ExecutionContributor) {
                $row->forceFill(['share_bp' => $share, 'is_primary' => $employeeId === $primaryId])->save();

                continue;
            }

            ExecutionContributor::query()->create([
                'execution_id' => $execution->id,
                'employee_id' => $employeeId,
                'is_primary' => $employeeId === $primaryId,
                'share_bp' => $share,
                'joined_via' => $viaForNew,
            ]);
        }

        $execution->unsetRelation('contributors');
    }
}
