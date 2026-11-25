"use client";

import { Button } from "@/components/ui/button";
import { useServiceWorker } from "@/lib/pwa/use-service-worker";

export function UpdateBanner() {
  const { updateReady, applyUpdate } = useServiceWorker();

  if (!updateReady) {
    return null;
  }

  return (
    <div role="status" className="flex items-center justify-center gap-3 bg-primary px-4 py-2 text-sm text-primary-foreground">
      A new version of the app is ready.
      <Button size="sm" variant="secondary" onClick={applyUpdate}>
        Refresh now
      </Button>
    </div>
  );
}
