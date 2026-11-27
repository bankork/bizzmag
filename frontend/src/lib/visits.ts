import type { AppNotification, AreaKey, CatalogueCategory, CatalogueItem, ItemType, Order, OrderItem } from "@/lib/api/types";

/**
 * Plain-language state of one item on a visit, as staff and the desk talk
 * about it. Worked out only from what the API sent; nothing is decided here.
 */
export type ServiceState = "waiting" | "done" | "disputed" | "awaiting_approval" | "not_approved" | "removed" | "product";

export const SERVICE_STATE: Record<ServiceState, { label: string; tone: "success" | "warning" | "muted" | "outline" | "secondary" }> = {
  waiting: { label: "Waiting", tone: "outline" },
  done: { label: "Done", tone: "success" },
  disputed: { label: "Disputed", tone: "warning" },
  awaiting_approval: { label: "Waiting for approval", tone: "warning" },
  not_approved: { label: "Not approved", tone: "muted" },
  removed: { label: "Removed", tone: "muted" },
  product: { label: "Product", tone: "secondary" },
};

export function serviceState(item: OrderItem): ServiceState {
  if (item.status === "cancelled") {
    return "removed";
  }

  if (item.status === "rejected") {
    return "not_approved";
  }

  if (item.type === "product") {
    return "product";
  }

  if (item.work?.status === "disputed") {
    return "disputed";
  }

  if (item.work) {
    return "done";
  }

  return item.status === "pending_approval" ? "awaiting_approval" : "waiting";
}

/** Still on the visit: not removed and not rejected. */
export function isOnVisit(item: OrderItem): boolean {
  return ["requested", "approved", "pending_approval"].includes(item.status);
}

/** Whether work can still be recorded on it (FR-EXE-003): a service still on the visit. */
export function acceptsWork(item: OrderItem): boolean {
  return item.type === "service" && isOnVisit(item);
}

/** "Just now", "25 min", "2 h 5 min", "1 day 3 h" (FR-ORD-015). */
export function timeOpen(openedAt: string, now: Date = new Date()): string {
  const minutes = Math.max(0, Math.floor((now.getTime() - new Date(openedAt).getTime()) / 60_000));

  if (minutes < 1) {
    return "Just now";
  }

  if (minutes < 60) {
    return `${minutes} min`;
  }

  const hours = Math.floor(minutes / 60);

  if (hours < 24) {
    const rest = minutes % 60;

    return rest === 0 ? `${hours} h` : `${hours} h ${rest} min`;
  }

  const days = Math.floor(hours / 24);
  const restHours = hours % 24;

  return `${days} ${days === 1 ? "day" : "days"}${restHours === 0 ? "" : ` ${restHours} h`}`;
}

/** UI-010: the visits with my own assignments first, then the oldest first. */
export function forStaff(orders: Order[]): Order[] {
  const mine = (order: Order) => order.items.some((item) => item.assigned_to_me && acceptsWork(item) && item.work === null);

  return [...orders].sort((a, b) => Number(mine(b)) - Number(mine(a)) || a.opened_at.localeCompare(b.opened_at));
}

/** Where a notification leads, in the area the person is using. */
export function notificationLink(notification: AppNotification, area: AreaKey): string | null {
  if (notification.type === "additional.pending" && area === "admin") {
    return "/admin/approvals/";
  }

  const orderId = notification.data.order_id;

  if (orderId === undefined) {
    return null;
  }

  return area === "staff" ? `/staff/visit/?id=${orderId}` : `/desk/visit/?id=${orderId}`;
}

/** Something that can be put on a visit: a priced service or product, or one variant. */
export type VisitChoice = { id: number; name: string; category: string; type: ItemType; priceKobo?: number | null; quantityAllowed: boolean };

/**
 * The catalogue as the desk chooses from it: every orderable item, with a
 * variant listed as "Knotless braids, Medium", in the owner's category
 * order. Prices only when the API sent them.
 */
export function visitChoices(items: CatalogueItem[], categories: CatalogueCategory[], onlyType?: ItemType): VisitChoice[] {
  const position = new Map(categories.map((category, index) => [category.id, { index, name: category.name, active: category.is_active }]));
  const choices: { rank: number; choice: VisitChoice }[] = [];

  for (const item of items) {
    const category = position.get(item.category_id);

    if (!category?.active || (onlyType && item.type !== onlyType)) {
      continue;
    }

    const entries = item.has_variants ? (item.variants ?? []).map((variant) => ({ ...variant, name: `${item.name}, ${variant.name}` })) : [item];

    for (const entry of entries) {
      if (!entry.orderable) {
        continue;
      }

      choices.push({
        rank: category.index,
        choice: {
          id: entry.id,
          name: entry.name,
          category: category.name,
          type: item.type,
          priceKobo: entry.price_kobo,
          quantityAllowed: item.type === "product" || item.quantity_allowed,
        },
      });
    }
  }

  return choices.sort((a, b) => a.rank - b.rank).map((entry) => entry.choice);
}
