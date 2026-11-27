import type { Metadata } from "next";
import { StaffToday } from "@/components/staff/staff-today";
import { AreaShell } from "@/components/shared/area-shell";

export const metadata: Metadata = { title: "Staff" };

export default function StaffHome() {
  return (
    <AreaShell area="staff" title="Today" mobileFirst>
      <StaffToday />
    </AreaShell>
  );
}
