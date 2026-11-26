const ALPHABET = "abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789";

/**
 * A readable temporary password, for example "Temp-k7Rm4qXa". The person must
 * replace it at their first sign-in.
 */
export function temporaryPassword(length = 8): string {
  const bytes = new Uint8Array(length);
  crypto.getRandomValues(bytes);

  return `Temp-${Array.from(bytes, (byte) => ALPHABET[byte % ALPHABET.length]).join("")}`;
}
