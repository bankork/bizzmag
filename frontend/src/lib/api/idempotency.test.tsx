import { act, renderHook } from "@testing-library/react";
import { describe, expect, it } from "vitest";
import { useIdempotencyKey } from "./idempotency";

describe("useIdempotencyKey", () => {
  it("keeps the same key across re-renders, so a retry replays instead of repeating", () => {
    const { result, rerender } = renderHook(() => useIdempotencyKey());
    const first = result.current.key;

    rerender();

    expect(result.current.key).toBe(first);
  });

  it("issues a new key after reset, for the next action", () => {
    const { result } = renderHook(() => useIdempotencyKey());
    const first = result.current.key;

    act(() => result.current.reset());

    expect(result.current.key).not.toBe(first);
  });
});
