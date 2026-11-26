import { describe, expect, it } from "vitest";
import { areasFor, navFor } from "./areas";

const EMPLOYEE = ["order.view_open", "execution.submit", "execution.join", "execution.dispute", "additional.request", "report.own"];
const CASHIER = ["order.create", "order.view_open", "payment.record", "report.own"];

describe("areas and navigation by permission", () => {
  it("gives an employee only the staff area", () => {
    expect(areasFor(EMPLOYEE).map((area) => area.key)).toEqual(["staff"]);
  });

  it("gives a cashier the staff and desk areas but not admin", () => {
    expect(areasFor(CASHIER).map((area) => area.key)).toEqual(["staff", "desk"]);
  });

  it("shows admin links only for the permissions held", () => {
    expect(navFor("admin", ["settings.manage"]).map((item) => item.label)).toEqual(["Overview", "Settings"]);
    expect(navFor("admin", ["user.manage", "role.manage", "settings.manage"]).map((item) => item.label)).toEqual(["Overview", "Staff", "Roles", "Settings"]);
    expect(navFor("admin", EMPLOYEE)).toEqual([]);
  });
});
