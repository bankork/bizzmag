/**
 * Live preview of document number formats on the Settings screen. Mirrors
 * App\Domain\Business\Settings\NumberFormat on the backend, which remains
 * the authority: the server validates and issues every real number.
 */
const TOKEN = /\{(YYYY|YY|MM|DD|YYMMDD|SEQ:(\d+))\}/g;

export function previewNumber(format: string, date: Date, sequence = 1): string {
  const parts = lagosDateParts(date);

  return format.replace(TOKEN, (_match, token: string, width?: string) => {
    switch (token) {
      case "YYYY":
        return parts.year;
      case "YY":
        return parts.year.slice(2);
      case "MM":
        return parts.month;
      case "DD":
        return parts.day;
      case "YYMMDD":
        return `${parts.year.slice(2)}${parts.month}${parts.day}`;
      default:
        return String(sequence).padStart(Number(width), "0");
    }
  });
}

/** The same plain-English problems the server reports. Empty when valid. */
export function numberFormatProblems(format: string): string[] {
  if (format.trim() === "") {
    return ["Enter a format."];
  }

  const problems: string[] = [];

  if (format.length > 40) {
    problems.push("Keep the format to 40 characters or fewer.");
  }

  const sequences = [...format.matchAll(/\{SEQ:(\d+)\}/g)];

  if (sequences.length !== 1) {
    problems.push("Include the running number exactly once, for example {SEQ:5}.");
  } else {
    const width = Number(sequences[0][1]);

    if (width < 1 || width > 8) {
      problems.push("The running number can have 1 to 8 digits.");
    }
  }

  const literal = format.replace(TOKEN, "");

  if (literal.includes("{") || literal.includes("}")) {
    problems.push("Use only these codes: {YYYY}, {YY}, {MM}, {DD}, {YYMMDD} and {SEQ:n}.");
  } else if (!/^[A-Za-z0-9\-/_.]*$/.test(literal)) {
    problems.push("Use only letters, numbers and - / _ . between the codes.");
  }

  return problems;
}

function lagosDateParts(date: Date): { year: string; month: string; day: string } {
  const parts = new Intl.DateTimeFormat("en-GB", { timeZone: "Africa/Lagos", year: "numeric", month: "2-digit", day: "2-digit" }).formatToParts(date);
  const pick = (type: string) => parts.find((part) => part.type === type)?.value ?? "";

  return { year: pick("year"), month: pick("month"), day: pick("day") };
}
