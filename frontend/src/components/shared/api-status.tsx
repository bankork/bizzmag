"use client";

import { useQuery } from "@tanstack/react-query";
import { CircleCheck, CircleAlert, LoaderCircle } from "lucide-react";
import { apiFetch } from "@/lib/api/client";
import { config } from "@/lib/config";

type Meta = {
  name: string;
  api_version: string;
  release: string;
  currency: string;
  server_time: string;
};

/**
 * Confirms the web app can reach the API it was built for. Useful right
 * after a deployment.
 */
export function ApiStatus() {
  const meta = useQuery({
    queryKey: ["system", "meta"],
    queryFn: ({ signal }) => apiFetch<Meta>("meta", { signal }).then((response) => response.data),
  });

  if (meta.isPending) {
    return (
      <p className="flex items-center gap-2 text-sm text-muted-foreground">
        <LoaderCircle aria-hidden className="size-4 animate-spin" /> Checking the API connection...
      </p>
    );
  }

  if (meta.isError) {
    return (
      <p className="flex items-center gap-2 text-sm text-destructive">
        <CircleAlert aria-hidden className="size-4" /> Cannot reach the API at {config.apiUrl}. {meta.error.message}
      </p>
    );
  }

  return (
    <p className="flex items-center gap-2 text-sm text-emerald-700">
      <CircleCheck aria-hidden className="size-4" /> Connected to {meta.data.name} API {meta.data.api_version} (release {meta.data.release}).
    </p>
  );
}
