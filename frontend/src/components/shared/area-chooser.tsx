"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { useEffect } from "react";
import { LayoutDashboard, Scissors, Store, type LucideIcon } from "lucide-react";
import { buttonVariants } from "@/components/ui/button";
import type { AreaKey } from "@/lib/api/types";
import { useAuth } from "@/lib/auth/auth-provider";
import { AREAS, areasFor } from "@/lib/auth/areas";

const ICONS: Record<AreaKey, LucideIcon> = { staff: Scissors, desk: Store, admin: LayoutDashboard };

/**
 * The start page. Signed-out visitors are asked to sign in. Someone who can
 * use only one area goes straight to it; someone with several chooses.
 */
export function AreaChooser() {
  const auth = useAuth();
  const router = useRouter();
  const areas = areasFor(auth.permissions);

  useEffect(() => {
    if (auth.status === "authenticated" && areas.length === 1) {
      router.replace(areas[0].href);
    }
  }, [auth.status, areas, router]);

  if (auth.status === "anonymous") {
    return (
      <Link href="/login/" className={buttonVariants({ size: "lg", className: "h-11 w-full sm:w-auto" })}>
        Sign in
      </Link>
    );
  }

  if (auth.status === "loading") {
    return <p className="text-sm text-muted-foreground">Checking your sign-in...</p>;
  }

  return (
    <ul className="grid gap-3 sm:grid-cols-3">
      {areas.map((area) => {
        const Icon = ICONS[area.key];

        return (
          <li key={area.key}>
            <Link
              href={area.href}
              className="flex h-full flex-col gap-2 rounded-xl border bg-card p-4 transition-colors hover:border-primary focus-visible:outline-2 focus-visible:outline-primary"
            >
              <Icon aria-hidden className="size-5 text-primary" />
              <span className="font-medium">{area.title}</span>
              <span className="text-sm text-muted-foreground">{AREAS[area.key].description}</span>
            </Link>
          </li>
        );
      })}
    </ul>
  );
}
