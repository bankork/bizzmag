<?php

namespace Tests\Unit\Domain\Shared\Money;

use App\Domain\Shared\Money\Money;
use App\Domain\Shared\Money\Percentage;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    #[TestWith(['35000', 3500000])]
    #[TestWith(['35,000.50', 3500050])]
    #[TestWith(['₦35,000.00', 3500000])]
    #[TestWith(['0.5', 50])]
    #[TestWith(['0.05', 5])]
    #[TestWith(['-2,000', -200000])]
    public function test_parses_naira_strings_without_losing_kobo(string $input, int $expectedKobo): void
    {
        $this->assertSame($expectedKobo, Money::fromNaira($input)->kobo);
    }

    public function test_parses_whole_naira_integers(): void
    {
        $this->assertSame(2000000, Money::fromNaira(20000)->kobo);
    }

    #[TestWith(['35,000.505'])]
    #[TestWith(['abc'])]
    #[TestWith(['1.2.3'])]
    #[TestWith([''])]
    public function test_rejects_malformed_naira_strings(string $input): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::fromNaira($input);
    }

    #[TestWith([3500000, '₦35,000.00'])]
    #[TestWith([2902500, '₦29,025.00'])]
    #[TestWith([5, '₦0.05'])]
    #[TestWith([0, '₦0.00'])]
    #[TestWith([-200000, '-₦2,000.00'])]
    #[TestWith([12000000000, '₦120,000,000.00'])]
    public function test_formats_as_naira_with_kobo(int $kobo, string $expected): void
    {
        $this->assertSame($expected, Money::fromKobo($kobo)->format());
    }

    public function test_applies_a_percentage_rounding_half_up(): void
    {
        $tenPercentOfThirtyThousand = Money::fromKobo(3000000)->percentage(Percentage::fromBasisPoints(1000));
        $vatOnOneKobo = Money::fromKobo(1)->percentage(Percentage::fromBasisPoints(750));
        $vatOnSevenKobo = Money::fromKobo(7)->percentage(Percentage::fromBasisPoints(750));

        $this->assertSame(300000, $tenPercentOfThirtyThousand->kobo);
        $this->assertSame(0, $vatOnOneKobo->kobo);
        $this->assertSame(1, $vatOnSevenKobo->kobo);
    }

    public function test_adds_subtracts_and_multiplies(): void
    {
        $braids = Money::fromNaira('35,000');
        $attachments = Money::fromNaira('3,500');

        $this->assertSame(3850000, $braids->plus($attachments)->kobo);
        $this->assertSame(3150000, $braids->minus($attachments)->kobo);
        $this->assertSame(1050000, $attachments->times(3)->kobo);
    }

    public function test_refuses_percentages_of_negative_amounts(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::fromKobo(-100)->percentage(Percentage::fromBasisPoints(1000));
    }

    public function test_serialises_for_the_api_as_integer_kobo(): void
    {
        $this->assertSame(
            ['amount_kobo' => 3500000, 'currency' => 'NGN', 'formatted' => '₦35,000.00'],
            Money::fromKobo(3500000)->jsonSerialize(),
        );
    }

    public function test_compares_amounts(): void
    {
        $small = Money::fromKobo(100);
        $large = Money::fromKobo(200);

        $this->assertTrue($small->lessThan($large));
        $this->assertTrue($large->greaterThan($small));
        $this->assertTrue($small->equals(Money::fromKobo(100)));
        $this->assertTrue(Money::zero()->isZero());
        $this->assertTrue(Money::fromKobo(-1)->isNegative());
    }
}
