<?php

namespace App\Domain\Shared\Money;

use InvalidArgumentException;
use OverflowException;

/**
 * Integer rounding helpers used by every money calculation (SRS §7.6.2).
 *
 * All monetary values are whole kobo and never negative at the point of
 * rounding, so "half up" and "half away from zero" are the same rule.
 */
final class Rounding
{
    /**
     * Divide and round half up: round(numerator / denominator).
     *
     * Implemented as intdiv(2n + d, 2d) so no floating point is involved.
     */
    public static function roundDiv(int $numerator, int $denominator): int
    {
        if ($numerator < 0) {
            throw new InvalidArgumentException('roundDiv requires a non-negative numerator.');
        }

        if ($denominator <= 0) {
            throw new InvalidArgumentException('roundDiv requires a positive denominator.');
        }

        if ($numerator > intdiv(PHP_INT_MAX - $denominator, 2)) {
            throw new OverflowException('roundDiv numerator is too large.');
        }

        return intdiv(2 * $numerator + $denominator, 2 * $denominator);
    }

    /**
     * Multiply two non-negative integers, refusing silent overflow.
     */
    public static function multiply(int $left, int $right): int
    {
        if ($left < 0 || $right < 0) {
            throw new InvalidArgumentException('multiply requires non-negative operands.');
        }

        if ($left !== 0 && $right > intdiv(PHP_INT_MAX, $left)) {
            throw new OverflowException('Multiplication would overflow.');
        }

        return $left * $right;
    }
}
