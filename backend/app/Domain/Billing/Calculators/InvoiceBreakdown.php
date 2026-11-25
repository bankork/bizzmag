<?php

namespace App\Domain\Billing\Calculators;

/**
 * The immutable result of an invoice calculation.
 *
 * The same breakdown feeds the draft preview, the finalisation snapshot and
 * the amendment preview (TDR-03).
 */
final readonly class InvoiceBreakdown
{
    /**
     * @param  list<LineBreakdown>  $lines
     */
    public function __construct(
        public array $lines,
        public TaxRule $tax,
        public int $subtotalKobo,
        public int $lineDiscountTotalKobo,
        public int $invoiceDiscountKobo,
        public int $taxKobo,
        public int $netTotalKobo,
        public int $totalKobo,
    ) {}

    public function discountTotalKobo(): int
    {
        return $this->lineDiscountTotalKobo + $this->invoiceDiscountKobo;
    }

    public function line(string|int $key): ?LineBreakdown
    {
        foreach ($this->lines as $line) {
            if ($line->key === $key) {
                return $line;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'lines' => array_map(fn (LineBreakdown $line) => $line->toArray(), $this->lines),
            'tax' => [
                'enabled' => $this->tax->enabled,
                'name' => $this->tax->name,
                'rate_bp' => $this->tax->rateBasisPoints,
                'mode' => $this->tax->mode->value,
            ],
            'subtotal_kobo' => $this->subtotalKobo,
            'line_discount_total_kobo' => $this->lineDiscountTotalKobo,
            'invoice_discount_kobo' => $this->invoiceDiscountKobo,
            'discount_total_kobo' => $this->discountTotalKobo(),
            'tax_kobo' => $this->taxKobo,
            'net_total_kobo' => $this->netTotalKobo,
            'total_kobo' => $this->totalKobo,
        ];
    }
}
