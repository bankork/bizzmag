import { describe, expect, it } from "vitest";
import type { CatalogueHistoryEntry, CatalogueItem } from "@/lib/api/types";
import { describeItemChange, describePrice, describeSplit } from "./catalogue-format";

function item(overrides: Partial<CatalogueItem>): CatalogueItem {
  return {
    id: 1, category_id: 1, parent_id: null, type: "service", name: "Knotless braids", description: null, has_variants: false, price_kobo: 4_000_000,
    taxable: true, quantity_allowed: false, allocation_method: "equal", primary_share_bp: null, sort_order: 1, is_active: true, orderable: true, version: 1,
    ...overrides,
  };
}

function entry(overrides: Partial<CatalogueHistoryEntry>): CatalogueHistoryEntry {
  return { id: 1, action: "catalogue.item_updated", actor: null, before: null, after: null, at: null, item: { id: 1, name: "Knotless braids", is_variant: false }, ...overrides };
}

describe("describePrice", () => {
  it("shows a single price, or the cheapest variant", () => {
    expect(describePrice(item({}))).toBe("₦40,000");
    expect(describePrice(item({ has_variants: true, price_kobo: null, variants: [item({ price_kobo: 5_500_000 }), item({ price_kobo: 3_000_000 })] }))).toBe("2 variants from ₦30,000");
    expect(describePrice(item({ has_variants: true, price_kobo: null, variants: [] }))).toBe("No variants yet");
  });

  it("shows nothing when prices are hidden", () => {
    const hidden = item({});
    delete hidden.price_kobo;

    expect(describePrice(hidden)).toBe("");
  });
});

describe("describeSplit", () => {
  it("names the method in plain words", () => {
    expect(describeSplit({ allocation_method: "equal", primary_share_bp: null })).toBe("Shared equally");
    expect(describeSplit({ allocation_method: "primary_assistant", primary_share_bp: 6_000 })).toBe("Primary 60%, assistants share 40%");
    expect(describeSplit({ allocation_method: null, primary_share_bp: null })).toBeNull();
  });
});

describe("describeItemChange", () => {
  it("spells out a price change on a variant", () => {
    expect(
      describeItemChange(entry({ before: { price_kobo: 4_000_000, name: "Medium" }, after: { price_kobo: 4_500_000, name: "Medium" }, item: { id: 2, name: "Medium", is_variant: true } })),
    ).toBe("Medium: Price ₦40,000 to ₦45,000");
  });

  it("lists other changed settings", () => {
    expect(describeItemChange(entry({ before: { taxable: true, primary_share_bp: 5_000 }, after: { taxable: false, primary_share_bp: 6_000 } }))).toBe("Changed taxable, credit split");
  });

  it("describes creation and activation", () => {
    expect(describeItemChange(entry({ action: "catalogue.item_created", after: { price_kobo: 800_000 } }))).toBe("Added at ₦8,000");
    expect(describeItemChange(entry({ action: "catalogue.item_deactivated" }))).toBe("Deactivated");
  });
});
