"use client";

import { useState } from "react";
import { ConfirmDialog } from "@/components/admin/confirm-dialog";
import { FormField } from "@/components/shared/form-field";
import { StaffPicker } from "@/components/visits/staff-picker";
import { Input } from "@/components/ui/input";
import { NativeSelect } from "@/components/ui/native-select";
import { Textarea } from "@/components/ui/textarea";
import { ApiError } from "@/lib/api/errors";
import { newIdempotencyKey } from "@/lib/api/idempotency";
import type { AllocationMethod, Execution, OrderItem } from "@/lib/api/types";
import { useStaffDirectory, useVisitMutation, workRequests, type SplitInput } from "@/lib/api/visits";
import { formatBasisPoints, parsePercent } from "@/lib/format";

/**
 * Supervisor and desk actions on recorded work. The server checks every
 * rule (who may do it, segregation of duties); these dialogs only collect
 * the choices and show the server's answer.
 */

function failure(error: unknown): never {
  if (error instanceof ApiError) {
    throw new ApiError({ status: error.status, code: error.code, message: Object.values(error.fields)[0]?.[0] ?? error.message });
  }

  throw error;
}

function PrimaryChooser({ id, people, value, onChange }: { id: string; people: number[]; value: number | null; onChange: (id: number) => void }) {
  const staff = useStaffDirectory();
  const names = new Map((staff.data ?? []).map((person) => [person.id, person.name]));

  return (
    <FormField id={id} label="Main person">
      <NativeSelect id={id} value={value ?? ""} onChange={(event) => onChange(Number(event.target.value))}>
        <option value="" disabled>
          Choose who led the work
        </option>
        {people.map((person) => (
          <option key={person} value={person}>
            {names.get(person) ?? `Staff ${person}`}
          </option>
        ))}
      </NativeSelect>
    </FormField>
  );
}

/**
 * Record work from the desk, for example a supervisor recording for a
 * stylist whose phone is flat. Someone who does no salon work picks the
 * main person and is not credited (P3 Q1).
 */
export function RecordWorkDialog({ item, myEmployeeId }: { item: OrderItem; myEmployeeId: number | null }) {
  const [people, setPeople] = useState<number[]>([]);
  const [primary, setPrimary] = useState<number | null>(null);
  const submit = useVisitMutation(workRequests.submit);
  const [key] = useState(newIdempotencyKey);

  return (
    <ConfirmDialog
      trigger="Record work"
      title={`Record ${item.name}`}
      description={myEmployeeId ? "You are recorded as the main person. Add anyone who worked with you." : "Choose who did the work and who led it. You are not credited."}
      confirmLabel="Record"
      onConfirm={async () => {
        const chosenPrimary = myEmployeeId ?? primary;

        if (!chosenPrimary) {
          throw new ApiError({ status: 422, code: "VALIDATION_FAILED", message: "Choose the main person." });
        }

        await submit.mutateAsync({ itemId: item.id, colleagueIds: people.filter((person) => person !== chosenPrimary), primaryId: myEmployeeId ? null : chosenPrimary, key }).catch(failure);
      }}
    >
      <StaffPicker legend={myEmployeeId ? "Worked with you" : "Who did it"} selected={people} onChange={setPeople} hide={myEmployeeId ? [myEmployeeId] : []} />
      {myEmployeeId ? null : <PrimaryChooser id={`record-primary-${item.id}`} people={people} value={primary} onChange={setPrimary} />}
    </ConfirmDialog>
  );
}

/** FR-EXE-010: replace a record; the original stays visible as replaced. */
export function CorrectDialog({ work }: { work: Execution }) {
  const current = work.contributors.map((person) => person.employee_id);
  const [people, setPeople] = useState<number[]>(current);
  const [primary, setPrimary] = useState<number | null>(work.contributors.find((person) => person.is_primary)?.employee_id ?? null);
  const [reason, setReason] = useState("");
  const correct = useVisitMutation(workRequests.correct);

  return (
    <ConfirmDialog
      trigger="Correct"
      title="Correct this record"
      description="A new record replaces this one. The original stays on the visit, marked replaced, and everyone on either is told."
      confirmLabel="Save correction"
      onConfirm={async () => {
        if (!primary || !people.includes(primary)) {
          throw new ApiError({ status: 422, code: "VALIDATION_FAILED", message: "Choose a main person who is on the record." });
        }

        await correct.mutateAsync({ executionId: work.id, employeeIds: people, primaryId: primary, reason }).catch(failure);
      }}
    >
      <StaffPicker legend="Who did the work" selected={people} onChange={setPeople} />
      <PrimaryChooser id={`correct-primary-${work.id}`} people={people} value={primary} onChange={setPrimary} />
      <FormField id={`correct-reason-${work.id}`} label="Reason">
        <Textarea id={`correct-reason-${work.id}`} rows={2} value={reason} onChange={(event) => setReason(event.target.value)} />
      </FormField>
    </ConfirmDialog>
  );
}

/** FR-ATTR-009: change how one record's credit is shared, with a reason. */
export function SplitDialog({ work }: { work: Execution }) {
  const [method, setMethod] = useState<AllocationMethod>(work.allocation_method);
  const [primary, setPrimary] = useState<number>(work.contributors.find((person) => person.is_primary)?.employee_id ?? work.contributors[0].employee_id);
  const [primaryShare, setPrimaryShare] = useState(work.primary_share_bp ? (work.primary_share_bp / 100).toString() : "50");
  const [custom, setCustom] = useState<Record<number, string>>(() => Object.fromEntries(work.contributors.map((person) => [person.employee_id, (person.share_bp / 100).toString()])));
  const [reason, setReason] = useState("");
  const override = useVisitMutation(workRequests.overrideSplit);

  async function save() {
    const split: SplitInput = { method, reason };

    if (method === "primary_assistant") {
      const share = parsePercent(primaryShare);

      if (share === null || share < 100) {
        throw new ApiError({ status: 422, code: "VALIDATION_FAILED", message: "Enter the main person's share, from 1% to 100%." });
      }

      split.primary_share_bp = share;
    }

    if (method === "custom") {
      const shares = work.contributors.map((person) => ({ employee_id: person.employee_id, share_bp: parsePercent(custom[person.employee_id] ?? "") ?? 0 }));

      if (shares.reduce((total, share) => total + share.share_bp, 0) !== 10_000 || shares.some((share) => share.share_bp < 1)) {
        throw new ApiError({ status: 422, code: "VALIDATION_FAILED", message: "Give everyone a share; together they must make exactly 100%." });
      }

      split.shares = shares;
    }

    await override.mutateAsync({ executionId: work.id, split, primaryId: primary }).catch(failure);
  }

  return (
    <ConfirmDialog trigger="Change split" title="Change how credit is shared" description="This changes only this record. The person who recorded it cannot change it themselves." confirmLabel="Save split" onConfirm={save}>
      <FormField id={`split-method-${work.id}`} label="How to share">
        <NativeSelect id={`split-method-${work.id}`} value={method} onChange={(event) => setMethod(event.target.value as AllocationMethod)}>
          <option value="equal">Equally</option>
          <option value="primary_assistant">Main person and assistants</option>
          <option value="custom">Set each person&apos;s share</option>
        </NativeSelect>
      </FormField>
      {method !== "custom" ? (
        <FormField id={`split-primary-${work.id}`} label="Main person">
          <NativeSelect id={`split-primary-${work.id}`} value={primary} onChange={(event) => setPrimary(Number(event.target.value))}>
            {work.contributors.map((person) => (
              <option key={person.employee_id} value={person.employee_id}>
                {person.name}
              </option>
            ))}
          </NativeSelect>
        </FormField>
      ) : null}
      {method === "primary_assistant" ? (
        <FormField id={`split-share-${work.id}`} label="Main person's share (%)">
          <Input id={`split-share-${work.id}`} inputMode="decimal" value={primaryShare} onChange={(event) => setPrimaryShare(event.target.value)} />
        </FormField>
      ) : null}
      {method === "custom"
        ? work.contributors.map((person) => (
            <FormField key={person.employee_id} id={`split-custom-${work.id}-${person.employee_id}`} label={`${person.name} (%)`} hint={`Now ${formatBasisPoints(person.share_bp)}`}>
              <Input
                id={`split-custom-${work.id}-${person.employee_id}`}
                inputMode="decimal"
                value={custom[person.employee_id] ?? ""}
                onChange={(event) => setCustom({ ...custom, [person.employee_id]: event.target.value })}
              />
            </FormField>
          ))
        : null}
      <FormField id={`split-reason-${work.id}`} label="Reason">
        <Textarea id={`split-reason-${work.id}`} rows={2} value={reason} onChange={(event) => setReason(event.target.value)} />
      </FormField>
    </ConfirmDialog>
  );
}
