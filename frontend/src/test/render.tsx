import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { render } from "@testing-library/react";
import type { ReactElement } from "react";
import { AuthProvider, ME_QUERY_KEY } from "@/lib/auth/auth-provider";
import type { Me } from "@/lib/api/types";

/**
 * Renders a component with the real auth provider. Pass `me` to start
 * signed in, or null to start signed out, without calling the API.
 */
export function renderWithAuth(ui: ReactElement, me: Me | null) {
  const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false }, mutations: { retry: false } } });
  queryClient.setQueryData(ME_QUERY_KEY, me);

  return {
    queryClient,
    ...render(
      <QueryClientProvider client={queryClient}>
        <AuthProvider>{ui}</AuthProvider>
      </QueryClientProvider>,
    ),
  };
}

export function makeMe(overrides: Partial<Me> & { permissions?: string[] } = {}): Me {
  return {
    user: { id: 1, name: "Kemi Adeyemi", email: "kemi@adunni.test", phone: "+2348060001917", must_change_password: false, employee_profile: null },
    business: { id: 1, name: "Adunni Hair & Beauty Studio", currency: "NGN", timezone: "Africa/Lagos", logo_url: null },
    roles: [{ id: 3, name: "Front desk / Cashier", system_key: "cashier" }],
    permissions: ["order.create", "order.view_open", "payment.record"],
    discount_cap: { percent_bp: 1000, per_invoice_kobo: 500000 },
    session: { kind: "privileged", idle_timeout_seconds: 1800 },
    home: "desk",
    ...overrides,
  };
}
