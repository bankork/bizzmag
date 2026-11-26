import { ApiStatus } from "@/components/shared/api-status";
import { AreaChooser } from "@/components/shared/area-chooser";

export default function Home() {
  return (
    <main className="mx-auto flex w-full max-w-3xl flex-1 flex-col gap-8 px-4 py-10">
      <header className="space-y-2">
        <p className="text-sm font-medium text-primary">Salon Operations</p>
        <h1 className="text-2xl font-semibold tracking-tight">Choose where to work</h1>
        <p className="text-muted-foreground">Sign in to go to the area your role allows.</p>
      </header>

      <AreaChooser />

      <footer className="border-t pt-6">
        <ApiStatus />
      </footer>
    </main>
  );
}
