import type { Metadata } from "next";
import { SettingsSections } from "@/components/admin/settings-sections";
import { AreaShell } from "@/components/shared/area-shell";

export const metadata: Metadata = { title: "Settings" };

export default function SettingsPage() {
  return (
    <AreaShell area="admin" title="Settings" permissions={["settings.manage"]}>
      <SettingsSections />
    </AreaShell>
  );
}
