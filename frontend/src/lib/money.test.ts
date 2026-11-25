import { describe, expect, it } from "vitest";
import { formatKobo, formatKoboCompact } from "./money";

describe("formatKobo", () => {
  it.each([
    [3500000, "₦35,000.00"],
    [2902500, "₦29,025.00"],
    [4547250, "₦45,472.50"],
    [5, "₦0.05"],
    [0, "₦0.00"],
    [-200000, "-₦2,000.00"],
    [12000000000, "₦120,000,000.00"],
  ])("formats %i kobo as %s", (kobo, expected) => {
    expect(formatKobo(kobo)).toBe(expected);
  });

  it("refuses fractional kobo, which would mean money was calculated with floats", () => {
    expect(() => formatKobo(100.5)).toThrow(RangeError);
  });
});

describe("formatKoboCompact", () => {
  it("rounds half up to the nearest naira and drops the kobo", () => {
    expect(formatKoboCompact(4547250)).toBe("₦45,473");
    expect(formatKoboCompact(4547249)).toBe("₦45,472");
  });
});
