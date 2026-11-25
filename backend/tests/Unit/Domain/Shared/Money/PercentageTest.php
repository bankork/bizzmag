<?php

namespace Tests\Unit\Domain\Shared\Money;

use App\Domain\Shared\Money\Percentage;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class PercentageTest extends TestCase
{
    #[TestWith(['7.5', 750])]
    #[TestWith(['7.50%', 750])]
    #[TestWith(['33.33', 3333])]
    #[TestWith(['100', 10000])]
    #[TestWith(['0', 0])]
    #[TestWith(['0.01', 1])]
    public function test_parses_human_percentages_into_basis_points(string $input, int $expected): void
    {
        $this->assertSame($expected, Percentage::fromPercent($input)->basisPoints);
    }

    #[TestWith(['100.01'])]
    #[TestWith(['7.555'])]
    #[TestWith(['-5'])]
    #[TestWith(['ten'])]
    public function test_rejects_invalid_percentages(string $input): void
    {
        $this->expectException(InvalidArgumentException::class);

        Percentage::fromPercent($input);
    }

    #[TestWith([750, '7.5%'])]
    #[TestWith([3333, '33.33%'])]
    #[TestWith([10000, '100%'])]
    #[TestWith([5, '0.05%'])]
    #[TestWith([1050, '10.5%'])]
    public function test_formats_without_trailing_zeros(int $basisPoints, string $expected): void
    {
        $this->assertSame($expected, Percentage::fromBasisPoints($basisPoints)->format());
    }

    public function test_rejects_basis_points_above_one_hundred_percent(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Percentage::fromBasisPoints(10001);
    }
}
