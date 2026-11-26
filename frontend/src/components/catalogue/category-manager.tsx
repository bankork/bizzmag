"use client";

import { useState } from "react";
import { ArrowDown, ArrowUp, FolderCog } from "lucide-react";
import { Alert } from "@/components/ui/alert";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { createCategory, renameCategory, reorderCategories, setCategoryActive, useCatalogueMutation } from "@/lib/api/catalogue";
import { ApiError } from "@/lib/api/errors";
import type { CatalogueCategory, ItemType } from "@/lib/api/types";

type Props = { type: ItemType; categories: CatalogueCategory[] };

/**
 * Add, rename, reorder and deactivate the categories of one type.
 * Categories are never deleted.
 */
export function CategoryManager({ type, categories }: Props) {
  const [failure, setFailure] = useState<string | null>(null);
  const [newName, setNewName] = useState("");
  const mutate = useCatalogueMutation(async (action: () => Promise<unknown>) => action());
  const label = type === "service" ? "service" : "product";
  const ordered = [...categories].sort((a, b) => a.sort_order - b.sort_order || a.name.localeCompare(b.name));

  async function run(action: () => Promise<unknown>) {
    setFailure(null);

    try {
      await mutate.mutateAsync(action);

      return true;
    } catch (error) {
      setFailure(error instanceof ApiError ? (error.fieldMessage("name") ?? error.message) : "Something went wrong. Please try again.");

      return false;
    }
  }

  function move(index: number, offset: number) {
    const ids = ordered.map((category) => category.id);
    const [moved] = ids.splice(index, 1);
    ids.splice(index + offset, 0, moved);
    void run(() => reorderCategories(type, ids));
  }

  return (
    <Dialog onOpenChange={() => setFailure(null)}>
      <DialogTrigger render={<Button variant="outline" />}>
        <FolderCog aria-hidden />
        Categories
      </DialogTrigger>
      <DialogContent>
        <DialogTitle>{type === "service" ? "Service categories" : "Product categories"}</DialogTitle>
        <DialogDescription>The order here is the order on the till. A category with active items must be emptied before it can be deactivated.</DialogDescription>

        <div className="mt-4 space-y-3">
          {failure ? <Alert variant="destructive">{failure}</Alert> : null}
          <ul className="divide-y rounded-lg border">
            {ordered.map((category, index) => (
              <CategoryRow
                key={category.id}
                category={category}
                first={index === 0}
                last={index === ordered.length - 1}
                busy={mutate.isPending}
                onMove={(offset) => move(index, offset)}
                onRename={(name) => run(() => renameCategory(category, name))}
                onToggle={() => run(() => setCategoryActive(category.id, !category.is_active))}
              />
            ))}
            {ordered.length === 0 ? <li className="p-3 text-sm text-muted-foreground">No {label} categories yet.</li> : null}
          </ul>

          <form
            className="flex gap-2"
            onSubmit={async (event) => {
              event.preventDefault();

              if (newName.trim() !== "" && (await run(() => createCategory(type, newName.trim())))) {
                setNewName("");
              }
            }}
          >
            <label htmlFor={`new-${type}-category`} className="sr-only">
              New category name
            </label>
            <Input id={`new-${type}-category`} placeholder={type === "service" ? "For example Braids" : "For example Hair products"} value={newName} onChange={(event) => setNewName(event.target.value)} />
            <Button type="submit" disabled={mutate.isPending || newName.trim() === ""}>
              Add
            </Button>
          </form>
        </div>

        <DialogFooter>
          <DialogClose render={<Button type="button" variant="outline" />}>Done</DialogClose>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

type RowProps = {
  category: CatalogueCategory;
  first: boolean;
  last: boolean;
  busy: boolean;
  onMove: (offset: number) => void;
  onRename: (name: string) => Promise<boolean>;
  onToggle: () => void;
};

function CategoryRow({ category, first, last, busy, onMove, onRename, onToggle }: RowProps) {
  const [name, setName] = useState(category.name);
  const changed = name.trim() !== category.name && name.trim() !== "";

  return (
    <li className="flex flex-wrap items-center gap-2 p-2">
      <div className="flex flex-col">
        <Button type="button" variant="ghost" size="icon-sm" disabled={first || busy} onClick={() => onMove(-1)} aria-label={`Move ${category.name} up`}>
          <ArrowUp aria-hidden />
        </Button>
        <Button type="button" variant="ghost" size="icon-sm" disabled={last || busy} onClick={() => onMove(1)} aria-label={`Move ${category.name} down`}>
          <ArrowDown aria-hidden />
        </Button>
      </div>
      <label htmlFor={`category-${category.id}`} className="sr-only">
        Name of {category.name}
      </label>
      <Input id={`category-${category.id}`} value={name} onChange={(event) => setName(event.target.value)} className="min-w-0 flex-1" />
      {changed ? (
        <Button type="button" size="sm" disabled={busy} onClick={() => void onRename(name.trim())}>
          Save
        </Button>
      ) : null}
      {!category.is_active ? <Badge variant="muted">Inactive</Badge> : null}
      <Button type="button" size="sm" variant="outline" disabled={busy} onClick={onToggle}>
        {category.is_active ? "Deactivate" : "Activate"}
      </Button>
    </li>
  );
}
