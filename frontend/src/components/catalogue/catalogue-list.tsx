"use client";

import Link from "next/link";
import { useMemo, useState } from "react";
import { Plus, Search } from "lucide-react";
import { cn } from "cn";
import { CategoryManager } from "@/components/catalogue/category-manager";
import { describePrice, describeSplit } from "@/components/catalogue/catalogue-format";
import { Alert } from "@/components/ui/alert";
import { Badge } from "@/components/ui/badge";
import { buttonVariants } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Skeleton } from "@/components/ui/skeleton";
import { Switch } from "@/components/ui/switch";
import { useCatalogueItems, useCategories } from "@/lib/api/catalogue";
import type { CatalogueItem, ItemType } from "@/lib/api/types";

/**
 * Admin › Catalogue: services and products by category, in the till order.
 */
export function CatalogueList() {
  const [type, setType] = useState<ItemType>("service");
  const [search, setSearch] = useState("");
  const [showInactive, setShowInactive] = useState(false);
  const categories = useCategories();
  const items = useCatalogueItems();

  const groups = useMemo(() => {
    const term = search.trim().toLowerCase();
    const matches = (item: CatalogueItem) =>
      term === "" || item.name.toLowerCase().includes(term) || (item.variants ?? []).some((variant) => variant.name.toLowerCase().includes(term));

    return (categories.data ?? [])
      .filter((category) => category.type === type && (showInactive || category.is_active))
      .sort((a, b) => a.sort_order - b.sort_order)
      .map((category) => ({
        category,
        items: (items.data ?? []).filter((item) => item.category_id === category.id && (showInactive || item.is_active) && matches(item)),
      }))
      .filter((group) => group.items.length > 0 || term === "");
  }, [categories.data, items.data, search, showInactive, type]);

  const typeCategories = (categories.data ?? []).filter((category) => category.type === type);
  const error = categories.error ?? items.error;

  return (
    <div className="space-y-4">
      <div role="tablist" aria-label="Catalogue" className="inline-flex rounded-lg border bg-card p-1">
        {(
          [
            ["service", "Services"],
            ["product", "Products"],
          ] as const
        ).map(([value, label]) => (
          <button
            key={value}
            type="button"
            role="tab"
            aria-selected={type === value}
            onClick={() => setType(value)}
            className={cn("rounded-md px-4 py-1.5 text-sm font-medium", type === value ? "bg-primary text-primary-foreground" : "text-muted-foreground hover:text-foreground")}
          >
            {label}
          </button>
        ))}
      </div>

      <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div className="flex flex-1 items-center gap-3">
          <div className="relative flex-1 sm:max-w-xs">
            <label htmlFor="catalogue-search" className="sr-only">
              Search the catalogue
            </label>
            <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" aria-hidden />
            <Input id="catalogue-search" placeholder="Search by name or variant" value={search} onChange={(event) => setSearch(event.target.value)} className="pl-9" />
          </div>
          <label className="flex items-center gap-2 text-sm whitespace-nowrap">
            <Switch checked={showInactive} onCheckedChange={setShowInactive} aria-label="Show inactive" />
            Show inactive
          </label>
        </div>
        <div className="flex gap-2">
          <CategoryManager type={type} categories={typeCategories} />
          <Link href={`/admin/catalogue/edit/?new=${type}`} className={cn(buttonVariants(), typeCategories.length === 0 && "pointer-events-none opacity-50")} aria-disabled={typeCategories.length === 0}>
            <Plus aria-hidden />
            {type === "service" ? "Add service" : "Add product"}
          </Link>
        </div>
      </div>

      {error ? <Alert variant="destructive">{error.message}</Alert> : null}
      {categories.isPending || items.isPending ? <Skeleton className="h-64 w-full" /> : null}
      {!categories.isPending && typeCategories.length === 0 ? (
        <Alert variant="info">Start by adding a {type === "service" ? "service" : "product"} category with the Categories button.</Alert>
      ) : null}

      {groups.map(({ category, items: rows }) => (
        <section key={category.id} aria-labelledby={`category-heading-${category.id}`} className="rounded-xl border bg-card">
          <h2 id={`category-heading-${category.id}`} className="flex items-center gap-2 border-b px-4 py-2.5 text-sm font-semibold">
            {category.name}
            {!category.is_active ? <Badge variant="muted">Inactive</Badge> : null}
          </h2>
          <ul className="divide-y">
            {rows.length === 0 ? <li className="px-4 py-3 text-sm text-muted-foreground">Nothing in this category yet.</li> : null}
            {rows.map((item) => (
              <li key={item.id}>
                <Link href={`/admin/catalogue/edit/?id=${item.id}`} className="flex flex-col gap-1 px-4 py-3 hover:bg-muted/50 sm:flex-row sm:items-center sm:justify-between">
                  <span className="min-w-0">
                    <span className="flex flex-wrap items-center gap-2 font-medium">
                      {item.name}
                      {!item.is_active ? <Badge variant="muted">Inactive</Badge> : null}
                      {!item.taxable ? <Badge variant="outline">Tax exempt</Badge> : null}
                    </span>
                    {item.type === "service" ? <span className="block text-xs text-muted-foreground">{describeSplit(item)}</span> : null}
                  </span>
                  <span className="text-sm font-medium tabular-nums sm:text-right">{describePrice(item)}</span>
                </Link>
              </li>
            ))}
          </ul>
        </section>
      ))}
    </div>
  );
}
