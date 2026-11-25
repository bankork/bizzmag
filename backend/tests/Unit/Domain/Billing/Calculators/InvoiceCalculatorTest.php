<?php

namespace Tests\Unit\Domain\Billing\Calculators;

use App\Domain\Billing\Calculators\DiscountInput;
use App\Domain\Billing\Calculators\InvoiceBreakdown;
use App\Domain\Billing\Calculators\InvoiceCalculator;
use App\Domain\Billing\Calculators\InvoiceInput;
use App\Domain\Billing\Calculators\LineInput;
use App\Domain\Billing\Calculators\LineType;
use App\Domain\Billing\Calculators\TaxMode;
use App\Domain\Billing\Calculators\TaxRule;
use App\Domain\Billing\Exceptions\InvalidDiscount;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Random\Engine\Mt19937;
use Random\Randomizer;

class InvoiceCalculatorTest extends TestCase
{
    public function test_worked_example_e3_spreads_invoice_discount_and_adds_exclusive_vat(): void
    {
        $input = new InvoiceInput(
            lines: [
                new LineInput('braids', LineType::Service, 2000000),
                new LineInput('pedicure', LineType::Service, 1000000),
            ],
            tax: TaxRule::exclusive(750),
            invoiceDiscount: DiscountInput::percent(1000),
        );

        $breakdown = (new InvoiceCalculator)->calculate($input);

        $this->assertSame(200000, $breakdown->line('braids')->invoiceDiscountShareKobo);
        $this->assertSame(1800000, $breakdown->line('braids')->netKobo);
        $this->assertSame(135000, $breakdown->line('braids')->taxKobo);
        $this->assertSame(100000, $breakdown->line('pedicure')->invoiceDiscountShareKobo);
        $this->assertSame(900000, $breakdown->line('pedicure')->netKobo);
        $this->assertSame(67500, $breakdown->line('pedicure')->taxKobo);
        $this->assertSame(3000000, $breakdown->subtotalKobo);
        $this->assertSame(300000, $breakdown->invoiceDiscountKobo);
        $this->assertSame(202500, $breakdown->taxKobo);
        $this->assertSame(2902500, $breakdown->totalKobo);
    }

    public function test_worked_example_e4_extracts_inclusive_vat_from_the_price(): void
    {
        $input = new InvoiceInput(
            lines: [new LineInput('braids', LineType::Service, 2150000)],
            tax: TaxRule::inclusive(750),
        );

        $breakdown = (new InvoiceCalculator)->calculate($input);

        $this->assertSame(150000, $breakdown->taxKobo);
        $this->assertSame(2000000, $breakdown->netTotalKobo);
        $this->assertSame(2150000, $breakdown->totalKobo);
    }

    public function test_exempt_lines_carry_no_tax(): void
    {
        $input = new InvoiceInput(
            lines: [
                new LineInput('braids', LineType::Service, 2000000),
                new LineInput('attachments', LineType::Product, 350000, taxable: false),
            ],
            tax: TaxRule::exclusive(750),
        );

        $breakdown = (new InvoiceCalculator)->calculate($input);

        $this->assertSame(150000, $breakdown->line('braids')->taxKobo);
        $this->assertSame(0, $breakdown->line('attachments')->taxKobo);
        $this->assertSame(2500000, $breakdown->totalKobo);
    }

    public function test_disabled_tax_leaves_totals_untaxed(): void
    {
        $input = new InvoiceInput(
            lines: [new LineInput('silk-press', LineType::Service, 1800000)],
            tax: TaxRule::disabled(),
        );

        $breakdown = (new InvoiceCalculator)->calculate($input);

        $this->assertSame(0, $breakdown->taxKobo);
        $this->assertSame(1800000, $breakdown->totalKobo);
    }

    public function test_multiplies_quantity_and_applies_line_discounts_first(): void
    {
        $input = new InvoiceInput(
            lines: [new LineInput('attachments', LineType::Product, 350000, quantity: 3, discount: DiscountInput::fixed(50000))],
            tax: TaxRule::disabled(),
        );

        $line = (new InvoiceCalculator)->calculate($input)->line('attachments');

        $this->assertSame(1050000, $line->lineValueKobo);
        $this->assertSame(50000, $line->lineDiscountKobo);
        $this->assertSame(1000000, $line->lineTotalKobo);
    }

    public function test_invoice_discount_remainder_goes_to_the_highest_value_line(): void
    {
        $input = new InvoiceInput(
            lines: [
                new LineInput('a', LineType::Service, 100),
                new LineInput('b', LineType::Service, 100),
                new LineInput('c', LineType::Service, 101),
            ],
            tax: TaxRule::disabled(),
            invoiceDiscount: DiscountInput::fixed(100),
        );

        $breakdown = (new InvoiceCalculator)->calculate($input);

        $this->assertSame(33, $breakdown->line('a')->invoiceDiscountShareKobo);
        $this->assertSame(33, $breakdown->line('b')->invoiceDiscountShareKobo);
        $this->assertSame(34, $breakdown->line('c')->invoiceDiscountShareKobo);
    }

    public function test_rejects_a_line_discount_larger_than_the_line(): void
    {
        $this->expectException(InvalidDiscount::class);

        (new InvoiceCalculator)->calculate(new InvoiceInput(
            lines: [new LineInput('nails', LineType::Service, 700000, discount: DiscountInput::fixed(700001))],
            tax: TaxRule::disabled(),
        ));
    }

    public function test_rejects_an_invoice_discount_larger_than_the_invoice(): void
    {
        $this->expectException(InvalidDiscount::class);

        (new InvoiceCalculator)->calculate(new InvoiceInput(
            lines: [new LineInput('nails', LineType::Service, 700000)],
            tax: TaxRule::disabled(),
            invoiceDiscount: DiscountInput::fixed(700001),
        ));
    }

    public function test_a_full_discount_produces_a_zero_invoice(): void
    {
        $breakdown = (new InvoiceCalculator)->calculate(new InvoiceInput(
            lines: [new LineInput('makeup', LineType::Service, 2500000)],
            tax: TaxRule::exclusive(750),
            invoiceDiscount: DiscountInput::percent(10000),
        ));

        $this->assertSame(0, $breakdown->totalKobo);
        $this->assertSame(0, $breakdown->taxKobo);
    }

    /**
     * Invariants I-1 to I-4 (SRS §7.6.3) over randomly generated invoices.
     * The seed is fixed so a failure is reproducible.
     */
    #[Group('property')]
    public function test_invariants_hold_for_generated_invoices(): void
    {
        $random = new Randomizer(new Mt19937(20261125));
        $calculator = new InvoiceCalculator;

        for ($case = 0; $case < 1500; $case++) {
            $input = $this->generateInvoice($random);

            $breakdown = $calculator->calculate($input);

            $this->assertInvariants($input, $breakdown, "case {$case}");
        }
    }

    private function assertInvariants(InvoiceInput $input, InvoiceBreakdown $breakdown, string $label): void
    {
        $shareTotal = 0;
        $netPlusTax = 0;
        $discounted = 0;

        foreach ($breakdown->lines as $line) {
            $this->assertGreaterThanOrEqual(0, $line->invoiceDiscountShareKobo, "{$label}: negative share");
            $this->assertLessThanOrEqual($line->lineValueKobo - $line->lineDiscountKobo, $line->invoiceDiscountShareKobo, "{$label}: share above line");
            $this->assertGreaterThanOrEqual(0, $line->netKobo, "{$label}: negative net");
            $this->assertGreaterThanOrEqual(0, $line->taxKobo, "{$label}: negative tax");
            $shareTotal += $line->invoiceDiscountShareKobo;
            $netPlusTax += $line->netKobo + $line->taxKobo;
            $discounted += $line->discountedKobo;
        }

        $this->assertSame($breakdown->invoiceDiscountKobo, $shareTotal, "{$label}: I-1 discount spread");

        if (! $input->tax->enabled || $input->tax->mode === TaxMode::Exclusive) {
            $this->assertSame($breakdown->totalKobo, $netPlusTax, "{$label}: I-2 exclusive total");
        } else {
            $this->assertSame($breakdown->totalKobo, $discounted, "{$label}: I-3 inclusive total");
        }

        $this->assertGreaterThanOrEqual(0, $breakdown->totalKobo, "{$label}: I-4 negative total");
    }

    private function generateInvoice(Randomizer $random): InvoiceInput
    {
        $lines = [];

        for ($i = 0, $count = $random->getInt(1, 8); $i < $count; $i++) {
            $unitPrice = $random->getInt(0, 1) === 0 ? $random->getInt(0, 500) : $random->getInt(1, 50000) * 100;
            $quantity = $random->getInt(1, 4);
            $value = $unitPrice * $quantity;

            $discount = match ($random->getInt(0, 3)) {
                0 => DiscountInput::fixed($random->getInt(0, $value)),
                1 => DiscountInput::percent($random->getInt(0, 10000)),
                default => null,
            };

            $lines[] = new LineInput(
                key: "line-{$i}",
                type: $random->getInt(0, 4) === 0 ? LineType::Product : LineType::Service,
                unitPriceKobo: $unitPrice,
                quantity: $quantity,
                taxable: $random->getInt(0, 5) !== 0,
                discount: $discount,
            );
        }

        $tax = match ($random->getInt(0, 2)) {
            0 => TaxRule::disabled(),
            1 => TaxRule::exclusive($random->getInt(0, 2500)),
            default => TaxRule::inclusive($random->getInt(0, 2500)),
        };

        $invoiceDiscount = match ($random->getInt(0, 2)) {
            0 => DiscountInput::percent($random->getInt(0, 10000)),
            1 => DiscountInput::fixed($random->getInt(0, $this->valueAfterLineDiscounts($lines))),
            default => null,
        };

        return new InvoiceInput($lines, $tax, $invoiceDiscount);
    }

    /**
     * Independent re-statement of steps 1 to 3, used only to keep a generated
     * fixed invoice discount within range.
     *
     * @param  list<LineInput>  $lines
     */
    private function valueAfterLineDiscounts(array $lines): int
    {
        return (new InvoiceCalculator)->calculate(new InvoiceInput($lines, TaxRule::disabled()))->totalKobo;
    }
}
