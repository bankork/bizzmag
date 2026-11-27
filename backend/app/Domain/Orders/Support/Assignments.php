<?php

namespace App\Domain\Orders\Support;

use App\Domain\Identity\Support\SalonStaff;
use App\Domain\Orders\Models\OrderItem;
use App\Domain\Orders\Models\OrderItemAssignment;
use App\Models\User;

/**
 * Sets the employees expected to do a service (FR-ORD-005, FR-ORD-006).
 * Only people who can be credited may be assigned.
 */
final class Assignments
{
    public function __construct(private readonly SalonStaff $staff) {}

    /**
     * Replace the item's assignments with the given employees.
     *
     * @param  list<int>  $employeeIds
     * @return array{before: list<int>, after: list<int>, added: list<int>}
     */
    public function replace(OrderItem $item, array $employeeIds, User $actor): array
    {
        $before = $item->assignments()->pluck('employee_id')->map(fn ($id) => (int) $id)->all();
        $after = array_values(array_unique($employeeIds));
        $added = array_values(array_diff($after, $before));

        $this->staff->requireEligible($added);

        $removed = array_values(array_diff($before, $after));

        if ($removed !== []) {
            $item->assignments()->whereIn('employee_id', $removed)->delete();
        }

        foreach ($added as $employeeId) {
            OrderItemAssignment::query()->create(['order_item_id' => $item->id, 'employee_id' => $employeeId, 'assigned_by' => $actor->id]);
        }

        sort($before);
        sort($after);

        return ['before' => $before, 'after' => $after, 'added' => $added];
    }
}
