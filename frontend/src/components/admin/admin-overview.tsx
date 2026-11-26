"use client";

import Link from "next/link";
import { ArrowRight, Settings, ShieldCheck, Users } from "lucide-react";
import { ComingSoon } from "@/components/shared/coming-soon";
import { useAuth } from "@/lib/auth/auth-provider";

const SHORTCUTS = [
  { href: "/admin/users/", title: "Staff", description: "Add staff, assign roles, reset passwords, disable accounts.", permission: "user.manage", icon: Users },
  { href: "/admin/roles/", title: "Roles", description: "What each role may do, and discount limits.", permission: "role.manage", icon: ShieldCheck },
  { href: "/admin/settings/", title: "Settings", description: "Salon details, numbering, time limits and tax.", permission: "settings.manage", icon: Settings },
] as const;

export function AdminOverview() {
  const auth = useAuth();
  const shortcuts = SHORTCUTS.filter((shortcut) => auth.can(shortcut.permission));

  return (
    <div className="space-y-6">
      {shortcuts.length > 0 ? (
        <ul className="grid gap-3 sm:grid-cols-3">
          {shortcuts.map(({ href, title, description, icon: Icon }) => (
            <li key={href}>
              <Link href={href} className="group flex h-full flex-col gap-2 rounded-xl border bg-card p-4 transition-colors hover:border-primary">
                <Icon className="size-5 text-primary" aria-hidden />
                <span className="flex items-center gap-1 font-medium">
                  {title}
                  <ArrowRight className="size-4 opacity-0 transition-opacity group-hover:opacity-100" aria-hidden />
                </span>
                <span className="text-sm text-muted-foreground">{description}</span>
              </Link>
            </li>
          ))}
        </ul>
      ) : null}
      <ComingSoon phase="Phase 6" items={["Billed, collected and outstanding revenue", "Exceptions and cashier closings"]} />
    </div>
  );
}
