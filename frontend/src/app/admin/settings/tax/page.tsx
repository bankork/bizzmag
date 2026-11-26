import type { Metadata } from "next";
import { TaxSettings } from "@/components/admin/tax-settings";
import { AreaShell } from "@/components/shared/area-shell";

export const metadata: Metadata = { title: "Tax" };

export default function TaxPage() {
  return (
    <AreaShell area="admin" title="Tax" permissions={["settings.manage"]}>
      <TaxSettings />
    </AreaShell>
  );
}
