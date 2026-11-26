import type { Metadata } from "next";
import { Suspense } from "react";
import { ItemEditor } from "@/components/catalogue/item-editor";
import { AreaShell } from "@/components/shared/area-shell";
import { Skeleton } from "@/components/ui/skeleton";

export const metadata: Metadata = { title: "Catalogue item" };

export default function CatalogueItemPage() {
  return (
    <AreaShell area="admin" title="Services and products" permissions={["catalogue.manage"]}>
      <Suspense fallback={<Skeleton className="h-64 w-full" />}>
        <ItemEditor />
      </Suspense>
    </AreaShell>
  );
}
