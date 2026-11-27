"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { Plus, Search } from "lucide-react";
import { Alert } from "@/components/ui/alert";
import { Badge } from "@/components/ui/badge";
import { Button, buttonVariants } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { NativeSelect } from "@/components/ui/native-select";
import { Skeleton } from "@/components/ui/skeleton";
import type { Order } from "@/lib/api/types";
import { useOrders, type OrderFilters } from "@/lib/api/visits";
import { useAuth } from "@/lib/auth/auth-provider";
import { formatKobo } from "@/lib/money";
import { timeOpen } from "@/lib/visits";

/**
 * Desk › Visits board: every open visit, oldest first, with how long it has
 * been open and where each service stands (FR-ORD-015, SRS §8.3). It
 * refreshes by itself.
 */
export function VisitBoard() {
  const auth = useAuth();
  const [search, setSearch] = useState("");
  const [filters, setFilters] = useState<OrderFilters>({ status: "open", q: "", page: 1 });
  const orders = useOrders(filters);
  const now = useNow();

  return (
    <div className="space-y-4">
      <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <form
          role="search"
          className="flex flex-1 flex-wrap gap-2"
          onSubmit={(event) => {
            event.preventDefault();
            setFilters((current) => ({ ...current, q: search.trim(), page: 1 }));
          }}
        >
          <label htmlFor="visit-search" className="sr-only">
            Search visits
          </label>
          <Input id="visit-search" placeholder="Reference or name" value={search} onChange={(event) => setSearch(event.target.value)} className="sm:max-w-xs" />
          <Button type="submit" variant="outline" aria-label="Search">
            <Search aria-hidden />
          </Button>
          {auth.can("order.view_all") ? (
            <>
              <label htmlFor="visit-status" className="sr-only">
                Show
              </label>
              <NativeSelect id="visit-status" value={filters.status} onChange={(event) => setFilters((current) => ({ ...current, status: event.target.value as OrderFilters["status"], page: 1 }))} className="w-36">
                <option value="open">Open</option>
                <option value="cancelled">Cancelled</option>
                <option value="all">All visits</option>
              </NativeSelect>
            </>
          ) : null}
        </form>
        {auth.can("order.create") ? (
          <Link href="/desk/visits/new/" className={buttonVariants()}>
            <Plus aria-hidden />
            New visit
          </Link>
        ) : null}
      </div>

      {orders.isError ? <Alert variant="destructive">{orders.error.message}</Alert> : null}

      <ul className="grid gap-3 md:grid-cols-2" aria-label="Visits" aria-busy={orders.isFetching}>
        {orders.isPending ? Array.from({ length: 4 }, (_, index) => <Skeleton key={index} className="h-28 w-full" />) : null}
        {orders.data?.orders.length === 0 ? (
          <li className="rounded-xl border bg-card p-6 text-center text-sm text-muted-foreground md:col-span-2">
            {filters.q ? "No visit matches this search." : filters.status === "open" ? "No open visits. Start one with New visit." : "No visits here."}
          </li>
        ) : null}
        {orders.data?.orders.map((order) => (
          <li key={order.id}>
            <VisitCard order={order} now={now} />
          </li>
        ))}
      </ul>
    </div>
  );
}

function VisitCard({ order, now }: { order: Order; now: Date }) {
  const { services, recorded, disputed, waiting, pending_approval: pending } = order.progress;

  return (
    <Link href={`/desk/visit/?id=${order.id}`} className="block space-y-2 rounded-xl border bg-card p-4 hover:border-primary/50">
      <div className="flex items-start justify-between gap-2">
        <div className="min-w-0">
          <p className="truncate font-medium">{order.customer_name}</p>
          <p className="text-xs text-muted-foreground tabular-nums">{order.reference}</p>
        </div>
        <span className="text-sm text-muted-foreground tabular-nums">{order.status === "open" ? timeOpen(order.opened_at, now) : order.status === "cancelled" ? "Cancelled" : "Billed"}</span>
      </div>
      <p className="line-clamp-2 text-sm">{order.items.filter((item) => item.status !== "cancelled" && item.status !== "rejected").map((item) => item.name).join(", ")}</p>
      <div className="flex flex-wrap items-center gap-1.5">
        {services > 0 ? <Badge variant={recorded === services ? "success" : "outline"}>{recorded} of {services} done</Badge> : null}
        {waiting > 0 ? <Badge variant="outline">{waiting} waiting</Badge> : null}
        {disputed > 0 ? <Badge variant="warning">{disputed} disputed</Badge> : null}
        {pending > 0 ? <Badge variant="warning">{pending} waiting for approval</Badge> : null}
        {order.subtotal_kobo !== undefined ? <span className="ml-auto text-sm font-medium tabular-nums">{formatKobo(order.subtotal_kobo)}</span> : null}
      </div>
    </Link>
  );
}

/** The current time, ticking once a minute, for "open for 25 min". */
export function useNow(): Date {
  const [now, setNow] = useState(() => new Date());

  useEffect(() => {
    const timer = window.setInterval(() => setNow(new Date()), 60_000);

    return () => window.clearInterval(timer);
  }, []);

  return now;
}
