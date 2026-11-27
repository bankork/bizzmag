import { describe, expect, it } from "vitest";
import type { AppNotification, CatalogueCategory, CatalogueItem, Order, OrderItem } from "@/lib/api/types";
import { acceptsWork, forStaff, isOnVisit, notificationLink, serviceState, timeOpen, visitChoices } from "./visits";

function item(overrides: Partial<OrderItem> = {}): OrderItem {
  return {
    id: 1, type: "service", name: "Cornrows", quantity: 1, status: "requested", origin: "order", request_note: null,
    decision_reason: null, cancel_reason: null, requested_by: 1, assigned: [], assigned_to_me: false, work: null, history: [],
    ...overrides,
  };
}

function order(id: number, openedAt: string, items: OrderItem[]): Order {
  return {
    id, reference: `V-${id}`, status: "open", customer_name: "Guest", is_guest: true, opened_at: openedAt, opened_business_day: "2026-11-27",
    opened_by: "Kemi", cancelled_at: null, cancel_reason: null, progress: { services: 1, recorded: 0, disputed: 0, waiting: 1, pending_approval: 0 }, items,
  };
}

const work = { id: 9, order_item_id: 1, status: "confirmed" as const, late: false, submitted_by: { id: 1, name: "Ada" }, submitted_at: "", allocation_method: "equal" as const,
  primary_share_bp: null, supersedes_id: null, override_reason: null, dispute_reason: null, disputed_by: null, resolution_reason: null, includes_me: false, contributors: [] };

describe("serviceState", () => {
  it("names each state the way the salon talks about it", () => {
    expect(serviceState(item())).toBe("waiting");
    expect(serviceState(item({ work }))).toBe("done");
    expect(serviceState(item({ work: { ...work, status: "disputed" } }))).toBe("disputed");
    expect(serviceState(item({ status: "pending_approval" }))).toBe("awaiting_approval");
    expect(serviceState(item({ status: "pending_approval", work }))).toBe("done");
    expect(serviceState(item({ status: "rejected" }))).toBe("not_approved");
    expect(serviceState(item({ status: "cancelled", work }))).toBe("removed");
    expect(serviceState(item({ type: "product" }))).toBe("product");
  });
});

describe("isOnVisit and acceptsWork", () => {
  it("keeps products on the visit but records work only on services", () => {
    expect(isOnVisit(item({ type: "product" }))).toBe(true);
    expect(acceptsWork(item({ type: "product" }))).toBe(false);
    expect(acceptsWork(item({ status: "pending_approval" }))).toBe(true);
    expect(isOnVisit(item({ status: "rejected" }))).toBe(false);
    expect(acceptsWork(item({ status: "cancelled" }))).toBe(false);
  });
});

describe("timeOpen", () => {
  const now = new Date("2026-11-27T12:00:00Z");

  it("reads naturally from minutes to days", () => {
    expect(timeOpen("2026-11-27T11:59:40Z", now)).toBe("Just now");
    expect(timeOpen("2026-11-27T11:35:00Z", now)).toBe("25 min");
    expect(timeOpen("2026-11-27T10:00:00Z", now)).toBe("2 h");
    expect(timeOpen("2026-11-27T09:55:00Z", now)).toBe("2 h 5 min");
    expect(timeOpen("2026-11-26T09:00:00Z", now)).toBe("1 day 3 h");
    expect(timeOpen("2026-11-25T12:00:00Z", now)).toBe("2 days");
  });
});

describe("forStaff", () => {
  it("puts visits with my waiting assignments first, then the oldest", () => {
    const old = order(1, "2026-11-27T08:00:00Z", [item()]);
    const mine = order(2, "2026-11-27T10:00:00Z", [item({ assigned_to_me: true })]);
    const mineDone = order(3, "2026-11-27T07:00:00Z", [item({ assigned_to_me: true, work })]);

    expect(forStaff([old, mineDone, mine]).map((visit) => visit.id)).toEqual([2, 3, 1]);
  });
});

describe("notificationLink", () => {
  const base: AppNotification = { id: 1, type: "execution.added", title: "", body: null, data: { order_id: 7 }, read: false, created_at: "" };

  it("opens the visit in the area the person is using", () => {
    expect(notificationLink(base, "staff")).toBe("/staff/visit/?id=7");
    expect(notificationLink(base, "desk")).toBe("/desk/visit/?id=7");
    expect(notificationLink({ ...base, type: "additional.pending" }, "admin")).toBe("/admin/approvals/");
    expect(notificationLink({ ...base, data: {} }, "staff")).toBeNull();
  });
});

describe("visitChoices", () => {
  const category = (id: number, name: string, type: "service" | "product", is_active = true): CatalogueCategory =>
    ({ id, type, name, sort_order: id, is_active, version: 1, items_count: null });
  const entry = (overrides: Partial<CatalogueItem>): CatalogueItem => ({
    id: 1, category_id: 1, parent_id: null, type: "service", name: "Cornrows", description: null, has_variants: false, price_kobo: 800_000, taxable: true,
    quantity_allowed: false, allocation_method: "equal", primary_share_bp: null, sort_order: 0, is_active: true, orderable: true, version: 1, ...overrides,
  });

  it("lists orderable items and variants in category order, skipping what cannot be chosen", () => {
    const categories = [category(2, "Products", "product"), category(1, "Braids", "service"), category(3, "Old", "service", false)];
    const items = [
      entry({ id: 10, category_id: 1, name: "Knotless braids", has_variants: true, price_kobo: null, orderable: false, variants: [
        entry({ id: 11, parent_id: 10, name: "Medium", price_kobo: 4_000_000 }),
        entry({ id: 12, parent_id: 10, name: "Large", orderable: false }),
      ] }),
      entry({ id: 20, category_id: 2, type: "product", name: "Edge control", price_kobo: 500_000 }),
      entry({ id: 30, category_id: 3, name: "Retired style" }),
    ];

    expect(visitChoices(items, categories)).toEqual([
      { id: 20, name: "Edge control", category: "Products", type: "product", priceKobo: 500_000, quantityAllowed: true },
      { id: 11, name: "Knotless braids, Medium", category: "Braids", type: "service", priceKobo: 4_000_000, quantityAllowed: false },
    ]);
    expect(visitChoices(items, categories, "service").map((choice) => choice.id)).toEqual([11]);
  });
});
