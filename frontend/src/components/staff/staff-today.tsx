"use client";

import Link from "next/link";
import { ChevronRight } from "lucide-react";
import { useNow } from "@/components/visits/visit-board";
import { Alert } from "@/components/ui/alert";
import { Badge } from "@/components/ui/badge";
import { Skeleton } from "@/components/ui/skeleton";
import { useOrders } from "@/lib/api/visits";
import { acceptsWork, forStaff, timeOpen } from "@/lib/visits";

/**
 * Staff › Today (UI-010): the open visits, with the ones where I am
 * assigned to waiting work first. Tap one to record work. No prices, no
 * phone numbers: the API does not send them.
 */
export function StaffToday() {
  const orders = useOrders({ status: "open" });
  const now = useNow();

  if (orders.isPending) {
    return (
      <div className="space-y-3">
        {Array.from({ length: 3 }, (_, index) => (
          <Skeleton key={index} className="h-24 w-full" />
        ))}
      </div>
    );
  }

  if (orders.isError) {
    return <Alert variant="destructive">{orders.error.message}</Alert>;
  }

  const visits = forStaff(orders.data.orders);

  if (visits.length === 0) {
    return <p className="rounded-xl border bg-card p-6 text-center text-sm text-muted-foreground">No open visits right now. They appear here as soon as the desk opens one.</p>;
  }

  return (
    <ul className="space-y-3" aria-label="Open visits">
      {visits.map((visit) => {
        const mine = visit.items.filter((item) => item.assigned_to_me && acceptsWork(item) && item.work === null);
        const services = visit.items.filter((item) => item.type === "service" && acceptsWork(item));

        return (
          <li key={visit.id}>
            <Link href={`/staff/visit/?id=${visit.id}`} className="flex min-h-16 items-center gap-3 rounded-xl border bg-card p-4 active:bg-muted">
              <div className="min-w-0 flex-1 space-y-1">
                <div className="flex items-center gap-2">
                  <p className="truncate text-base font-medium">{visit.customer_name}</p>
                  {mine.length > 0 ? <Badge>For you</Badge> : null}
                </div>
                <p className="truncate text-sm text-muted-foreground">{services.map((item) => item.name).join(", ") || "No services"}</p>
                <p className="text-xs text-muted-foreground tabular-nums">
                  {visit.reference} · {timeOpen(visit.opened_at, now)} · {visit.progress.recorded} of {visit.progress.services} done
                </p>
              </div>
              <ChevronRight className="size-5 text-muted-foreground" aria-hidden />
            </Link>
          </li>
        );
      })}
    </ul>
  );
}
