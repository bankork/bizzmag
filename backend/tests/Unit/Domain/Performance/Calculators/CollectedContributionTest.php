<?php

namespace Tests\Unit\Domain\Performance\Calculators;

use App\Domain\Performance\Calculators\CollectedContribution;
use PHPUnit\Framework\TestCase;

class CollectedContributionTest extends TestCase
{
    public function test_worked_example_e5_half_paid_invoice_credits_half(): void
    {
        $this->assertSame(300000, CollectedContribution::compute(600000, 1451250, 2902500));
    }

    public function test_overpayment_never_credits_more_than_the_allocation(): void
    {
        $this->assertSame(600000, CollectedContribution::compute(600000, 9999999, 2902500));
    }

    public function test_unpaid_or_zero_value_invoices_credit_nothing(): void
    {
        $this->assertSame(0, CollectedContribution::compute(600000, 0, 2902500));
        $this->assertSame(0, CollectedContribution::compute(0, 0, 0));
    }
}
