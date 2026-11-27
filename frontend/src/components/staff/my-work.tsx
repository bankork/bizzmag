"use client";

import { useState } from "react";
import { Alert } from "@/components/ui/alert";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import type { ExecutionStatus } from "@/lib/api/types";
import { useMyWork } from "@/lib/api/visits";
import { formatBasisPoints, formatDateTime } from "@/lib/format";

const STATUS: Record<ExecutionStatus, { label: string; tone: "success" | "warning" | "muted" }> = {
  confirmed: { label: "Recorded", tone: "success" },
  disputed: { label: "Disputed", tone: "warning" },
  superseded: { label: "Replaced", tone: "muted" },
  cancelled: { label: "Cancelled", tone: "muted" },
};

/**
 * Staff › My work: the services I am credited on, newest first, with my
 * share. Shares only, never money (BR-041).
 */
export function MyWork() {
  const [page, setPage] = useState(1);
  const work = useMyWork(page);

  if (work.isPending) {
    return <Skeleton className="h-64 w-full" />;
  }

  if (work.isError) {
    return <Alert variant="destructive">{work.error.message}</Alert>;
  }

  const { pagination } = work.data;

  return (
    <div className="space-y-3">
      {work.data.work.length === 0 ? <p className="rounded-xl border bg-card p-6 text-center text-sm text-muted-foreground">Nothing recorded yet. Services you record or are added to appear here.</p> : null}
      <ul className="divide-y rounded-xl border bg-card" aria-label="My work">
        {work.data.work.map((entry) => (
          <li key={entry.id} className="space-y-1 p-4">
            <div className="flex items-start justify-between gap-2">
              <p className="font-medium">{entry.item_name}</p>
              <Badge variant={STATUS[entry.status].tone}>{STATUS[entry.status].label}</Badge>
            </div>
            <p className="text-sm text-muted-foreground">
              {entry.order.customer_name} · {entry.order.reference}
            </p>
            <p className="text-xs text-muted-foreground tabular-nums">
              {formatDateTime(entry.submitted_at)}
              {entry.my_share_bp !== null ? ` · your share ${formatBasisPoints(entry.my_share_bp)}` : ""}
              {entry.contributors.length > 1 ? ` · with ${entry.contributors.length - 1} ${entry.contributors.length === 2 ? "other" : "others"}` : ""}
            </p>
          </li>
        ))}
      </ul>
      {pagination && pagination.last_page > 1 ? (
        <div className="flex justify-between">
          <Button variant="outline" disabled={page <= 1} onClick={() => setPage(page - 1)}>
            Newer
          </Button>
          <Button variant="outline" disabled={page >= pagination.last_page} onClick={() => setPage(page + 1)}>
            Older
          </Button>
        </div>
      ) : null}
    </div>
  );
}
