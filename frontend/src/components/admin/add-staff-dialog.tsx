"use client";

import Link from "next/link";
import { useState } from "react";
import { useForm, useWatch } from "react-hook-form";
import { UserPlus } from "lucide-react";
import { StaffFormFields, STAFF_SERVER_FIELDS, type StaffDetails } from "@/components/admin/staff-form-fields";
import { describedBy, FormField } from "@/components/shared/form-field";
import { Alert } from "@/components/ui/alert";
import { Button, buttonVariants } from "@/components/ui/button";
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { useRoles, useUserMutation } from "@/lib/api/admin";
import { apiFetch } from "@/lib/api/client";
import { ApiError } from "@/lib/api/errors";
import type { StaffUser } from "@/lib/api/types";
import { useAuth } from "@/lib/auth/auth-provider";
import { temporaryPassword } from "@/lib/temporary-password";

type Values = StaffDetails;

type Created = { user: StaffUser; password: string };

export function AddStaffDialog() {
  const auth = useAuth();
  const [open, setOpen] = useState(false);
  const [created, setCreated] = useState<Created | null>(null);
  const [failure, setFailure] = useState<string | null>(null);
  const canAssignRoles = auth.can("role.manage");
  const roles = useRoles(open && canAssignRoles);
  const form = useForm<Values>({ defaultValues: blank() });
  const { errors, isSubmitting } = form.formState;
  const doesSalonWork = useWatch({ control: form.control, name: "does_salon_work" });

  const create = useUserMutation(async (values: Values) => {
    const body = {
      name: values.name.trim(),
      email: values.email.trim() || null,
      phone: values.phone.trim() || null,
      password: values.password,
      role_ids: values.role_ids.map(Number),
      employee_profile: values.does_salon_work ? { display_name: values.display_name.trim() || values.name.trim().split(" ")[0], job_title: values.job_title.trim() || null } : null,
    };

    return (await apiFetch<StaffUser>("users", { method: "POST", body })).data;
  });

  async function onSubmit(values: Values) {
    setFailure(null);

    try {
      const user = await create.mutateAsync(values);
      setCreated({ user, password: values.password });
    } catch (error) {
      if (!(error instanceof ApiError)) {
        setFailure("Something went wrong. Please try again.");

        return;
      }

      for (const [field, messages] of Object.entries(error.fields)) {
        const target = field === "password" ? "password" : STAFF_SERVER_FIELDS[field as keyof typeof STAFF_SERVER_FIELDS];

        if (target) {
          form.setError(target, { type: "server", message: messages[0] });
        }
      }

      setFailure(Object.keys(error.fields).length > 0 ? null : error.message);
    }
  }

  return (
    <Dialog
      open={open}
      onOpenChange={(next) => {
        setOpen(next);

        if (next) {
          form.reset(blank());
          setFailure(null);
          setCreated(null);
        }
      }}
    >
      <DialogTrigger render={<Button />}>
        <UserPlus aria-hidden />
        Add staff
      </DialogTrigger>
      <DialogContent>
        <DialogTitle>{created ? `${created.user.name} has been added` : "Add staff"}</DialogTitle>
        <DialogDescription>They sign in with their phone number or email and the temporary password, then choose their own.</DialogDescription>

        {created ? (
          <div className="mt-4 space-y-4">
            <Alert variant="success">
              Temporary password: <strong className="font-mono select-all">{created.password}</strong>
              <span className="mt-1 block">Write it down or tell them now. It is not shown again.</span>
            </Alert>
            <DialogFooter>
              <DialogClose render={<Button type="button" variant="outline" />}>Done</DialogClose>
              <Link href={`/admin/users/edit/?id=${created.user.id}`} className={buttonVariants()} onClick={() => setOpen(false)}>
                Open their details
              </Link>
            </DialogFooter>
          </div>
        ) : (
        <form noValidate onSubmit={form.handleSubmit(onSubmit)} className="mt-4 space-y-4">
          {failure ? <Alert variant="destructive">{failure}</Alert> : null}
          {!canAssignRoles ? <Alert variant="warning">You need permission to manage roles to add staff.</Alert> : null}

          <StaffFormFields register={form.register} errors={errors} roles={roles.data} doesSalonWork={doesSalonWork} />

          <FormField id="password" label="Temporary password" hint="Give this to them in person. They must change it." error={errors.password?.message}>
            <div className="flex gap-2">
              <Input id="password" autoComplete="off" spellCheck={false} aria-describedby={describedBy("password", "hint", errors.password?.message)} {...form.register("password")} />
              <Button type="button" variant="outline" onClick={() => form.setValue("password", temporaryPassword())}>
                New
              </Button>
            </div>
          </FormField>

          <DialogFooter>
            <DialogClose render={<Button type="button" variant="outline" />}>Cancel</DialogClose>
            <Button type="submit" disabled={isSubmitting || !canAssignRoles}>
              {isSubmitting ? "Adding..." : "Add staff"}
            </Button>
          </DialogFooter>
        </form>
        )}
      </DialogContent>
    </Dialog>
  );
}

function blank(): Values {
  return { name: "", email: "", phone: "", does_salon_work: true, display_name: "", job_title: "", role_ids: [], password: temporaryPassword() };
}
