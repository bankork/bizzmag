/**
 * The API client reports a lost session (401) here, so the auth provider can
 * send the person to sign in with the right message, whichever screen they
 * were on.
 */
export type SessionEndReason = "idle" | "signed-out" | "disabled" | "unauthenticated";

type Listener = (reason: SessionEndReason) => void;

const listeners = new Set<Listener>();

export function onSessionEnded(listener: Listener): () => void {
  listeners.add(listener);

  return () => {
    listeners.delete(listener);
  };
}

export function emitSessionEnded(reason: SessionEndReason): void {
  for (const listener of listeners) {
    listener(reason);
  }
}

const REASON_KEY = "bizzmag:signed-out-reason";

/** Remember why the session ended, to explain it on the sign-in page. */
export function rememberSignOutReason(reason: SessionEndReason): void {
  try {
    sessionStorage.setItem(REASON_KEY, reason);
  } catch {
    // Private mode or storage blocked: the message is a nicety only.
  }
}

/** Read the reason without clearing it. */
export function peekSignOutReason(): SessionEndReason | null {
  try {
    return sessionStorage.getItem(REASON_KEY) as SessionEndReason | null;
  } catch {
    return null;
  }
}

export function takeSignOutReason(): SessionEndReason | null {
  try {
    const value = sessionStorage.getItem(REASON_KEY);
    sessionStorage.removeItem(REASON_KEY);

    return value === "idle" || value === "signed-out" || value === "disabled" || value === "unauthenticated" ? value : null;
  } catch {
    return null;
  }
}
