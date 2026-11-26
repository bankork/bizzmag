<?php

namespace App\Domain\Billing\Calculators;

use App\Domain\Shared\Tax\TaxMode;
use InvalidArgumentException;

/**
 * The tax setting applied to one calculation (FR-TAX-001).
 */
final readonly class TaxRule
{
    private function __construct(
        public bool $enabled,
        public int $rateBasisPoints,
        public TaxMode $mode,
        public string $name,
    ) {
        if ($rateBasisPoints < 0 || $rateBasisPoints > 10000) {
            throw new InvalidArgumentException('A tax rate must be between 0% and 100%.');
        }
    }

    public static function disabled(): self
    {
        return new self(false, 0, TaxMode::Exclusive, '');
    }

    public static function exclusive(int $rateBasisPoints, string $name = 'VAT'): self
    {
        return new self(true, $rateBasisPoints, TaxMode::Exclusive, $name);
    }

    public static function inclusive(int $rateBasisPoints, string $name = 'VAT'): self
    {
        return new self(true, $rateBasisPoints, TaxMode::Inclusive, $name);
    }
}
