<?php

namespace Tests\Unit\Domain\Shared\Money;

use App\Domain\Shared\Money\Apportion;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ApportionTest extends TestCase
{
    public function test_gives_the_rounding_remainder_to_the_target(): void
    {
        $parts = Apportion::withRemainderTo(2000000, [1, 1, 1], targetIndex: 0);

        $this->assertSame([666666, 666667, 666667], $parts);
    }

    public function test_spreads_proportionally_to_weights(): void
    {
        $parts = Apportion::withRemainderTo(300000, [2000000, 1000000], targetIndex: 0);

        $this->assertSame([200000, 100000], $parts);
    }

    public function test_returns_zeros_when_all_weights_and_total_are_zero(): void
    {
        $this->assertSame([0, 0], Apportion::withRemainderTo(0, [0, 0], targetIndex: 0));
    }

    public function test_falls_back_to_largest_remainder_when_the_target_would_go_negative(): void
    {
        // Rounding each assistant up would leave the primary with -1 kobo.
        $parts = Apportion::withRemainderTo(2, [1, 1, 1, 1], targetIndex: 0);

        $this->assertSame(2, array_sum($parts));
        $this->assertSame([1, 1, 0, 0], $parts);
    }

    public function test_largest_remainder_breaks_ties_in_favour_of_the_preferred_item(): void
    {
        $parts = Apportion::largestRemainder(1, [1, 1, 1], preferredIndex: 2);

        $this->assertSame([0, 0, 1], $parts);
    }

    public function test_refuses_a_non_zero_total_across_zero_weights(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Apportion::withRemainderTo(100, [0, 0], targetIndex: 0);
    }

    public function test_refuses_a_target_that_is_not_an_item(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Apportion::withRemainderTo(100, [1, 1], targetIndex: 5);
    }
}
