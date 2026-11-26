import { waitFor } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import { apiFetch } from "@/lib/api/client";
import { ME_QUERY_KEY } from "@/lib/auth/auth-provider";
import { makeMe, renderWithAuth } from "@/test/render";
import { takeSignOutReason } from "./session-events";

describe("losing the session", () => {
  afterEach(() => {
    vi.unstubAllGlobals();
    sessionStorage.clear();
  });

  it("signs the browser out and remembers why when the server says the session went idle", async () => {
    vi.stubGlobal(
      "fetch",
      vi.fn().mockResolvedValue(new Response(JSON.stringify({ success: false, code: "SESSION_IDLE", message: "Signed out." }), { status: 401 })),
    );
    const { queryClient } = renderWithAuth(<div />, makeMe());

    await expect(apiFetch("users")).rejects.toMatchObject({ code: "SESSION_IDLE" });

    await waitFor(() => expect(queryClient.getQueryData(ME_QUERY_KEY)).toBeNull());
    expect(takeSignOutReason()).toBe("idle");
  });
});
