"use client";

import { Skeleton } from "@/components/ui/skeleton";
import { useStaffDirectory } from "@/lib/api/visits";

type StaffPickerProps = {
  legend: string;
  selected: number[];
  onChange: (ids: number[]) => void;
  /** People who are always on and cannot be unticked, such as yourself. */
  locked?: number[];
  hide?: number[];
};

/**
 * Choose people who do salon work, by name. Large touch targets, because it
 * is used one-handed on phones.
 */
export function StaffPicker({ legend, selected, onChange, locked = [], hide = [] }: StaffPickerProps) {
  const staff = useStaffDirectory();

  if (staff.isPending) {
    return <Skeleton className="h-24 w-full" />;
  }

  const people = (staff.data ?? []).filter((person) => !hide.includes(person.id));

  return (
    <fieldset className="space-y-2">
      <legend className="text-sm font-medium">{legend}</legend>
      <div className="grid grid-cols-2 gap-2">
        {people.map((person) => {
          const checked = selected.includes(person.id) || locked.includes(person.id);

          return (
            <label key={person.id} className="flex min-h-11 cursor-pointer items-center gap-2 rounded-lg border px-3 py-2 text-sm has-checked:border-primary has-checked:bg-primary/5">
              <input
                type="checkbox"
                className="size-4 accent-primary"
                checked={checked}
                disabled={locked.includes(person.id)}
                onChange={(event) => onChange(event.target.checked ? [...selected, person.id] : selected.filter((id) => id !== person.id))}
              />
              <span className="min-w-0 truncate">
                {person.name}
                {person.is_me ? " (you)" : ""}
              </span>
            </label>
          );
        })}
      </div>
    </fieldset>
  );
}
