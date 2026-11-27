"use client";

import Link from "next/link";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import { useCustomerVisits } from "@/lib/api/visits";
import { useAuth } from "@/lib/auth/auth-provider";
import { formatDateTime } from "@/lib/format";
import { formatKobo } from "@/lib/money";

/**
 * A customer's visits, newest first (FR-CUS-010). Bills and payments join
 * this list in Phases 4 and 5.
 */
export function CustomerVisits({ customerId }: { customerId: number }) {
  const auth = useAuth();
  const allowed = auth.can("order.view_all");
  const visits = useCustomerVisits(customerId, allowed);

  return (
    <Card>
      <CardHeader>
        <CardTitle>Visits</CardTitle>
        <CardDescription>Bills and payments appear here from Phases 4 and 5.</CardDescription>
      </CardHeader>
      <CardContent>
        {!allowed ? <p className="text-sm text-muted-foreground">You do not have access to visit history.</p> : null}
        {allowed && visits.isPending ? <Skeleton className="h-16 w-full" /> : null}
        {visits.data?.length === 0 ? <p className="text-sm text-muted-foreground">No visits yet.</p> : null}
        {visits.data && visits.data.length > 0 ? (
          <ul className="divide-y rounded-lg border" aria-label="Visits">
            {visits.data.map((visit) => (
              <li key={visit.id}>
                <Link href={`/desk/visit/?id=${visit.id}`} className="flex flex-col gap-0.5 p-3 hover:bg-muted/50 sm:flex-row sm:items-center sm:justify-between">
                  <span className="min-w-0">
                    <span className="block text-sm font-medium tabular-nums">
                      {visit.reference} · {formatDateTime(visit.opened_at)}
                    </span>
                    <span className="block truncate text-xs text-muted-foreground">{visit.items.join(", ")}</span>
                  </span>
                  <span className="flex items-center gap-2">
                    {visit.status !== "open" ? <Badge variant="muted">{visit.status === "cancelled" ? "Cancelled" : "Billed"}</Badge> : <Badge variant="outline">Open</Badge>}
                    <span className="text-sm tabular-nums">{formatKobo(visit.subtotal_kobo)}</span>
                  </span>
                </Link>
              </li>
            ))}
          </ul>
        ) : null}
      </CardContent>
    </Card>
  );
}
