<?php

namespace App\Domain\Execution\Allocation;

use App\Domain\Execution\Exceptions\InvalidAllocation;
use App\Domain\Shared\Money\Apportion;
use App\Domain\Shared\Money\Percentage;

/**
 * Splits one service line's net value between its contributors (SRS §7.6.4).
 *
 * Shares are whole basis points that always total 10000. Money follows the
 * same rule as everywhere else: every non-primary contributor is rounded
 * half up, and the primary receives the remainder, so the parts add up to
 * the line value exactly (invariant I-5).
 */
final class AllocationCalculator
{
    /**
     * @param  list<ContributorInput>  $contributors
     * @param  int|null  $primaryShareBasisPoints  Required for the Primary/Assistant method.
     * @return list<ContributorAllocation>
     */
    public function allocate(
        int $baseKobo,
        AllocationMethod $method,
        array $contributors,
        ?int $primaryShareBasisPoints = null,
    ): array {
        $primaryIndex = $this->validate($baseKobo, $contributors);
        $shares = $this->shares($method, $contributors, $primaryIndex, $primaryShareBasisPoints);

        $weights = $method === AllocationMethod::Equal ? array_fill(0, count($contributors), 1) : $shares;
        $amounts = Apportion::withRemainderTo($baseKobo, $weights, $primaryIndex);

        return array_map(
            fn (int $index) => new ContributorAllocation(
                employeeId: $contributors[$index]->employeeId,
                isPrimary: $index === $primaryIndex,
                shareBasisPoints: $shares[$index],
                allocatedKobo: $amounts[$index],
                participatedKobo: $baseKobo,
            ),
            array_keys($contributors),
        );
    }

    /**
     * @param  list<ContributorInput>  $contributors
     */
    private function validate(int $baseKobo, array $contributors): int
    {
        if ($baseKobo < 0) {
            throw new InvalidAllocation('A line value cannot be negative.');
        }

        if ($contributors === []) {
            throw new InvalidAllocation('At least one contributor is required.');
        }

        $employeeIds = array_map(fn (ContributorInput $contributor) => $contributor->employeeId, $contributors);

        if (count(array_unique($employeeIds)) !== count($employeeIds)) {
            throw new InvalidAllocation('An employee can appear only once on a service.');
        }

        $primaries = array_keys(array_filter($contributors, fn (ContributorInput $contributor) => $contributor->isPrimary));

        if (count($primaries) !== 1) {
            throw new InvalidAllocation('Exactly one contributor must be the primary.');
        }

        return $primaries[0];
    }

    /**
     * @param  list<ContributorInput>  $contributors
     * @return list<int>
     */
    private function shares(AllocationMethod $method, array $contributors, int $primaryIndex, ?int $primaryShare): array
    {
        $count = count($contributors);

        if ($method === AllocationMethod::Custom) {
            return $this->customShares($contributors);
        }

        if ($count === 1) {
            return [Percentage::FULL];
        }

        if ($method === AllocationMethod::Equal) {
            $eachShare = intdiv(Percentage::FULL, $count);
            $primaryValue = Percentage::FULL - $eachShare * ($count - 1);
        } else {
            if ($primaryShare === null || $primaryShare < 1 || $primaryShare > Percentage::FULL - ($count - 1)) {
                throw new InvalidAllocation('The primary share leaves no share for the assistants.');
            }

            $eachShare = intdiv(Percentage::FULL - $primaryShare, $count - 1);
            $primaryValue = Percentage::FULL - $eachShare * ($count - 1);
        }

        $shares = array_fill(0, $count, $eachShare);
        $shares[$primaryIndex] = $primaryValue;

        return $shares;
    }

    /**
     * @param  list<ContributorInput>  $contributors
     * @return list<int>
     */
    private function customShares(array $contributors): array
    {
        $shares = [];

        foreach ($contributors as $contributor) {
            $share = $contributor->customShareBasisPoints;

            if ($share === null || $share < 1 || $share > Percentage::FULL) {
                throw new InvalidAllocation('Every contributor needs a share between 0.01% and 100%.');
            }

            $shares[] = $share;
        }

        if (array_sum($shares) !== Percentage::FULL) {
            throw new InvalidAllocation('Custom shares must add up to exactly 100%.');
        }

        return $shares;
    }
}
