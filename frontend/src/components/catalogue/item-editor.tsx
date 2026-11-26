"use client";

import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import { useState } from "react";
import { ArrowLeft } from "lucide-react";
import { describeItemChange } from "@/components/catalogue/catalogue-format";
import { ConfirmDialog } from "@/components/admin/confirm-dialog";
import { describedBy, FormField } from "@/components/shared/form-field";
import { Alert } from "@/components/ui/alert";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { NativeSelect } from "@/components/ui/native-select";
import { Skeleton } from "@/components/ui/skeleton";
import { Switch } from "@/components/ui/switch";
import { Textarea } from "@/components/ui/textarea";
import { createItem, setItemActive, updateItem, useCatalogueHistory, useCatalogueItem, useCatalogueMutation, useCategories, type ItemInput } from "@/lib/api/catalogue";
import { ApiError } from "@/lib/api/errors";
import type { CatalogueItem, ItemType, SplitMethod } from "@/lib/api/types";
import { splitExample } from "@/lib/credit-split";
import { formatBasisPoints, formatDateTime, koboToNairaInput, parseNaira, parsePercent } from "@/lib/format";
import { formatKoboCompact } from "@/lib/money";

const BACK = (
  <Link href="/admin/catalogue/" className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
    <ArrowLeft className="size-4" aria-hidden />
    Catalogue
  </Link>
);

export function ItemEditor() {
  const params = useSearchParams();
  const newType = params.get("new");
  const id = Number(params.get("id"));
  const validId = Number.isInteger(id) && id > 0;
  const item = useCatalogueItem(validId ? id : null);

  if (newType === "service" || newType === "product") {
    return (
      <div className="space-y-5">
        {BACK}
        <h2 className="text-xl font-semibold">{newType === "service" ? "New service" : "New product"}</h2>
        <DetailsForm key={newType} type={newType} item={null} />
      </div>
    );
  }

  if (!validId || item.isError) {
    return (
      <Alert variant="destructive">
        This item was not found.{" "}
        <Link href="/admin/catalogue/" className="underline">
          Back to the catalogue
        </Link>
      </Alert>
    );
  }

  if (!item.data) {
    return <Skeleton className="h-64 w-full" />;
  }

  const record = item.data;

  return (
    <div className="space-y-5">
      {BACK}
      <div className="flex flex-wrap items-center gap-2">
        <h2 className="text-xl font-semibold">{record.name}</h2>
        {record.is_active ? <Badge variant="success">Active</Badge> : <Badge variant="muted">Inactive</Badge>}
        {record.is_active && !record.orderable && !record.has_variants ? <Badge variant="warning">Its category is inactive</Badge> : null}
      </div>
      <DetailsForm key={`${record.id}-${record.version}`} type={record.type} item={record} />
      {record.has_variants ? <VariantsCard parent={record} /> : null}
      <StatusCard item={record} />
      <HistoryCard id={record.id} />
    </div>
  );
}

type Errors = Partial<Record<"name" | "category_id" | "price_kobo" | "allocation_method" | "primary_share_bp" | "has_variants" | "description", string>>;

function DetailsForm({ type, item }: { type: ItemType; item: CatalogueItem | null }) {
  const router = useRouter();
  const categories = useCategories();
  const options = (categories.data ?? []).filter((category) => category.type === type).sort((a, b) => a.sort_order - b.sort_order);
  const [name, setName] = useState(item?.name ?? "");
  const [categoryId, setCategoryId] = useState(item?.category_id ? String(item.category_id) : "");
  const [description, setDescription] = useState(item?.description ?? "");
  const [taxable, setTaxable] = useState(item?.taxable ?? true);
  const [quantityAllowed, setQuantityAllowed] = useState(item?.quantity_allowed ?? false);
  const [hasVariants, setHasVariants] = useState(item?.has_variants ?? false);
  const [price, setPrice] = useState(typeof item?.price_kobo === "number" ? koboToNairaInput(item.price_kobo) : "");
  const [method, setMethod] = useState<SplitMethod>(item?.allocation_method ?? "equal");
  const [share, setShare] = useState(formatBasisPoints(item?.primary_share_bp ?? 5_000).replace(/%$/, ""));
  const [errors, setErrors] = useState<Errors>({});
  const [failure, setFailure] = useState<string | null>(null);
  const [saved, setSaved] = useState(false);
  const save = useCatalogueMutation(async (body: ItemInput) => (item ? updateItem(item.id, item.version, body) : createItem(body)));
  const isService = type === "service";
  const variantsLocked = item !== null && (item.variants?.length ?? 0) > 0;
  const priceKobo = parseNaira(price);
  const shareBp = parsePercent(share);
  const examplePrice = hasVariants ? (item?.variants?.[0]?.price_kobo ?? null) : priceKobo;

  async function submit(event: React.FormEvent) {
    event.preventDefault();
    setFailure(null);
    setSaved(false);

    const local: Errors = {};

    if (name.trim() === "") local.name = "Enter a name.";
    if (categoryId === "") local.category_id = "Choose a category.";
    if (!hasVariants && priceKobo === null) local.price_kobo = "Enter a price, for example 40,000.";
    if (isService && method === "primary_assistant" && (shareBp === null || shareBp < 100)) local.primary_share_bp = "Enter a share between 1 and 100.";

    setErrors(local);

    if (Object.keys(local).length > 0) {
      return;
    }

    const body: ItemInput = {
      name: name.trim(),
      category_id: Number(categoryId),
      description: description.trim() || null,
      taxable,
      has_variants: hasVariants,
      price_kobo: hasVariants ? null : priceKobo,
      ...(isService ? { quantity_allowed: quantityAllowed, allocation_method: method, primary_share_bp: method === "primary_assistant" ? shareBp : null } : {}),
    };

    try {
      const result = await save.mutateAsync(body);

      if (item === null) {
        router.replace(`/admin/catalogue/edit/?id=${result.id}`);
      } else {
        setSaved(true);
      }
    } catch (error) {
      if (error instanceof ApiError && error.code === "STALE") {
        setFailure("Someone else changed this item while you were editing. Reload the page to see their change, then try again.");
      } else if (error instanceof ApiError && Object.keys(error.fields).length > 0) {
        setErrors(Object.fromEntries(Object.entries(error.fields).map(([field, messages]) => [field, messages[0]])) as Errors);
      } else {
        setFailure(error instanceof ApiError ? error.message : "Something went wrong. Please try again.");
      }
    }
  }

  return (
    <Card>
      <form noValidate onSubmit={submit}>
        <CardHeader>
          <CardTitle>Details</CardTitle>
          <CardDescription>Price changes apply to new visits only. Visits already open keep the price they started with.</CardDescription>
        </CardHeader>
        <CardContent className="space-y-5">
          {failure ? <Alert variant="destructive">{failure}</Alert> : null}
          {saved ? <Alert variant="success">Saved. The change is in the history below.</Alert> : null}

          <div className="grid gap-4 sm:grid-cols-2">
            <FormField id="item-name" label="Name" error={errors.name}>
              <Input id="item-name" value={name} onChange={(event) => setName(event.target.value)} aria-invalid={errors.name ? true : undefined} aria-describedby={describedBy("item-name", undefined, errors.name)} />
            </FormField>
            <FormField id="item-category" label="Category" error={errors.category_id}>
              <NativeSelect id="item-category" value={categoryId} onChange={(event) => setCategoryId(event.target.value)} aria-invalid={errors.category_id ? true : undefined}>
                <option value="">Choose a category</option>
                {options.map((category) => (
                  <option key={category.id} value={category.id}>
                    {category.name}
                    {category.is_active ? "" : " (inactive)"}
                  </option>
                ))}
              </NativeSelect>
            </FormField>
          </div>

          <FormField id="item-description" label="Description (optional)" error={errors.description}>
            <Textarea id="item-description" rows={2} value={description} onChange={(event) => setDescription(event.target.value)} />
          </FormField>

          {isService ? (
            <ToggleRow
              label="This service has variants"
              hint={variantsLocked ? "It has variants, so this stays on. Deactivate variants you no longer offer." : "For example Small, Medium and Large, each with its own price."}
              checked={hasVariants}
              disabled={variantsLocked}
              onChange={setHasVariants}
              error={errors.has_variants}
            />
          ) : null}

          {!hasVariants ? (
            <FormField id="item-price" label="Price" hint="In naira, for example 40,000" error={errors.price_kobo}>
              <div className="relative sm:max-w-xs">
                <span className="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-muted-foreground">₦</span>
                <Input id="item-price" inputMode="decimal" value={price} onChange={(event) => setPrice(event.target.value)} className="pl-7" aria-invalid={errors.price_kobo ? true : undefined} aria-describedby={describedBy("item-price", "hint", errors.price_kobo)} />
              </div>
            </FormField>
          ) : (
            <p className="rounded-lg bg-muted px-4 py-3 text-sm">{item ? "Each variant has its own price, set below." : "Save first, then add the variants and their prices."}</p>
          )}

          <ToggleRow label="Taxable" hint="Switch off for items exempt from tax. Only matters when tax is switched on." checked={taxable} onChange={setTaxable} />
          {isService ? (
            <ToggleRow label="Can be charged more than once on a visit" hint="For example nail art per set. Most services are charged once." checked={quantityAllowed} onChange={setQuantityAllowed} />
          ) : (
            <p className="text-sm text-muted-foreground">Products can always be sold in any quantity and are not credited to staff.</p>
          )}

          {isService ? (
            <fieldset className="space-y-3">
              <legend className="text-sm font-medium">How credit is shared when several staff do this service</legend>
              {(
                [
                  ["equal", "Shared equally", "Everyone who worked on it gets the same share."],
                  ["primary_assistant", "Primary and assistants", "The person who led it gets a set share; the others share the rest equally."],
                ] as const
              ).map(([value, title, text]) => (
                <label key={value} className="flex items-start gap-2 rounded-lg border p-3 text-sm has-checked:border-primary">
                  <input type="radio" name="split-method" value={value} checked={method === value} onChange={() => setMethod(value)} className="mt-0.5 size-4 accent-primary" />
                  <span>
                    <span className="font-medium">{title}</span>
                    <span className="block text-xs text-muted-foreground">{text}</span>
                  </span>
                </label>
              ))}
              {errors.allocation_method ? <p className="text-sm text-destructive">{errors.allocation_method}</p> : null}
              {method === "primary_assistant" ? (
                <FormField id="item-share" label="Primary's share" error={errors.primary_share_bp}>
                  <div className="relative w-32">
                    <Input id="item-share" inputMode="decimal" value={share} onChange={(event) => setShare(event.target.value)} className="pr-8" aria-invalid={errors.primary_share_bp ? true : undefined} aria-describedby={describedBy("item-share", undefined, errors.primary_share_bp)} />
                    <span className="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-muted-foreground">%</span>
                  </div>
                </FormField>
              ) : null}
              {typeof examplePrice === "number" && examplePrice > 0 ? <SplitExample priceKobo={examplePrice} method={method} shareBp={shareBp} /> : null}
              <p className="text-xs text-muted-foreground">A supervisor can still change the split for one visit, giving a reason.</p>
            </fieldset>
          ) : null}
        </CardContent>
        <CardFooter>
          <Button type="submit" disabled={save.isPending}>
            {save.isPending ? "Saving..." : item ? "Save changes" : type === "service" ? "Add service" : "Add product"}
          </Button>
        </CardFooter>
      </form>
    </Card>
  );
}

function ToggleRow({ label, hint, checked, disabled, onChange, error }: { label: string; hint: string; checked: boolean; disabled?: boolean; onChange: (value: boolean) => void; error?: string }) {
  return (
    <div>
      <label className="flex items-center justify-between gap-4 rounded-lg border p-3">
        <span>
          <span className="block text-sm font-medium">{label}</span>
          <span className="text-xs text-muted-foreground">{hint}</span>
        </span>
        <Switch checked={checked} disabled={disabled} onCheckedChange={onChange} aria-label={label} />
      </label>
      {error ? <p className="mt-1 text-sm text-destructive">{error}</p> : null}
    </div>
  );
}

/**
 * An illustration only: the server allocates when the bill is final, after
 * discounts and without tax.
 */
function SplitExample({ priceKobo, method, shareBp }: { priceKobo: number; method: SplitMethod; shareBp: number | null }) {
  return (
    <div className="rounded-lg bg-muted px-4 py-3 text-sm" aria-live="polite">
      <p className="font-medium">Example on {formatKoboCompact(priceKobo)}</p>
      <ul className="mt-1 space-y-0.5 text-muted-foreground">
        {[1, 2, 3].map((staff) => {
          const shares = splitExample(priceKobo, staff, method, shareBp);
          const [primary, ...assistants] = shares;

          return (
            <li key={staff}>
              {staff === 1 ? "1 person" : `${staff} people`}: {staff === 1 ? formatKoboCompact(primary.kobo) : `primary ${formatKoboCompact(primary.kobo)}, ${assistants.length === 1 ? "assistant" : "each assistant"} ${formatKoboCompact(assistants[0].kobo)}`}
            </li>
          );
        })}
      </ul>
    </div>
  );
}

function VariantsCard({ parent }: { parent: CatalogueItem }) {
  const variants = parent.variants ?? [];
  const [name, setName] = useState("");
  const [price, setPrice] = useState("");
  const [failure, setFailure] = useState<string | null>(null);
  const add = useCatalogueMutation(async (body: ItemInput) => createItem(body));

  async function submit(event: React.FormEvent) {
    event.preventDefault();
    setFailure(null);
    const kobo = parseNaira(price);

    if (name.trim() === "" || kobo === null) {
      setFailure("Enter a variant name and its price.");

      return;
    }

    try {
      await add.mutateAsync({ parent_id: parent.id, name: name.trim(), price_kobo: kobo });
      setName("");
      setPrice("");
    } catch (error) {
      setFailure(error instanceof ApiError ? (error.fieldMessage("name") ?? error.fieldMessage("price_kobo") ?? error.message) : "Something went wrong. Please try again.");
    }
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle>Variants</CardTitle>
        <CardDescription>Each variant has its own price. Tax, quantity and credit split come from the service above.</CardDescription>
      </CardHeader>
      <CardContent className="space-y-4">
        <ul className="divide-y rounded-lg border">
          {variants.length === 0 ? <li className="p-3 text-sm text-muted-foreground">No variants yet. Add the first one below.</li> : null}
          {variants.map((variant) => (
            <VariantRow key={`${variant.id}-${variant.version}`} variant={variant} />
          ))}
        </ul>
        <form noValidate onSubmit={submit} className="space-y-2">
          {failure ? <Alert variant="destructive">{failure}</Alert> : null}
          <div className="flex flex-col gap-2 sm:flex-row">
            <label htmlFor="variant-name" className="sr-only">
              Variant name
            </label>
            <Input id="variant-name" placeholder="Variant, for example Medium" value={name} onChange={(event) => setName(event.target.value)} />
            <label htmlFor="variant-price" className="sr-only">
              Variant price in naira
            </label>
            <div className="relative sm:w-40">
              <span className="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-muted-foreground">₦</span>
              <Input id="variant-price" inputMode="decimal" placeholder="40,000" value={price} onChange={(event) => setPrice(event.target.value)} className="pl-7" />
            </div>
            <Button type="submit" disabled={add.isPending}>
              Add variant
            </Button>
          </div>
        </form>
      </CardContent>
    </Card>
  );
}

function VariantRow({ variant }: { variant: CatalogueItem }) {
  const [name, setName] = useState(variant.name);
  const [price, setPrice] = useState(typeof variant.price_kobo === "number" ? koboToNairaInput(variant.price_kobo) : "");
  const [failure, setFailure] = useState<string | null>(null);
  const save = useCatalogueMutation(async (body: ItemInput) => updateItem(variant.id, variant.version, body));
  const toggle = useCatalogueMutation(async () => setItemActive(variant.id, !variant.is_active));
  const kobo = parseNaira(price);
  const changed = name.trim() !== variant.name || kobo !== variant.price_kobo;

  async function submit(event: React.FormEvent) {
    event.preventDefault();
    setFailure(null);

    if (name.trim() === "" || kobo === null) {
      setFailure("Enter a name and a price.");

      return;
    }

    try {
      await save.mutateAsync({ name: name.trim(), price_kobo: kobo });
    } catch (error) {
      setFailure(error instanceof ApiError ? (error.code === "STALE" ? "Someone else changed this variant. Reload the page." : (error.fieldMessage("name") ?? error.message)) : "Something went wrong. Please try again.");
    }
  }

  return (
    <li className="p-2">
      <form noValidate onSubmit={submit} className="flex flex-wrap items-center gap-2">
        <label htmlFor={`variant-${variant.id}-name`} className="sr-only">
          Name of {variant.name}
        </label>
        <Input id={`variant-${variant.id}-name`} value={name} onChange={(event) => setName(event.target.value)} className="min-w-0 flex-1" />
        <label htmlFor={`variant-${variant.id}-price`} className="sr-only">
          Price of {variant.name} in naira
        </label>
        <div className="relative w-36">
          <span className="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-muted-foreground">₦</span>
          <Input id={`variant-${variant.id}-price`} inputMode="decimal" value={price} onChange={(event) => setPrice(event.target.value)} className="pl-7" />
        </div>
        {changed ? (
          <Button type="submit" size="sm" disabled={save.isPending}>
            Save
          </Button>
        ) : null}
        {!variant.is_active ? <Badge variant="muted">Inactive</Badge> : null}
        <Button type="button" size="sm" variant="outline" disabled={toggle.isPending} onClick={() => toggle.mutate()}>
          {variant.is_active ? "Deactivate" : "Activate"}
        </Button>
      </form>
      {failure ? <p className="mt-1 text-sm text-destructive">{failure}</p> : null}
    </li>
  );
}

function StatusCard({ item }: { item: CatalogueItem }) {
  const toggle = useCatalogueMutation(async () => setItemActive(item.id, !item.is_active));

  return (
    <Card>
      <CardHeader>
        <CardTitle>{item.is_active ? "Stop offering this" : "Offer this again"}</CardTitle>
        <CardDescription>
          {item.is_active
            ? "A deactivated item cannot be chosen for new visits. Past visits keep it. Nothing is deleted."
            : "Activate it to make it available for new visits again."}
        </CardDescription>
      </CardHeader>
      <CardFooter>
        <ConfirmDialog
          trigger={<Button variant={item.is_active ? "outline" : "default"}>{item.is_active ? "Deactivate" : "Activate"}</Button>}
          title={item.is_active ? `Deactivate ${item.name}?` : `Activate ${item.name}?`}
          description={item.is_active ? "It disappears from the till for new visits. You can activate it again at any time." : "It becomes available for new visits."}
          confirmLabel={item.is_active ? "Deactivate" : "Activate"}
          onConfirm={async () => {
            await toggle.mutateAsync();
          }}
        />
      </CardFooter>
    </Card>
  );
}

function HistoryCard({ id }: { id: number }) {
  const history = useCatalogueHistory(id);

  return (
    <Card>
      <CardHeader>
        <CardTitle>Change history</CardTitle>
        <CardDescription>Every change, including each price change, who made it and when.</CardDescription>
      </CardHeader>
      <CardContent>
        {history.isPending ? <Skeleton className="h-16 w-full" /> : null}
        {history.data?.length === 0 ? <p className="text-sm text-muted-foreground">No changes recorded yet.</p> : null}
        <ol className="space-y-3">
          {history.data?.map((entry) => (
            <li key={entry.id} className="border-l-2 pl-3 text-sm">
              <p className="font-medium">{describeItemChange(entry)}</p>
              <p className="text-xs text-muted-foreground">
                {entry.actor?.name ?? "System"} · {formatDateTime(entry.at)}
              </p>
            </li>
          ))}
        </ol>
      </CardContent>
    </Card>
  );
}
