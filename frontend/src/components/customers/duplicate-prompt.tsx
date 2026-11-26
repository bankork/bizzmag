"use client";

import Link from "next/link";
import { Alert } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import { ApiError } from "@/lib/api/errors";
import type { DuplicateMatch } from "@/lib/api/types";

/** The matches in a DUPLICATE_PHONE refusal, or null for any other error. */
export function duplicateMatches(error: unknown): DuplicateMatch[] | null {
  if (!(error instanceof ApiError) || error.code !== "DUPLICATE_PHONE") {
    return null;
  }

  const matches = error.data.matches;

  return Array.isArray(matches) ? (matches as DuplicateMatch[]) : [];
}

type Props = {
  message: string;
  matches: DuplicateMatch[];
  basePath: string;
  confirmLabel: string;
  busy: boolean;
  onConfirm: () => void;
};

/**
 * FR-CUS-009: the number already belongs to another customer. Offer the
 * existing record first; saving anyway is a deliberate second step.
 */
export function DuplicatePrompt({ message, matches, basePath, confirmLabel, busy, onConfirm }: Props) {
  return (
    <Alert variant="warning">
      <p className="font-medium">{message}</p>
      <ul className="mt-2 space-y-1">
        {matches.map((match) => (
          <li key={match.id}>
            <Link href={`${basePath}edit/?id=${match.id}`} className="underline">
              Open {match.name}
            </Link>
          </li>
        ))}
      </ul>
      <Button type="button" size="sm" variant="outline" className="mt-3" disabled={busy} onClick={onConfirm}>
        {confirmLabel}
      </Button>
    </Alert>
  );
}
