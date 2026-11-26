"use client";

import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { useEffect, type ReactNode } from "react";
import { ShieldAlert } from "lucide-react";
import { buttonVariants } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import { useAuth } from "@/lib/auth/auth-provider";
import { AREAS } from "@/lib/auth/areas";
import { peekSignOutReason } from "@/lib/auth/session-events";

type RequireAuthProps = {
  /** Any one of these permissions opens the page. Omit for any signed-in user. */
  permissions?: readonly string[];
  children: ReactNode;
};

export const PASSWORD_PAGE = "/account/";

/**
 * Shows the page only to a signed-in person with one of the permissions.
 *
 * Children are not rendered otherwise, so a refused page never calls the
 * API. This is for clarity only: the API refuses the same requests itself.
 */
export function RequireAuth({ permissions, children }: RequireAuthProps) {
  const auth = useAuth();
  const router = useRouter();
  const pathname = usePathname();
  const mustChangePassword = auth.me?.user.must_change_password === true && pathname !== PASSWORD_PAGE;

  useEffect(() => {
    if (auth.status === "anonymous") {
      // After choosing to sign out there is nothing to come back to.
      const next = `${window.location.pathname}${window.location.search}`;
      router.replace(peekSignOutReason() === "signed-out" ? "/login/" : `/login/?next=${encodeURIComponent(next)}`);
    } else if (mustChangePassword) {
      router.replace(PASSWORD_PAGE);
    }
  }, [auth.status, mustChangePassword, router]);

  if (auth.status !== "authenticated" || mustChangePassword) {
    return <PageSkeleton />;
  }

  if (permissions && !auth.canAny(permissions)) {
    return <NoAccess homeHref={AREAS[auth.me?.home ?? "staff"].href} />;
  }

  return children;
}

export function PageSkeleton() {
  return (
    <div className="mx-auto w-full max-w-5xl space-y-4 px-4 py-6" aria-busy="true" aria-label="Loading">
      <Skeleton className="h-8 w-48" />
      <Skeleton className="h-32 w-full" />
      <Skeleton className="h-32 w-full" />
    </div>
  );
}

function NoAccess({ homeHref }: { homeHref: string }) {
  return (
    <div className="mx-auto flex w-full max-w-md flex-1 flex-col items-center justify-center gap-4 px-4 py-16 text-center">
      <ShieldAlert className="size-10 text-muted-foreground" aria-hidden />
      <div className="space-y-1">
        <h1 className="text-lg font-semibold">You do not have access to this page</h1>
        <p className="text-sm text-muted-foreground">If you need it for your work, ask the salon owner to change your role.</p>
      </div>
      <Link href={homeHref} className={buttonVariants({ variant: "outline" })}>
        Go to my area
      </Link>
    </div>
  );
}
