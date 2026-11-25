"use client";

import { useCallback, useState } from "react";

/**
 * A new Idempotency-Key for one user intent (SRS §9, Stage 2 §4.4).
 */
export function newIdempotencyKey(): string {
  return crypto.randomUUID();
}

/**
 * Holds one idempotency key for the lifetime of a dialog or form.
 *
 * The key is created when the user opens the action and reused on every
 * retry of that same action, so a retry after a timeout replays the first
 * result instead of, for example, recording a payment twice. Call `reset`
 * after success, when the user starts a new action.
 */
export function useIdempotencyKey(): { key: string; reset: () => void } {
  const [key, setKey] = useState(newIdempotencyKey);
  const reset = useCallback(() => setKey(newIdempotencyKey()), []);

  return { key, reset };
}
