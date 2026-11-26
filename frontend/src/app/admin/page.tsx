import type { Metadata } from "next";
import { AdminOverview } from "@/components/admin/admin-overview";
import { AreaShell } from "@/components/shared/area-shell";

export const metadata: Metadata = { title: "Owner and admin" };

export default function AdminHome() {
  return (
    <AreaShell area="admin" title="Overview">
      <AdminOverview />
    </AreaShell>
  );
}
