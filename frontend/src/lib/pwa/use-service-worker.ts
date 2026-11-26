"use client";

import { useCallback, useEffect, useRef, useState } from "react";

/**
 * Registers /sw.js in production builds and reports when a new version is
 * waiting (NFR-PWA-007). The new version is applied only when the user
 * agrees, so a form that is being filled in is never interrupted.
 *
 * The page reloads only after the person asked for the update. The first
 * install also changes the controlling worker (clients.claim), and
 * reloading then would cut off whatever they were doing, such as signing in.
 */
export function useServiceWorker(): { updateReady: boolean; applyUpdate: () => void } {
  const [waiting, setWaiting] = useState<ServiceWorker | null>(null);
  const updateRequested = useRef(false);

  useEffect(() => {
    if (process.env.NODE_ENV !== "production" || !("serviceWorker" in navigator)) {
      return;
    }

    let reloading = false;

    const onControllerChange = () => {
      if (updateRequested.current && !reloading) {
        reloading = true;
        window.location.reload();
      }
    };

    navigator.serviceWorker.addEventListener("controllerchange", onControllerChange);

    navigator.serviceWorker
      .register("/sw.js", { scope: "/" })
      .then((registration) => {
        if (registration.waiting && navigator.serviceWorker.controller) {
          setWaiting(registration.waiting);
        }

        registration.addEventListener("updatefound", () => {
          const installing = registration.installing;

          installing?.addEventListener("statechange", () => {
            if (installing.state === "installed" && navigator.serviceWorker.controller) {
              setWaiting(installing);
            }
          });
        });
      })
      .catch(() => {
        // The app still works without the service worker; it is only lost offline support.
      });

    return () => navigator.serviceWorker.removeEventListener("controllerchange", onControllerChange);
  }, []);

  const applyUpdate = useCallback(() => {
    updateRequested.current = true;
    waiting?.postMessage({ type: "SKIP_WAITING" });
  }, [waiting]);

  return { updateReady: waiting !== null, applyUpdate };
}
