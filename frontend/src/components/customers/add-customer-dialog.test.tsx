import { fireEvent, screen, waitFor } from "@testing-library/react";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { resetCsrfStateForTests } from "@/lib/api/client";
import { makeMe, renderWithAuth } from "@/test/render";
import { AddCustomerDialog } from "./add-customer-dialog";

const push = vi.fn();

vi.mock("next/navigation", () => ({
  useRouter: () => ({ replace: vi.fn(), push }),
  usePathname: () => "/desk/customers/",
}));

function respond(status: number, body: unknown) {
  return new Response(JSON.stringify(body), { status, headers: { "Content-Type": "application/json" } });
}

const DUPLICATE = {
  success: false,
  code: "DUPLICATE_PHONE",
  message: "This number already belongs to Funke Adeyemi. Add anyway?",
  data: { matches: [{ id: 7, name: "Funke Adeyemi" }] },
};

const CREATED = { success: true, data: { id: 9, name: "Bisi Adeyemi", has_phone: true, has_email: false, notes: null, is_removed: false, removed_at: null, version: 1, created_at: null } };

const CASHIER = makeMe({ permissions: ["customer.view", "customer.view_contact", "customer.manage", "order.create"] });

function openAndFill() {
  fireEvent.click(screen.getByRole("button", { name: "Add customer" }));
  fireEvent.change(screen.getByLabelText("Name"), { target: { value: "Bisi Adeyemi" } });
  fireEvent.change(screen.getByLabelText("Phone (optional)"), { target: { value: "0803 000 1234" } });
  fireEvent.click(screen.getAllByRole("button", { name: "Add customer" }).at(-1) as HTMLElement);
}

function requestBodies(fetchMock: ReturnType<typeof vi.fn>) {
  return fetchMock.mock.calls.filter(([url]) => String(url).endsWith("/customers")).map(([, init]) => JSON.parse(String(init.body)));
}

describe("AddCustomerDialog", () => {
  beforeEach(() => {
    push.mockReset();
    resetCsrfStateForTests();
    document.cookie = "XSRF-TOKEN=token";
  });

  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it("shows the existing customer and saves only after the desk confirms", async () => {
    const fetchMock = vi.fn().mockResolvedValueOnce(respond(409, DUPLICATE)).mockResolvedValueOnce(respond(201, CREATED));
    vi.stubGlobal("fetch", fetchMock);
    renderWithAuth(<AddCustomerDialog basePath="/desk/customers/" />, CASHIER);

    openAndFill();

    expect(await screen.findByText("This number already belongs to Funke Adeyemi. Add anyway?")).toBeTruthy();
    expect(screen.getByRole("link", { name: "Open Funke Adeyemi" }).getAttribute("href")).toMatch(/^\/desk\/customers\/edit\/?\?id=7$/);
    expect(push).not.toHaveBeenCalled();

    fireEvent.click(screen.getByRole("button", { name: "Add anyway" }));

    await waitFor(() => expect(push).toHaveBeenCalledWith("/desk/customers/edit/?id=9"));
    expect(requestBodies(fetchMock).map((body) => body.confirm_duplicate)).toEqual([false, true]);
  });

  it("drops the warning when the phone number is changed", async () => {
    vi.stubGlobal("fetch", vi.fn().mockResolvedValueOnce(respond(409, DUPLICATE)));
    renderWithAuth(<AddCustomerDialog basePath="/desk/customers/" />, CASHIER);

    openAndFill();
    await screen.findByText("This number already belongs to Funke Adeyemi. Add anyway?");
    fireEvent.change(screen.getByLabelText("Phone (optional)"), { target: { value: "0803 000 9999" } });

    expect(screen.queryByText("This number already belongs to Funke Adeyemi. Add anyway?")).toBeNull();
  });

  it("does not ask for phone or email without permission to see them", () => {
    renderWithAuth(<AddCustomerDialog basePath="/desk/customers/" />, makeMe({ permissions: ["customer.view", "customer.manage"] }));

    fireEvent.click(screen.getByRole("button", { name: "Add customer" }));

    expect(screen.queryByLabelText("Phone (optional)")).toBeNull();
    expect(screen.getByLabelText("Name")).toBeTruthy();
  });
});
