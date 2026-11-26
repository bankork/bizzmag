import { describe, expect, it } from "vitest";
import type { HistoryEntry } from "@/lib/api/types";
import { describeTaxChange } from "./tax-settings";

const entry = (before: Record<string, unknown>, after: Record<string, unknown>): HistoryEntry => ({ id: 1, action: "tax.updated", actor: null, before, after, at: null });

describe("tax change history", () => {
  it("describes switching tax on and off", () => {
    expect(describeTaxChange(entry({ enabled: false, name: "VAT", rate_bp: 750, mode: "exclusive" }, { enabled: true, name: "VAT", rate_bp: 750, mode: "exclusive" }))).toBe(
      "Switched on: VAT 7.5% added on top",
    );
    expect(describeTaxChange(entry({ enabled: true, name: "VAT", rate_bp: 750, mode: "inclusive" }, { enabled: false, name: "VAT", rate_bp: 750, mode: "inclusive" }))).toBe(
      "Switched off (was VAT 7.5% included in prices)",
    );
  });

  it("describes a rate change", () => {
    expect(describeTaxChange(entry({ enabled: true, name: "VAT", rate_bp: 750, mode: "exclusive" }, { enabled: true, name: "VAT", rate_bp: 1000, mode: "exclusive" }))).toBe(
      "Changed from VAT 7.5% added on top to VAT 10% added on top",
    );
  });
});
