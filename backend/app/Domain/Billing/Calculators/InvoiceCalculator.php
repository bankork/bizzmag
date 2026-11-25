<?php

namespace App\Domain\Billing\Calculators;

use App\Domain\Billing\Exceptions\InvalidDiscount;
use App\Domain\Shared\Money\Apportion;
use App\Domain\Shared\Money\Percentage;
use App\Domain\Shared\Money\Rounding;

/**
 * The single, pure invoice calculation (TDR-03, SRS §7.6.3).
 *
 * No database, no clock, no configuration. Given the same input it always
 * returns the same breakdown, so preview, finalisation and amendment agree.
 *
 * Order of operations (BR-033):
 *  1. line value = unit price x quantity
 *  2. line discount
 *  3. invoice discount spread across lines by value
 *  4. line net
 *  5. tax per line
 *  6. totals as exact sums of rounded line values
 */
final class InvoiceCalculator
{
    public function calculate(InvoiceInput $input): InvoiceBreakdown
    {
        $values = [];
        $lineDiscounts = [];
        $afterLineDiscount = [];

        foreach ($input->lines as $index => $line) {
            $values[$index] = Rounding::multiply($line->unitPriceKobo, $line->quantity);
            $lineDiscounts[$index] = $this->discountAmount($line->discount, $values[$index]);

            if ($lineDiscounts[$index] > $values[$index]) {
                throw new InvalidDiscount('A line discount is larger than the line value.', ['line' => $line->key]);
            }

            $afterLineDiscount[$index] = $values[$index] - $lineDiscounts[$index];
        }

        $remainingValue = array_sum($afterLineDiscount);
        $invoiceDiscount = $this->discountAmount($input->invoiceDiscount, $remainingValue);

        if ($invoiceDiscount > $remainingValue) {
            throw new InvalidDiscount('The invoice discount is larger than the invoice value.');
        }

        $shares = $input->lines === []
            ? []
            : Apportion::withRemainderTo(
                $invoiceDiscount,
                $afterLineDiscount,
                $this->highestValueIndex($afterLineDiscount),
                $afterLineDiscount,
            );

        $lines = [];

        foreach ($input->lines as $index => $line) {
            $discounted = $afterLineDiscount[$index] - $shares[$index];
            [$net, $tax, $lineTotal] = $this->applyTax($input->tax, $line->taxable, $discounted);

            $lines[] = new LineBreakdown(
                key: $line->key,
                type: $line->type,
                description: $line->description,
                quantity: $line->quantity,
                unitPriceKobo: $line->unitPriceKobo,
                lineValueKobo: $values[$index],
                lineDiscountKobo: $lineDiscounts[$index],
                invoiceDiscountShareKobo: $shares[$index],
                discountedKobo: $discounted,
                netKobo: $net,
                taxKobo: $tax,
                lineTotalKobo: $lineTotal,
                taxable: $line->taxable,
            );
        }

        return new InvoiceBreakdown(
            lines: $lines,
            tax: $input->tax,
            subtotalKobo: array_sum($values),
            lineDiscountTotalKobo: array_sum($lineDiscounts),
            invoiceDiscountKobo: $invoiceDiscount,
            taxKobo: array_sum(array_map(fn (LineBreakdown $line) => $line->taxKobo, $lines)),
            netTotalKobo: array_sum(array_map(fn (LineBreakdown $line) => $line->netKobo, $lines)),
            totalKobo: array_sum(array_map(fn (LineBreakdown $line) => $line->lineTotalKobo, $lines)),
        );
    }

    private function discountAmount(?DiscountInput $discount, int $baseKobo): int
    {
        if ($discount === null) {
            return 0;
        }

        return match ($discount->kind) {
            DiscountKind::Fixed => $discount->value,
            DiscountKind::Percent => Rounding::roundDiv(
                Rounding::multiply($baseKobo, $discount->value),
                Percentage::FULL,
            ),
        };
    }

    /**
     * The line that receives the invoice-discount remainder: highest value,
     * earliest line on a tie.
     *
     * @param  list<int>  $values
     */
    private function highestValueIndex(array $values): int
    {
        $best = 0;

        foreach ($values as $index => $value) {
            if ($value > $values[$best]) {
                $best = $index;
            }
        }

        return $best;
    }

    /**
     * @return array{0: int, 1: int, 2: int} [net, tax, line total]
     */
    private function applyTax(TaxRule $tax, bool $taxable, int $discountedKobo): array
    {
        if (! $tax->enabled || ! $taxable || $tax->rateBasisPoints === 0) {
            return [$discountedKobo, 0, $discountedKobo];
        }

        if ($tax->mode === TaxMode::Exclusive) {
            $taxKobo = Rounding::roundDiv(Rounding::multiply($discountedKobo, $tax->rateBasisPoints), Percentage::FULL);

            return [$discountedKobo, $taxKobo, $discountedKobo + $taxKobo];
        }

        $taxKobo = Rounding::roundDiv(
            Rounding::multiply($discountedKobo, $tax->rateBasisPoints),
            Percentage::FULL + $tax->rateBasisPoints,
        );

        return [$discountedKobo - $taxKobo, $taxKobo, $discountedKobo];
    }
}
