import type { Metadata } from "next";
import { AreaShell } from "@/components/shared/area-shell";
import { NewVisitForm } from "@/components/visits/new-visit-form";

export const metadata: Metadata = { title: "New visit" };

export default function NewVisitPage() {
  return (
    <AreaShell area="desk" title="New visit" permissions={["order.create"]}>
      <NewVisitForm />
    </AreaShell>
  );
}
