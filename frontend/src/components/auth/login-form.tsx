"use client";

import { zodResolver } from "@hookform/resolvers/zod";
import { Eye, EyeOff } from "lucide-react";
import { useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import { useForm } from "react-hook-form";
import { z } from "zod";
import { describedBy, FormField } from "@/components/shared/form-field";
import { Alert } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { ApiError } from "@/lib/api/errors";
import { useAuth } from "@/lib/auth/auth-provider";
import { AREAS } from "@/lib/auth/areas";
import { takeSignOutReason, type SessionEndReason } from "@/lib/auth/session-events";
import { applyServerErrors } from "@/lib/forms";
import { useHydrated } from "@/lib/use-hydrated";

const schema = z.object({
  identifier: z.string().trim().min(1, "Enter your email or phone number."),
  password: z.string().min(1, "Enter your password."),
});

type Values = z.infer<typeof schema>;

const REASON_MESSAGES: Partial<Record<SessionEndReason, string>> = {
  idle: "You were signed out after a period of inactivity. Please sign in again.",
  "signed-out": "You have signed out.",
  disabled: "This account has been disabled. Speak to the salon owner.",
};

/**
 * Sign in with an email address or a phone number (P1 Q1).
 */
export function LoginForm() {
  const auth = useAuth();
  const router = useRouter();
  const [showPassword, setShowPassword] = useState(false);
  const [notice, setNotice] = useState<string | null>(null);
  const [failure, setFailure] = useState<string | null>(null);
  const form = useForm<Values>({ resolver: zodResolver(schema), defaultValues: { identifier: "", password: "" } });
  const { errors, isSubmitting } = form.formState;
  const hydrated = useHydrated();

  useEffect(() => {
    const reason = takeSignOutReason();

    if (reason && REASON_MESSAGES[reason]) {
      // Reading sessionStorage must wait until the browser has the page.
      // eslint-disable-next-line react-hooks/set-state-in-effect
      setNotice(REASON_MESSAGES[reason] ?? null);
    }
  }, []);

  useEffect(() => {
    if (auth.status === "authenticated" && auth.me && !isSubmitting) {
      router.replace(safeNext() ?? AREAS[auth.me.home].href);
    }
  }, [auth.status, auth.me, isSubmitting, router]);

  async function onSubmit(values: Values) {
    setFailure(null);
    setNotice(null);

    try {
      const me = await auth.signIn(values.identifier, values.password);
      router.replace(me.user.must_change_password ? "/account/" : (safeNext() ?? AREAS[me.home].href));
    } catch (error) {
      if (error instanceof ApiError && error.code === "LOGIN_LOCKED") {
        const minutes = Math.max(1, Math.ceil(Number(error.data.retry_after_seconds ?? 900) / 60));
        setFailure(`Too many attempts. Try again in ${minutes} minute${minutes === 1 ? "" : "s"}.`);

        return;
      }

      setFailure(applyServerErrors(error, form.setError, ["identifier", "password"]));
    }
  }

  return (
    // method="post" keeps the password out of the address bar if someone
    // submits before the page has finished loading.
    <form noValidate method="post" onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
      {notice ? <Alert variant="info">{notice}</Alert> : null}
      {failure ? <Alert variant="destructive">{failure}</Alert> : null}

      <FormField id="identifier" label="Email or phone number" error={errors.identifier?.message}>
        <Input
          id="identifier"
          autoComplete="username"
          inputMode="email"
          autoCapitalize="none"
          spellCheck={false}
          placeholder="kemi@adunni.test or 0803 123 4567"
          aria-invalid={errors.identifier ? true : undefined}
          aria-describedby={describedBy("identifier", undefined, errors.identifier?.message)}
          {...form.register("identifier")}
        />
      </FormField>

      <FormField id="password" label="Password" error={errors.password?.message}>
        <div className="relative">
          <Input
            id="password"
            type={showPassword ? "text" : "password"}
            autoComplete="current-password"
            className="pr-11"
            aria-invalid={errors.password ? true : undefined}
            aria-describedby={describedBy("password", undefined, errors.password?.message)}
            {...form.register("password")}
          />
          <button
            type="button"
            onClick={() => setShowPassword((shown) => !shown)}
            className="absolute inset-y-0 right-0 flex w-10 items-center justify-center text-muted-foreground hover:text-foreground"
            aria-label={showPassword ? "Hide password" : "Show password"}
            aria-pressed={showPassword}
          >
            {showPassword ? <EyeOff className="size-4" aria-hidden /> : <Eye className="size-4" aria-hidden />}
          </button>
        </div>
      </FormField>

      <Button type="submit" size="lg" className="h-11 w-full" disabled={isSubmitting || !hydrated}>
        {isSubmitting ? "Signing in..." : "Sign in"}
      </Button>

      <p className="text-center text-xs text-muted-foreground">Forgot your password? Ask the salon owner to reset it.</p>
    </form>
  );
}

/** Only same-site paths are followed after sign-in. */
function safeNext(): string | null {
  const next = new URLSearchParams(window.location.search).get("next");

  return next && next.startsWith("/") && !next.startsWith("//") && !next.startsWith("/login") ? next : null;
}
