import type { Metadata } from "next";
import Link from "next/link";
import { Button } from "@/components/ui/button";

export const metadata: Metadata = { title: "Sign in" };

export default function LoginPage() {
  return (
    <main className="mx-auto flex w-full max-w-sm flex-1 flex-col justify-center gap-6 px-4 py-10">
      <div className="space-y-1">
        <h1 className="text-2xl font-semibold">Sign in</h1>
        <p className="text-sm text-muted-foreground">Sign-in is switched on in Phase 1.</p>
      </div>

      <form className="space-y-4">
        <label className="block space-y-1 text-sm">
          <span className="font-medium">Email or phone</span>
          <input disabled className="w-full rounded-lg border bg-muted px-3 py-2" placeholder="chiamaka@adunni.test" />
        </label>
        <label className="block space-y-1 text-sm">
          <span className="font-medium">Password</span>
          <input disabled type="password" className="w-full rounded-lg border bg-muted px-3 py-2" />
        </label>
        <Button type="button" disabled className="w-full">
          Sign in
        </Button>
      </form>

      <Link href="/" className="text-center text-sm text-muted-foreground underline-offset-4 hover:underline">
        Back
      </Link>
    </main>
  );
}
