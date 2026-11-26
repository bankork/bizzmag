import { screen } from "@testing-library/react";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { makeMe, renderWithAuth } from "@/test/render";
import { RequireAuth } from "./require-auth";

const replace = vi.fn();

vi.mock("next/navigation", () => ({
  useRouter: () => ({ replace, push: vi.fn() }),
  usePathname: () => "/admin/settings/",
}));

describe("RequireAuth", () => {
  beforeEach(() => {
    replace.mockReset();
    window.history.replaceState(null, "", "/admin/settings/?tab=tax");
  });

  it("sends a signed-out visitor to sign in and comes back afterwards", () => {
    renderWithAuth(<RequireAuth>Secret</RequireAuth>, null);

    expect(screen.queryByText("Secret")).toBeNull();
    expect(replace).toHaveBeenCalledWith("/login/?next=%2Fadmin%2Fsettings%2F%3Ftab%3Dtax");
  });

  it("does not render a page the person has no permission for", () => {
    renderWithAuth(<RequireAuth permissions={["settings.manage"]}>Secret</RequireAuth>, makeMe());

    expect(screen.queryByText("Secret")).toBeNull();
    expect(screen.getByText("You do not have access to this page")).toBeTruthy();
    expect(screen.getByRole("link", { name: "Go to my area" }).getAttribute("href")).toMatch(/^\/desk\/?$/);
  });

  it("renders the page when any one of the permissions is held", () => {
    renderWithAuth(<RequireAuth permissions={["user.manage", "settings.manage"]}>Secret</RequireAuth>, makeMe({ permissions: ["settings.manage"] }));

    expect(screen.getByText("Secret")).toBeTruthy();
  });

  it("sends someone who must change their password to My account first", () => {
    const me = makeMe();
    me.user.must_change_password = true;

    renderWithAuth(<RequireAuth>Secret</RequireAuth>, me);

    expect(screen.queryByText("Secret")).toBeNull();
    expect(replace).toHaveBeenCalledWith("/account/");
  });
});
