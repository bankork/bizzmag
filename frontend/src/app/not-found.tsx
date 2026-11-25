import Link from "next/link";

export default function NotFound() {
  return (
    <main className="mx-auto flex w-full max-w-md flex-1 flex-col items-center justify-center gap-3 px-4 text-center">
      <h1 className="text-xl font-semibold">Page not found</h1>
      <p className="text-sm text-muted-foreground">The page you opened does not exist. It may have moved.</p>
      <Link href="/" className="text-sm text-primary underline-offset-4 hover:underline">
        Go to the start page
      </Link>
    </main>
  );
}
