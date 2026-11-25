import Link from "next/link";
import type { ReactNode } from "react";

type AreaShellProps = {
  title: string;
  subtitle: string;
  children: ReactNode;
  /** The employee area is used one-handed on phones, so it gets a bottom bar. */
  mobileFirst?: boolean;
};

/**
 * Frame shared by the Employee, Front Desk and Admin areas. Navigation is
 * added phase by phase, and is filtered by permission from Phase 1.
 */
export function AreaShell({ title, subtitle, children, mobileFirst = false }: AreaShellProps) {
  return (
    <div className="flex min-h-full flex-1 flex-col">
      <header className="border-b bg-card px-4 py-3 pt-[max(0.75rem,env(safe-area-inset-top))]">
        <div className="mx-auto flex max-w-5xl items-center justify-between gap-4">
          <div>
            <p className="text-xs font-medium uppercase tracking-wide text-muted-foreground">{subtitle}</p>
            <h1 className="text-lg font-semibold">{title}</h1>
          </div>
          <Link href="/" className="text-sm text-muted-foreground underline-offset-4 hover:underline">
            Switch area
          </Link>
        </div>
      </header>

      <main className={`mx-auto w-full flex-1 px-4 py-6 ${mobileFirst ? "max-w-md pb-24" : "max-w-5xl"}`}>{children}</main>

      {mobileFirst ? (
        <nav
          aria-label="Employee"
          className="fixed inset-x-0 bottom-0 border-t bg-card px-4 pt-2 pb-[max(0.5rem,env(safe-area-inset-bottom))]"
        >
          <p className="text-center text-xs text-muted-foreground">Today&apos;s orders, history and performance appear here from Phase 3.</p>
        </nav>
      ) : null}
    </div>
  );
}
