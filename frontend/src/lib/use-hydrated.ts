import { useSyncExternalStore } from "react";

const subscribe = () => () => {};

/**
 * False in the prerendered HTML, true once React runs in the browser. Forms
 * use it to stay disabled until they can handle a submit themselves.
 */
export function useHydrated(): boolean {
  return useSyncExternalStore(
    subscribe,
    () => true,
    () => false,
  );
}
