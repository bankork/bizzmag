"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { apiFetch } from "./client";
import type { AppNotification } from "./types";

/** FR-NOT-001: refreshed at least every 30 seconds while the app is open. */
export const NOTIFICATION_REFRESH_MS = 30_000;

const notificationKey = ["notifications"] as const;

export function useNotifications(enabled: boolean) {
  return useQuery({
    queryKey: notificationKey,
    queryFn: async () => {
      const result = await apiFetch<AppNotification[]>("notifications");

      return { notifications: result.data, unread: Number(result.meta?.unread_count ?? 0) };
    },
    enabled,
    refetchInterval: NOTIFICATION_REFRESH_MS,
    refetchIntervalInBackground: false,
  });
}

export function useMarkNotificationsRead() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (ids: number[] | "all") => (await apiFetch<{ marked: number }>("notifications/read", { method: "POST", body: ids === "all" ? { all: true } : { ids } })).data,
    onSuccess: () => void queryClient.invalidateQueries({ queryKey: notificationKey }),
  });
}
