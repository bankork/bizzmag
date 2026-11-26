"use client";

import type { FieldErrors, UseFormRegister } from "react-hook-form";
import { describedBy, FormField } from "@/components/shared/form-field";
import { Input } from "@/components/ui/input";
import type { Role } from "@/lib/api/types";

export type StaffDetails = {
  name: string;
  email: string;
  phone: string;
  does_salon_work: boolean;
  display_name: string;
  job_title: string;
  role_ids: string[];
  /** Only used when adding staff: the temporary password. */
  password: string;
};

type Props = {
  register: UseFormRegister<StaffDetails>;
  errors: FieldErrors<StaffDetails>;
  roles?: Role[];
  doesSalonWork: boolean;
};

/**
 * Name, contact, roles and salon-work fields shared by Add staff and Edit staff.
 */
export function StaffFormFields({ register, errors, roles, doesSalonWork }: Props) {
  return (
    <div className="space-y-4">
      <FormField id="name" label="Full name" error={errors.name?.message}>
        <Input id="name" autoComplete="off" aria-invalid={errors.name ? true : undefined} aria-describedby={describedBy("name", undefined, errors.name?.message)} {...register("name")} />
      </FormField>
      <div className="grid gap-4 sm:grid-cols-2">
        <FormField id="phone" label="Phone number" hint="They can sign in with this." error={errors.phone?.message}>
          <Input id="phone" type="tel" inputMode="tel" placeholder="0803 123 4567" aria-invalid={errors.phone ? true : undefined} aria-describedby={describedBy("phone", "hint", errors.phone?.message)} {...register("phone")} />
        </FormField>
        <FormField id="email" label="Email (optional)" error={errors.email?.message}>
          <Input id="email" type="email" autoCapitalize="none" aria-invalid={errors.email ? true : undefined} aria-describedby={describedBy("email", undefined, errors.email?.message)} {...register("email")} />
        </FormField>
      </div>

      {roles ? (
        <fieldset className="space-y-2">
          <legend className="text-sm font-medium">Roles</legend>
          <div className="grid gap-2 sm:grid-cols-2">
            {roles.map((role) => (
              <label key={role.id} className="flex items-start gap-2 rounded-lg border p-3 text-sm has-checked:border-primary">
                <input type="checkbox" value={String(role.id)} className="mt-0.5 size-4 accent-primary" {...register("role_ids")} />
                <span>
                  <span className="font-medium">{role.name}</span>
                  {role.description ? <span className="block text-xs text-muted-foreground">{role.description}</span> : null}
                </span>
              </label>
            ))}
          </div>
          {errors.role_ids?.message ? <p className="text-sm text-destructive">{errors.role_ids.message}</p> : null}
        </fieldset>
      ) : null}

      <label className="flex items-start gap-2 text-sm">
        <input type="checkbox" className="mt-0.5 size-4 accent-primary" {...register("does_salon_work")} />
        <span>
          <span className="font-medium">Does salon work</span>
          <span className="block text-xs text-muted-foreground">They can be named on services and credited for them.</span>
        </span>
      </label>

      {doesSalonWork ? (
        <div className="grid gap-4 sm:grid-cols-2">
          <FormField id="display_name" label="Name on the board" hint="Usually a first name." error={errors.display_name?.message}>
            <Input id="display_name" aria-invalid={errors.display_name ? true : undefined} aria-describedby={describedBy("display_name", "hint", errors.display_name?.message)} {...register("display_name")} />
          </FormField>
          <FormField id="job_title" label="Job title (optional)" error={errors.job_title?.message}>
            <Input id="job_title" placeholder="Braider" {...register("job_title")} />
          </FormField>
        </div>
      ) : null}
    </div>
  );
}

/** Field names the server uses, mapped to the form's fields. */
export const STAFF_SERVER_FIELDS = {
  name: "name",
  email: "email",
  phone: "phone",
  role_ids: "role_ids",
  "employee_profile.display_name": "display_name",
  "employee_profile.job_title": "job_title",
} as const;
