import type { Metadata } from "next";
import { MyWork } from "@/components/staff/my-work";
import { AreaShell } from "@/components/shared/area-shell";

export const metadata: Metadata = { title: "My work" };

export default function MyWorkPage() {
  return (
    <AreaShell area="staff" title="My work" permissions={["execution.submit", "execution.join"]} mobileFirst>
      <MyWork />
    </AreaShell>
  );
}
