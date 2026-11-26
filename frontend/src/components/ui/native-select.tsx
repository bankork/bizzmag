import type { ComponentProps } from "react";
import { cn } from "cn";

/**
 * A styled native select: reliable on every phone and with screen readers.
 */
function NativeSelect({ className, ...props }: ComponentProps<"select">) {
  return (
    <select
      data-slot="native-select"
      className={cn(
        "h-10 w-full rounded-lg border border-input bg-background px-3 text-base outline-none focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50 disabled:opacity-50 md:text-sm",
        className,
      )}
      {...props}
    />
  );
}

export { NativeSelect };
