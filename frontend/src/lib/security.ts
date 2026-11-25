import { config } from "@/lib/config";

/**
 * Content Security Policy for every page (Stage 3 §9).
 *
 * Built at build time from NEXT_PUBLIC_API_URL and delivered as a <meta>
 * tag, so the same policy applies on Apache, Nginx and Vercel without any
 * host configuration. Next.js inlines small bootstrap scripts, so
 * 'unsafe-inline' is required for scripts; no third-party origins are allowed.
 * frame-ancestors cannot be set from a meta tag; X-Frame-Options covers it.
 */
export function contentSecurityPolicy(): string {
  const apiOrigin = new URL(config.apiUrl).origin;
  const development = process.env.NODE_ENV === "development";

  return [
    "default-src 'self'",
    `script-src 'self' 'unsafe-inline'${development ? " 'unsafe-eval'" : ""}`,
    "style-src 'self' 'unsafe-inline'",
    "img-src 'self' data: blob:",
    "font-src 'self' data:",
    `connect-src 'self' ${apiOrigin}${development ? " ws:" : ""}`,
    "worker-src 'self'",
    "manifest-src 'self'",
    "base-uri 'self'",
    "form-action 'self'",
  ].join("; ");
}
