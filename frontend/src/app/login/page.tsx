import type { Metadata } from "next";
import { LoginForm } from "@/components/auth/login-form";

export const metadata: Metadata = { title: "Sign in" };

export default function LoginPage() {
  return (
    <main className="mx-auto flex w-full max-w-sm flex-1 flex-col justify-center gap-6 px-4 py-10">
      <div className="space-y-1">
        <p className="text-sm font-medium text-primary">Salon Operations</p>
        <h1 className="text-2xl font-semibold">Sign in</h1>
        <p className="text-sm text-muted-foreground">Use the email or phone number the salon registered for you.</p>
      </div>

      <LoginForm />
    </main>
  );
}
