import type { Metadata } from "next";
import { AreaShell } from "@/components/shared/area-shell";
import { ApprovalsQueue } from "@/components/visits/approvals-queue";

export const metadata: Metadata = { title: "Approvals" };

export default function ApprovalsPage() {
  return (
    <AreaShell area="admin" title="Approvals" permissions={["additional.decide"]}>
      <ApprovalsQueue />
    </AreaShell>
  );
}
