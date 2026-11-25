<?php

namespace App\Domain\Shared\Money;

use InvalidArgumentException;

/**
 * Splits a whole number of kobo across weights without creating or losing
 * value (SRS §7.6.2 "where remainders go").
 *
 * Used for spreading an invoice discount across lines (weights = line
 * values) and for splitting staff credit (weights = shares in basis points).
 */
final class Apportion
{
    /**
     * The SRS rule: every item except the target gets round_div(total x w, W);
     * the target receives whatever remains, so the parts always sum to total.
     *
     * For tiny totals that rule can push the target below zero or above its
     * cap. In that rare case the split falls back to the largest-remainder
     * method, which keeps every part between 0 and ceil(total x w / W).
     *
     * @param  list<int>  $weights  Non-negative weights.
     * @param  int  $targetIndex  The item that receives the rounding remainder.
     * @param  list<int>|null  $caps  Optional upper bound per item.
     * @return list<int>
     */
    public static function withRemainderTo(int $total, array $weights, int $targetIndex, ?array $caps = null): array
    {
        $weightTotal = self::validate($total, $weights, $targetIndex);

        if ($weightTotal === 0) {
            return array_fill(0, count($weights), 0);
        }

        $parts = [];
        $assigned = 0;

        foreach ($weights as $index => $weight) {
            if ($index === $targetIndex) {
                $parts[$index] = 0;

                continue;
            }

            $parts[$index] = Rounding::roundDiv(Rounding::multiply($total, $weight), $weightTotal);
            $assigned += $parts[$index];
        }

        $parts[$targetIndex] = $total - $assigned;

        if (self::withinBounds($parts, $caps)) {
            return $parts;
        }

        return self::largestRemainder($total, $weights, $targetIndex);
    }

    /**
     * Largest-remainder (Hamilton) split. Ties go to the preferred item first,
     * then to items in their original order.
     *
     * @param  list<int>  $weights
     * @return list<int>
     */
    public static function largestRemainder(int $total, array $weights, int $preferredIndex): array
    {
        $weightTotal = self::validate($total, $weights, $preferredIndex);

        if ($weightTotal === 0) {
            return array_fill(0, count($weights), 0);
        }

        $parts = [];
        $remainders = [];
        $assigned = 0;

        foreach ($weights as $index => $weight) {
            $product = Rounding::multiply($total, $weight);
            $parts[$index] = intdiv($product, $weightTotal);
            $remainders[$index] = $product % $weightTotal;
            $assigned += $parts[$index];
        }

        $order = array_keys($weights);
        usort($order, function (int $left, int $right) use ($remainders, $preferredIndex): int {
            return [$remainders[$right], $right === $preferredIndex, -$right]
                <=> [$remainders[$left], $left === $preferredIndex, -$left];
        });

        for ($leftover = $total - $assigned, $i = 0; $leftover > 0; $leftover--, $i++) {
            $parts[$order[$i]]++;
        }

        return $parts;
    }

    /**
     * @param  list<int>  $weights
     */
    private static function validate(int $total, array $weights, int $targetIndex): int
    {
        if ($total < 0) {
            throw new InvalidArgumentException('Cannot apportion a negative total.');
        }

        if ($weights === [] || ! array_key_exists($targetIndex, $weights)) {
            throw new InvalidArgumentException('The remainder target must be one of the weighted items.');
        }

        $weightTotal = 0;

        foreach ($weights as $weight) {
            if ($weight < 0) {
                throw new InvalidArgumentException('Weights cannot be negative.');
            }

            $weightTotal += $weight;
        }

        if ($weightTotal === 0 && $total !== 0) {
            throw new InvalidArgumentException('Cannot apportion a non-zero total across zero weights.');
        }

        return $weightTotal;
    }

    /**
     * @param  array<int, int>  $parts
     * @param  list<int>|null  $caps
     */
    private static function withinBounds(array $parts, ?array $caps): bool
    {
        foreach ($parts as $index => $part) {
            if ($part < 0 || ($caps !== null && $part > $caps[$index])) {
                return false;
            }
        }

        return true;
    }
}
