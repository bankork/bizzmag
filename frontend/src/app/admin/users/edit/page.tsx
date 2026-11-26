import type { Metadata } from "next";
import { Suspense } from "react";
import { StaffEditor } from "@/components/admin/staff-editor";
import { AreaShell } from "@/components/shared/area-shell";
import { Skeleton } from "@/components/ui/skeleton";

export const metadata: Metadata = { title: "Staff member" };

export default function EditStaffPage() {
  return (
    <AreaShell area="admin" title="Staff" permissions={["user.manage"]}>
      <Suspense fallback={<Skeleton className="h-64 w-full" />}>
        <StaffEditor />
      </Suspense>
    </AreaShell>
  );
}
