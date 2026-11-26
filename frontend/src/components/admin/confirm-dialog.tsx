"use client";

import { useState, type ReactNode } from "react";
import { Alert } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from "@/components/ui/dialog";
import { ApiError } from "@/lib/api/errors";

type ConfirmDialogProps = {
  trigger: ReactNode;
  title: string;
  description: ReactNode;
  confirmLabel: string;
  destructive?: boolean;
  children?: ReactNode;
  onConfirm: () => Promise<void>;
};

/**
 * Asks before an action that changes someone's access. Shows the server's
 * message if the action is refused.
 */
export function ConfirmDialog({ trigger, title, description, confirmLabel, destructive = false, children, onConfirm }: ConfirmDialogProps) {
  const [open, setOpen] = useState(false);
  const [busy, setBusy] = useState(false);
  const [failure, setFailure] = useState<string | null>(null);

  async function confirm() {
    setBusy(true);
    setFailure(null);

    try {
      await onConfirm();
      setOpen(false);
    } catch (error) {
      setFailure(error instanceof ApiError ? error.message : "Something went wrong. Please try again.");
    } finally {
      setBusy(false);
    }
  }

  return (
    <Dialog
      open={open}
      onOpenChange={(next) => {
        setOpen(next);
        setFailure(null);
      }}
    >
      <DialogTrigger render={<Button variant={destructive ? "destructive" : "outline"} />}>{trigger}</DialogTrigger>
      <DialogContent>
        <DialogTitle>{title}</DialogTitle>
        <DialogDescription>{description}</DialogDescription>
        <div className="mt-4 space-y-3">
          {children}
          {failure ? <Alert variant="destructive">{failure}</Alert> : null}
        </div>
        <DialogFooter>
          <DialogClose render={<Button type="button" variant="outline" />}>Cancel</DialogClose>
          <Button type="button" variant={destructive ? "destructive" : "default"} disabled={busy} onClick={confirm}>
            {busy ? "Working..." : confirmLabel}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
