import { describe, expect, it } from "vitest";
import { describeDiscountCap } from "./discount-cap";
import { formatBasisPoints, formatPhone, koboToNairaInput, parseNaira, parsePercent } from "./format";

describe("rates and amounts", () => {
  it("formats basis points as a percentage", () => {
    expect(formatBasisPoints(750)).toBe("7.5%");
    expect(formatBasisPoints(1000)).toBe("10%");
    expect(formatBasisPoints(1234)).toBe("12.34%");
  });

  it("parses what people type as a percentage", () => {
    expect(parsePercent("7.5")).toBe(750);
    expect(parsePercent(" 10 % ")).toBe(1000);
    expect(parsePercent("0")).toBe(0);
    expect(parsePercent("100")).toBe(10000);
    expect(parsePercent("100.01")).toBeNull();
    expect(parsePercent("7.555")).toBeNull();
    expect(parsePercent("ten")).toBeNull();
  });

  it("parses naira into integer kobo without floating point loss", () => {
    expect(parseNaira("5,000")).toBe(500000);
    expect(parseNaira("₦5,000.50")).toBe(500050);
    expect(parseNaira("0.29")).toBe(29);
    expect(parseNaira("1.5")).toBe(150);
    expect(parseNaira("-5")).toBeNull();
    expect(parseNaira("5.001")).toBeNull();
  });

  it("fills a naira input from kobo", () => {
    expect(koboToNairaInput(500000)).toBe("5,000");
    expect(koboToNairaInput(500050)).toBe("5,000.50");
  });

  it("shows Nigerian numbers the local way", () => {
    expect(formatPhone("+2348031234567")).toBe("0803 123 4567");
    expect(formatPhone("+442079460958")).toBe("+442079460958");
    expect(formatPhone(null)).toBe("");
  });

  it("describes discount limits in plain words", () => {
    expect(describeDiscountCap(null, null)).toBe("No limit");
    expect(describeDiscountCap(1000, 500000)).toBe("Up to 10% and at most ₦5,000 per bill");
    expect(describeDiscountCap(3000, null)).toBe("Up to 30%");
    expect(describeDiscountCap(0, 0)).toBe("No discounts");
  });
});
