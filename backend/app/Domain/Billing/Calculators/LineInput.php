<?php

namespace App\Domain\Billing\Calculators;

use InvalidArgumentException;

/**
 * One billable line, as the calculator sees it.
 *
 * The unit price is already final: the caller passes the override price
 * when one exists, otherwise the catalogue snapshot price.
 */
final readonly class LineInput
{
    public function __construct(
        public string|int $key,
        public LineType $type,
        public int $unitPriceKobo,
        public int $quantity = 1,
        public bool $taxable = true,
        public ?DiscountInput $discount = null,
        public string $description = '',
    ) {
        if ($unitPriceKobo < 0) {
            throw new InvalidArgumentException('A price cannot be negative.');
        }

        if ($quantity < 1) {
            throw new InvalidArgumentException('Quantity must be at least 1.');
        }
    }
}
