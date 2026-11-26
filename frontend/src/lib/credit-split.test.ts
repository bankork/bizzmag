import { describe, expect, it } from "vitest";
import { splitExample } from "./credit-split";

describe("splitExample", () => {
  it("gives a single person everything", () => {
    expect(splitExample(4_000_000, 1, "primary_assistant", 6_000)).toEqual([{ role: "primary", shareBp: 10_000, kobo: 4_000_000 }]);
  });

  it("splits equally and gives the remainder to the primary", () => {
    expect(splitExample(1_000_000, 3, "equal", null)).toEqual([
      { role: "primary", shareBp: 3_334, kobo: 333_334 },
      { role: "assistant", shareBp: 3_333, kobo: 333_333 },
      { role: "assistant", shareBp: 3_333, kobo: 333_333 },
    ]);
  });

  it("gives the primary their share and the assistants the rest", () => {
    expect(splitExample(4_000_000, 3, "primary_assistant", 6_000)).toEqual([
      { role: "primary", shareBp: 6_000, kobo: 2_400_000 },
      { role: "assistant", shareBp: 2_000, kobo: 800_000 },
      { role: "assistant", shareBp: 2_000, kobo: 800_000 },
    ]);
  });

  it("uses 50% when no primary share is set", () => {
    expect(splitExample(1_000_001, 2, "primary_assistant", null).map((share) => share.kobo)).toEqual([500_000, 500_001]);
  });

  it("always adds up to the base", () => {
    for (const staff of [2, 3, 4, 7]) {
      const shares = splitExample(1_234_567, staff, "primary_assistant", 3_333);

      expect(shares.reduce((total, share) => total + share.kobo, 0)).toBe(1_234_567);
      expect(shares.reduce((total, share) => total + share.shareBp, 0)).toBe(10_000);
    }
  });
});
