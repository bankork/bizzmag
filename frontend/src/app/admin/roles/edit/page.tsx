import type { Metadata } from "next";
import { Suspense } from "react";
import { RoleEditor } from "@/components/admin/role-editor";
import { AreaShell } from "@/components/shared/area-shell";
import { Skeleton } from "@/components/ui/skeleton";

export const metadata: Metadata = { title: "Role" };

export default function EditRolePage() {
  return (
    <AreaShell area="admin" title="Roles" permissions={["role.manage"]}>
      <Suspense fallback={<Skeleton className="h-96 w-full" />}>
        <RoleEditor />
      </Suspense>
    </AreaShell>
  );
}
