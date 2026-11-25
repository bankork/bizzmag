import type { Metadata } from "next";
import { AreaShell } from "@/components/shared/area-shell";
import { ComingSoon } from "@/components/shared/coming-soon";

export const metadata: Metadata = { title: "Owner and admin" };

export default function AdminHome() {
  return (
    <AreaShell title="Dashboard" subtitle="Owner and admin">
      <div className="grid gap-4 md:grid-cols-2">
        <ComingSoon phase="Phase 1" items={["Staff accounts and roles", "Business and tax settings"]} />
        <ComingSoon phase="Phase 6" items={["Billed, collected and outstanding revenue", "Exceptions and cashier closings"]} />
      </div>
    </AreaShell>
  );
}
