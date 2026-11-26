import type { Metadata } from "next";
import { AreaShell } from "@/components/shared/area-shell";
import { ComingSoon } from "@/components/shared/coming-soon";

export const metadata: Metadata = { title: "Front desk" };

export default function DeskHome() {
  return (
    <AreaShell area="desk" title="Visits board">
      <div className="grid gap-4 md:grid-cols-2">
        <ComingSoon phase="Phase 3" items={["Open a visit for a customer or guest", "Live status of every service"]} />
        <ComingSoon phase="Phases 4 and 5" items={["Invoices, discounts and tax", "Payments, receipts and WhatsApp sharing"]} />
      </div>
    </AreaShell>
  );
}
