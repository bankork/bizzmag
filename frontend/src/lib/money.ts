/**
 * Display formatting for money received from the API as integer kobo.
 *
 * The frontend never calculates authoritative amounts (SRS BR-016). This
 * module only formats, using integer arithmetic so no kobo is lost to
 * floating point.
 */
export function formatKobo(kobo: number): string {
  if (!Number.isSafeInteger(kobo)) {
    throw new RangeError(`Money must be a whole number of kobo, got ${kobo}.`);
  }

  const negative = kobo < 0;
  const absolute = Math.abs(kobo);
  const naira = Math.floor(absolute / 100);
  const remainder = absolute % 100;

  const grouped = naira.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
  const koboPart = remainder.toString().padStart(2, "0");

  return `${negative ? "-" : ""}₦${grouped}.${koboPart}`;
}

/**
 * Whole-naira display without kobo, for compact places such as dashboard tiles.
 * Rounds half up to the nearest naira.
 */
export function formatKoboCompact(kobo: number): string {
  const rounded = Math.sign(kobo) * Math.floor((Math.abs(kobo) + 50) / 100) * 100;

  return formatKobo(rounded).replace(/\.00$/, "");
}
