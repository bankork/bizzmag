import { fireEvent, screen, waitFor } from "@testing-library/react";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { resetCsrfStateForTests } from "@/lib/api/client";
import type { Order } from "@/lib/api/types";
import { makeMe, renderWithAuth } from "@/test/render";
import { StaffVisit } from "./staff-visit";

vi.mock("next/navigation", () => ({
  useRouter: () => ({ replace: vi.fn(), push: vi.fn() }),
  usePathname: () => "/staff/visit/",
  useSearchParams: () => new URLSearchParams("id=5"),
}));

const ADA = makeMe({
  user: { id: 21, name: "Ada Okafor", email: "ada@adunni.test", phone: null, must_change_password: false, employee_profile: { id: 31, display_name: "Ada", job_title: "Braider", is_active: true } },
  roles: [{ id: 4, name: "Employee", system_key: "employee" }],
  permissions: ["order.view_open", "execution.submit", "execution.join", "execution.dispute", "additional.request"],
  home: "staff",
});

const VISIT: Order = {
  id: 5, reference: "V-261127-004", status: "open", customer_name: "Funke Adeyemi", is_guest: false, opened_at: "2026-11-27T09:00:00Z",
  opened_business_day: "2026-11-27", opened_by: "Kemi", cancelled_at: null, cancel_reason: null,
  progress: { services: 1, recorded: 0, disputed: 0, waiting: 1, pending_approval: 0 },
  items: [{
    id: 11, type: "service", name: "Knotless braids, Medium", quantity: 1, status: "requested", origin: "order", request_note: null, decision_reason: null,
    cancel_reason: null, requested_by: 3, assigned: [{ employee_id: 31, name: "Ada" }], assigned_to_me: true, work: null, history: [],
  }],
};

function respond(status: number, body: unknown) {
  return new Response(JSON.stringify(body), { status, headers: { "Content-Type": "application/json" } });
}

function fetchWith(submitResponses: Response[]) {
  return vi.fn(async (url: string, init?: RequestInit) => {
    if (url.includes("/executions")) {
      return submitResponses.shift() ?? respond(500, { success: false, code: "SERVER_ERROR", message: "Unexpected" });
    }

    if (url.includes("/staff-directory")) {
      return respond(200, { success: true, data: [{ id: 31, name: "Ada", job_title: null, is_me: true }, { id: 32, name: "Bisi", job_title: null, is_me: false }] });
    }

    if (url.includes("/orders/5") && (init?.method ?? "GET") === "GET") {
      return respond(200, { success: true, data: VISIT });
    }

    return respond(404, { success: false, code: "NOT_FOUND", message: "Not found" });
  });
}

function submitCalls(fetchMock: ReturnType<typeof fetchWith>) {
  return fetchMock.mock.calls.filter(([url]) => String(url).includes("/executions"));
}

describe("StaffVisit", () => {
  beforeEach(() => {
    resetCsrfStateForTests();
    document.cookie = "XSRF-TOKEN=token";
  });

  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it("records in one tap from the service and shows no prices", async () => {
    const fetchMock = fetchWith([respond(201, { success: true, data: { id: 90, order_id: 5 } })]);
    vi.stubGlobal("fetch", fetchMock);
    renderWithAuth(<StaffVisit />, ADA);

    fireEvent.click(await screen.findByRole("button", { name: /Knotless braids, Medium/ }));
    expect(screen.queryByText(/₦/)).toBeNull();
    fireEvent.click(screen.getByRole("button", { name: "I did this" }));

    expect(await screen.findByText("Recorded. Thank you.")).toBeTruthy();
    const [[url, init]] = submitCalls(fetchMock);
    expect(String(url)).toMatch(/\/order-items\/11\/executions$/);
    expect(JSON.parse(String(init?.body))).toEqual({ contributor_employee_ids: [] });
  });

  it("retries with the same key after a failure, so the work is recorded once", async () => {
    const fetchMock = fetchWith([respond(500, { success: false, code: "SERVER_ERROR", message: "Something went wrong." }), respond(201, { success: true, data: { id: 90, order_id: 5 } })]);
    vi.stubGlobal("fetch", fetchMock);
    renderWithAuth(<StaffVisit />, ADA);

    fireEvent.click(await screen.findByRole("button", { name: /Knotless braids, Medium/ }));
    fireEvent.click(screen.getByRole("button", { name: "I did this" }));
    expect(await screen.findByText("Something went wrong.")).toBeTruthy();
    fireEvent.click(screen.getByRole("button", { name: "I did this" }));
    await screen.findByText("Recorded. Thank you.");

    const keys = submitCalls(fetchMock).map(([, init]) => (init?.headers as Record<string, string>)["Idempotency-Key"]);
    expect(keys).toHaveLength(2);
    expect(keys[0]).toBeTruthy();
    expect(keys[1]).toBe(keys[0]);
  });

  it("offers to join when a colleague recorded it first", async () => {
    const fetchMock = fetchWith([respond(409, { success: false, code: "EXECUTION_EXISTS", message: "This service has already been recorded.", data: { execution_id: 77, can_join: true } })]);
    vi.stubGlobal("fetch", fetchMock);
    renderWithAuth(<StaffVisit />, ADA);

    fireEvent.click(await screen.findByRole("button", { name: /Knotless braids, Medium/ }));
    fireEvent.click(screen.getByRole("button", { name: "I did this" }));

    expect(await screen.findByText("Someone has just recorded this. You can join them below.")).toBeTruthy();
    await waitFor(() => expect(fetchMock.mock.calls.filter(([url]) => String(url).endsWith("/orders/5")).length).toBeGreaterThanOrEqual(2));
  });

  it("adds colleagues in one more step", async () => {
    const fetchMock = fetchWith([respond(201, { success: true, data: { id: 90, order_id: 5 } })]);
    vi.stubGlobal("fetch", fetchMock);
    renderWithAuth(<StaffVisit />, ADA);

    fireEvent.click(await screen.findByRole("button", { name: /Knotless braids, Medium/ }));
    fireEvent.click(screen.getByRole("button", { name: "With colleagues" }));
    fireEvent.click(await screen.findByLabelText("Bisi"));
    fireEvent.click(screen.getByRole("button", { name: "We did this (2 people)" }));

    await waitFor(() => expect(submitCalls(fetchMock)).toHaveLength(1));
    expect(JSON.parse(String(submitCalls(fetchMock)[0][1]?.body))).toEqual({ contributor_employee_ids: [32] });
    expect(screen.queryByLabelText("Ada (you)")).toBeNull();
  });
});
