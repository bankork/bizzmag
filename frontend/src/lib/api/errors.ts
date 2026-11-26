/**
 * Every failure from the API, or from reaching it, becomes an ApiError with
 * a stable `code` (SRS §9). Screens branch on the code, never on the message.
 */
export type ApiErrorCode =
  | "VALIDATION_FAILED"
  | "UNAUTHENTICATED"
  | "SESSION_IDLE"
  | "INVALID_CREDENTIALS"
  | "LOGIN_LOCKED"
  | "ACCOUNT_DISABLED"
  | "NO_BUSINESS_ACCESS"
  | "PASSWORD_CHANGE_REQUIRED"
  | "INCORRECT_PASSWORD"
  | "SELF_ACTION_NOT_ALLOWED"
  | "LAST_ADMINISTRATOR"
  | "SYSTEM_ROLE_PROTECTED"
  | "ROLE_IN_USE"
  | "FORBIDDEN"
  | "NOT_FOUND"
  | "CSRF_TOKEN_MISMATCH"
  | "TOO_MANY_REQUESTS"
  | "IDEMPOTENCY_KEY_REQUIRED"
  | "IDEMPOTENCY_MISMATCH"
  | "TOTAL_CHANGED"
  | "EXECUTION_EXISTS"
  | "STALE"
  | "CLOSING_SUBMITTED"
  | "INVALID_TRANSITION"
  | "GATE_BLOCKED"
  | "SOD_VIOLATION"
  | "DUPLICATE_PHONE"
  | "CUSTOMER_REMOVED"
  | "CATEGORY_IN_USE"
  | "SERVER_ERROR"
  | "NETWORK_ERROR"
  | (string & {});

export type FieldErrors = Record<string, string[]>;

export class ApiError extends Error {
  readonly status: number;
  readonly code: ApiErrorCode;
  readonly fields: FieldErrors;
  readonly data: Record<string, unknown>;
  readonly requestId: string | null;

  constructor(options: {
    status: number;
    code: ApiErrorCode;
    message: string;
    fields?: FieldErrors;
    data?: Record<string, unknown>;
    requestId?: string | null;
  }) {
    super(options.message);
    this.name = "ApiError";
    this.status = options.status;
    this.code = options.code;
    this.fields = options.fields ?? {};
    this.data = options.data ?? {};
    this.requestId = options.requestId ?? null;
  }

  /** The first message for a form field, if the server reported one. */
  fieldMessage(field: string): string | undefined {
    return this.fields[field]?.[0];
  }

  get isNetworkError(): boolean {
    return this.code === "NETWORK_ERROR";
  }
}

type ErrorEnvelope = {
  success?: boolean;
  code?: string;
  message?: string;
  errors?: FieldErrors;
  data?: Record<string, unknown>;
};

/**
 * Build an ApiError from a failed HTTP response body. Unknown or non-JSON
 * bodies still produce a usable error with a safe message.
 */
export function errorFromResponse(status: number, body: unknown, requestId: string | null): ApiError {
  const envelope = (typeof body === "object" && body !== null ? body : {}) as ErrorEnvelope;

  return new ApiError({
    status,
    code: envelope.code ?? (status >= 500 ? "SERVER_ERROR" : `HTTP_${status}`),
    message: envelope.message ?? defaultMessage(status),
    fields: envelope.errors,
    data: envelope.data,
    requestId,
  });
}

export function networkError(): ApiError {
  return new ApiError({
    status: 0,
    code: "NETWORK_ERROR",
    message: "You appear to be offline. Check your connection and try again.",
  });
}

function defaultMessage(status: number): string {
  if (status >= 500) {
    return "Something went wrong. Please try again.";
  }

  return "The request could not be completed.";
}
