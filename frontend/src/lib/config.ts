/**
 * Build-time configuration. In a static export these values are baked into
 * the bundle when `pnpm build` runs, so set them in the build environment
 * (CI, Vercel project settings, or .env.production.local).
 */
export const config = {
  /** The Laravel API origin, for example https://api.your-domain */
  apiUrl: (process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000").replace(/\/+$/, ""),

  /** local | staging | production */
  appEnv: process.env.NEXT_PUBLIC_APP_ENV ?? "local",

  /** How often live screens (desk board, notifications) refresh. */
  pollIntervalMs: Number(process.env.NEXT_PUBLIC_POLL_INTERVAL_MS ?? 20000),
} as const;
