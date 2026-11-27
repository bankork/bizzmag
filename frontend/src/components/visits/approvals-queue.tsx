"use client";

import Link from "next/link";
import { ConfirmDialog } from "@/components/admin/confirm-dialog";
import { ReasonDialog } from "@/components/visits/reason-dialog";
import { Alert } from "@/components/ui/alert";
import { Skeleton } from "@/components/ui/skeleton";
import { useApprovals, useVisitMutation, visitRequests } from "@/lib/api/visits";
import { formatDateTime } from "@/lib/format";
import { formatKobo } from "@/lib/money";

/**
 * Admin › Approvals (FR-ADD-009): extra services staff asked for, oldest
 * first. Nobody can approve their own request; the owner may, and it is
 * flagged in the change log (BR-040).
 */
export function ApprovalsQueue() {
  const approvals = useApprovals();
  const approve = useVisitMutation(visitRequests.approve);
  const reject = useVisitMutation(visitRequests.reject);

  if (approvals.isPending) {
    return <Skeleton className="h-48 w-full" />;
  }

  if (approvals.isError) {
    return <Alert variant="destructive">{approvals.error.message}</Alert>;
  }

  if (approvals.data.length === 0) {
    return <p className="rounded-xl border bg-card p-6 text-center text-sm text-muted-foreground">Nothing waiting for approval.</p>;
  }

  return (
    <ul className="divide-y rounded-xl border bg-card" aria-label="Waiting for approval">
      {approvals.data.map((request) => (
        <li key={request.order_item_id} className="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
          <div className="min-w-0 space-y-0.5">
            <p className="font-medium">
              {request.name}
              {request.quantity > 1 ? ` × ${request.quantity}` : ""}
              {request.line_value_kobo !== undefined ? <span className="ml-2 font-normal text-muted-foreground tabular-nums">{formatKobo(request.line_value_kobo)}</span> : null}
            </p>
            <p className="text-sm text-muted-foreground">
              <Link href={`/desk/visit/?id=${request.order.id}`} className="underline-offset-4 hover:underline">
                {request.order.customer_name}, {request.order.reference}
              </Link>{" "}
              · asked by {request.requested_by.name ?? "staff"}, {formatDateTime(request.requested_at)}
            </p>
            {request.note ? <p className="text-sm">&ldquo;{request.note}&rdquo;</p> : null}
          </div>
          <div className="flex gap-2">
            <ConfirmDialog trigger="Approve" title={`Approve ${request.name}?`} description="It becomes part of the bill at the catalogue price, and the person who asked is told." confirmLabel="Approve" onConfirm={async () => void (await approve.mutateAsync({ itemId: request.order_item_id }))} />
            <ReasonDialog trigger="Reject" triggerSize="default" title={`Reject ${request.name}`} description="It will not be charged. The person who asked is told." confirmLabel="Reject" destructive onConfirm={(reason) => reject.mutateAsync({ itemId: request.order_item_id, reason })} />
          </div>
        </li>
      ))}
    </ul>
  );
}
