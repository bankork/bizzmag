import type { ComponentProps } from "react";
import { cva, type VariantProps } from "class-variance-authority";
import { cn } from "cn";

const alertVariants = cva("rounded-lg border px-4 py-3 text-sm [&_strong]:font-semibold", {
  variants: {
    variant: {
      default: "bg-card",
      info: "border-sky-200 bg-sky-50 text-sky-950 dark:border-sky-900 dark:bg-sky-950/40 dark:text-sky-100",
      success: "border-emerald-200 bg-emerald-50 text-emerald-950 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-100",
      warning: "border-amber-200 bg-amber-50 text-amber-950 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-100",
      destructive: "border-destructive/30 bg-destructive/10 text-destructive",
    },
  },
  defaultVariants: { variant: "default" },
});

/**
 * An inline message. Errors and warnings are announced to screen readers.
 */
function Alert({ className, variant, ...props }: ComponentProps<"div"> & VariantProps<typeof alertVariants>) {
  const urgent = variant === "destructive" || variant === "warning";

  return <div data-slot="alert" role={urgent ? "alert" : "status"} className={cn(alertVariants({ variant }), className)} {...props} />;
}

export { Alert };
