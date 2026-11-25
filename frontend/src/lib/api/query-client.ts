import { QueryClient } from "@tanstack/react-query";
import { ApiError } from "./errors";

/**
 * Server state lives in TanStack Query (ADR-06). Client errors (4xx) are not
 * retried, because retrying a refused request cannot change the answer.
 * Mutations are never retried automatically; the user decides, and the
 * idempotency key makes a manual retry safe.
 */
export function makeQueryClient(): QueryClient {
  return new QueryClient({
    defaultOptions: {
      queries: {
        staleTime: 15_000,
        refetchOnWindowFocus: true,
        retry: (failureCount, error) => {
          if (error instanceof ApiError && error.status >= 400 && error.status < 500) {
            return false;
          }

          return failureCount < 2;
        },
      },
      mutations: {
        retry: false,
      },
    },
  });
}
