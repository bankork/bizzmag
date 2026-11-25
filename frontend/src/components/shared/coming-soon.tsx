type ComingSoonProps = {
  phase: string;
  items: string[];
};

export function ComingSoon({ phase, items }: ComingSoonProps) {
  return (
    <section className="rounded-xl border bg-card p-5">
      <h2 className="font-medium">Arriving in {phase}</h2>
      <ul className="mt-3 list-disc space-y-1 pl-5 text-sm text-muted-foreground">
        {items.map((item) => (
          <li key={item}>{item}</li>
        ))}
      </ul>
    </section>
  );
}
