"use client";

import Link from "next/link";
import { useRef, useState, type FormEvent, type ReactNode } from "react";
import { ImageUp, Percent } from "lucide-react";
import { describedBy, FormField } from "@/components/shared/form-field";
import { Alert } from "@/components/ui/alert";
import { Button, buttonVariants } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Skeleton } from "@/components/ui/skeleton";
import { Textarea } from "@/components/ui/textarea";
import { useSettings, useSettingsMutation } from "@/lib/api/admin";
import { apiFetch } from "@/lib/api/client";
import { ApiError } from "@/lib/api/errors";
import type { BusinessSettings, RangedSetting } from "@/lib/api/types";
import { formatPhone } from "@/lib/format";
import { numberFormatProblems, previewNumber } from "@/lib/number-format";

export function SettingsSections() {
  const settings = useSettings();

  if (settings.isPending) {
    return <Skeleton className="h-96 w-full" />;
  }

  if (settings.isError) {
    return <Alert variant="destructive">{settings.error.message}</Alert>;
  }

  return (
    <div className="space-y-5">
      <Link href="/admin/settings/tax/" className="flex items-center justify-between rounded-xl border bg-card p-4 hover:border-primary">
        <span className="flex items-center gap-3">
          <Percent className="size-5 text-primary" aria-hidden />
          <span>
            <span className="block font-medium">Tax</span>
            <span className="text-sm text-muted-foreground">Switch VAT on or off, and set its rate.</span>
          </span>
        </span>
        <span className="text-sm text-primary">Open</span>
      </Link>
      <ProfileSection settings={settings.data} />
      <NumberingSection settings={settings.data} />
      <TimeLimitsSection settings={settings.data} />
    </div>
  );
}

type Status = { failure: string | null; fieldErrors: Record<string, string>; saved: boolean };

/** Saves one section, sending the version the screen was loaded with. */
function useSectionSave() {
  const [status, setStatus] = useState<Status>({ failure: null, fieldErrors: {}, saved: false });
  const mutation = useSettingsMutation(async (body: Record<string, unknown>) => (await apiFetch<BusinessSettings>("settings", { method: "PATCH", body })).data);

  async function save(body: Record<string, unknown>) {
    setStatus({ failure: null, fieldErrors: {}, saved: false });

    try {
      await mutation.mutateAsync(body);
      setStatus({ failure: null, fieldErrors: {}, saved: true });
    } catch (error) {
      if (error instanceof ApiError && error.code === "STALE") {
        setStatus({ failure: "Someone else saved settings while you were editing. Reload the page to see their changes, then try again.", fieldErrors: {}, saved: false });
      } else if (error instanceof ApiError && Object.keys(error.fields).length > 0) {
        setStatus({ failure: null, fieldErrors: Object.fromEntries(Object.entries(error.fields).map(([field, messages]) => [field, messages[0]])), saved: false });
      } else {
        setStatus({ failure: error instanceof ApiError ? error.message : "Something went wrong. Please try again.", fieldErrors: {}, saved: false });
      }
    }
  }

  return { status, save, saving: mutation.isPending };
}

function Section({ title, description, status, saving, onSubmit, children }: { title: string; description?: string; status: Status; saving: boolean; onSubmit: (event: FormEvent) => void; children: ReactNode }) {
  return (
    <Card>
      <form noValidate onSubmit={onSubmit}>
        <CardHeader>
          <CardTitle>{title}</CardTitle>
          {description ? <CardDescription>{description}</CardDescription> : null}
        </CardHeader>
        <CardContent className="space-y-4">
          {status.failure ? <Alert variant="destructive">{status.failure}</Alert> : null}
          {status.saved ? <Alert variant="success">Saved.</Alert> : null}
          {children}
        </CardContent>
        <CardFooter>
          <Button type="submit" disabled={saving}>
            {saving ? "Saving..." : "Save"}
          </Button>
        </CardFooter>
      </form>
    </Card>
  );
}

const PROFILE_FIELDS = [
  { key: "name", label: "Salon name" },
  { key: "phone", label: "Phone number" },
  { key: "email", label: "Email" },
] as const;

function ProfileSection({ settings }: { settings: BusinessSettings }) {
  const { status, save, saving } = useSectionSave();
  const [values, setValues] = useState({
    name: settings.profile.name,
    phone: formatPhone(settings.profile.phone),
    email: settings.profile.email ?? "",
    address: settings.profile.address ?? "",
    receipt_footer: settings.profile.receipt_footer ?? "",
    invoice_footer: settings.profile.invoice_footer ?? "",
  });
  const set = (key: keyof typeof values) => (value: string) => setValues((current) => ({ ...current, [key]: value }));
  const error = (key: string) => status.fieldErrors[`profile.${key}`];

  return (
    <Section
      title="Salon details"
      description="Shown on receipts and invoices."
      status={status}
      saving={saving}
      onSubmit={(event) => {
        event.preventDefault();
        void save({
          version: settings.version,
          profile: Object.fromEntries(Object.entries(values).map(([key, value]) => [key, key === "name" ? value.trim() : value.trim() || null])),
        });
      }}
    >
      <LogoField logoUrl={settings.profile.logo_url} />
      <div className="grid gap-4 sm:grid-cols-3">
        {PROFILE_FIELDS.map(({ key, label }) => (
          <FormField key={key} id={`profile-${key}`} label={label} error={error(key)}>
            <Input id={`profile-${key}`} value={values[key]} onChange={(event) => set(key)(event.target.value)} aria-invalid={error(key) ? true : undefined} aria-describedby={describedBy(`profile-${key}`, undefined, error(key))} />
          </FormField>
        ))}
      </div>
      <FormField id="profile-address" label="Address" error={error("address")}>
        <Textarea id="profile-address" value={values.address} onChange={(event) => set("address")(event.target.value)} rows={2} />
      </FormField>
      <div className="grid gap-4 sm:grid-cols-2">
        <FormField id="profile-receipt" label="Receipt footer" error={error("receipt_footer")}>
          <Textarea id="profile-receipt" value={values.receipt_footer} onChange={(event) => set("receipt_footer")(event.target.value)} rows={2} />
        </FormField>
        <FormField id="profile-invoice" label="Invoice footer" error={error("invoice_footer")}>
          <Textarea id="profile-invoice" value={values.invoice_footer} onChange={(event) => set("invoice_footer")(event.target.value)} rows={2} />
        </FormField>
      </div>
      <p className="text-xs text-muted-foreground">
        Currency: {settings.profile.currency}. Time zone: {settings.profile.timezone}.
      </p>
    </Section>
  );
}

function LogoField({ logoUrl }: { logoUrl: string | null }) {
  const input = useRef<HTMLInputElement>(null);
  const [failure, setFailure] = useState<string | null>(null);
  const upload = useSettingsMutation(async (file: File) => {
    const formData = new FormData();
    formData.append("logo", file);

    return (await apiFetch<BusinessSettings>("settings/logo", { method: "POST", formData })).data;
  });

  return (
    <div className="flex flex-wrap items-center gap-4">
      <div className="flex size-20 items-center justify-center overflow-hidden rounded-lg border bg-muted">
        {/* eslint-disable-next-line @next/next/no-img-element -- static export serves the logo from the API */}
        {logoUrl ? <img src={logoUrl} alt="Salon logo" className="size-full object-contain" /> : <ImageUp className="size-6 text-muted-foreground" aria-hidden />}
      </div>
      <div className="space-y-1">
        <input
          ref={input}
          type="file"
          accept="image/png,image/jpeg"
          className="sr-only"
          id="logo-upload"
          onChange={async (event) => {
            const file = event.target.files?.[0];
            event.target.value = "";
            setFailure(null);

            if (file) {
              try {
                await upload.mutateAsync(file);
              } catch (error) {
                setFailure(error instanceof ApiError ? (error.fieldMessage("logo") ?? error.message) : "The logo could not be uploaded.");
              }
            }
          }}
        />
        <label htmlFor="logo-upload" className={buttonVariants({ variant: "outline", size: "sm" })}>
          {upload.isPending ? "Uploading..." : logoUrl ? "Change logo" : "Upload logo"}
        </label>
        <p className="text-xs text-muted-foreground">PNG or JPEG, up to 1 MB.</p>
        {failure ? <p className="text-sm text-destructive">{failure}</p> : null}
      </div>
    </div>
  );
}

const DOCUMENTS = [
  { key: "order", label: "Visit reference" },
  { key: "invoice", label: "Invoice number" },
  { key: "receipt", label: "Receipt number" },
] as const;

function NumberingSection({ settings }: { settings: BusinessSettings }) {
  const { status, save, saving } = useSectionSave();
  const [formats, setFormats] = useState({ order: settings.numbering.order.format, invoice: settings.numbering.invoice.format, receipt: settings.numbering.receipt.format });
  const today = new Date();

  return (
    <Section
      title="Numbering"
      description="Codes: {YYYY} year, {YY} short year, {MM} month, {DD} day, {YYMMDD} date, {SEQ:5} running number with 5 digits. The running number starts again each day, month or year, depending on the smallest date code used."
      status={status}
      saving={saving}
      onSubmit={(event) => {
        event.preventDefault();

        if (DOCUMENTS.some(({ key }) => numberFormatProblems(formats[key]).length > 0)) {
          return;
        }

        void save({ version: settings.version, numbering: formats });
      }}
    >
      {DOCUMENTS.map(({ key, label }) => {
        const problems = numberFormatProblems(formats[key]);
        const error = status.fieldErrors[`numbering.${key}`] ?? problems[0];
        const hint = problems.length === 0 ? `Next one looks like ${previewNumber(formats[key], today)}` : undefined;

        return (
          <FormField key={key} id={`numbering-${key}`} label={label} hint={hint} error={error}>
            <Input
              id={`numbering-${key}`}
              value={formats[key]}
              spellCheck={false}
              className="font-mono"
              onChange={(event) => setFormats((current) => ({ ...current, [key]: event.target.value }))}
              aria-invalid={error ? true : undefined}
              aria-describedby={describedBy(`numbering-${key}`, hint, error)}
            />
          </FormField>
        );
      })}
      <Alert variant="info">Changing a format does not renumber existing documents. Numbers already issued keep their old format.</Alert>
    </Section>
  );
}

const LIMITS = [
  { group: "windows", key: "late_submission_hours", label: "Late work window", unit: "hours", hint: "How long after a bill is final staff can still record work on it." },
  { group: "windows", key: "transfer_alert_days", label: "Unconfirmed transfer alert", unit: "days", hint: "Warn when a bank transfer is still not confirmed after this long." },
  { group: "windows", key: "share_link_days", label: "Shared receipt links expire after", unit: "days", hint: "Links sent to customers stop working after this." },
  { group: "sessions", key: "idle_minutes_privileged", label: "Sign out cashiers and managers after", unit: "minutes idle", hint: "Anyone who can handle money, bills or settings." },
  { group: "sessions", key: "idle_days_low_risk", label: "Sign out staff on their own phones after", unit: "days idle", hint: "People who only record their own work." },
] as const;

function TimeLimitsSection({ settings }: { settings: BusinessSettings }) {
  const { status, save, saving } = useSectionSave();
  const current = (group: "windows" | "sessions", key: string): RangedSetting => (settings[group] as Record<string, RangedSetting>)[key];
  const [values, setValues] = useState<Record<string, string>>(Object.fromEntries(LIMITS.map(({ group, key }) => [key, String(current(group, key).value)])));

  return (
    <Section
      title="Time limits"
      status={status}
      saving={saving}
      onSubmit={(event) => {
        event.preventDefault();
        const body: Record<string, unknown> = { version: settings.version, windows: {}, sessions: {} };

        for (const { group, key } of LIMITS) {
          (body[group] as Record<string, number>)[key] = Number(values[key]);
        }

        void save(body);
      }}
    >
      <div className="grid gap-4 sm:grid-cols-2">
        {LIMITS.map(({ group, key, label, unit, hint }) => {
          const setting = current(group, key);
          const error = status.fieldErrors[`${group}.${key}`];
          const fullHint = `${hint} Allowed: ${setting.range?.min} to ${setting.range?.max}. Default: ${setting.default}.`;

          return (
            <FormField key={key} id={`limit-${key}`} label={`${label} (${unit})`} hint={fullHint} error={error}>
              <Input
                id={`limit-${key}`}
                type="number"
                inputMode="numeric"
                min={setting.range?.min}
                max={setting.range?.max}
                value={values[key]}
                onChange={(event) => setValues((all) => ({ ...all, [key]: event.target.value }))}
                aria-invalid={error ? true : undefined}
                aria-describedby={describedBy(`limit-${key}`, fullHint, error)}
              />
            </FormField>
          );
        })}
      </div>
    </Section>
  );
}
