import Link from "next/link";
import { Scissors, Store, LayoutDashboard } from "lucide-react";
import { ApiStatus } from "@/components/shared/api-status";

const areas = [
  {
    href: "/staff/",
    title: "Staff",
    description: "Record the services you perform and see your performance.",
    icon: Scissors,
  },
  {
    href: "/desk/",
    title: "Front desk",
    description: "Open visits, take payments and issue receipts.",
    icon: Store,
  },
  {
    href: "/admin/",
    title: "Owner and admin",
    description: "Dashboard, approvals, staff, settings and reports.",
    icon: LayoutDashboard,
  },
];

export default function Home() {
  return (
    <main className="mx-auto flex w-full max-w-3xl flex-1 flex-col gap-8 px-4 py-10">
      <header className="space-y-2">
        <p className="text-sm font-medium text-primary">Salon Operations</p>
        <h1 className="text-2xl font-semibold tracking-tight">Choose where to work</h1>
        <p className="text-muted-foreground">
          After sign-in (Phase 1), you will go straight to the area your role allows.
        </p>
      </header>

      <ul className="grid gap-3 sm:grid-cols-3">
        {areas.map(({ href, title, description, icon: Icon }) => (
          <li key={href}>
            <Link
              href={href}
              className="flex h-full flex-col gap-2 rounded-xl border bg-card p-4 transition-colors hover:border-primary focus-visible:outline-2 focus-visible:outline-primary"
            >
              <Icon aria-hidden className="size-5 text-primary" />
              <span className="font-medium">{title}</span>
              <span className="text-sm text-muted-foreground">{description}</span>
            </Link>
          </li>
        ))}
      </ul>

      <footer className="flex flex-col gap-3 border-t pt-6">
        <ApiStatus />
        <Link href="/login/" className="text-sm text-primary underline-offset-4 hover:underline">
          Go to sign in
        </Link>
      </footer>
    </main>
  );
}
