import type { Metadata } from "next";
import { Suspense } from "react";
import { CustomerEditor } from "@/components/customers/customer-editor";
import { AreaShell } from "@/components/shared/area-shell";
import { Skeleton } from "@/components/ui/skeleton";

export const metadata: Metadata = { title: "Customer" };

export default function CustomerPage() {
  return (
    <AreaShell area="admin" title="Customers" permissions={["customer.view"]}>
      <Suspense fallback={<Skeleton className="h-64 w-full" />}>
        <CustomerEditor basePath="/admin/customers/" />
      </Suspense>
    </AreaShell>
  );
}
