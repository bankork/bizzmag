<?php

namespace Tests\Unit\Database\Seeders\Demo\Support;

use Database\Seeders\Demo\Support\PaymentReferences;
use PHPUnit\Framework\TestCase;
use Random\Engine\Mt19937;
use Random\Randomizer;

class PaymentReferencesTest extends TestCase
{
    public function test_transfer_references_name_the_sender_in_capitals(): void
    {
        $references = new PaymentReferences(new Randomizer(new Mt19937(3)));

        $transfer = $references->transfer('Adaeze Okeke');

        $this->assertStringContainsString('ADAEZE OKEKE', $transfer['reference']);
        $this->assertNotSame('', $transfer['bank']);
    }

    public function test_pos_references_carry_a_twelve_digit_rrn(): void
    {
        $references = new PaymentReferences(new Randomizer(new Mt19937(3)));

        $pos = $references->pos();

        $this->assertMatchesRegularExpression('/RRN \d{12}$/', $pos['reference']);
    }
}
