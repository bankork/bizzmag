"use client";

import { QueryClientProvider } from "@tanstack/react-query";
import { useState, type ReactNode } from "react";
import { makeQueryClient } from "@/lib/api/query-client";
import { AuthProvider } from "@/lib/auth/auth-provider";
import { OfflineBanner } from "@/components/shared/offline-banner";
import { UpdateBanner } from "@/components/shared/update-banner";

export function Providers({ children }: { children: ReactNode }) {
  const [queryClient] = useState(makeQueryClient);

  return (
    <QueryClientProvider client={queryClient}>
      <AuthProvider>
        <OfflineBanner />
        <UpdateBanner />
        {children}
      </AuthProvider>
    </QueryClientProvider>
  );
}
