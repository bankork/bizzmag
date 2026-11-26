import { formatKobo } from "@/lib/money";
import { formatBasisPoints } from "@/lib/format";

/** "Up to 10% and ₦5,000 per bill", "No limit", "No discounts". */
export function describeDiscountCap(percentBp: number | null, perInvoiceKobo: number | null): string {
  if (percentBp === null && perInvoiceKobo === null) {
    return "No limit";
  }

  if (percentBp === 0 || perInvoiceKobo === 0) {
    return "No discounts";
  }

  const parts = [percentBp === null ? null : `up to ${formatBasisPoints(percentBp)}`, perInvoiceKobo === null ? null : `at most ${formatKobo(perInvoiceKobo).replace(/\.00$/, "")} per bill`].filter(Boolean);

  return parts.join(" and ").replace(/^./, (first) => first.toUpperCase());
}
