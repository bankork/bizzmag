"use client";

import { zodResolver } from "@hookform/resolvers/zod";
import { useRouter } from "next/navigation";
import { useState } from "react";
import { useForm } from "react-hook-form";
import { z } from "zod";
import { describedBy, FormField } from "@/components/shared/form-field";
import { Alert } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { apiFetch } from "@/lib/api/client";
import { ApiError } from "@/lib/api/errors";
import type { Me } from "@/lib/api/types";
import { useAuth } from "@/lib/auth/auth-provider";
import { AREAS } from "@/lib/auth/areas";
import { applyServerErrors } from "@/lib/forms";

const schema = z
  .object({
    current_password: z.string().min(1, "Enter your current password."),
    password: z.string().min(8, "Use at least 8 characters."),
    password_confirmation: z.string(),
  })
  .refine((values) => values.password === values.password_confirmation, {
    path: ["password_confirmation"],
    message: "The two new passwords do not match.",
  });

type Values = z.infer<typeof schema>;

const FIELDS = ["current_password", "password", "password_confirmation"] as const;

export function ChangePasswordForm({ forced }: { forced: boolean }) {
  const auth = useAuth();
  const router = useRouter();
  const [failure, setFailure] = useState<string | null>(null);
  const [saved, setSaved] = useState(false);
  const form = useForm<Values>({ resolver: zodResolver(schema), defaultValues: { current_password: "", password: "", password_confirmation: "" } });
  const { errors, isSubmitting } = form.formState;

  async function onSubmit(values: Values) {
    setFailure(null);
    setSaved(false);

    try {
      const me = (await apiFetch<Me>("me/password", { method: "PUT", body: values })).data;
      auth.setMe(me);
      form.reset();

      if (forced) {
        router.replace(AREAS[me.home].href);
      } else {
        setSaved(true);
      }
    } catch (error) {
      if (error instanceof ApiError && error.code === "INCORRECT_PASSWORD") {
        form.setError("current_password", { type: "server", message: error.message });

        return;
      }

      setFailure(applyServerErrors(error, form.setError, FIELDS));
    }
  }

  return (
    <form noValidate onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
      {failure ? <Alert variant="destructive">{failure}</Alert> : null}
      {saved ? <Alert variant="success">Your password has been changed. Other devices have been signed out.</Alert> : null}

      <FormField id="current_password" label={forced ? "Temporary password" : "Current password"} error={errors.current_password?.message}>
        <Input
          id="current_password"
          type="password"
          autoComplete="current-password"
          aria-invalid={errors.current_password ? true : undefined}
          aria-describedby={describedBy("current_password", undefined, errors.current_password?.message)}
          {...form.register("current_password")}
        />
      </FormField>
      <FormField id="password" label="New password" hint="At least 8 characters. Avoid easy guesses like password123." error={errors.password?.message}>
        <Input
          id="password"
          type="password"
          autoComplete="new-password"
          aria-invalid={errors.password ? true : undefined}
          aria-describedby={describedBy("password", "hint", errors.password?.message)}
          {...form.register("password")}
        />
      </FormField>
      <FormField id="password_confirmation" label="New password again" error={errors.password_confirmation?.message}>
        <Input
          id="password_confirmation"
          type="password"
          autoComplete="new-password"
          aria-invalid={errors.password_confirmation ? true : undefined}
          aria-describedby={describedBy("password_confirmation", undefined, errors.password_confirmation?.message)}
          {...form.register("password_confirmation")}
        />
      </FormField>
      <Button type="submit" disabled={isSubmitting}>
        {isSubmitting ? "Saving..." : "Change password"}
      </Button>
    </form>
  );
}
