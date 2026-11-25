<?php

namespace App\Domain\Shared\Money;

use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * An amount of Nigerian Naira held as whole kobo (TDR-01).
 *
 * Money is immutable and never uses floating point. Most business values are
 * non-negative; signed values are allowed only where a difference is the
 * point (closing discrepancies, bonus adjustments).
 */
final readonly class Money implements JsonSerializable, Stringable
{
    public const string CURRENCY = 'NGN';

    public const string SYMBOL = '₦';

    private function __construct(public int $kobo) {}

    public static function fromKobo(int $kobo): self
    {
        return new self($kobo);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    /**
     * Parse a naira amount such as "35000", "35,000.50" or "₦35,000.00".
     *
     * Strings are parsed digit by digit so no precision is lost.
     */
    public static function fromNaira(string|int $amount): self
    {
        if (is_int($amount)) {
            return new self(Rounding::multiply(abs($amount), 100) * ($amount < 0 ? -1 : 1));
        }

        $clean = str_replace([self::SYMBOL, ',', ' '], '', trim($amount));

        if (preg_match('/^(-?)(\d+)(?:\.(\d{1,2}))?$/', $clean, $matches) !== 1) {
            throw new InvalidArgumentException("Invalid naira amount: {$amount}.");
        }

        $naira = (int) $matches[2];
        $kobo = (int) str_pad($matches[3] ?? '0', 2, '0');
        $total = Rounding::multiply($naira, 100) + $kobo;

        return new self($matches[1] === '-' ? -$total : $total);
    }

    public function plus(self $other): self
    {
        return new self($this->kobo + $other->kobo);
    }

    public function minus(self $other): self
    {
        return new self($this->kobo - $other->kobo);
    }

    public function times(int $quantity): self
    {
        if ($quantity < 0) {
            throw new InvalidArgumentException('Quantity cannot be negative.');
        }

        return new self(Rounding::multiply($this->assertNonNegative()->kobo, $quantity));
    }

    /**
     * Apply a percentage, rounding half up to the kobo.
     */
    public function percentage(Percentage $percentage): self
    {
        $product = Rounding::multiply($this->assertNonNegative()->kobo, $percentage->basisPoints);

        return new self(Rounding::roundDiv($product, Percentage::FULL));
    }

    public function isZero(): bool
    {
        return $this->kobo === 0;
    }

    public function isNegative(): bool
    {
        return $this->kobo < 0;
    }

    public function isPositive(): bool
    {
        return $this->kobo > 0;
    }

    public function equals(self $other): bool
    {
        return $this->kobo === $other->kobo;
    }

    public function greaterThan(self $other): bool
    {
        return $this->kobo > $other->kobo;
    }

    public function lessThan(self $other): bool
    {
        return $this->kobo < $other->kobo;
    }

    /**
     * Format for display, for example "₦35,000.00" or "-₦2,000.00".
     */
    public function format(): string
    {
        $absolute = abs($this->kobo);
        $naira = number_format(intdiv($absolute, 100));
        $kobo = str_pad((string) ($absolute % 100), 2, '0', STR_PAD_LEFT);

        return ($this->kobo < 0 ? '-' : '').self::SYMBOL.$naira.'.'.$kobo;
    }

    public function __toString(): string
    {
        return $this->format();
    }

    /**
     * @return array{amount_kobo: int, currency: string, formatted: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'amount_kobo' => $this->kobo,
            'currency' => self::CURRENCY,
            'formatted' => $this->format(),
        ];
    }

    private function assertNonNegative(): self
    {
        if ($this->kobo < 0) {
            throw new InvalidArgumentException('This operation requires a non-negative amount.');
        }

        return $this;
    }
}
