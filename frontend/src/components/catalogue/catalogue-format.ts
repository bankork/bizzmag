import type { CatalogueHistoryEntry, CatalogueItem } from "@/lib/api/types";
import { formatBasisPoints } from "@/lib/format";
import { formatKoboCompact } from "@/lib/money";

/** "₦40,000", "3 variants from ₦30,000", "No price yet". */
export function describePrice(item: CatalogueItem): string {
  if (!item.has_variants) {
    return typeof item.price_kobo === "number" ? formatKoboCompact(item.price_kobo) : "";
  }

  const prices = (item.variants ?? []).map((variant) => variant.price_kobo).filter((price): price is number => typeof price === "number");
  const count = item.variants?.length ?? 0;

  if (count === 0) {
    return "No variants yet";
  }

  return `${count} ${count === 1 ? "variant" : "variants"} from ${formatKoboCompact(Math.min(...prices))}`;
}

/** "Shared equally", "Primary 60%, assistants share 40%". */
export function describeSplit(item: Pick<CatalogueItem, "allocation_method" | "primary_share_bp">): string | null {
  if (item.allocation_method === null) {
    return null;
  }

  if (item.allocation_method === "equal") {
    return "Shared equally";
  }

  const primary = item.primary_share_bp ?? 5_000;

  return `Primary ${formatBasisPoints(primary)}, assistants share ${formatBasisPoints(10_000 - primary)}`;
}

type ItemSnapshot = Partial<Record<"name" | "price_kobo" | "taxable" | "quantity_allowed" | "allocation_method" | "primary_share_bp" | "is_active" | "description" | "category_id" | "has_variants", unknown>>;

const FIELD_LABELS: Record<string, string> = {
  name: "name",
  description: "description",
  category_id: "category",
  taxable: "taxable",
  quantity_allowed: "quantity",
  allocation_method: "credit split",
  primary_share_bp: "credit split",
  has_variants: "variants setting",
  sort_order: "display order",
};

/** One line for the change history, price changes first and in full. */
export function describeItemChange(entry: CatalogueHistoryEntry): string {
  const before = (entry.before ?? {}) as ItemSnapshot;
  const after = (entry.after ?? {}) as ItemSnapshot;
  const subject = entry.item.is_variant ? `${entry.item.name ?? "Variant"}: ` : "";
  const price = (value: unknown) => (typeof value === "number" ? formatKoboCompact(value) : "no price");

  switch (entry.action) {
    case "catalogue.item_created":
      return `${subject}Added${after.has_variants ? " with variants" : ` at ${price(after.price_kobo)}`}`;
    case "catalogue.item_deactivated":
      return `${subject}Deactivated`;
    case "catalogue.item_activated":
      return `${subject}Activated again`;
  }

  const parts: string[] = [];

  if (before.price_kobo !== after.price_kobo) {
    parts.push(`price ${price(before.price_kobo)} to ${price(after.price_kobo)}`);
  }

  const others = new Set(
    Object.keys(after)
      .filter((field) => field !== "price_kobo" && JSON.stringify(before[field as keyof ItemSnapshot]) !== JSON.stringify(after[field as keyof ItemSnapshot]))
      .map((field) => FIELD_LABELS[field] ?? field),
  );

  if (others.size > 0) {
    parts.push(`changed ${[...others].join(", ")}`);
  }

  const text = parts.join("; ") || "saved";

  return `${subject}${text.charAt(0).toUpperCase()}${text.slice(1)}`;
}
