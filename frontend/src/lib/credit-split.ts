import type { SplitMethod } from "@/lib/api/types";

/**
 * An illustration of how a service's credit would be shared, for the
 * catalogue editor. The server does the real allocation when a bill is
 * final (SRS §7.6.4); this follows the same rules so the example matches:
 *
 * - Equal: each assistant gets floor(10000 / n) bp and round_div(base, n);
 *   the primary gets the remainder.
 * - Primary and assistants: the primary gets P; assistants share 10000 - P
 *   equally (floor), the remainder bp goes to the primary; amounts are
 *   round_div(base x bp, 10000), the primary gets base minus the others.
 */
export type SplitShare = { role: "primary" | "assistant"; shareBp: number; kobo: number };

export function splitExample(baseKobo: number, staff: number, method: SplitMethod, primaryShareBp: number | null): SplitShare[] {
  if (staff < 1 || !Number.isSafeInteger(baseKobo) || baseKobo < 0) {
    return [];
  }

  if (staff === 1) {
    return [{ role: "primary", shareBp: 10_000, kobo: baseKobo }];
  }

  const assistants = staff - 1;
  const assistantBp = method === "equal" ? Math.floor(10_000 / staff) : Math.floor((10_000 - (primaryShareBp ?? 5_000)) / assistants);
  const assistantKobo = method === "equal" ? roundDiv(baseKobo, staff) : roundDiv(baseKobo * assistantBp, 10_000);

  return [
    { role: "primary", shareBp: 10_000 - assistantBp * assistants, kobo: baseKobo - assistantKobo * assistants },
    ...Array.from({ length: assistants }, () => ({ role: "assistant" as const, shareBp: assistantBp, kobo: assistantKobo })),
  ];
}

/** Integer division rounding half up, as the server's Rounding::divide. */
function roundDiv(numerator: number, denominator: number): number {
  return Math.floor((numerator * 2 + denominator) / (2 * denominator));
}
