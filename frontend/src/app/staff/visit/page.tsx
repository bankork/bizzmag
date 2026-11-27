import type { Metadata } from "next";
import { Suspense } from "react";
import { StaffVisit } from "@/components/staff/staff-visit";
import { AreaShell } from "@/components/shared/area-shell";
import { Skeleton } from "@/components/ui/skeleton";

export const metadata: Metadata = { title: "Visit" };

export default function StaffVisitPage() {
  return (
    <AreaShell area="staff" title="Visit" permissions={["order.view_open"]} mobileFirst>
      <Suspense fallback={<Skeleton className="h-72 w-full" />}>
        <StaffVisit />
      </Suspense>
    </AreaShell>
  );
}
