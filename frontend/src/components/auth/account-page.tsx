"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { LogOut } from "lucide-react";
import { ChangePasswordForm } from "@/components/auth/change-password-form";
import { RequireAuth } from "@/components/auth/require-auth";
import { Alert } from "@/components/ui/alert";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { useAuth } from "@/lib/auth/auth-provider";
import { AREAS } from "@/lib/auth/areas";
import { formatPhone } from "@/lib/format";

export function AccountPage() {
  return (
    <RequireAuth>
      <Account />
    </RequireAuth>
  );
}

function Account() {
  const auth = useAuth();
  const router = useRouter();
  const me = auth.me;

  if (!me) {
    return null;
  }

  const forced = me.user.must_change_password;
  const idleMinutes = Math.round(me.session.idle_timeout_seconds / 60);

  async function signOut() {
    await auth.signOut();
    router.replace("/login/");
  }

  return (
    <main className="mx-auto w-full max-w-xl flex-1 space-y-5 px-4 py-8">
      <div className="flex items-center justify-between gap-3">
        <div>
          <p className="text-xs font-medium uppercase tracking-wide text-muted-foreground">{me.business.name}</p>
          <h1 className="text-xl font-semibold">My account</h1>
        </div>
        {!forced ? (
          <Link href={AREAS[me.home].href} className="text-sm text-primary underline-offset-4 hover:underline">
            Back to {AREAS[me.home].title}
          </Link>
        ) : null}
      </div>

      {forced ? (
        <Alert variant="warning">
          <strong>Choose a new password to continue.</strong> Your password was set by the salon, so only you should know the new one.
        </Alert>
      ) : (
        <Card>
          <CardHeader>
            <CardTitle>{me.user.name}</CardTitle>
            <CardDescription>
              {[me.user.email, formatPhone(me.user.phone)].filter(Boolean).join(" · ")}
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-3 text-sm">
            <div className="flex flex-wrap gap-1.5">
              {me.roles.map((role) => (
                <Badge key={role.id} variant="secondary">
                  {role.name}
                </Badge>
              ))}
            </div>
            <p className="text-muted-foreground">
              {me.session.kind === "personal"
                ? `On this device you stay signed in until you have been inactive for ${Math.round(idleMinutes / 1440)} days.`
                : `For safety you are signed out after ${idleMinutes} minutes without activity.`}
            </p>
          </CardContent>
        </Card>
      )}

      <Card>
        <CardHeader>
          <CardTitle>{forced ? "New password" : "Change password"}</CardTitle>
        </CardHeader>
        <CardContent>
          <ChangePasswordForm forced={forced} />
        </CardContent>
      </Card>

      <Button variant="outline" onClick={signOut}>
        <LogOut aria-hidden />
        Sign out
      </Button>
    </main>
  );
}
