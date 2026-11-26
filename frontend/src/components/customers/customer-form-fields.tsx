"use client";

import { describedBy, FormField } from "@/components/shared/form-field";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";

export type CustomerValues = { name: string; phone: string; email: string; notes: string };

export type CustomerErrors = Partial<Record<keyof CustomerValues, string>>;

/** Server field names to form fields. */
export function customerFieldErrors(fields: Record<string, string[]>): CustomerErrors {
  const errors: CustomerErrors = {};

  for (const key of ["name", "phone", "email", "notes"] as const) {
    if (fields[key]) {
      errors[key] = fields[key][0];
    }
  }

  return errors;
}

type Props = {
  values: CustomerValues;
  errors: CustomerErrors;
  showContact: boolean;
  onChange: (values: CustomerValues) => void;
};

export function CustomerFormFields({ values, errors, showContact, onChange }: Props) {
  const set = (field: keyof CustomerValues) => (event: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => onChange({ ...values, [field]: event.target.value });

  return (
    <div className="space-y-4">
      <FormField id="customer-name" label="Name" error={errors.name}>
        <Input id="customer-name" autoComplete="off" value={values.name} onChange={set("name")} aria-invalid={errors.name ? true : undefined} aria-describedby={describedBy("customer-name", undefined, errors.name)} />
      </FormField>
      {showContact ? (
        <div className="grid gap-4 sm:grid-cols-2">
          <FormField id="customer-phone" label="Phone (optional)" hint="For example 0803 123 4567" error={errors.phone}>
            <Input id="customer-phone" type="tel" inputMode="tel" autoComplete="off" value={values.phone} onChange={set("phone")} aria-invalid={errors.phone ? true : undefined} aria-describedby={describedBy("customer-phone", "hint", errors.phone)} />
          </FormField>
          <FormField id="customer-email" label="Email (optional)" error={errors.email}>
            <Input id="customer-email" type="email" autoComplete="off" value={values.email} onChange={set("email")} aria-invalid={errors.email ? true : undefined} aria-describedby={describedBy("customer-email", undefined, errors.email)} />
          </FormField>
        </div>
      ) : null}
      <FormField id="customer-notes" label="Notes (optional)" hint="Preferences only, for example a favourite style. No health or payment details." error={errors.notes}>
        <Textarea id="customer-notes" rows={2} value={values.notes} onChange={set("notes")} aria-describedby={describedBy("customer-notes", "hint", errors.notes)} />
      </FormField>
    </div>
  );
}
