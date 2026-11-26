import type { FieldValues, Path, UseFormSetError } from "react-hook-form";
import { ApiError } from "@/lib/api/errors";

/**
 * Puts the server's field messages on the matching form fields, and
 * returns the message to show above the form for anything else.
 */
export function applyServerErrors<T extends FieldValues>(error: unknown, setError: UseFormSetError<T>, fields: readonly Path<T>[]): string | null {
  if (!(error instanceof ApiError)) {
    return "Something went wrong. Please try again.";
  }

  let unmatched = false;

  for (const [field, messages] of Object.entries(error.fields)) {
    const match = fields.find((candidate) => candidate === field);

    if (match) {
      setError(match, { type: "server", message: messages[0] });
    } else {
      unmatched = true;
    }
  }

  if (Object.keys(error.fields).length > 0 && !unmatched) {
    return null;
  }

  return error.message;
}
