"use client";

import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import { useMemo, useState } from "react";
import { ArrowLeft, Lock, Trash2 } from "lucide-react";
import { ConfirmDialog } from "@/components/admin/confirm-dialog";
import { describedBy, FormField } from "@/components/shared/form-field";
import { Alert } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Skeleton } from "@/components/ui/skeleton";
import { usePermissionCatalogue, useRoleMutation, useRoles } from "@/lib/api/admin";
import { apiFetch } from "@/lib/api/client";
import { ApiError } from "@/lib/api/errors";
import type { PermissionDefinition, Role } from "@/lib/api/types";
import { formatBasisPoints, koboToNairaInput, parseNaira, parsePercent } from "@/lib/format";

export function RoleEditor() {
  const params = useSearchParams();
  const id = params.get("id");
  const roles = useRoles();
  const catalogue = usePermissionCatalogue();

  if (roles.isPending || catalogue.isPending) {
    return <Skeleton className="h-96 w-full" />;
  }

  if (roles.isError || catalogue.isError) {
    return <Alert variant="destructive">{(roles.error ?? catalogue.error)?.message}</Alert>;
  }

  const role = id === null ? null : (roles.data.find((candidate) => candidate.id === Number(id)) ?? undefined);

  if (role === undefined) {
    return (
      <Alert variant="destructive">
        This role was not found.{" "}
        <Link href="/admin/roles/" className="underline">
          Back to roles
        </Link>
      </Alert>
    );
  }

  return <Editor key={role?.id ?? "new"} role={role} catalogue={catalogue.data} />;
}

type Errors = Partial<Record<"name" | "description" | "percent" | "naira" | "permissions", string>>;

function Editor({ role, catalogue }: { role: Role | null; catalogue: PermissionDefinition[] }) {
  const router = useRouter();
  const locked = role?.is_owner ?? false;
  const [name, setName] = useState(role?.name ?? "");
  const [description, setDescription] = useState(role?.description ?? "");
  const [percent, setPercent] = useState(role?.discount_cap_bp === null || role === null ? "" : trimPercent(role.discount_cap_bp));
  const [naira, setNaira] = useState(role?.discount_cap_kobo === null || role === null ? "" : koboToNairaInput(role.discount_cap_kobo));
  const [permissions, setPermissions] = useState<Set<string>>(new Set(role?.permissions ?? []));
  const [errors, setErrors] = useState<Errors>({});
  const [failure, setFailure] = useState<string | null>(null);
  const [saved, setSaved] = useState(false);

  const groups = useMemo(() => {
    const byGroup = new Map<string, PermissionDefinition[]>();

    for (const permission of catalogue) {
      byGroup.set(permission.group, [...(byGroup.get(permission.group) ?? []), permission]);
    }

    return [...byGroup.entries()];
  }, [catalogue]);

  const save = useRoleMutation(async (body: Record<string, unknown>) =>
    role === null ? (await apiFetch<Role>("roles", { method: "POST", body })).data : (await apiFetch<Role>(`roles/${role.id}`, { method: "PATCH", body })).data,
  );
  const remove = useRoleMutation(async () => {
    await apiFetch<null>(`roles/${role?.id}`, { method: "DELETE" });

    return null;
  });

  function toggle(permission: string, checked: boolean) {
    setPermissions((current) => {
      const next = new Set(current);

      if (checked) {
        next.add(permission);
      } else {
        next.delete(permission);
      }

      return next;
    });
  }

  async function submit(event: React.FormEvent) {
    event.preventDefault();
    setFailure(null);
    setSaved(false);

    const found: Errors = {};
    const percentBp = percent.trim() === "" ? null : parsePercent(percent);
    const capKobo = naira.trim() === "" ? null : parseNaira(naira);

    if (name.trim() === "") {
      found.name = "Enter a name for the role.";
    }

    if (percent.trim() !== "" && percentBp === null) {
      found.percent = "Enter a percentage from 0 to 100, for example 10.";
    }

    if (naira.trim() !== "" && capKobo === null) {
      found.naira = "Enter an amount in naira, for example 5,000.";
    }

    setErrors(found);

    if (Object.keys(found).length > 0) {
      return;
    }

    const body: Record<string, unknown> = { name: name.trim(), description: description.trim() || null, discount_cap_bp: percentBp, discount_cap_kobo: capKobo };

    if (!locked) {
      body.permissions = [...permissions];
    }

    try {
      const result = await save.mutateAsync(body);
      setSaved(true);

      if (role === null && result) {
        router.replace(`/admin/roles/edit/?id=${result.id}`);
      }
    } catch (error) {
      if (error instanceof ApiError && Object.keys(error.fields).length > 0) {
        setErrors({
          name: error.fieldMessage("name"),
          description: error.fieldMessage("description"),
          percent: error.fieldMessage("discount_cap_bp"),
          naira: error.fieldMessage("discount_cap_kobo"),
          permissions: error.fieldMessage("permissions"),
        });

        return;
      }

      setFailure(error instanceof ApiError ? error.message : "Something went wrong. Please try again.");
    }
  }

  return (
    <form noValidate onSubmit={submit} className="space-y-5">
      <Link href="/admin/roles/" className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
        <ArrowLeft className="size-4" aria-hidden />
        All roles
      </Link>

      {failure ? <Alert variant="destructive">{failure}</Alert> : null}
      {saved ? <Alert variant="success">Role saved. Changes apply to each person&apos;s next action.</Alert> : null}

      <Card>
        <CardHeader>
          <CardTitle>{role === null ? "New role" : role.name}</CardTitle>
          {role?.is_system ? <CardDescription>A default role. You can rename it and change what it allows, but not delete it.</CardDescription> : null}
        </CardHeader>
        <CardContent className="space-y-4">
          <FormField id="role-name" label="Name" error={errors.name}>
            <Input id="role-name" value={name} onChange={(event) => setName(event.target.value)} aria-invalid={errors.name ? true : undefined} aria-describedby={describedBy("role-name", undefined, errors.name)} />
          </FormField>
          <FormField id="role-description" label="Description (optional)" error={errors.description}>
            <Input id="role-description" value={description} onChange={(event) => setDescription(event.target.value)} />
          </FormField>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Discount limit</CardTitle>
          <CardDescription>The most this role can take off a bill without approval. Leave a box empty for no limit. With several roles, the highest limit applies.</CardDescription>
        </CardHeader>
        <CardContent className="grid gap-4 sm:grid-cols-2">
          <FormField id="cap-percent" label="Percentage of the bill" hint={percent.trim() === "" ? "No percentage limit" : undefined} error={errors.percent}>
            <div className="relative">
              <Input id="cap-percent" inputMode="decimal" value={percent} onChange={(event) => setPercent(event.target.value)} className="pr-8" aria-invalid={errors.percent ? true : undefined} aria-describedby={describedBy("cap-percent", percent.trim() === "" ? "hint" : undefined, errors.percent)} />
              <span className="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-muted-foreground">%</span>
            </div>
          </FormField>
          <FormField id="cap-naira" label="Most per bill" hint={naira.trim() === "" ? "No naira limit" : undefined} error={errors.naira}>
            <div className="relative">
              <span className="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-muted-foreground">₦</span>
              <Input id="cap-naira" inputMode="decimal" value={naira} onChange={(event) => setNaira(event.target.value)} className="pl-7" aria-invalid={errors.naira ? true : undefined} aria-describedby={describedBy("cap-naira", naira.trim() === "" ? "hint" : undefined, errors.naira)} />
            </div>
          </FormField>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>What this role may do</CardTitle>
          {locked ? (
            <CardDescription className="flex items-center gap-1.5">
              <Lock className="size-3.5" aria-hidden />
              The Owner role always keeps full control, so its permissions cannot be changed.
            </CardDescription>
          ) : (
            <CardDescription>{permissions.size} of {catalogue.length} selected.</CardDescription>
          )}
        </CardHeader>
        <CardContent className="space-y-5">
          {errors.permissions ? <Alert variant="destructive">{errors.permissions}</Alert> : null}
          {groups.map(([group, items]) => (
            <fieldset key={group} className="space-y-2">
              <legend className="text-sm font-semibold">{group}</legend>
              <div className="grid gap-1.5 sm:grid-cols-2">
                {items.map((permission) => (
                  <label key={permission.name} className="flex items-start gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-muted/50">
                    <input
                      type="checkbox"
                      className="mt-0.5 size-4 accent-primary"
                      checked={permissions.has(permission.name)}
                      disabled={locked}
                      onChange={(event) => toggle(permission.name, event.target.checked)}
                    />
                    <span>{permission.label}</span>
                  </label>
                ))}
              </div>
            </fieldset>
          ))}
        </CardContent>
        <CardFooter className="justify-between">
          <Button type="submit" disabled={save.isPending}>
            {save.isPending ? "Saving..." : role === null ? "Create role" : "Save role"}
          </Button>
          {role !== null && !role.is_system ? (
            <ConfirmDialog
              destructive
              trigger={
                <>
                  <Trash2 aria-hidden />
                  Delete role
                </>
              }
              title={`Delete ${role.name}?`}
              description={role.users_count > 0 ? `${role.users_count} people have this role. Give them another role first.` : "Nobody has this role. Deleting it cannot be undone."}
              confirmLabel="Delete role"
              onConfirm={async () => {
                await remove.mutateAsync(undefined);
                router.replace("/admin/roles/");
              }}
            />
          ) : null}
        </CardFooter>
      </Card>
    </form>
  );
}

function trimPercent(basisPoints: number): string {
  return formatBasisPoints(basisPoints).replace(/%$/, "");
}
