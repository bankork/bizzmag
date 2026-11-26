import type { Metadata } from "next";
import { RoleList } from "@/components/admin/role-list";
import { AreaShell } from "@/components/shared/area-shell";

export const metadata: Metadata = { title: "Roles" };

export default function RolesPage() {
  return (
    <AreaShell area="admin" title="Roles" permissions={["role.manage"]}>
      <RoleList />
    </AreaShell>
  );
}
