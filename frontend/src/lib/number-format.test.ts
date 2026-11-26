import { describe, expect, it } from "vitest";
import { numberFormatProblems, previewNumber } from "./number-format";

// The same cases as the backend NumberFormatTest, so preview and server agree.
const date = new Date("2026-11-25T13:00:00Z");

describe("number format preview", () => {
  it.each([
    ["V-{YYMMDD}-{SEQ:3}", 7, "V-261125-007"],
    ["INV-{YYYY}-{SEQ:5}", 77, "INV-2026-00077"],
    ["RCT/{YY}{MM}/{SEQ:4}", 12, "RCT/2611/0012"],
    ["{DD}.{MM}.{YYYY}-{SEQ:2}", 3, "25.11.2026-03"],
    ["N{SEQ:2}", 1234, "N1234"],
  ])("renders %s", (format, sequence, expected) => {
    expect(numberFormatProblems(format)).toEqual([]);
    expect(previewNumber(format, date, sequence)).toBe(expected);
  });

  it("uses the Lagos date, not the device date", () => {
    expect(previewNumber("{YYMMDD}-{SEQ:1}", new Date("2026-11-25T23:30:00Z"))).toBe("261126-1");
  });

  it.each([
    ["INV-{YYYY}", "Include the running number exactly once, for example {SEQ:5}."],
    ["INV-{SEQ:9}", "The running number can have 1 to 8 digits."],
    ["INV-{WEEK}-{SEQ:3}", "Use only these codes: {YYYY}, {YY}, {MM}, {DD}, {YYMMDD} and {SEQ:n}."],
    ["INV {SEQ:3}", "Use only letters, numbers and - / _ . between the codes."],
    ["", "Enter a format."],
  ])("explains what is wrong with %s", (format, problem) => {
    expect(numberFormatProblems(format)).toContain(problem);
  });
});
