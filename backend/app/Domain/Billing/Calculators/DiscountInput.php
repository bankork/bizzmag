<?php

namespace App\Domain\Billing\Calculators;

use InvalidArgumentException;

/**
 * A discount as entered: a fixed amount in kobo, or a percentage in basis points.
 */
final readonly class DiscountInput
{
    private function __construct(
        public DiscountKind $kind,
        public int $value,
    ) {
        if ($value < 0) {
            throw new InvalidArgumentException('A discount cannot be negative.');
        }

        if ($kind === DiscountKind::Percent && $value > 10000) {
            throw new InvalidArgumentException('A percentage discount cannot exceed 100%.');
        }
    }

    public static function fixed(int $amountKobo): self
    {
        return new self(DiscountKind::Fixed, $amountKobo);
    }

    public static function percent(int $basisPoints): self
    {
        return new self(DiscountKind::Percent, $basisPoints);
    }
}
