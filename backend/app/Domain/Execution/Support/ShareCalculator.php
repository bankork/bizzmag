<?php

namespace App\Domain\Execution\Support;

use App\Domain\Catalogue\Models\SplitMethod;
use App\Domain\Execution\Allocation\AllocationCalculator;
use App\Domain\Execution\Allocation\AllocationMethod;
use App\Domain\Execution\Allocation\ContributorAllocation;
use App\Domain\Execution\Allocation\ContributorInput;
use App\Domain\Orders\Models\OrderItem;

/**
 * Shares in basis points for the people on one record (SRS §7.6.4), using
 * the P0 allocation calculator. Money values are worked out from these
 * shares once the line has a net value, at finalisation (P4).
 */
final class ShareCalculator
{
    public function __construct(private readonly AllocationCalculator $calculator) {}

    /**
     * The method the item was added with (P3 Q4). Equal when none was set.
     */
    public static function defaultMethod(OrderItem $item): AllocationMethod
    {
        return AllocationMethod::from($item->allocation_method_snapshot->value ?? SplitMethod::Equal->value);
    }

    /**
     * @param  list<int>  $employeeIds  Everyone on the record, including the primary.
     * @param  array<int, int>|null  $customShares  Employee ID => basis points, for the Custom method.
     * @return array<int, int> Employee ID => share in basis points, in the given order.
     */
    public function shares(AllocationMethod $method, ?int $primaryShareBp, array $employeeIds, int $primaryId, ?array $customShares = null): array
    {
        $count = count($employeeIds);
        $primaryShare = null;

        if ($method === AllocationMethod::PrimaryAssistant) {
            // Every contributor holds at least 0.01% (DR-008), so a primary
            // share of 100% leaves each assistant 1 basis point.
            $primaryShare = min($primaryShareBp ?? SplitMethod::DEFAULT_PRIMARY_SHARE_BP, 10_000 - ($count - 1));
        }

        $inputs = array_map(
            fn (int $id) => new ContributorInput($id, $id === $primaryId, $customShares[$id] ?? null),
            $employeeIds,
        );

        $shares = [];

        foreach ($this->calculator->allocate(0, $method, $inputs, $primaryShare) as $allocation) {
            /** @var ContributorAllocation $allocation */
            $shares[$allocation->employeeId] = $allocation->shareBasisPoints;
        }

        return $shares;
    }
}
