import type { Metadata } from "next";
import { AreaShell } from "@/components/shared/area-shell";
import { VisitBoard } from "@/components/visits/visit-board";

export const metadata: Metadata = { title: "Front desk" };

export default function DeskHome() {
  return (
    <AreaShell area="desk" title="Visits board" permissions={["order.view_all", "order.create"]}>
      <VisitBoard />
    </AreaShell>
  );
}
