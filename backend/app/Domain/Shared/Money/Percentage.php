<?php

namespace App\Domain\Shared\Money;

use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * A percentage stored as whole basis points, where 10000 = 100.00%.
 *
 * Examples: 7.5% is 750, 33.33% is 3333.
 */
final readonly class Percentage implements JsonSerializable, Stringable
{
    public const int FULL = 10000;

    private function __construct(public int $basisPoints)
    {
        if ($basisPoints < 0 || $basisPoints > self::FULL) {
            throw new InvalidArgumentException("Basis points must be between 0 and 10000, got {$basisPoints}.");
        }
    }

    public static function fromBasisPoints(int $basisPoints): self
    {
        return new self($basisPoints);
    }

    /**
     * Parse a human percentage such as "7.5", "7.50" or "33.33%".
     */
    public static function fromPercent(string $percent): self
    {
        $clean = trim(str_replace('%', '', $percent));

        if (preg_match('/^(\d{1,3})(?:\.(\d{1,2}))?$/', $clean, $matches) !== 1) {
            throw new InvalidArgumentException("Invalid percentage: {$percent}.");
        }

        $whole = (int) $matches[1];
        $fraction = (int) str_pad($matches[2] ?? '0', 2, '0');

        return new self($whole * 100 + $fraction);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public static function full(): self
    {
        return new self(self::FULL);
    }

    public function isZero(): bool
    {
        return $this->basisPoints === 0;
    }

    /**
     * Format as a human percentage, trimming trailing zeros: 750 is "7.5%".
     */
    public function format(): string
    {
        $whole = intdiv($this->basisPoints, 100);
        $fraction = $this->basisPoints % 100;

        if ($fraction === 0) {
            return "{$whole}%";
        }

        return $whole.'.'.rtrim(str_pad((string) $fraction, 2, '0', STR_PAD_LEFT), '0').'%';
    }

    public function __toString(): string
    {
        return $this->format();
    }

    /**
     * @return array{basis_points: int, formatted: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'basis_points' => $this->basisPoints,
            'formatted' => $this->format(),
        ];
    }
}
