"use client";

import Link from "next/link";
import { Plus } from "lucide-react";
import { Alert } from "@/components/ui/alert";
import { Badge } from "@/components/ui/badge";
import { buttonVariants } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import { useRoles } from "@/lib/api/admin";
import { describeDiscountCap } from "@/lib/discount-cap";

export function RoleList() {
  const roles = useRoles();

  return (
    <div className="space-y-4">
      <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <p className="text-sm text-muted-foreground">What each role may do. People can hold more than one role.</p>
        <Link href="/admin/roles/edit/" className={buttonVariants()}>
          <Plus aria-hidden />
          New role
        </Link>
      </div>

      {roles.isError ? <Alert variant="destructive">{roles.error.message}</Alert> : null}
      {roles.isPending ? <Skeleton className="h-48 w-full" /> : null}

      <ul className="grid gap-3 sm:grid-cols-2">
        {roles.data?.map((role) => (
          <li key={role.id}>
            <Link href={`/admin/roles/edit/?id=${role.id}`} className="flex h-full flex-col gap-2 rounded-xl border bg-card p-4 hover:border-primary">
              <span className="flex flex-wrap items-center gap-2">
                <span className="font-medium">{role.name}</span>
                {role.is_system ? <Badge variant="muted">Default</Badge> : <Badge variant="outline">Custom</Badge>}
              </span>
              {role.description ? <span className="text-sm text-muted-foreground">{role.description}</span> : null}
              <span className="mt-auto flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted-foreground">
                <span>
                  {role.users_count} {role.users_count === 1 ? "person" : "people"}
                </span>
                <span>{role.permissions.length} permissions</span>
                <span>Discounts: {describeDiscountCap(role.discount_cap_bp, role.discount_cap_kobo)}</span>
              </span>
            </Link>
          </li>
        ))}
      </ul>
    </div>
  );
}
