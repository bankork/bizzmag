"use client";

import Link from "next/link";
import { useSearchParams } from "next/navigation";
import { useEffect, useState } from "react";
import { useForm, useWatch } from "react-hook-form";
import { ArrowLeft, KeyRound, UserCheck, UserX } from "lucide-react";
import { ConfirmDialog } from "@/components/admin/confirm-dialog";
import { StaffFormFields, STAFF_SERVER_FIELDS, type StaffDetails } from "@/components/admin/staff-form-fields";
import { Alert } from "@/components/ui/alert";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Skeleton } from "@/components/ui/skeleton";
import { Textarea } from "@/components/ui/textarea";
import { useRoles, useUser, useUserMutation } from "@/lib/api/admin";
import { apiFetch } from "@/lib/api/client";
import { ApiError } from "@/lib/api/errors";
import type { StaffUser } from "@/lib/api/types";
import { useAuth } from "@/lib/auth/auth-provider";
import { formatDateTime, formatPhone } from "@/lib/format";
import { temporaryPassword } from "@/lib/temporary-password";

export function StaffEditor() {
  const params = useSearchParams();
  const id = Number(params.get("id"));
  const user = useUser(Number.isInteger(id) && id > 0 ? id : null);

  if (!Number.isInteger(id) || id <= 0 || user.isError) {
    return (
      <Alert variant="destructive">
        {user.error instanceof ApiError && user.error.code !== "NOT_FOUND" ? user.error.message : "This staff member was not found."}{" "}
        <Link href="/admin/users/" className="underline">
          Back to staff
        </Link>
      </Alert>
    );
  }

  if (!user.data) {
    return <Skeleton className="h-64 w-full" />;
  }

  return <Editor key={user.data.id} user={user.data} />;
}

function Editor({ user }: { user: StaffUser }) {
  const auth = useAuth();
  const isSelf = auth.me?.user.id === user.id;
  const canAssignRoles = auth.can("role.manage");

  return (
    <div className="space-y-5">
      <Link href="/admin/users/" className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
        <ArrowLeft className="size-4" aria-hidden />
        All staff
      </Link>

      <div className="flex flex-wrap items-center gap-2">
        <h2 className="text-xl font-semibold">{user.name}</h2>
        {user.is_active ? <Badge variant="success">Active</Badge> : <Badge variant="muted">Disabled {user.disabled_at ? formatDateTime(user.disabled_at) : ""}</Badge>}
        {user.must_change_password && user.is_active ? <Badge variant="warning">Must choose a new password</Badge> : null}
      </div>
      <p className="text-sm text-muted-foreground">Last signed in: {formatDateTime(user.last_login_at)}</p>

      <DetailsCard user={user} />
      {canAssignRoles ? <RolesCard user={user} /> : null}
      {!isSelf ? <AccessCard user={user} /> : <Alert variant="info">This is your own account. Change your password in My account.</Alert>}
    </div>
  );
}

function toForm(user: StaffUser): StaffDetails {
  return {
    name: user.name,
    email: user.email ?? "",
    phone: formatPhone(user.phone),
    does_salon_work: user.employee_profile?.is_active ?? false,
    display_name: user.employee_profile?.display_name ?? user.name.split(" ")[0],
    job_title: user.employee_profile?.job_title ?? "",
    role_ids: user.roles.map((role) => String(role.id)),
    password: "",
  };
}

function DetailsCard({ user }: { user: StaffUser }) {
  const [failure, setFailure] = useState<string | null>(null);
  const [saved, setSaved] = useState(false);
  const form = useForm<StaffDetails>({ defaultValues: toForm(user) });
  const { errors, isSubmitting, isDirty } = form.formState;
  const doesSalonWork = useWatch({ control: form.control, name: "does_salon_work" });

  const save = useUserMutation(async (values: StaffDetails) => {
    const hadProfile = user.employee_profile !== null;
    const body = {
      name: values.name.trim(),
      email: values.email.trim() || null,
      phone: values.phone.trim() || null,
      employee_profile:
        values.does_salon_work || hadProfile
          ? { display_name: values.display_name.trim() || values.name.trim().split(" ")[0], job_title: values.job_title.trim() || null, is_active: values.does_salon_work }
          : null,
    };

    return (await apiFetch<StaffUser>(`users/${user.id}`, { method: "PATCH", body })).data;
  });

  useEffect(() => {
    form.reset(toForm(user));
  }, [user, form]);

  async function onSubmit(values: StaffDetails) {
    setFailure(null);
    setSaved(false);

    try {
      await save.mutateAsync(values);
      setSaved(true);
    } catch (error) {
      if (!(error instanceof ApiError)) {
        setFailure("Something went wrong. Please try again.");

        return;
      }

      for (const [field, messages] of Object.entries(error.fields)) {
        const target = STAFF_SERVER_FIELDS[field as keyof typeof STAFF_SERVER_FIELDS];

        if (target) {
          form.setError(target, { type: "server", message: messages[0] });
        }
      }

      setFailure(Object.keys(error.fields).length > 0 ? null : error.message);
    }
  }

  return (
    <Card>
      <form noValidate onSubmit={form.handleSubmit(onSubmit)}>
        <CardHeader>
          <CardTitle>Details</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          {failure ? <Alert variant="destructive">{failure}</Alert> : null}
          {saved && !isDirty ? <Alert variant="success">Saved.</Alert> : null}
          <StaffFormFields register={form.register} errors={errors} doesSalonWork={doesSalonWork} />
        </CardContent>
        <CardFooter>
          <Button type="submit" disabled={isSubmitting || !isDirty}>
            {isSubmitting ? "Saving..." : "Save details"}
          </Button>
        </CardFooter>
      </form>
    </Card>
  );
}

function RolesCard({ user }: { user: StaffUser }) {
  const roles = useRoles();
  const [selected, setSelected] = useState<number[]>(user.roles.map((role) => role.id));
  const [failure, setFailure] = useState<string | null>(null);
  const [saved, setSaved] = useState(false);
  const unchanged = [...selected].sort().join() === user.roles.map((role) => role.id).sort().join();

  const save = useUserMutation(async (roleIds: number[]) => (await apiFetch<StaffUser>(`users/${user.id}/roles`, { method: "PUT", body: { role_ids: roleIds } })).data);

  async function submit() {
    setFailure(null);
    setSaved(false);

    try {
      await save.mutateAsync(selected);
      setSaved(true);
    } catch (error) {
      setFailure(error instanceof ApiError ? error.message : "Something went wrong. Please try again.");
    }
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle>Roles</CardTitle>
        <CardDescription>A person can hold several roles. They can do everything any of their roles allows.</CardDescription>
      </CardHeader>
      <CardContent className="space-y-3">
        {failure ? <Alert variant="destructive">{failure}</Alert> : null}
        {saved && unchanged ? <Alert variant="success">Roles saved. The change applies to their next action.</Alert> : null}
        {roles.isPending ? <Skeleton className="h-24 w-full" /> : null}
        <div className="grid gap-2 sm:grid-cols-2">
          {roles.data?.map((role) => (
            <label key={role.id} className="flex items-start gap-2 rounded-lg border p-3 text-sm has-checked:border-primary">
              <input
                type="checkbox"
                className="mt-0.5 size-4 accent-primary"
                checked={selected.includes(role.id)}
                onChange={(event) => setSelected((current) => (event.target.checked ? [...current, role.id] : current.filter((roleId) => roleId !== role.id)))}
              />
              <span>
                <span className="font-medium">{role.name}</span>
                {role.description ? <span className="block text-xs text-muted-foreground">{role.description}</span> : null}
              </span>
            </label>
          ))}
        </div>
      </CardContent>
      <CardFooter>
        <Button onClick={submit} disabled={save.isPending || unchanged || selected.length === 0}>
          {save.isPending ? "Saving..." : "Save roles"}
        </Button>
        {selected.length === 0 ? <span className="text-sm text-muted-foreground">Choose at least one role.</span> : null}
      </CardFooter>
    </Card>
  );
}

function AccessCard({ user }: { user: StaffUser }) {
  const [reason, setReason] = useState("");
  const [password, setPassword] = useState(temporaryPassword);
  const [resetDone, setResetDone] = useState<string | null>(null);

  const disable = useUserMutation(async () => (await apiFetch<StaffUser>(`users/${user.id}/disable`, { method: "POST", body: { reason: reason.trim() || null } })).data);
  const enable = useUserMutation(async () => (await apiFetch<StaffUser>(`users/${user.id}/enable`, { method: "POST" })).data);
  const reset = useUserMutation(async (newPassword: string) => (await apiFetch<StaffUser>(`users/${user.id}/reset-password`, { method: "POST", body: { password: newPassword } })).data);

  return (
    <Card>
      <CardHeader>
        <CardTitle>Access</CardTitle>
        <CardDescription>Nobody is ever deleted. Disabling keeps everything they did in every report.</CardDescription>
      </CardHeader>
      <CardContent className="space-y-3">
        {resetDone ? (
          <Alert variant="success">
            New temporary password: <strong className="font-mono select-all">{resetDone}</strong>
            <span className="mt-1 block">Tell them in person. They were signed out and must choose a new password.</span>
          </Alert>
        ) : null}
        <div className="flex flex-wrap gap-2">
          {user.is_active ? (
            <ConfirmDialog
              trigger={
                <>
                  <KeyRound aria-hidden />
                  Reset password
                </>
              }
              title={`Reset ${user.name}'s password?`}
              description="They will be signed out everywhere and must choose a new password when they next sign in."
              confirmLabel="Reset password"
              onConfirm={async () => {
                await reset.mutateAsync(password);
                setResetDone(password);
                setPassword(temporaryPassword());
              }}
            >
              <label htmlFor="temporary-password" className="text-sm font-medium">
                Temporary password
              </label>
              <Input id="temporary-password" value={password} onChange={(event) => setPassword(event.target.value)} spellCheck={false} />
            </ConfirmDialog>
          ) : null}

          {user.is_active ? (
            <ConfirmDialog
              destructive
              trigger={
                <>
                  <UserX aria-hidden />
                  Disable
                </>
              }
              title={`Disable ${user.name}?`}
              description="They are signed out at once and cannot sign in. Their past work stays in every report."
              confirmLabel="Disable"
              onConfirm={async () => {
                await disable.mutateAsync(undefined);
              }}
            >
              <label htmlFor="disable-reason" className="text-sm font-medium">
                Reason (optional)
              </label>
              <Textarea id="disable-reason" value={reason} onChange={(event) => setReason(event.target.value)} placeholder="For example: left the salon" />
            </ConfirmDialog>
          ) : (
            <ConfirmDialog
              trigger={
                <>
                  <UserCheck aria-hidden />
                  Enable
                </>
              }
              title={`Enable ${user.name}?`}
              description="They can sign in again with their current password."
              confirmLabel="Enable"
              onConfirm={async () => {
                await enable.mutateAsync(undefined);
              }}
            />
          )}
        </div>
      </CardContent>
    </Card>
  );
}
