"use client";

import { WifiOff } from "lucide-react";
import { useOnlineStatus } from "@/lib/pwa/use-online-status";

export function OfflineBanner() {
  const online = useOnlineStatus();

  if (online) {
    return null;
  }

  return (
    <div role="status" className="flex items-center justify-center gap-2 bg-amber-100 px-4 py-2 text-sm text-amber-900">
      <WifiOff aria-hidden className="size-4" />
      You are offline. Payments and service records will be available again when you reconnect.
    </div>
  );
}
