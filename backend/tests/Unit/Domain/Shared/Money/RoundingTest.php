<?php

namespace Tests\Unit\Domain\Shared\Money;

use App\Domain\Shared\Money\Rounding;
use InvalidArgumentException;
use OverflowException;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class RoundingTest extends TestCase
{
    #[TestWith([10, 4, 3])] // 2.5 rounds up to 3
    #[TestWith([9, 4, 2])] // 2.25 rounds down to 2
    #[TestWith([11, 4, 3])] // 2.75 rounds up to 3
    #[TestWith([1, 2, 1])] // 0.5 rounds up to 1
    #[TestWith([1, 3, 0])] // 0.333 rounds down to 0
    #[TestWith([0, 7, 0])]
    #[TestWith([2000000, 3, 666667])]
    #[TestWith([2150000 * 750, 10750, 150000])] // E4 inclusive VAT
    public function test_divides_and_rounds_half_up(int $numerator, int $denominator, int $expected): void
    {
        $this->assertSame($expected, Rounding::roundDiv($numerator, $denominator));
    }

    public function test_rejects_a_negative_numerator(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Rounding::roundDiv(-1, 2);
    }

    public function test_rejects_a_zero_denominator(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Rounding::roundDiv(1, 0);
    }

    public function test_refuses_to_overflow_when_rounding(): void
    {
        $this->expectException(OverflowException::class);

        Rounding::roundDiv(PHP_INT_MAX, 3);
    }

    public function test_refuses_to_overflow_when_multiplying(): void
    {
        $this->expectException(OverflowException::class);

        Rounding::multiply(PHP_INT_MAX, 2);
    }

    public function test_multiplies_large_but_safe_values(): void
    {
        $this->assertSame(1_000_000_000_000_000_000, Rounding::multiply(1_000_000_000, 1_000_000_000));
    }
}
