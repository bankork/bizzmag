"use client";

import { useState, type ReactNode } from "react";
import { FormField, describedBy } from "@/components/shared/form-field";
import { Alert } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from "@/components/ui/dialog";
import { Textarea } from "@/components/ui/textarea";
import { ApiError } from "@/lib/api/errors";

type ReasonDialogProps = {
  trigger: ReactNode;
  triggerVariant?: "outline" | "destructive" | "ghost" | "secondary" | "default";
  triggerSize?: "default" | "sm" | "lg";
  title: string;
  description: ReactNode;
  confirmLabel: string;
  destructive?: boolean;
  /** Approvals may go without a reason; everything else needs one. */
  optional?: boolean;
  children?: ReactNode;
  onConfirm: (reason: string) => Promise<unknown>;
};

/**
 * An action that needs a written reason: removing, cancelling, rejecting,
 * disputing or resolving (SRS §6). Shows the server's answer if refused.
 */
export function ReasonDialog({ trigger, triggerVariant = "outline", triggerSize = "sm", title, description, confirmLabel, destructive = false, optional = false, children, onConfirm }: ReasonDialogProps) {
  const [open, setOpen] = useState(false);
  const [reason, setReason] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [failure, setFailure] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);
  const id = `reason-${title.toLowerCase().replace(/[^a-z]+/g, "-")}`;

  async function confirm() {
    setFailure(null);

    if (!optional && reason.trim().length < 3) {
      setError("Give a reason of at least 3 characters.");

      return;
    }

    setError(null);
    setBusy(true);

    try {
      await onConfirm(reason.trim());
      setOpen(false);
    } catch (problem) {
      setFailure(problem instanceof ApiError ? (problem.fieldMessage("reason") ?? problem.message) : "Something went wrong. Please try again.");
    } finally {
      setBusy(false);
    }
  }

  return (
    <Dialog
      open={open}
      onOpenChange={(next) => {
        setOpen(next);

        if (next) {
          setReason("");
          setError(null);
          setFailure(null);
        }
      }}
    >
      <DialogTrigger render={<Button variant={triggerVariant} size={triggerSize} />}>{trigger}</DialogTrigger>
      <DialogContent>
        <DialogTitle>{title}</DialogTitle>
        <DialogDescription>{description}</DialogDescription>
        <div className="mt-4 space-y-3">
          {children}
          <FormField id={id} label={optional ? "Note (optional)" : "Reason"} error={error ?? undefined}>
            <Textarea id={id} value={reason} rows={3} maxLength={500} aria-invalid={error !== null} aria-describedby={describedBy(id, undefined, error ?? undefined)} onChange={(event) => setReason(event.target.value)} />
          </FormField>
          {failure ? <Alert variant="destructive">{failure}</Alert> : null}
        </div>
        <DialogFooter>
          <DialogClose render={<Button type="button" variant="outline" />}>Back</DialogClose>
          <Button type="button" variant={destructive ? "destructive" : "default"} disabled={busy} onClick={confirm}>
            {busy ? "Working..." : confirmLabel}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
