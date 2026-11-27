import type { Metadata } from "next";
import { Suspense } from "react";
import { AreaShell } from "@/components/shared/area-shell";
import { VisitDetail } from "@/components/visits/visit-detail";
import { Skeleton } from "@/components/ui/skeleton";

export const metadata: Metadata = { title: "Visit" };

export default function VisitPage() {
  return (
    <AreaShell area="desk" title="Visit" permissions={["order.view_all"]}>
      <Suspense fallback={<Skeleton className="h-96 w-full" />}>
        <VisitDetail />
      </Suspense>
    </AreaShell>
  );
}
