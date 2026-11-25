<?php

namespace App\Domain\Billing\Calculators;

/**
 * Everything the invoice calculation needs, and nothing else.
 */
final readonly class InvoiceInput
{
    /**
     * @param  list<LineInput>  $lines  Billable lines only, in display order.
     */
    public function __construct(
        public array $lines,
        public TaxRule $tax,
        public ?DiscountInput $invoiceDiscount = null,
    ) {}
}
