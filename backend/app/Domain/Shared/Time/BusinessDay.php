<?php

namespace App\Domain\Shared\Time;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use JsonSerializable;
use Stringable;
use Throwable;

/**
 * A calendar date in the business's own timezone (RR-09, DR-002).
 *
 * Instants are stored in UTC. The business day is computed once, when the
 * event happens, and stored alongside it so reports never convert timezones
 * in SQL. A payment at 00:30 WAT on 26 November belongs to 26 November even
 * though it is still 25 November in UTC.
 */
final readonly class BusinessDay implements JsonSerializable, Stringable
{
    private function __construct(public string $date) {}

    public static function fromInstant(DateTimeInterface $instant, string $timezone): self
    {
        return new self(CarbonImmutable::instance($instant)->setTimezone($timezone)->toDateString());
    }

    public static function today(string $timezone): self
    {
        return self::fromInstant(CarbonImmutable::now(), $timezone);
    }

    public static function fromString(string $date): self
    {
        try {
            $parsed = CarbonImmutable::createFromFormat('!Y-m-d', $date);
        } catch (Throwable) {
            $parsed = null;
        }

        if (! $parsed instanceof CarbonImmutable || $parsed->toDateString() !== $date) {
            throw new InvalidArgumentException("Invalid business day: {$date}.");
        }

        return new self($date);
    }

    public function equals(self $other): bool
    {
        return $this->date === $other->date;
    }

    public function __toString(): string
    {
        return $this->date;
    }

    public function jsonSerialize(): string
    {
        return $this->date;
    }
}
