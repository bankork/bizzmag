import type { Execution } from "@/lib/api/types";
import { formatBasisPoints, formatDateTime } from "@/lib/format";

const METHOD_LABEL = { equal: "Shared equally", primary_assistant: "Main stylist and assistants", custom: "Set by a supervisor" } as const;

/**
 * Who did a service and how its credit is shared, in percentages. Never
 * money: staff see shares, not amounts (BR-041).
 */
export function PeopleOnWork({ work, compact = false }: { work: Execution; compact?: boolean }) {
  return (
    <div className="space-y-1 text-sm">
      <ul className="flex flex-wrap gap-x-3 gap-y-1" aria-label="People on this service">
        {work.contributors.map((person) => (
          <li key={person.employee_id} className="tabular-nums">
            <span className={person.is_primary ? "font-medium" : undefined}>{person.name}</span>
            {person.is_primary && work.contributors.length > 1 ? <span className="text-muted-foreground"> (main)</span> : null}{" "}
            <span className="text-muted-foreground">{formatBasisPoints(person.share_bp)}</span>
          </li>
        ))}
      </ul>
      {compact ? null : (
        <p className="text-xs text-muted-foreground">
          {METHOD_LABEL[work.allocation_method]} · recorded by {work.submitted_by.name}, {formatDateTime(work.submitted_at)}
          {work.late ? " · after the bill" : ""}
        </p>
      )}
      {work.status === "disputed" && work.dispute_reason ? (
        <p className="text-xs text-amber-800 dark:text-amber-200">
          Disputed by {work.disputed_by}: {work.dispute_reason}
        </p>
      ) : null}
    </div>
  );
}
