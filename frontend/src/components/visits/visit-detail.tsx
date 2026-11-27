"use client";

import Link from "next/link";
import { useSearchParams } from "next/navigation";
import { useState } from "react";
import { ArrowLeft } from "lucide-react";
import { ConfirmDialog } from "@/components/admin/confirm-dialog";
import { FormField } from "@/components/shared/form-field";
import { CustomerSearch } from "@/components/visits/customer-search";
import { ItemPicker } from "@/components/visits/item-picker";
import { PeopleOnWork } from "@/components/visits/people-on-work";
import { ReasonDialog } from "@/components/visits/reason-dialog";
import { ServiceStateBadge } from "@/components/visits/service-state-badge";
import { StaffPicker } from "@/components/visits/staff-picker";
import { useNow } from "@/components/visits/visit-board";
import { CorrectDialog, RecordWorkDialog, SplitDialog } from "@/components/visits/work-actions";
import { Alert } from "@/components/ui/alert";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Skeleton } from "@/components/ui/skeleton";
import { Textarea } from "@/components/ui/textarea";
import { ApiError } from "@/lib/api/errors";
import type { Customer, Order, OrderItem } from "@/lib/api/types";
import { useOrder, useVisitMutation, visitRequests, workRequests } from "@/lib/api/visits";
import { useAuth } from "@/lib/auth/auth-provider";
import { formatDateTime, parseNaira } from "@/lib/format";
import { formatKobo } from "@/lib/money";
import { acceptsWork, isOnVisit, timeOpen, type VisitChoice } from "@/lib/visits";

/**
 * One visit for the desk and supervisors (SRS §8.3): items with prices,
 * who is assigned, what has been recorded and by whom, and the actions each
 * person's permissions allow. The server decides every rule; refusals are
 * shown as they come back.
 */
export function VisitDetail() {
  const params = useSearchParams();
  const id = Number(params.get("id")) || null;
  const order = useOrder(id);
  const now = useNow();

  if (id === null) {
    return <Alert variant="destructive">No visit was chosen.</Alert>;
  }

  if (order.isPending) {
    return <Skeleton className="h-96 w-full" />;
  }

  if (order.isError) {
    return <Alert variant="destructive">{order.error.message}</Alert>;
  }

  const visit = order.data;

  return (
    <div className="space-y-4">
      <Link href="/desk/" className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
        <ArrowLeft className="size-4" aria-hidden />
        Visits board
      </Link>
      <VisitHeader visit={visit} now={now} />
      {visit.status === "cancelled" ? <Alert variant="info">Cancelled: {visit.cancel_reason}</Alert> : null}
      <Card>
        <CardHeader className="flex flex-row items-center justify-between gap-2">
          <CardTitle>Services and products</CardTitle>
          {visit.subtotal_kobo !== undefined ? <span className="text-lg font-semibold tabular-nums">{formatKobo(visit.subtotal_kobo)}</span> : null}
        </CardHeader>
        <CardContent className="space-y-3">
          <ul className="divide-y rounded-lg border" aria-label="Items">
            {visit.items.map((item) => (
              <li key={item.id}>
                <ItemRow item={item} open={visit.status === "open"} />
              </li>
            ))}
          </ul>
          {visit.subtotal_kobo !== undefined ? <p className="text-xs text-muted-foreground">Subtotal of the items being charged. Discounts and tax are added when the bill is prepared.</p> : null}
        </CardContent>
      </Card>
    </div>
  );
}

function VisitHeader({ visit, now }: { visit: Order; now: Date }) {
  const auth = useAuth();
  const isOpen = visit.status === "open";
  const cancel = useVisitMutation(visitRequests.cancelVisit);

  return (
    <div className="flex flex-col gap-3 rounded-xl border bg-card p-4 sm:flex-row sm:items-start sm:justify-between">
      <div className="space-y-1">
        <div className="flex flex-wrap items-center gap-2">
          <h2 className="text-xl font-semibold">{visit.customer_name}</h2>
          <Badge variant={isOpen ? "outline" : "muted"}>{isOpen ? "Open" : visit.status === "cancelled" ? "Cancelled" : "Billed"}</Badge>
        </div>
        <p className="text-sm text-muted-foreground tabular-nums">
          {visit.reference} · opened {formatDateTime(visit.opened_at)} by {visit.opened_by}
          {isOpen ? ` · ${timeOpen(visit.opened_at, now)}` : ""}
        </p>
        {visit.phone_display ? <p className="text-sm tabular-nums">{visit.phone_display}</p> : null}
      </div>
      {isOpen ? (
        <div className="flex flex-wrap gap-2">
          {auth.can("order.item.add") ? <AddItemDialog orderId={visit.id} /> : null}
          {visit.is_guest && auth.can("customer.associate") ? <LinkCustomerDialog orderId={visit.id} /> : null}
          {auth.can("order.cancel") ? (
            <ReasonDialog
              trigger="Cancel visit"
              triggerVariant="destructive"
              triggerSize="default"
              title="Cancel this visit"
              description="For a customer who left. Every item is removed, and any recorded work is cancelled with the same reason."
              confirmLabel="Cancel visit"
              destructive
              onConfirm={(reason) => cancel.mutateAsync({ orderId: visit.id, reason })}
            />
          ) : null}
        </div>
      ) : null}
    </div>
  );
}

function ItemRow({ item, open }: { item: OrderItem; open: boolean }) {
  const auth = useAuth();
  const live = open && isOnVisit(item);
  const myEmployeeId = auth.me?.user.employee_profile?.is_active ? auth.me.user.employee_profile.id : null;
  const cancelItem = useVisitMutation(visitRequests.cancelItem);
  const approve = useVisitMutation(visitRequests.approve);
  const reject = useVisitMutation(visitRequests.reject);
  const dispute = useVisitMutation(workRequests.dispute);
  const dismiss = useVisitMutation(workRequests.dismissDispute);
  const cancelWork = useVisitMutation(workRequests.cancel);
  const work = item.work;
  const canRemove = work ? auth.can("execution.correct") : auth.can("order.item.cancel_unexecuted");

  return (
    <div className="space-y-2 p-3">
      <div className="flex flex-wrap items-start justify-between gap-2">
        <div className="min-w-0 space-y-0.5">
          <p className="font-medium">
            {item.name}
            {item.quantity > 1 ? <span className="text-muted-foreground"> × {item.quantity}</span> : null}
          </p>
          {item.assigned.length > 0 ? <p className="text-xs text-muted-foreground">Assigned: {item.assigned.map((person) => person.name).join(", ")}</p> : null}
          {item.origin === "additional_request" ? <p className="text-xs text-muted-foreground">Extra service asked for by staff{item.request_note ? `: ${item.request_note}` : ""}</p> : null}
          {item.cancel_reason && item.status === "cancelled" ? <p className="text-xs text-muted-foreground">Removed: {item.cancel_reason}</p> : null}
          {item.decision_reason && item.status === "rejected" ? <p className="text-xs text-muted-foreground">Not approved: {item.decision_reason}</p> : null}
        </div>
        <div className="flex items-center gap-2">
          <ServiceStateBadge item={item} />
          {item.line_value_kobo !== undefined ? (
            <span className="text-right text-sm tabular-nums">
              {formatKobo(item.line_value_kobo)}
              {item.override_price_kobo !== null && item.override_price_kobo !== undefined && item.unit_price_kobo !== undefined ? (
                <span className="block text-xs text-muted-foreground">
                  was {formatKobo(item.unit_price_kobo)} each: {item.override_reason}
                </span>
              ) : null}
            </span>
          ) : null}
        </div>
      </div>

      {work ? (
        <div className="rounded-lg bg-muted/40 p-2">
          <PeopleOnWork work={work} />
        </div>
      ) : null}

      {item.history.length > 0 ? (
        <details className="text-xs text-muted-foreground">
          <summary className="cursor-pointer">Earlier records ({item.history.length})</summary>
          <ul className="mt-1 space-y-1">
            {item.history.map((old) => (
              <li key={old.id} className="rounded border border-dashed p-2">
                <span className="font-medium">{old.status === "superseded" ? "Replaced" : "Cancelled"}</span>
                {old.resolution_reason ? `: ${old.resolution_reason}` : ""}
                <PeopleOnWork work={old} compact />
              </li>
            ))}
          </ul>
        </details>
      ) : null}

      {open ? (
        <div className="flex flex-wrap gap-2">
          {item.status === "pending_approval" && auth.can("additional.decide") ? (
            <>
              <ConfirmDialog trigger="Approve" title={`Approve ${item.name}?`} description="It becomes part of the bill at the catalogue price." confirmLabel="Approve" onConfirm={async () => void (await approve.mutateAsync({ itemId: item.id }))} />
              <ReasonDialog trigger="Reject" title={`Reject ${item.name}`} description="It will not be charged. The person who asked is told." confirmLabel="Reject" destructive onConfirm={(reason) => reject.mutateAsync({ itemId: item.id, reason })} />
            </>
          ) : null}
          {live && acceptsWork(item) && !work && auth.can("execution.submit") ? <RecordWorkDialog item={item} myEmployeeId={myEmployeeId} /> : null}
          {live && item.type === "service" && auth.can("order.assign") ? <AssignDialog item={item} /> : null}
          {live && auth.can("order.item.price_override") ? <PriceDialog item={item} /> : null}
          {work?.status === "confirmed" && auth.can("allocation.override") ? <SplitDialog work={work} /> : null}
          {work && auth.can("execution.correct") ? <CorrectDialog work={work} /> : null}
          {work?.status === "confirmed" && auth.can("execution.correct") ? (
            <ReasonDialog trigger="Dispute" title="Dispute this record" description="The record stays, marked disputed, until a different supervisor resolves it." confirmLabel="Dispute" onConfirm={(reason) => dispute.mutateAsync({ executionId: work.id, reason })} />
          ) : null}
          {work?.status === "disputed" && auth.can("execution.correct") ? (
            <ReasonDialog trigger="Dismiss dispute" title="Dismiss the dispute" description="The record stands as it is. Everyone on it, and the person who disputed it, is told." confirmLabel="Dismiss dispute" onConfirm={(reason) => dismiss.mutateAsync({ executionId: work.id, reason })} />
          ) : null}
          {work && auth.can("execution.correct") ? (
            <ReasonDialog trigger="Cancel record" title="Cancel this record" description="The record stays on file as cancelled, and the service waits to be recorded again." confirmLabel="Cancel record" destructive onConfirm={(reason) => cancelWork.mutateAsync({ executionId: work.id, reason })} />
          ) : null}
          {live && canRemove ? (
            <ReasonDialog
              trigger="Remove"
              triggerVariant="ghost"
              title={`Remove ${item.name}`}
              description={work ? "This service has been recorded, so its record is cancelled too." : "Nobody has recorded this yet. It comes off the visit and is not charged."}
              confirmLabel="Remove"
              destructive
              onConfirm={(reason) => cancelItem.mutateAsync({ itemId: item.id, reason })}
            />
          ) : null}
        </div>
      ) : null}
    </div>
  );
}

function AssignDialog({ item }: { item: OrderItem }) {
  const [people, setPeople] = useState<number[]>(item.assigned.map((person) => person.employee_id));
  const assign = useVisitMutation(visitRequests.assign);

  return (
    <ConfirmDialog trigger="Assign" title={`Who will do ${item.name}?`} description="Assigned staff see it first on their phones and are told. Anyone can still record it." confirmLabel="Save" onConfirm={async () => void (await assign.mutateAsync({ itemId: item.id, employeeIds: people }))}>
      <StaffPicker legend="Assigned staff" selected={people} onChange={setPeople} />
    </ConfirmDialog>
  );
}

function PriceDialog({ item }: { item: OrderItem }) {
  const [price, setPrice] = useState("");
  const [reason, setReason] = useState("");
  const override = useVisitMutation(visitRequests.overridePrice);

  return (
    <ConfirmDialog
      trigger="Change price"
      title={`Change the price of ${item.name}`}
      description={`The catalogue price${item.unit_price_kobo !== undefined ? ` (${formatKobo(item.unit_price_kobo)})` : ""} stays on record next to the new one.`}
      confirmLabel="Change price"
      onConfirm={async () => {
        const kobo = parseNaira(price);

        if (kobo === null) {
          throw new ApiError({ status: 422, code: "VALIDATION_FAILED", message: "Enter the new price in naira, for example 35,000." });
        }

        await override.mutateAsync({ itemId: item.id, priceKobo: kobo, reason }).catch((error: unknown) => {
          throw error instanceof ApiError ? new ApiError({ status: error.status, code: error.code, message: Object.values(error.fields)[0]?.[0] ?? error.message }) : error;
        });
      }}
    >
      <FormField id={`price-${item.id}`} label="New price each (₦)">
        <Input id={`price-${item.id}`} inputMode="decimal" value={price} onChange={(event) => setPrice(event.target.value)} />
      </FormField>
      <FormField id={`price-reason-${item.id}`} label="Reason">
        <Textarea id={`price-reason-${item.id}`} rows={2} value={reason} onChange={(event) => setReason(event.target.value)} />
      </FormField>
    </ConfirmDialog>
  );
}

function AddItemDialog({ orderId }: { orderId: number }) {
  const [choice, setChoice] = useState<VisitChoice | null>(null);
  const [quantity, setQuantity] = useState(1);
  const add = useVisitMutation(visitRequests.addItem);

  return (
    <ConfirmDialog
      trigger="Add item"
      title="Add to this visit"
      description="At the catalogue price."
      confirmLabel={choice ? `Add ${choice.name}` : "Add"}
      onConfirm={async () => {
        if (!choice) {
          throw new ApiError({ status: 422, code: "VALIDATION_FAILED", message: "Choose a service or product." });
        }

        await add.mutateAsync({ orderId, item: { catalogue_item_id: choice.id, ...(quantity > 1 ? { quantity } : {}) } });
        setChoice(null);
        setQuantity(1);
      }}
    >
      {choice ? (
        <div className="flex items-end justify-between gap-2 rounded-lg border p-3">
          <p className="font-medium">{choice.name}</p>
          {choice.quantityAllowed ? (
            <FormField id="add-quantity" label="Quantity">
              <Input id="add-quantity" type="number" min={1} max={99} className="w-20" value={quantity} onChange={(event) => setQuantity(Math.max(1, Math.min(99, Number(event.target.value) || 1)))} />
            </FormField>
          ) : null}
        </div>
      ) : (
        <ItemPicker onPick={setChoice} />
      )}
    </ConfirmDialog>
  );
}

function LinkCustomerDialog({ orderId }: { orderId: number }) {
  const [customer, setCustomer] = useState<Customer | null>(null);
  const link = useVisitMutation(visitRequests.linkCustomer);

  return (
    <ConfirmDialog
      trigger="Link to customer"
      title="Link this guest visit to a customer"
      description="The visit moves to the customer's history. This is recorded."
      confirmLabel={customer ? `Link to ${customer.name}` : "Link"}
      onConfirm={async () => {
        if (!customer) {
          throw new ApiError({ status: 422, code: "VALIDATION_FAILED", message: "Choose a customer." });
        }

        await link.mutateAsync({ orderId, customerId: customer.id });
      }}
    >
      {customer ? <p className="rounded-lg border p-3 font-medium">{customer.name}</p> : <CustomerSearch onPick={setCustomer} />}
    </ConfirmDialog>
  );
}

