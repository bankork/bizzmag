"use client";

import Link from "next/link";
import { useState } from "react";
import { ArrowLeft } from "lucide-react";
import { describedBy, FormField } from "@/components/shared/form-field";
import { Alert } from "@/components/ui/alert";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Skeleton } from "@/components/ui/skeleton";
import { Switch } from "@/components/ui/switch";
import { useSettingsHistory, useTax, useTaxMutation } from "@/lib/api/admin";
import { apiFetch } from "@/lib/api/client";
import { ApiError } from "@/lib/api/errors";
import type { HistoryEntry, TaxSetting } from "@/lib/api/types";
import { formatBasisPoints, formatDateTime, parsePercent } from "@/lib/format";

export function TaxSettings() {
  const tax = useTax();

  return (
    <div className="space-y-5">
      <Link href="/admin/settings/" className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
        <ArrowLeft className="size-4" aria-hidden />
        Settings
      </Link>
      {tax.isPending ? <Skeleton className="h-64 w-full" /> : null}
      {tax.isError ? <Alert variant="destructive">{tax.error.message}</Alert> : null}
      {tax.data ? <TaxForm tax={tax.data} /> : null}
      <TaxHistory />
    </div>
  );
}

function TaxForm({ tax }: { tax: TaxSetting }) {
  const [enabled, setEnabled] = useState(tax.enabled);
  const [name, setName] = useState(tax.name);
  const [rate, setRate] = useState(formatBasisPoints(tax.rate_bp).replace(/%$/, ""));
  const [mode, setMode] = useState(tax.mode);
  const [errors, setErrors] = useState<Record<string, string | undefined>>({});
  const [failure, setFailure] = useState<string | null>(null);
  const [saved, setSaved] = useState(false);
  const save = useTaxMutation(async (body) => (await apiFetch<TaxSetting>("settings/tax", { method: "PATCH", body })).data);
  const rateBp = parsePercent(rate);

  async function submit(event: React.FormEvent) {
    event.preventDefault();
    setFailure(null);
    setSaved(false);

    if (rateBp === null || (enabled && rateBp === 0)) {
      setErrors({ rate: enabled ? "Enter a tax rate above 0%, or switch tax off." : "Enter a percentage, for example 7.5." });

      return;
    }

    setErrors({});

    try {
      await save.mutateAsync({ version: tax.version, enabled, name: name.trim(), rate_bp: rateBp, mode });
      setSaved(true);
    } catch (error) {
      if (error instanceof ApiError && error.code === "STALE") {
        setFailure("Someone else changed the tax setting while you were editing. Reload the page to see it, then try again.");
      } else if (error instanceof ApiError && Object.keys(error.fields).length > 0) {
        setErrors({ name: error.fieldMessage("name"), rate: error.fieldMessage("rate_bp"), mode: error.fieldMessage("mode") });
      } else {
        setFailure(error instanceof ApiError ? error.message : "Something went wrong. Please try again.");
      }
    }
  }

  const example = rateBp !== null && rateBp > 0 ? exampleLine(rateBp, mode) : null;

  return (
    <Card>
      <form noValidate onSubmit={submit}>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            Tax {tax.enabled ? <Badge variant="success">On</Badge> : <Badge variant="muted">Off</Badge>}
          </CardTitle>
          <CardDescription>One tax applies to every taxable item on bills made final from now on. Bills already final keep the tax they had.</CardDescription>
        </CardHeader>
        <CardContent className="space-y-5">
          {failure ? <Alert variant="destructive">{failure}</Alert> : null}
          {saved ? <Alert variant="success">Tax setting saved and recorded in the history below.</Alert> : null}

          <label className="flex items-center justify-between gap-4 rounded-lg border p-4">
            <span>
              <span className="block font-medium">Charge tax</span>
              <span className="text-sm text-muted-foreground">Turn on if the salon is registered to charge VAT.</span>
            </span>
            <Switch checked={enabled} onCheckedChange={setEnabled} aria-label="Charge tax" />
          </label>

          <div className="grid gap-4 sm:grid-cols-2">
            <FormField id="tax-name" label="Name on bills" error={errors.name}>
              <Input id="tax-name" value={name} onChange={(event) => setName(event.target.value)} aria-invalid={errors.name ? true : undefined} aria-describedby={describedBy("tax-name", undefined, errors.name)} />
            </FormField>
            <FormField id="tax-rate" label="Rate" error={errors.rate}>
              <div className="relative">
                <Input id="tax-rate" inputMode="decimal" value={rate} onChange={(event) => setRate(event.target.value)} className="pr-8" aria-invalid={errors.rate ? true : undefined} aria-describedby={describedBy("tax-rate", undefined, errors.rate)} />
                <span className="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-muted-foreground">%</span>
              </div>
            </FormField>
          </div>

          <fieldset className="space-y-2">
            <legend className="text-sm font-medium">How prices work</legend>
            {(
              [
                ["exclusive", "Added on top", "A ₦10,000 service is billed as ₦10,000 plus tax."],
                ["inclusive", "Already in the price", "A ₦10,000 service is billed as ₦10,000, which includes the tax."],
              ] as const
            ).map(([value, title, description]) => (
              <label key={value} className="flex items-start gap-2 rounded-lg border p-3 text-sm has-checked:border-primary">
                <input type="radio" name="tax-mode" value={value} checked={mode === value} onChange={() => setMode(value)} className="mt-0.5 size-4 accent-primary" />
                <span>
                  <span className="font-medium">{title}</span>
                  <span className="block text-xs text-muted-foreground">{description}</span>
                </span>
              </label>
            ))}
            {errors.mode ? <p className="text-sm text-destructive">{errors.mode}</p> : null}
          </fieldset>

          {example ? <p className="rounded-lg bg-muted px-4 py-3 text-sm">{example}</p> : null}
        </CardContent>
        <CardFooter>
          <Button type="submit" disabled={save.isPending}>
            {save.isPending ? "Saving..." : "Save tax setting"}
          </Button>
          {tax.updated_at ? <span className="text-xs text-muted-foreground">Last changed {formatDateTime(tax.updated_at)}</span> : null}
        </CardFooter>
      </form>
    </Card>
  );
}

/**
 * An illustration only. Real tax is always calculated by the server, line
 * by line, after discounts (FR-TAX-006).
 */
function exampleLine(rateBp: number, mode: "inclusive" | "exclusive"): string {
  const price = 1_000_000;
  const tax = mode === "exclusive" ? Math.floor((price * rateBp + 5_000) / 10_000) : Math.floor((price * rateBp * 2 + (10_000 + rateBp)) / (2 * (10_000 + rateBp)));
  const naira = (kobo: number) => `₦${(kobo / 100).toLocaleString("en-NG", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

  return mode === "exclusive"
    ? `Example: a ${naira(price)} service becomes ${naira(price + tax)} (${naira(tax)} tax).`
    : `Example: a ${naira(price)} service stays ${naira(price)}, of which ${naira(tax)} is tax.`;
}

function TaxHistory() {
  const history = useSettingsHistory("tax");

  return (
    <Card>
      <CardHeader>
        <CardTitle>Change history</CardTitle>
        <CardDescription>Every change to tax, who made it and when.</CardDescription>
      </CardHeader>
      <CardContent>
        {history.isPending ? <Skeleton className="h-16 w-full" /> : null}
        {history.data?.length === 0 ? <p className="text-sm text-muted-foreground">No changes yet. Tax has been off since the salon was set up.</p> : null}
        <ol className="space-y-3">
          {history.data?.map((entry) => (
            <li key={entry.id} className="border-l-2 pl-3 text-sm">
              <p className="font-medium">{describeTaxChange(entry)}</p>
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

type TaxSnapshot = { enabled?: boolean; name?: string; rate_bp?: number; mode?: string };

export function describeTaxChange(entry: HistoryEntry): string {
  const before = (entry.before ?? {}) as TaxSnapshot;
  const after = (entry.after ?? {}) as TaxSnapshot;
  const label = (snapshot: TaxSnapshot) => `${snapshot.name ?? "Tax"} ${formatBasisPoints(snapshot.rate_bp ?? 0)} ${snapshot.mode === "inclusive" ? "included in prices" : "added on top"}`;

  if (before.enabled !== after.enabled) {
    return after.enabled ? `Switched on: ${label(after)}` : `Switched off (was ${label(before)})`;
  }

  return `Changed from ${label(before)} to ${label(after)}`;
}
