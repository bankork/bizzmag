import type { ReactNode } from "react";
import { Label } from "@/components/ui/label";

type FormFieldProps = {
  id: string;
  label: string;
  hint?: string;
  error?: string;
  children: ReactNode;
};

/**
 * A labelled field with its hint and error. Give the input the same id, and
 * aria-describedby={describedBy(id, ...)} so both are read out.
 */
export function FormField({ id, label, hint, error, children }: FormFieldProps) {
  return (
    <div className="space-y-1.5">
      <Label htmlFor={id}>{label}</Label>
      {children}
      {hint ? (
        <p id={`${id}-hint`} className="text-xs text-muted-foreground">
          {hint}
        </p>
      ) : null}
      {error ? (
        <p id={`${id}-error`} className="text-sm text-destructive">
          {error}
        </p>
      ) : null}
    </div>
  );
}

export function describedBy(id: string, hint?: string, error?: string): string | undefined {
  const ids = [hint ? `${id}-hint` : null, error ? `${id}-error` : null].filter(Boolean);

  return ids.length > 0 ? ids.join(" ") : undefined;
}
