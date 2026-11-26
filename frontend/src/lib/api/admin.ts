"use client";

import { keepPreviousData, useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { apiFetch } from "./client";
import type { BusinessSettings, HistoryEntry, Pagination, PermissionDefinition, Role, StaffUser, TaxSetting } from "./types";

/**
 * Queries and mutations for the Phase 1 admin screens. Every write returns
 * the saved record from the server, which replaces the cached copy.
 */

export type UserFilters = { q?: string; status?: "active" | "disabled" | ""; page?: number };

export const adminKeys = {
  users: (filters: UserFilters) => ["users", filters] as const,
  user: (id: number) => ["user", id] as const,
  roles: ["roles"] as const,
  permissions: ["permissions"] as const,
  settings: ["settings"] as const,
  tax: ["settings", "tax"] as const,
  history: (area?: string) => ["settings", "history", area ?? "all"] as const,
};

export function useUsers(filters: UserFilters) {
  return useQuery({
    queryKey: adminKeys.users(filters),
    queryFn: async () => {
      const result = await apiFetch<StaffUser[]>("users", {
        query: { "filter[q]": filters.q || undefined, "filter[status]": filters.status || undefined, page: filters.page },
      });

      return { users: result.data, pagination: result.meta?.pagination as Pagination | undefined };
    },
    placeholderData: keepPreviousData,
  });
}

export function useUser(id: number | null) {
  return useQuery({
    queryKey: adminKeys.user(id ?? 0),
    queryFn: async () => (await apiFetch<StaffUser>(`users/${id}`)).data,
    enabled: id !== null,
  });
}

export function useRoles(enabled = true) {
  return useQuery({ queryKey: adminKeys.roles, queryFn: async () => (await apiFetch<Role[]>("roles")).data, enabled });
}

export function usePermissionCatalogue() {
  return useQuery({
    queryKey: adminKeys.permissions,
    queryFn: async () => (await apiFetch<PermissionDefinition[]>("permissions")).data,
    staleTime: Infinity,
  });
}

export function useSettings() {
  return useQuery({ queryKey: adminKeys.settings, queryFn: async () => (await apiFetch<BusinessSettings>("settings")).data });
}

export function useTax() {
  return useQuery({ queryKey: adminKeys.tax, queryFn: async () => (await apiFetch<TaxSetting>("settings/tax")).data });
}

export function useSettingsHistory(area?: "tax" | "settings") {
  return useQuery({
    queryKey: adminKeys.history(area),
    queryFn: async () => (await apiFetch<HistoryEntry[]>("settings/history", { query: { "filter[area]": area } })).data,
  });
}

/** A user write that refreshes the list and the user's own cache entry. */
export function useUserMutation<TVariables>(request: (variables: TVariables) => Promise<StaffUser>) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: request,
    onSuccess: (user) => {
      queryClient.setQueryData(adminKeys.user(user.id), user);
      void queryClient.invalidateQueries({ queryKey: ["users"] });
      void queryClient.invalidateQueries({ queryKey: adminKeys.roles });
    },
  });
}

export function useRoleMutation<TVariables>(request: (variables: TVariables) => Promise<Role | null>) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: request,
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: adminKeys.roles });
      void queryClient.invalidateQueries({ queryKey: ["user"] });
      void queryClient.invalidateQueries({ queryKey: ["me"] });
    },
  });
}

export function useSettingsMutation<TVariables>(request: (variables: TVariables) => Promise<BusinessSettings>) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: request,
    onSuccess: (settings) => {
      queryClient.setQueryData(adminKeys.settings, settings);
      void queryClient.invalidateQueries({ queryKey: ["settings", "history"] });
      void queryClient.invalidateQueries({ queryKey: ["me"] });
    },
  });
}

export function useTaxMutation(request: (tax: Omit<TaxSetting, "updated_at">) => Promise<TaxSetting>) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: request,
    onSuccess: (tax) => {
      queryClient.setQueryData(adminKeys.tax, tax);
      void queryClient.invalidateQueries({ queryKey: ["settings", "history"] });
    },
  });
}
