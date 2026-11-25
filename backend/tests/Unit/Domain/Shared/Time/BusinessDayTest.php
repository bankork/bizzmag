<?php

namespace Tests\Unit\Domain\Shared\Time;

use App\Domain\Shared\Time\BusinessDay;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class BusinessDayTest extends TestCase
{
    public function test_a_payment_just_after_midnight_in_lagos_belongs_to_the_lagos_date(): void
    {
        $instant = new DateTimeImmutable('2026-11-25 23:30:00', new DateTimeZone('UTC'));

        $day = BusinessDay::fromInstant($instant, 'Africa/Lagos');

        $this->assertSame('2026-11-26', $day->date);
    }

    public function test_an_evening_payment_in_lagos_stays_on_the_same_date(): void
    {
        $instant = new DateTimeImmutable('2026-11-25 21:59:59', new DateTimeZone('UTC'));

        $day = BusinessDay::fromInstant($instant, 'Africa/Lagos');

        $this->assertSame('2026-11-25', $day->date);
    }

    #[TestWith(['2026-02-30'])]
    #[TestWith(['25-11-2026'])]
    #[TestWith(['2026-11-25 10:00'])]
    public function test_rejects_invalid_dates(string $input): void
    {
        $this->expectException(InvalidArgumentException::class);

        BusinessDay::fromString($input);
    }

    public function test_compares_by_date(): void
    {
        $this->assertTrue(BusinessDay::fromString('2026-11-25')->equals(BusinessDay::fromString('2026-11-25')));
        $this->assertFalse(BusinessDay::fromString('2026-11-25')->equals(BusinessDay::fromString('2026-11-26')));
    }
}
