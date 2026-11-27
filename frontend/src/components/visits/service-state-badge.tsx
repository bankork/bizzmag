import { Badge } from "@/components/ui/badge";
import type { OrderItem } from "@/lib/api/types";
import { SERVICE_STATE, serviceState } from "@/lib/visits";

export function ServiceStateBadge({ item }: { item: OrderItem }) {
  const state = SERVICE_STATE[serviceState(item)];

  return <Badge variant={state.tone}>{state.label}</Badge>;
}
