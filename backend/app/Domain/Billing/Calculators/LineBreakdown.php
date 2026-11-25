<?php

namespace App\Domain\Billing\Calculators;

/**
 * The calculated values for one line (SRS §7.6.3, steps 1 to 8).
 */
final readonly class LineBreakdown
{
    public function __construct(
        public string|int $key,
        public LineType $type,
        public string $description,
        public int $quantity,
        public int $unitPriceKobo,
        /** value_i = unit x quantity */
        public int $lineValueKobo,
        /** ld_i */
        public int $lineDiscountKobo,
        /** s_i, this line's share of the invoice-level discount */
        public int $invoiceDiscountShareKobo,
        /** g_i = value - line discount - invoice discount share */
        public int $discountedKobo,
        /** net_i, after discounts and before tax. The base for staff contribution. */
        public int $netKobo,
        public int $taxKobo,
        public int $lineTotalKobo,
        public bool $taxable,
    ) {}

    /**
     * @return array<string, int|string|bool>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'type' => $this->type->value,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'unit_price_kobo' => $this->unitPriceKobo,
            'line_value_kobo' => $this->lineValueKobo,
            'line_discount_kobo' => $this->lineDiscountKobo,
            'invoice_discount_share_kobo' => $this->invoiceDiscountShareKobo,
            'discounted_kobo' => $this->discountedKobo,
            'net_kobo' => $this->netKobo,
            'tax_kobo' => $this->taxKobo,
            'line_total_kobo' => $this->lineTotalKobo,
            'taxable' => $this->taxable,
        ];
    }
}
