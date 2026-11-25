<?php

namespace App\Domain\Performance\Calculators;

use App\Domain\Shared\Money\Rounding;
use InvalidArgumentException;

/**
 * Collected contribution (PRD-7.5-7, SRS §7.6.7):
 * allocated x (net paid / invoice total), with net paid capped at the total.
 */
final class CollectedContribution
{
    public static function compute(int $allocatedKobo, int $netPaidKobo, int $invoiceTotalKobo): int
    {
        if ($allocatedKobo < 0 || $invoiceTotalKobo < 0) {
            throw new InvalidArgumentException('Amounts cannot be negative.');
        }

        if ($invoiceTotalKobo === 0 || $netPaidKobo <= 0) {
            return 0;
        }

        $paid = min($netPaidKobo, $invoiceTotalKobo);

        return Rounding::roundDiv(Rounding::multiply($allocatedKobo, $paid), $invoiceTotalKobo);
    }
}
