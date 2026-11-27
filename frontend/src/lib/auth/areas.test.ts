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

  it("shows the catalogue to the owner and customers to the desk", () => {
    expect(navFor("admin", ["catalogue.manage", "customer.view"]).map((item) => item.label)).toEqual(["Overview", "Catalogue", "Customers"]);
    expect(navFor("desk", [...CASHIER, "customer.view"]).map((item) => item.label)).toEqual(["Visits", "New visit", "Customers"]);
    expect(navFor("desk", CASHIER).map((item) => item.label)).toEqual(["Visits", "New visit"]);
  });

  it("gives staff their own work and supervisors the approvals queue", () => {
    expect(navFor("staff", EMPLOYEE).map((item) => item.label)).toEqual(["Today", "My work"]);
    expect(navFor("staff", CASHIER).map((item) => item.label)).toEqual(["Today"]);
    expect(navFor("admin", ["additional.decide"]).map((item) => item.label)).toEqual(["Approvals"]);
    expect(areasFor(["additional.decide"]).map((area) => area.key)).toEqual(["admin"]);
  });
});
