"use client";

import { useMemo, useState } from "react";
import { Plus } from "lucide-react";
import { Input } from "@/components/ui/input";
import { Skeleton } from "@/components/ui/skeleton";
import { useCatalogueItems, useCategories } from "@/lib/api/catalogue";
import type { ItemType } from "@/lib/api/types";
import { formatKobo } from "@/lib/money";
import { visitChoices, type VisitChoice } from "@/lib/visits";

type ItemPickerProps = {
  onPick: (choice: VisitChoice) => void;
  onlyType?: ItemType;
  label?: string;
};

/**
 * Search the catalogue and pick a service, variant or product. Prices show
 * only for people allowed to see them; the API leaves them out otherwise.
 */
export function ItemPicker({ onPick, onlyType, label = "Find a service or product" }: ItemPickerProps) {
  const items = useCatalogueItems();
  const categories = useCategories();
  const [search, setSearch] = useState("");
  const choices = useMemo(() => visitChoices(items.data ?? [], categories.data ?? [], onlyType), [items.data, categories.data, onlyType]);
  const term = search.trim().toLowerCase();
  const shown = term === "" ? choices : choices.filter((choice) => choice.name.toLowerCase().includes(term) || choice.category.toLowerCase().includes(term));

  if (items.isPending || categories.isPending) {
    return <Skeleton className="h-40 w-full" />;
  }

  return (
    <div className="space-y-2">
      <label htmlFor="item-search" className="text-sm font-medium">
        {label}
      </label>
      <Input id="item-search" placeholder="For example knotless or pedicure" value={search} onChange={(event) => setSearch(event.target.value)} />
      <ul className="max-h-72 divide-y overflow-y-auto rounded-lg border" aria-label="Catalogue">
        {shown.length === 0 ? <li className="p-3 text-sm text-muted-foreground">Nothing matches this search.</li> : null}
        {shown.map((choice, index) => {
          const heading = index === 0 || shown[index - 1].category !== choice.category ? choice.category : null;

          return (
            <li key={choice.id}>
              {heading ? <p className="bg-muted/60 px-3 py-1 text-xs font-medium uppercase tracking-wide text-muted-foreground">{heading}</p> : null}
              <button type="button" className="flex min-h-11 w-full items-center justify-between gap-3 px-3 py-2 text-left text-sm hover:bg-muted/50" onClick={() => onPick(choice)}>
                <span>{choice.name}</span>
                <span className="flex items-center gap-2 text-muted-foreground tabular-nums">
                  {choice.priceKobo !== undefined && choice.priceKobo !== null ? formatKobo(choice.priceKobo) : null}
                  <Plus className="size-4" aria-hidden />
                  <span className="sr-only">Add {choice.name}</span>
                </span>
              </button>
            </li>
          );
        })}
      </ul>
    </div>
  );
}
