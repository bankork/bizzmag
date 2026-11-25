<?php

namespace Tests\Unit\Domain\Execution\Allocation;

use App\Domain\Execution\Allocation\AllocationCalculator;
use App\Domain\Execution\Allocation\AllocationMethod;
use App\Domain\Execution\Allocation\ContributorAllocation;
use App\Domain\Execution\Allocation\ContributorInput;
use App\Domain\Execution\Exceptions\InvalidAllocation;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Random\Engine\Mt19937;
use Random\Randomizer;

class AllocationCalculatorTest extends TestCase
{
    private const CHIAMAKA = 9;

    private const FUNMILAYO = 12;

    private const BLESSING = 17;

    public function test_worked_example_e1_equal_split_gives_the_remainder_to_the_primary(): void
    {
        $result = (new AllocationCalculator)->allocate(2000000, AllocationMethod::Equal, [
            new ContributorInput(self::CHIAMAKA, isPrimary: true),
            new ContributorInput(self::FUNMILAYO),
            new ContributorInput(self::BLESSING),
        ]);

        $this->assertSame([
            ['employee_id' => self::CHIAMAKA, 'is_primary' => true, 'share_bp' => 3334, 'allocated_kobo' => 666666, 'participated_kobo' => 2000000],
            ['employee_id' => self::FUNMILAYO, 'is_primary' => false, 'share_bp' => 3333, 'allocated_kobo' => 666667, 'participated_kobo' => 2000000],
            ['employee_id' => self::BLESSING, 'is_primary' => false, 'share_bp' => 3333, 'allocated_kobo' => 666667, 'participated_kobo' => 2000000],
        ], array_map(fn (ContributorAllocation $allocation) => $allocation->toArray(), $result));
    }

    public function test_worked_example_e2_primary_assistant_split(): void
    {
        $result = (new AllocationCalculator)->allocate(2000000, AllocationMethod::PrimaryAssistant, [
            new ContributorInput(self::CHIAMAKA, isPrimary: true),
            new ContributorInput(self::FUNMILAYO),
            new ContributorInput(self::BLESSING),
        ], primaryShareBasisPoints: 5000);

        $this->assertSame([1000000, 500000, 500000], array_map(fn ($allocation) => $allocation->allocatedKobo, $result));
        $this->assertSame([5000, 2500, 2500], array_map(fn ($allocation) => $allocation->shareBasisPoints, $result));
    }

    public function test_a_solo_service_credits_the_whole_value(): void
    {
        $result = (new AllocationCalculator)->allocate(1800000, AllocationMethod::PrimaryAssistant, [
            new ContributorInput(self::CHIAMAKA, isPrimary: true),
        ], primaryShareBasisPoints: 6000);

        $this->assertSame(10000, $result[0]->shareBasisPoints);
        $this->assertSame(1800000, $result[0]->allocatedKobo);
    }

    public function test_custom_shares_are_applied_as_entered(): void
    {
        $result = (new AllocationCalculator)->allocate(4000000, AllocationMethod::Custom, [
            new ContributorInput(self::CHIAMAKA, isPrimary: true, customShareBasisPoints: 7000),
            new ContributorInput(self::FUNMILAYO, customShareBasisPoints: 3000),
        ]);

        $this->assertSame([2800000, 1200000], array_map(fn ($allocation) => $allocation->allocatedKobo, $result));
    }

    public function test_rejects_custom_shares_that_do_not_total_one_hundred_percent(): void
    {
        $this->expectException(InvalidAllocation::class);
        $this->expectExceptionMessage('Custom shares must add up to exactly 100%.');

        (new AllocationCalculator)->allocate(4000000, AllocationMethod::Custom, [
            new ContributorInput(self::CHIAMAKA, isPrimary: true, customShareBasisPoints: 7000),
            new ContributorInput(self::FUNMILAYO, customShareBasisPoints: 2999),
        ]);
    }

    public function test_rejects_a_zero_share_contributor(): void
    {
        $this->expectException(InvalidAllocation::class);

        (new AllocationCalculator)->allocate(4000000, AllocationMethod::Custom, [
            new ContributorInput(self::CHIAMAKA, isPrimary: true, customShareBasisPoints: 10000),
            new ContributorInput(self::FUNMILAYO, customShareBasisPoints: 0),
        ]);
    }

    public function test_rejects_the_same_employee_twice(): void
    {
        $this->expectException(InvalidAllocation::class);
        $this->expectExceptionMessage('An employee can appear only once on a service.');

        (new AllocationCalculator)->allocate(2000000, AllocationMethod::Equal, [
            new ContributorInput(self::CHIAMAKA, isPrimary: true),
            new ContributorInput(self::CHIAMAKA),
        ]);
    }

    public function test_rejects_a_service_without_exactly_one_primary(): void
    {
        $this->expectException(InvalidAllocation::class);
        $this->expectExceptionMessage('Exactly one contributor must be the primary.');

        (new AllocationCalculator)->allocate(2000000, AllocationMethod::Equal, [
            new ContributorInput(self::CHIAMAKA),
            new ContributorInput(self::FUNMILAYO),
        ]);
    }

    public function test_rejects_a_primary_share_that_leaves_nothing_for_assistants(): void
    {
        $this->expectException(InvalidAllocation::class);

        (new AllocationCalculator)->allocate(2000000, AllocationMethod::PrimaryAssistant, [
            new ContributorInput(self::CHIAMAKA, isPrimary: true),
            new ContributorInput(self::FUNMILAYO),
        ], primaryShareBasisPoints: 10000);
    }

    /**
     * Invariant I-5 (allocations add up to the line value) and share rules,
     * over generated services. Fixed seed for reproducibility.
     */
    #[Group('property')]
    public function test_allocations_always_add_up_to_the_line_value(): void
    {
        $random = new Randomizer(new Mt19937(20261125));
        $calculator = new AllocationCalculator;

        for ($case = 0; $case < 2000; $case++) {
            $count = $random->getInt(1, 5);
            $primaryIndex = $random->getInt(0, $count - 1);
            $method = AllocationMethod::cases()[$random->getInt(0, 2)];
            $base = $random->getInt(0, 1) === 0 ? $random->getInt(0, 20) : $random->getInt(1, 12000000);
            $customShares = $this->randomShares($random, $count);

            $contributors = [];

            for ($i = 0; $i < $count; $i++) {
                $contributors[] = new ContributorInput(100 + $i, $i === $primaryIndex, $customShares[$i]);
            }

            $result = $calculator->allocate($base, $method, $contributors, $random->getInt(1, 10000 - ($count - 1)));

            $this->assertSame($base, array_sum(array_map(fn ($allocation) => $allocation->allocatedKobo, $result)), "case {$case}: I-5");
            $this->assertSame(10000, array_sum(array_map(fn ($allocation) => $allocation->shareBasisPoints, $result)), "case {$case}: shares");

            foreach ($result as $allocation) {
                $this->assertGreaterThanOrEqual(0, $allocation->allocatedKobo, "case {$case}: negative allocation");
                $this->assertGreaterThanOrEqual(1, $allocation->shareBasisPoints, "case {$case}: zero share");
                $this->assertSame($base, $allocation->participatedKobo, "case {$case}: participated value");
            }
        }
    }

    /**
     * @return list<int>
     */
    private function randomShares(Randomizer $random, int $count): array
    {
        $cuts = [];

        while (count($cuts) < $count - 1) {
            $cuts[$random->getInt(1, 9999)] = true;
        }

        $points = array_keys($cuts);
        sort($points);
        $points = [0, ...$points, 10000];

        $shares = [];

        for ($i = 1; $i < count($points); $i++) {
            $shares[] = $points[$i] - $points[$i - 1];
        }

        return $shares;
    }
}
