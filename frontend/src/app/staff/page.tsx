import type { Metadata } from "next";
import { AreaShell } from "@/components/shared/area-shell";
import { ComingSoon } from "@/components/shared/coming-soon";

export const metadata: Metadata = { title: "Staff" };

export default function StaffHome() {
  return (
    <AreaShell title="My work today" subtitle="Staff" mobileFirst>
      <ComingSoon
        phase="Phase 3"
        items={[
          "Today's open visits, with your assignments first",
          "Record a service in three taps",
          "Add collaborators or join a shared service",
          "Request an extra service for a customer",
        ]}
      />
    </AreaShell>
  );
}
