/**
 * Display and input helpers. Amounts stay integer kobo and rates stay basis
 * points (10000 = 100%); these functions only convert what people type and
 * read.
 */

/** 750 -> "7.5%", 1000 -> "10%". */
export function formatBasisPoints(basisPoints: number): string {
  return `${trimDecimals(basisPoints / 100)}%`;
}

/** "7.5" or "7.5%" -> 750. Returns null when it is not a valid percentage. */
export function parsePercent(input: string): number | null {
  const cleaned = input.trim().replace(/%$/, "").trim();

  if (!/^\d{1,3}(\.\d{1,2})?$/.test(cleaned)) {
    return null;
  }

  const basisPoints = Math.round(Number(cleaned) * 100);

  return basisPoints <= 10_000 ? basisPoints : null;
}

/** "5,000" or "₦5,000.50" -> 500050 kobo. Returns null when not a valid amount. */
export function parseNaira(input: string): number | null {
  const cleaned = input.trim().replace(/[₦,\s]/g, "");

  if (!/^\d{1,12}(\.\d{1,2})?$/.test(cleaned)) {
    return null;
  }

  const [naira, kobo = ""] = cleaned.split(".");

  return Number(naira) * 100 + Number(kobo.padEnd(2, "0"));
}

/** 500000 -> "5,000"; 500050 -> "5,000.50". For filling an input. */
export function koboToNairaInput(kobo: number): string {
  const naira = Math.floor(kobo / 100).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
  const remainder = kobo % 100;

  return remainder === 0 ? naira : `${naira}.${remainder.toString().padStart(2, "0")}`;
}

/** A date and time in the salon's timezone, for example "25 Nov 2026, 14:05". */
export function formatDateTime(iso: string | null, timeZone = "Africa/Lagos"): string {
  if (!iso) {
    return "Never";
  }

  return new Intl.DateTimeFormat("en-NG", { dateStyle: "medium", timeStyle: "short", timeZone }).format(new Date(iso));
}

/** "+2348031234567" -> "0803 123 4567" for Nigerian numbers. */
export function formatPhone(e164: string | null): string {
  if (!e164) {
    return "";
  }

  const match = /^\+234(\d{3})(\d{3})(\d{4})$/.exec(e164);

  return match ? `0${match[1]} ${match[2]} ${match[3]}` : e164;
}

function trimDecimals(value: number): string {
  return Number.isInteger(value) ? value.toString() : value.toFixed(2).replace(/0+$/, "").replace(/\.$/, "");
}
