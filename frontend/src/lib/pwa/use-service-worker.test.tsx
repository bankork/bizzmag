import { act, renderHook, waitFor } from "@testing-library/react";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { useServiceWorker } from "./use-service-worker";

type Listener = () => void;

function fakeServiceWorkerContainer(waitingWorker: { postMessage: ReturnType<typeof vi.fn> } | null) {
  const listeners: Record<string, Listener[]> = {};

  return {
    controller: {},
    register: vi.fn().mockResolvedValue({ waiting: waitingWorker, installing: null, addEventListener: vi.fn() }),
    addEventListener: (type: string, listener: Listener) => {
      (listeners[type] ??= []).push(listener);
    },
    removeEventListener: vi.fn(),
    fire: (type: string) => listeners[type]?.forEach((listener) => listener()),
  };
}

describe("useServiceWorker", () => {
  const reload = vi.fn();

  beforeEach(() => {
    vi.stubEnv("NODE_ENV", "production");
    vi.stubGlobal("location", { ...window.location, reload });
    reload.mockReset();
  });

  afterEach(() => {
    vi.unstubAllEnvs();
    vi.unstubAllGlobals();
  });

  it("does not reload when the first service worker takes control", async () => {
    const container = fakeServiceWorkerContainer(null);
    Object.defineProperty(navigator, "serviceWorker", { value: container, configurable: true });

    renderHook(() => useServiceWorker());
    await waitFor(() => expect(container.register).toHaveBeenCalled());
    container.fire("controllerchange");

    expect(reload).not.toHaveBeenCalled();
  });

  it("reloads once the person has asked for the update", async () => {
    const worker = { postMessage: vi.fn() };
    const container = fakeServiceWorkerContainer(worker);
    Object.defineProperty(navigator, "serviceWorker", { value: container, configurable: true });

    const { result } = renderHook(() => useServiceWorker());
    await waitFor(() => expect(result.current.updateReady).toBe(true));

    act(() => result.current.applyUpdate());
    container.fire("controllerchange");

    expect(worker.postMessage).toHaveBeenCalledWith({ type: "SKIP_WAITING" });
    expect(reload).toHaveBeenCalledTimes(1);
  });
});
