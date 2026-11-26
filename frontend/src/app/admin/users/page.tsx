import type { Metadata } from "next";
import { StaffList } from "@/components/admin/staff-list";
import { AreaShell } from "@/components/shared/area-shell";

export const metadata: Metadata = { title: "Staff" };

export default function StaffPage() {
  return (
    <AreaShell area="admin" title="Staff" permissions={["user.manage"]}>
      <StaffList />
    </AreaShell>
  );
}
