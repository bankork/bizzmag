import { config } from "@/lib/config";
import { emitSessionEnded } from "@/lib/auth/session-events";
import { ApiError, errorFromResponse, networkError } from "./errors";

/**
 * The single way the web app talks to the Laravel API (Stage 3 §5.2).
 *
 * - Sends the session cookie (`credentials: "include"`) for Sanctum SPA auth.
 * - Fetches the CSRF cookie once before the first state-changing request and
 *   echoes it back as X-XSRF-TOKEN.
 * - Retries once after a 419 (expired CSRF token).
 * - Sends an Idempotency-Key when the caller provides one.
 * - Sends FormData as multipart (file uploads) without forcing JSON.
 * - Reports a lost session (401) so the app can return to sign-in.
 * - Unwraps the { success, data, meta } envelope or throws an ApiError.
 */

export type ApiSuccess<T> = {
  data: T;
  meta?: Record<string, unknown>;
  replayed: boolean;
  requestId: string | null;
};

export type ApiRequestOptions = {
  method?: "GET" | "POST" | "PUT" | "PATCH" | "DELETE";
  body?: unknown;
  formData?: FormData;
  query?: Record<string, string | number | boolean | null | undefined>;
  idempotencyKey?: string;
  signal?: AbortSignal;
};

const XSRF_COOKIE = "XSRF-TOKEN";
let csrfReady: Promise<void> | null = null;

export async function apiFetch<T>(path: string, options: ApiRequestOptions = {}): Promise<ApiSuccess<T>> {
  const method = options.method ?? "GET";
  const mutating = method !== "GET";

  if (mutating) {
    await ensureCsrfCookie();
  }

  let response = await send(path, options);

  if (response.status === 419 && mutating) {
    csrfReady = null;
    await ensureCsrfCookie();
    response = await send(path, options);
  }

  return unwrap<T>(response);
}

/** Exposed for tests. */
export function resetCsrfStateForTests(): void {
  csrfReady = null;
}

async function send(path: string, options: ApiRequestOptions): Promise<Response> {
  const headers: Record<string, string> = {
    Accept: "application/json",
    "X-Requested-With": "XMLHttpRequest",
  };

  if (options.body !== undefined && options.formData === undefined) {
    headers["Content-Type"] = "application/json";
  }

  const xsrf = readCookie(XSRF_COOKIE);

  if (xsrf) {
    headers["X-XSRF-TOKEN"] = xsrf;
  }

  if (options.idempotencyKey) {
    headers["Idempotency-Key"] = options.idempotencyKey;
  }

  try {
    return await fetch(buildUrl(path, options.query), {
      method: options.method ?? "GET",
      headers,
      body: options.formData ?? (options.body === undefined ? undefined : JSON.stringify(options.body)),
      credentials: "include",
      signal: options.signal,
    });
  } catch (error) {
    if (error instanceof DOMException && error.name === "AbortError") {
      throw error;
    }

    throw networkError();
  }
}

async function unwrap<T>(response: Response): Promise<ApiSuccess<T>> {
  const requestId = response.headers.get("X-Request-Id");
  const body: unknown = await response.json().catch(() => null);

  if (!response.ok) {
    const error = errorFromResponse(response.status, body, requestId);

    if (error.status === 401) {
      emitSessionEnded(error.code === "SESSION_IDLE" ? "idle" : "unauthenticated");
    } else if (error.code === "ACCOUNT_DISABLED") {
      emitSessionEnded("disabled");
    }

    throw error;
  }

  const envelope = (body ?? {}) as { data?: T; meta?: Record<string, unknown> };

  return {
    data: envelope.data as T,
    meta: envelope.meta,
    replayed: response.headers.get("Idempotent-Replayed") === "true",
    requestId,
  };
}

function ensureCsrfCookie(): Promise<void> {
  if (readCookie(XSRF_COOKIE)) {
    return Promise.resolve();
  }

  csrfReady ??= fetch(`${config.apiUrl}/sanctum/csrf-cookie`, {
    credentials: "include",
    headers: { Accept: "application/json" },
  })
    .then(() => undefined)
    .catch(() => {
      csrfReady = null;
      throw networkError();
    });

  return csrfReady;
}

function buildUrl(path: string, query?: ApiRequestOptions["query"]): string {
  const url = new URL(`${config.apiUrl}/api/v1/${path.replace(/^\/+/, "")}`);

  for (const [key, value] of Object.entries(query ?? {})) {
    if (value !== null && value !== undefined) {
      url.searchParams.set(key, String(value));
    }
  }

  return url.toString();
}

function readCookie(name: string): string | null {
  if (typeof document === "undefined") {
    return null;
  }

  const match = document.cookie.split("; ").find((cookie) => cookie.startsWith(`${name}=`));

  return match ? decodeURIComponent(match.slice(name.length + 1)) : null;
}

export { ApiError };
