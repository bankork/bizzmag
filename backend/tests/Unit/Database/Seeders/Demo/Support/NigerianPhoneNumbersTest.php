<?php

namespace Tests\Unit\Database\Seeders\Demo\Support;

use Database\Seeders\Demo\Support\NigerianPhoneNumbers;
use PHPUnit\Framework\TestCase;
use Random\Engine\Mt19937;
use Random\Randomizer;

class NigerianPhoneNumbersTest extends TestCase
{
    public function test_generates_valid_e164_nigerian_mobile_numbers_with_the_demo_block(): void
    {
        $generator = new NigerianPhoneNumbers(new Randomizer(new Mt19937(1)));

        for ($i = 0; $i < 200; $i++) {
            $this->assertMatchesRegularExpression('/^\+234[789][01]\d000\d{4}$/', $generator->next());
        }
    }

    public function test_never_issues_the_same_number_twice(): void
    {
        $generator = new NigerianPhoneNumbers(new Randomizer(new Mt19937(1)));

        $numbers = array_map(fn () => $generator->next(), range(1, 500));

        $this->assertCount(500, array_unique($numbers));
    }

    public function test_the_same_seed_produces_the_same_numbers(): void
    {
        $first = new NigerianPhoneNumbers(new Randomizer(new Mt19937(42)));
        $second = new NigerianPhoneNumbers(new Randomizer(new Mt19937(42)));

        $this->assertSame([$first->next(), $first->next()], [$second->next(), $second->next()]);
    }

    public function test_formats_numbers_for_local_display(): void
    {
        $this->assertSame('0803 000 4821', NigerianPhoneNumbers::toLocal('+2348030004821'));
    }
}
