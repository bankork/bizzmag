import type { Metadata } from "next";
import { AreaShell } from "@/components/shared/area-shell";
import { ComingSoon } from "@/components/shared/coming-soon";

export const metadata: Metadata = { title: "Staff" };

export default function StaffHome() {
  return (
    <AreaShell area="staff" title="Today" mobileFirst>
      <div className="space-y-4">
        <ComingSoon phase="Phase 3" items={["See the services assigned to you", "Record work done, alone or with a colleague"]} />
        <ComingSoon phase="Phase 7" items={["Your performance and bonus progress"]} />
      </div>
    </AreaShell>
  );
}
