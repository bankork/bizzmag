"use client";

import Link from "next/link";
import { useSearchParams } from "next/navigation";
import { useQueryClient } from "@tanstack/react-query";
import { useState } from "react";
import { ArrowLeft, Check, ChevronDown, Plus, Users } from "lucide-react";
import { cn } from "cn";
import { ConfirmDialog } from "@/components/admin/confirm-dialog";
import { FormField } from "@/components/shared/form-field";
import { ItemPicker } from "@/components/visits/item-picker";
import { PeopleOnWork } from "@/components/visits/people-on-work";
import { ReasonDialog } from "@/components/visits/reason-dialog";
import { ServiceStateBadge } from "@/components/visits/service-state-badge";
import { StaffPicker } from "@/components/visits/staff-picker";
import { Alert } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import { Textarea } from "@/components/ui/textarea";
import { ApiError } from "@/lib/api/errors";
import { useIdempotencyKey } from "@/lib/api/idempotency";
import type { Execution, Order, OrderItem } from "@/lib/api/types";
import { useOrder, useVisitMutation, visitKeys, visitRequests, workRequests } from "@/lib/api/visits";
import { useAuth } from "@/lib/auth/auth-provider";
import { acceptsWork, type VisitChoice } from "@/lib/visits";

/**
 * Staff › one visit, on a phone (UI-010). Recording takes three taps from
 * the Today list: open the visit, tap the service, tap "I did this". Adding
 * colleagues is one more step. If someone recorded it first, the screen
 * offers Join instead.
 */
export function StaffVisit() {
  const params = useSearchParams();
  const id = Number(params.get("id")) || null;
  const order = useOrder(id);

  if (id === null) {
    return <Alert variant="destructive">No visit was chosen.</Alert>;
  }

  if (order.isPending) {
    return <Skeleton className="h-72 w-full" />;
  }

  if (order.isError) {
    return (
      <div className="space-y-3">
        <BackLink />
        <Alert variant="destructive">{order.error instanceof ApiError && order.error.code === "NOT_FOUND" ? "This visit is closed or no longer exists." : order.error.message}</Alert>
      </div>
    );
  }

  const visit = order.data;
  const services = visit.items.filter((item) => item.type === "service" && item.status !== "cancelled" && item.status !== "rejected");

  return (
    <div className="space-y-4">
      <BackLink />
      <div>
        <h2 className="text-xl font-semibold">{visit.customer_name}</h2>
        <p className="text-sm text-muted-foreground tabular-nums">{visit.reference}</p>
      </div>
      <ul className="space-y-3" aria-label="Services">
        {services.map((item) => (
          <li key={item.id}>
            <ServiceCard item={item} />
          </li>
        ))}
      </ul>
      <RequestExtra visit={visit} />
    </div>
  );
}

function BackLink() {
  return (
    <Link href="/staff/" className="inline-flex min-h-11 items-center gap-1 text-sm text-muted-foreground">
      <ArrowLeft className="size-4" aria-hidden />
      Today
    </Link>
  );
}

function ServiceCard({ item }: { item: OrderItem }) {
  const auth = useAuth();
  const [expanded, setExpanded] = useState(false);
  const [withOthers, setWithOthers] = useState(false);
  const [colleagues, setColleagues] = useState<number[]>([]);
  const [message, setMessage] = useState<{ tone: "success" | "destructive" | "info"; text: string } | null>(null);
  const queryClient = useQueryClient();
  const { key, reset } = useIdempotencyKey();
  const submit = useVisitMutation(workRequests.submit);
  const join = useVisitMutation(workRequests.join);
  const withdraw = useVisitMutation(visitRequests.withdraw);
  const dispute = useVisitMutation(workRequests.dispute);
  const me = auth.me?.user;
  const myEmployeeId = me?.employee_profile?.is_active ? me.employee_profile.id : null;
  const work = item.work;
  const mine = work?.submitted_by.id === me?.id;
  const canRecord = acceptsWork(item) && !work && auth.can("execution.submit") && myEmployeeId !== null;

  async function record() {
    setMessage(null);

    try {
      await submit.mutateAsync({ itemId: item.id, colleagueIds: colleagues, key });
      reset();
      setWithOthers(false);
      setColleagues([]);
      setMessage({ tone: "success", text: "Recorded. Thank you." });
    } catch (error) {
      if (error instanceof ApiError && error.code === "EXECUTION_EXISTS") {
        reset();
        void queryClient.invalidateQueries({ queryKey: visitKeys.all });
        setMessage({ tone: "info", text: error.data.can_join ? "Someone has just recorded this. You can join them below." : "Someone has just recorded this, and you are already on it." });
      } else {
        setMessage({ tone: "destructive", text: error instanceof ApiError ? error.message : "Not saved. Check your connection and tap again; it will only be recorded once." });
      }
    }
  }

  return (
    <div className={cn("rounded-xl border bg-card", item.assigned_to_me && !work ? "border-primary/60" : undefined)}>
      <button type="button" className="flex min-h-16 w-full items-center gap-3 p-4 text-left" aria-expanded={expanded} onClick={() => setExpanded(!expanded)}>
        <div className="min-w-0 flex-1 space-y-1">
          <p className="text-base font-medium">
            {item.name}
            {item.quantity > 1 ? ` × ${item.quantity}` : ""}
          </p>
          <div className="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
            <ServiceStateBadge item={item} />
            {item.assigned_to_me ? <span>Assigned to you</span> : item.assigned.length > 0 ? <span>For {item.assigned.map((person) => person.name).join(", ")}</span> : null}
          </div>
        </div>
        <ChevronDown className={cn("size-5 text-muted-foreground transition-transform", expanded && "rotate-180")} aria-hidden />
      </button>

      {expanded ? (
        <div className="space-y-3 border-t p-4">
          {message ? <Alert variant={message.tone === "destructive" ? "destructive" : message.tone === "success" ? "success" : "info"}>{message.text}</Alert> : null}
          {work ? <PeopleOnWork work={work} /> : null}

          {canRecord ? (
            <>
              {withOthers ? <StaffPicker legend="Who worked with you?" selected={colleagues} onChange={setColleagues} hide={myEmployeeId ? [myEmployeeId] : []} /> : null}
              <Button type="button" size="lg" className="h-12 w-full text-base" disabled={submit.isPending} onClick={record}>
                <Check aria-hidden />
                {submit.isPending ? "Saving..." : colleagues.length > 0 ? `We did this (${colleagues.length + 1} people)` : "I did this"}
              </Button>
              {withOthers ? null : (
                <Button type="button" variant="outline" size="lg" className="h-11 w-full" onClick={() => setWithOthers(true)}>
                  <Users aria-hidden />
                  With colleagues
                </Button>
              )}
            </>
          ) : null}

          {work?.status === "confirmed" && !work.includes_me && auth.can("execution.join") ? (
            <Button
              type="button"
              size="lg"
              className="h-12 w-full text-base"
              disabled={join.isPending}
              onClick={async () => {
                setMessage(null);

                try {
                  await join.mutateAsync({ executionId: work.id });
                  setMessage({ tone: "success", text: "You are on this service now." });
                } catch (error) {
                  setMessage({ tone: "destructive", text: error instanceof ApiError ? error.message : "Something went wrong. Please try again." });
                }
              }}
            >
              <Plus aria-hidden />
              {join.isPending ? "Joining..." : "Join"}
            </Button>
          ) : null}

          {work && mine && work.status === "confirmed" ? <ChangePeople work={work} myEmployeeId={myEmployeeId} /> : null}

          {work?.status === "confirmed" && work.includes_me && auth.can("execution.dispute") ? (
            <ReasonDialog
              trigger="Something is wrong"
              triggerSize="default"
              title="Dispute this record"
              description="Say what is wrong, for example who really did it. A supervisor will look at it."
              confirmLabel="Send dispute"
              onConfirm={(reason) => dispute.mutateAsync({ executionId: work.id, reason })}
            />
          ) : null}

          {item.status === "pending_approval" && item.requested_by === me?.id && !work ? (
            <Button type="button" variant="ghost" onClick={() => withdraw.mutate({ itemId: item.id })} disabled={withdraw.isPending}>
              Withdraw my request
            </Button>
          ) : null}

          {!canRecord && !work && myEmployeeId === null ? <p className="text-sm text-muted-foreground">You are not set up to be credited for salon work.</p> : null}
        </div>
      ) : null}
    </div>
  );
}

function ChangePeople({ work, myEmployeeId }: { work: Execution; myEmployeeId: number | null }) {
  const primary = work.contributors.find((person) => person.is_primary)?.employee_id;
  const locked = [primary, myEmployeeId].filter((id): id is number => id !== null && id !== undefined);
  const [people, setPeople] = useState<number[]>(work.contributors.map((person) => person.employee_id));
  const edit = useVisitMutation(workRequests.editPeople);

  return (
    <ConfirmDialog trigger="Change who worked on it" title="Who worked on this?" description="Everyone added or taken off is told. You stay on it." confirmLabel="Save" onConfirm={async () => void (await edit.mutateAsync({ executionId: work.id, employeeIds: [...new Set([...locked, ...people])] }))}>
      <StaffPicker legend="People on this service" selected={people} onChange={setPeople} locked={locked} />
    </ConfirmDialog>
  );
}

function RequestExtra({ visit }: { visit: Order }) {
  const auth = useAuth();
  const [choice, setChoice] = useState<VisitChoice | null>(null);
  const [note, setNote] = useState("");
  const request = useVisitMutation(visitRequests.requestExtra);

  if (!auth.can("additional.request")) {
    return null;
  }

  return (
    <ConfirmDialog
      trigger="Ask for an extra service"
      title="Extra service"
      description="A supervisor approves it before it is charged. You can record the work meanwhile."
      confirmLabel={choice ? `Ask for ${choice.name}` : "Ask"}
      onConfirm={async () => {
        if (!choice) {
          throw new ApiError({ status: 422, code: "VALIDATION_FAILED", message: "Choose the service." });
        }

        await request.mutateAsync({ orderId: visit.id, itemId: choice.id, note: note.trim() || null });
        setChoice(null);
        setNote("");
      }}
    >
      {choice ? <p className="rounded-lg border p-3 font-medium">{choice.name}</p> : <ItemPicker onPick={setChoice} onlyType="service" label="Which service?" />}
      <FormField id="extra-note" label="Note (optional)">
        <Textarea id="extra-note" rows={2} maxLength={300} value={note} onChange={(event) => setNote(event.target.value)} />
      </FormField>
    </ConfirmDialog>
  );
}
