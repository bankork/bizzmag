import type { Metadata } from "next";
import { CatalogueList } from "@/components/catalogue/catalogue-list";
import { AreaShell } from "@/components/shared/area-shell";

export const metadata: Metadata = { title: "Catalogue" };

export default function CataloguePage() {
  return (
    <AreaShell area="admin" title="Services and products" permissions={["catalogue.manage"]}>
      <CatalogueList />
    </AreaShell>
  );
}
