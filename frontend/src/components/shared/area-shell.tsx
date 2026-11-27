"use client";

import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import type { ReactNode } from "react";
import { LogOut, UserRound } from "lucide-react";
import { cn } from "cn";
import { RequireAuth } from "@/components/auth/require-auth";
import { NotificationBell } from "@/components/shared/notification-bell";
import { Button } from "@/components/ui/button";
import type { AreaKey } from "@/lib/api/types";
import { useAuth } from "@/lib/auth/auth-provider";
import { AREAS, areasFor, navFor } from "@/lib/auth/areas";

type AreaShellProps = {
  area: AreaKey;
  title: string;
  /** Any one of these permissions opens the page. Defaults to the area's permissions. */
  permissions?: readonly string[];
  /** The employee area is used one-handed on phones, so it gets a bottom bar. */
  mobileFirst?: boolean;
  actions?: ReactNode;
  children: ReactNode;
};

/**
 * Frame shared by the Staff, Front desk and Admin areas: the salon name,
 * navigation filtered by permission, and the account menu.
 */
export function AreaShell({ area, title, permissions, mobileFirst = false, actions, children }: AreaShellProps) {
  return (
    <RequireAuth permissions={permissions ?? AREAS[area].permissions}>
      <Frame area={area} title={title} mobileFirst={mobileFirst} actions={actions}>
        {children}
      </Frame>
    </RequireAuth>
  );
}

function Frame({ area, title, mobileFirst, actions, children }: Omit<AreaShellProps, "permissions">) {
  const auth = useAuth();
  const pathname = usePathname();
  const router = useRouter();
  const permissions = auth.permissions;
  const nav = navFor(area, permissions);
  const otherAreas = areasFor(permissions).filter((candidate) => candidate.key !== area);

  async function signOut() {
    await auth.signOut();
    router.replace("/login/");
  }

  const navLinks = nav.map((item) => {
    const active = item.href === pathname || (item.href !== AREAS[area].href && pathname.startsWith(item.href));

    return (
      <Link
        key={item.href}
        href={item.href}
        aria-current={active ? "page" : undefined}
        className={cn(
          "rounded-md px-3 py-1.5 text-sm font-medium whitespace-nowrap transition-colors",
          active ? "bg-primary text-primary-foreground" : "text-muted-foreground hover:bg-muted hover:text-foreground",
        )}
      >
        {item.label}
      </Link>
    );
  });

  return (
    <div className="flex min-h-full flex-1 flex-col">
      <header className="border-b bg-card px-4 pt-[max(0.75rem,env(safe-area-inset-top))] pb-3">
        <div className="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-3">
          <div className="min-w-0">
            <p className="truncate text-xs font-medium uppercase tracking-wide text-muted-foreground">
              {auth.me?.business.name} · {AREAS[area].title}
            </p>
            <h1 className="text-lg font-semibold">{title}</h1>
          </div>
          <div className="flex items-center gap-1">
            {otherAreas.map((other) => (
              <Link key={other.key} href={other.href} className="hidden rounded-md px-2 py-1.5 text-sm text-muted-foreground hover:bg-muted sm:inline-block">
                {other.title}
              </Link>
            ))}
            <NotificationBell area={area} />
            <Link href="/account/" className="inline-flex items-center gap-1.5 rounded-md px-2 py-1.5 text-sm hover:bg-muted" aria-label="My account">
              <UserRound className="size-4" aria-hidden />
              <span className="hidden max-w-32 truncate sm:inline">{auth.me?.user.name}</span>
            </Link>
            <Button variant="ghost" size="sm" onClick={signOut}>
              <LogOut aria-hidden />
              Sign out
            </Button>
          </div>
        </div>
        {!mobileFirst && nav.length > 1 ? (
          <nav aria-label={AREAS[area].title} className="mx-auto mt-3 flex max-w-5xl gap-1 overflow-x-auto">
            {navLinks}
          </nav>
        ) : null}
      </header>

      <main className={cn("mx-auto w-full flex-1 px-4 py-6", mobileFirst ? "max-w-md pb-24" : "max-w-5xl")}>
        {actions ? <div className="mb-4 flex flex-wrap justify-end gap-2">{actions}</div> : null}
        {children}
      </main>

      {mobileFirst ? (
        <nav aria-label={AREAS[area].title} className="fixed inset-x-0 bottom-0 flex justify-center gap-1 border-t bg-card px-4 pt-2 pb-[max(0.5rem,env(safe-area-inset-bottom))]">
          {navLinks}
        </nav>
      ) : null}
    </div>
  );
}
