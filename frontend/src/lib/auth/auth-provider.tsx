"use client";

import { createContext, useContext, useMemo, type ReactNode } from "react";

/**
 * Who is signed in and what they may see (Stage 3 §5.2).
 *
 * Phase 0 shell: always "anonymous". Phase 1 loads the user, business and
 * permissions from GET /api/v1/me. Permissions here only decide what the
 * interface shows; the API enforces every permission on its own.
 */
export type AuthUser = {
  id: number;
  name: string;
};

export type AuthState = {
  status: "loading" | "anonymous" | "authenticated";
  user: AuthUser | null;
  permissions: readonly string[];
  can: (permission: string) => boolean;
};

const AuthContext = createContext<AuthState | null>(null);

export function AuthProvider({ children }: { children: ReactNode }) {
  const value = useMemo<AuthState>(() => {
    const permissions: readonly string[] = [];

    return {
      status: "anonymous",
      user: null,
      permissions,
      can: (permission) => permissions.includes(permission),
    };
  }, []);

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthState {
  const context = useContext(AuthContext);

  if (!context) {
    throw new Error("useAuth must be used inside AuthProvider.");
  }

  return context;
}
