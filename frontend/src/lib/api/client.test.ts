import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { apiFetch, resetCsrfStateForTests } from "./client";
import { ApiError } from "./errors";

function jsonResponse(status: number, body: unknown, headers: Record<string, string> = {}): Response {
  return new Response(JSON.stringify(body), {
    status,
    headers: { "Content-Type": "application/json", ...headers },
  });
}

describe("apiFetch", () => {
  beforeEach(() => {
    resetCsrfStateForTests();
    document.cookie = "XSRF-TOKEN=; expires=Thu, 01 Jan 1970 00:00:00 GMT";
  });

  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it("unwraps the success envelope and reports replays", async () => {
    const fetchMock = vi.fn().mockResolvedValue(
      jsonResponse(200, { success: true, data: { api_version: "v1" } }, { "Idempotent-Replayed": "true", "X-Request-Id": "req-1" }),
    );
    vi.stubGlobal("fetch", fetchMock);

    const result = await apiFetch<{ api_version: string }>("meta");

    expect(result.data).toEqual({ api_version: "v1" });
    expect(result.replayed).toBe(true);
    expect(result.requestId).toBe("req-1");
    expect(fetchMock.mock.calls[0][0]).toBe("http://localhost:8000/api/v1/meta");
    expect(fetchMock.mock.calls[0][1]).toMatchObject({ credentials: "include", method: "GET" });
  });

  it("turns an error envelope into an ApiError with its code and field messages", async () => {
    vi.stubGlobal(
      "fetch",
      vi.fn().mockResolvedValue(
        jsonResponse(422, {
          success: false,
          code: "VALIDATION_FAILED",
          message: "Please check the highlighted fields.",
          errors: { amount_kobo: ["The amount kobo field is required."] },
        }),
      ),
    );

    const error = await apiFetch("meta").catch((caught: unknown) => caught);

    expect(error).toBeInstanceOf(ApiError);
    expect((error as ApiError).code).toBe("VALIDATION_FAILED");
    expect((error as ApiError).fieldMessage("amount_kobo")).toBe("The amount kobo field is required.");
  });

  it("reports a network failure as NETWORK_ERROR", async () => {
    vi.stubGlobal("fetch", vi.fn().mockRejectedValue(new TypeError("Failed to fetch")));

    const error = await apiFetch("meta").catch((caught: unknown) => caught);

    expect((error as ApiError).code).toBe("NETWORK_ERROR");
  });

  it("fetches the CSRF cookie before a mutation and sends the idempotency key", async () => {
    const fetchMock = vi.fn().mockImplementation(async (url: string) => {
      if (url.endsWith("/sanctum/csrf-cookie")) {
        document.cookie = "XSRF-TOKEN=abc%3D123";

        return new Response(null, { status: 204 });
      }

      return jsonResponse(201, { success: true, data: { id: 1 } });
    });
    vi.stubGlobal("fetch", fetchMock);

    await apiFetch("orders/1/payments", { method: "POST", body: { amount_kobo: 1000000 }, idempotencyKey: "key-0001-abcd" });

    expect(fetchMock.mock.calls[0][0]).toBe("http://localhost:8000/sanctum/csrf-cookie");
    const headers = fetchMock.mock.calls[1][1].headers as Record<string, string>;
    expect(headers["X-XSRF-TOKEN"]).toBe("abc=123");
    expect(headers["Idempotency-Key"]).toBe("key-0001-abcd");
  });

  it("refreshes the CSRF cookie and retries once after a 419", async () => {
    document.cookie = "XSRF-TOKEN=expired";
    let attempts = 0;
    const fetchMock = vi.fn().mockImplementation(async (url: string) => {
      if (url.endsWith("/sanctum/csrf-cookie")) {
        document.cookie = "XSRF-TOKEN=fresh";

        return new Response(null, { status: 204 });
      }

      attempts++;

      return attempts === 1
        ? jsonResponse(419, { success: false, code: "CSRF_TOKEN_MISMATCH", message: "Expired." })
        : jsonResponse(200, { success: true, data: { ok: true } });
    });
    vi.stubGlobal("fetch", fetchMock);

    const result = await apiFetch<{ ok: boolean }>("settings", { method: "PATCH", body: {} });

    expect(result.data.ok).toBe(true);
    expect(attempts).toBe(2);
  });

  it("adds query parameters and skips empty ones", async () => {
    const fetchMock = vi.fn().mockResolvedValue(jsonResponse(200, { success: true, data: [] }));
    vi.stubGlobal("fetch", fetchMock);

    await apiFetch("orders", { query: { "filter[status]": "open", page: 2, empty: undefined } });

    expect(fetchMock.mock.calls[0][0]).toBe("http://localhost:8000/api/v1/orders?filter%5Bstatus%5D=open&page=2");
  });
});
