<?php

namespace App\Console\Commands;

use App\Domain\Billing\Calculators\DiscountInput;
use App\Domain\Billing\Calculators\InvoiceBreakdown;
use App\Domain\Billing\Calculators\InvoiceCalculator;
use App\Domain\Billing\Calculators\InvoiceInput;
use App\Domain\Billing\Calculators\LineInput;
use App\Domain\Billing\Calculators\LineType;
use App\Domain\Billing\Calculators\TaxRule;
use App\Domain\Execution\Allocation\AllocationCalculator;
use App\Domain\Execution\Allocation\AllocationMethod;
use App\Domain\Execution\Allocation\ContributorInput;
use App\Domain\Shared\Money\Money;
use App\Domain\Shared\Money\Percentage;
use Database\Seeders\Demo\Support\DemoData;
use Illuminate\Console\Command;

/**
 * Shows the money engine working on realistic demo salon visits, and
 * confirms that every figure reconciles to the kobo. Reads no database.
 */
class DemoCalculationsCommand extends Command
{
    protected $signature = 'app:demo:calc';

    protected $description = 'Print worked invoices and staff credit for demo salon visits';

    /**
     * Staff names by employee number (position in Demo/Data/staff.php, from 1).
     *
     * @var array<int, string>
     */
    private array $staffNames = [];

    public function handle(InvoiceCalculator $invoices, AllocationCalculator $allocations): int
    {
        foreach (DemoData::staff() as $index => $member) {
            $this->staffNames[$index + 1] = $member['name'];
        }

        $allReconciled = true;

        foreach ($this->scenarios() as $scenario) {
            $allReconciled = $this->showScenario($scenario, $invoices, $allocations) && $allReconciled;
        }

        $this->newLine();

        if (! $allReconciled) {
            $this->error('At least one figure did not reconcile.');

            return self::FAILURE;
        }

        $this->info('Every invoice, discount share and staff credit reconciles to the kobo.');

        return self::SUCCESS;
    }

    /**
     * @param  array{title: string, note: string, input: InvoiceInput, credit: array<string, array{method: AllocationMethod, primary_share: int|null, staff: list<string>}>}  $scenario
     */
    private function showScenario(array $scenario, InvoiceCalculator $invoices, AllocationCalculator $allocations): bool
    {
        $breakdown = $invoices->calculate($scenario['input']);

        $this->newLine();
        $this->line("<options=bold>{$scenario['title']}</>");
        $this->line("<fg=gray>{$scenario['note']}</>");

        $this->table(
            ['Item', 'Qty', 'Value', 'Line discount', 'Invoice discount', 'Net', 'Tax', 'Line total'],
            array_map(fn ($line) => [
                $line->description,
                $line->quantity,
                $this->money($line->lineValueKobo),
                $this->money($line->lineDiscountKobo),
                $this->money($line->invoiceDiscountShareKobo),
                $this->money($line->netKobo),
                $this->money($line->taxKobo),
                $this->money($line->lineTotalKobo),
            ], $breakdown->lines),
        );

        $taxLabel = $breakdown->tax->enabled
            ? "{$breakdown->tax->name} ".Percentage::fromBasisPoints($breakdown->tax->rateBasisPoints)->format()." {$breakdown->tax->mode->value}"
            : 'Tax off';

        $this->line(sprintf(
            '  Subtotal %s | Discounts %s | %s %s | <options=bold>Customer pays %s</>',
            $this->money($breakdown->subtotalKobo),
            $this->money($breakdown->discountTotalKobo()),
            $taxLabel,
            $this->money($breakdown->taxKobo),
            $this->money($breakdown->totalKobo),
        ));

        $reconciled = $this->invoiceReconciles($breakdown);

        foreach ($scenario['credit'] as $lineKey => $credit) {
            $reconciled = $this->showCredit($breakdown, $lineKey, $credit, $allocations) && $reconciled;
        }

        $this->line($reconciled ? '  <info>Check: reconciles</info>' : '  <error>Check: does not reconcile</error>');

        return $reconciled;
    }

    /**
     * @param  array{method: AllocationMethod, primary_share: int|null, staff: list<string>}  $credit
     */
    private function showCredit(InvoiceBreakdown $breakdown, string $lineKey, array $credit, AllocationCalculator $allocations): bool
    {
        $line = $breakdown->line($lineKey);
        $contributors = array_map(
            fn (string $staffKey, int $position) => new ContributorInput((int) $staffKey, isPrimary: $position === 0),
            $credit['staff'],
            array_keys($credit['staff']),
        );

        $result = $allocations->allocate($line->netKobo, $credit['method'], $contributors, $credit['primary_share']);

        $this->line("  Staff credit for {$line->description} (".str_replace('_', '/', $credit['method']->value).'):');

        foreach ($result as $allocation) {
            $this->line(sprintf(
                '    %-20s %-9s %7s  credited %s',
                $this->staffNames[$allocation->employeeId],
                $allocation->isPrimary ? 'primary' : 'assistant',
                Percentage::fromBasisPoints($allocation->shareBasisPoints)->format(),
                $this->money($allocation->allocatedKobo),
            ));
        }

        return array_sum(array_map(fn ($allocation) => $allocation->allocatedKobo, $result)) === $line->netKobo;
    }

    private function invoiceReconciles(InvoiceBreakdown $breakdown): bool
    {
        $shares = array_sum(array_map(fn ($line) => $line->invoiceDiscountShareKobo, $breakdown->lines));
        $totals = array_sum(array_map(fn ($line) => $line->lineTotalKobo, $breakdown->lines));

        return $shares === $breakdown->invoiceDiscountKobo && $totals === $breakdown->totalKobo;
    }

    private function money(int $kobo): string
    {
        return Money::fromKobo($kobo)->format();
    }

    private function price(string $service, ?string $variant = null): int
    {
        return Money::fromNaira(DemoData::price($service, $variant))->kobo;
    }

    /**
     * Staff keys are positions in Demo/Data/staff.php: 5 Chiamaka, 6 Funmilayo,
     * 7 Blessing, 8 Hauwa, 9 Zainab, 10 Efe, 11 Tolani.
     *
     * @return list<array{title: string, note: string, input: InvoiceInput, credit: array<string, array{method: AllocationMethod, primary_share: int|null, staff: list<string>}>}>
     */
    private function scenarios(): array
    {
        return [
            [
                'title' => '1. Knotless braids (medium) by two braiders, with attachment hair',
                'note' => '10% invoice discount, VAT 7.5% added on top. Chiamaka is primary (60%).',
                'input' => new InvoiceInput(
                    lines: [
                        new LineInput('knotless', LineType::Service, $this->price('Knotless braids', 'Medium'), description: 'Knotless braids (Medium)'),
                        new LineInput('xpression', LineType::Product, $this->price('X-pression braiding hair (pack)'), quantity: 2, description: 'X-pression hair (pack)'),
                    ],
                    tax: TaxRule::exclusive(750),
                    invoiceDiscount: DiscountInput::percent(1000),
                ),
                'credit' => ['knotless' => ['method' => AllocationMethod::PrimaryAssistant, 'primary_share' => 6000, 'staff' => ['5', '6']]],
            ],
            [
                'title' => '2. Boho knotless braids by three braiders (Saturday rush)',
                'note' => 'Charged once at the catalogue price however many people work on it. Tax off.',
                'input' => new InvoiceInput(
                    lines: [new LineInput('boho', LineType::Service, $this->price('Boho knotless braids'), description: 'Boho knotless braids')],
                    tax: TaxRule::disabled(),
                ),
                'credit' => ['boho' => ['method' => AllocationMethod::Equal, 'primary_share' => null, 'staff' => ['5', '7', '8']]],
            ],
            [
                'title' => '3. Nails visit with a line discount',
                'note' => 'Acrylic long set and gel polish by Zainab, pedicure by Efe. ₦2,000 off the gel polish. VAT 7.5% included in prices.',
                'input' => new InvoiceInput(
                    lines: [
                        new LineInput('acrylic', LineType::Service, $this->price('Acrylic full set', 'Long'), description: 'Acrylic full set (Long)'),
                        new LineInput('gel', LineType::Service, $this->price('Gel polish (hands)'), discount: DiscountInput::fixed(200000), description: 'Gel polish (hands)'),
                        new LineInput('pedicure', LineType::Service, $this->price('Classic pedicure'), description: 'Classic pedicure'),
                    ],
                    tax: TaxRule::inclusive(750),
                ),
                'credit' => [
                    'acrylic' => ['method' => AllocationMethod::Equal, 'primary_share' => null, 'staff' => ['9']],
                    'gel' => ['method' => AllocationMethod::Equal, 'primary_share' => null, 'staff' => ['9']],
                    'pedicure' => ['method' => AllocationMethod::Equal, 'primary_share' => null, 'staff' => ['10']],
                ],
            ],
            [
                'title' => '4. Bridal makeup and gele tying',
                'note' => 'Tolani primary (70%), Ngozi assisting. Fixed ₦10,000 invoice discount. Tax off.',
                'input' => new InvoiceInput(
                    lines: [
                        new LineInput('bridal', LineType::Service, $this->price('Bridal makeup'), description: 'Bridal makeup'),
                        new LineInput('gele', LineType::Service, $this->price('Gele tying'), description: 'Gele tying'),
                    ],
                    tax: TaxRule::disabled(),
                    invoiceDiscount: DiscountInput::fixed(1000000),
                ),
                'credit' => [
                    'bridal' => ['method' => AllocationMethod::PrimaryAssistant, 'primary_share' => 7000, 'staff' => ['11', '2']],
                    'gele' => ['method' => AllocationMethod::Equal, 'primary_share' => null, 'staff' => ['11']],
                ],
            ],
        ];
    }
}
