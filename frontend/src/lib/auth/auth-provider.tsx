"use client";

import { useQuery, useQueryClient } from "@tanstack/react-query";
import { createContext, useCallback, useContext, useEffect, useMemo, type ReactNode } from "react";
import { apiFetch } from "@/lib/api/client";
import { ApiError } from "@/lib/api/errors";
import type { Me } from "@/lib/api/types";
import { onSessionEnded, rememberSignOutReason } from "./session-events";

/**
 * Who is signed in and what they may see (Stage 3 §6.3), from GET /me.
 *
 * Permissions here only decide what the interface shows. The API checks
 * every request on its own, so hiding something is never the protection.
 */
export type AuthState = {
  status: "loading" | "anonymous" | "authenticated";
  me: Me | null;
  permissions: readonly string[];
  can: (permission: string) => boolean;
  canAny: (permissions: readonly string[]) => boolean;
  /** Holds the Owner role. Only for the few owner-only actions, such as removing a customer's personal data. */
  isOwner: boolean;
  signIn: (identifier: string, password: string) => Promise<Me>;
  signOut: () => Promise<void>;
  /** Replace the cached session, for example after a password change. */
  setMe: (me: Me) => void;
};

export const ME_QUERY_KEY = ["me"] as const;

const AuthContext = createContext<AuthState | null>(null);

async function fetchMe(): Promise<Me | null> {
  try {
    return (await apiFetch<Me>("me")).data;
  } catch (error) {
    if (error instanceof ApiError && (error.status === 401 || error.code === "ACCOUNT_DISABLED" || error.code === "NO_BUSINESS_ACCESS")) {
      return null;
    }

    throw error;
  }
}

export function AuthProvider({ children }: { children: ReactNode }) {
  const queryClient = useQueryClient();
  const meQuery = useQuery({ queryKey: ME_QUERY_KEY, queryFn: fetchMe, staleTime: 60_000, retry: false });

  useEffect(
    () =>
      onSessionEnded((reason) => {
        if (queryClient.getQueryData(ME_QUERY_KEY)) {
          rememberSignOutReason(reason);
        }

        queryClient.setQueryData(ME_QUERY_KEY, null);
        queryClient.removeQueries({ predicate: (query) => query.queryKey[0] !== ME_QUERY_KEY[0] });
      }),
    [queryClient],
  );

  const signIn = useCallback(
    async (identifier: string, password: string) => {
      const me = (await apiFetch<Me>("auth/login", { method: "POST", body: { identifier, password } })).data;
      queryClient.setQueryData(ME_QUERY_KEY, me);

      return me;
    },
    [queryClient],
  );

  const signOut = useCallback(async () => {
    try {
      await apiFetch<null>("auth/logout", { method: "POST" });
    } catch {
      // Already signed out on the server: carry on clearing this browser.
    }

    rememberSignOutReason("signed-out");
    queryClient.setQueryData(ME_QUERY_KEY, null);
    queryClient.removeQueries({ predicate: (query) => query.queryKey[0] !== ME_QUERY_KEY[0] });
  }, [queryClient]);

  const setMe = useCallback((me: Me) => queryClient.setQueryData(ME_QUERY_KEY, me), [queryClient]);

  const value = useMemo<AuthState>(() => {
    const me = meQuery.data ?? null;
    const permissions: readonly string[] = me?.permissions ?? [];
    const status: AuthState["status"] = meQuery.isPending ? "loading" : me ? "authenticated" : "anonymous";

    return {
      status,
      me,
      permissions,
      can: (permission) => permissions.includes(permission),
      canAny: (required) => required.some((permission) => permissions.includes(permission)),
      isOwner: me?.roles.some((role) => role.system_key === "owner") ?? false,
      signIn,
      signOut,
      setMe,
    };
  }, [meQuery.data, meQuery.isPending, signIn, signOut, setMe]);

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthState {
  const context = useContext(AuthContext);

  if (!context) {
    throw new Error("useAuth must be used inside AuthProvider.");
  }

  return context;
}
