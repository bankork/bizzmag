import { fireEvent, screen, waitFor } from "@testing-library/react";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { resetCsrfStateForTests } from "@/lib/api/client";
import { rememberSignOutReason } from "@/lib/auth/session-events";
import { makeMe, renderWithAuth } from "@/test/render";
import { LoginForm } from "./login-form";

const replace = vi.fn();

vi.mock("next/navigation", () => ({
  useRouter: () => ({ replace, push: vi.fn() }),
  usePathname: () => "/login/",
}));

function respond(status: number, body: unknown) {
  return new Response(JSON.stringify(body), { status, headers: { "Content-Type": "application/json" } });
}

function fillAndSubmit(identifier: string, password: string) {
  fireEvent.change(screen.getByLabelText("Email or phone number"), { target: { value: identifier } });
  fireEvent.change(screen.getByLabelText("Password"), { target: { value: password } });
  fireEvent.click(screen.getByRole("button", { name: "Sign in" }));
}

describe("LoginForm", () => {
  beforeEach(() => {
    replace.mockReset();
    resetCsrfStateForTests();
    document.cookie = "XSRF-TOKEN=token";
    window.history.replaceState(null, "", "/login/");
  });

  afterEach(() => {
    vi.unstubAllGlobals();
    sessionStorage.clear();
  });

  it("asks for both fields before calling the API", async () => {
    const fetchMock = vi.fn();
    vi.stubGlobal("fetch", fetchMock);
    renderWithAuth(<LoginForm />, null);

    fireEvent.click(screen.getByRole("button", { name: "Sign in" }));

    expect(await screen.findByText("Enter your email or phone number.")).toBeTruthy();
    expect(screen.getByText("Enter your password.")).toBeTruthy();
    expect(fetchMock).not.toHaveBeenCalled();
  });

  it("signs in with a phone number and goes to the person's area", async () => {
    const fetchMock = vi.fn().mockResolvedValue(respond(200, { success: true, data: makeMe() }));
    vi.stubGlobal("fetch", fetchMock);
    renderWithAuth(<LoginForm />, null);

    fillAndSubmit("0806 000 1917", "Adunni@2026");

    await waitFor(() => expect(replace).toHaveBeenCalledWith("/desk/"));
    expect(JSON.parse(fetchMock.mock.calls[0][1].body)).toEqual({ identifier: "0806 000 1917", password: "Adunni@2026" });
  });

  it("returns to the page the person was sent from", async () => {
    window.history.replaceState(null, "", "/login/?next=%2Fadmin%2Fusers%2F");
    vi.stubGlobal("fetch", vi.fn().mockResolvedValue(respond(200, { success: true, data: makeMe({ home: "admin" }) })));
    renderWithAuth(<LoginForm />, null);

    fillAndSubmit("adunni@adunni.test", "Adunni@2026");

    await waitFor(() => expect(replace).toHaveBeenCalledWith("/admin/users/"));
  });

  it("never follows a next link to another site", async () => {
    window.history.replaceState(null, "", "/login/?next=%2F%2Fevil.example");
    vi.stubGlobal("fetch", vi.fn().mockResolvedValue(respond(200, { success: true, data: makeMe() })));
    renderWithAuth(<LoginForm />, null);

    fillAndSubmit("kemi@adunni.test", "Adunni@2026");

    await waitFor(() => expect(replace).toHaveBeenCalledWith("/desk/"));
  });

  it("shows how long a lockout lasts", async () => {
    vi.stubGlobal("fetch", vi.fn().mockResolvedValue(respond(429, { success: false, code: "LOGIN_LOCKED", message: "Too many attempts. Try again later.", data: { retry_after_seconds: 700 } })));
    renderWithAuth(<LoginForm />, null);

    fillAndSubmit("kemi@adunni.test", "wrong");

    expect(await screen.findByText("Too many attempts. Try again in 12 minutes.")).toBeTruthy();
    expect(replace).not.toHaveBeenCalled();
  });

  it("shows the server's message for wrong details", async () => {
    vi.stubGlobal("fetch", vi.fn().mockResolvedValue(respond(422, { success: false, code: "INVALID_CREDENTIALS", message: "Those details are not correct. Check them and try again." })));
    renderWithAuth(<LoginForm />, null);

    fillAndSubmit("kemi@adunni.test", "wrong");

    expect(await screen.findByText("Those details are not correct. Check them and try again.")).toBeTruthy();
  });

  it("explains an idle sign-out", async () => {
    rememberSignOutReason("idle");
    renderWithAuth(<LoginForm />, null);

    expect(await screen.findByText("You were signed out after a period of inactivity. Please sign in again.")).toBeTruthy();
  });
});
