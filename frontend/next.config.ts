import type { NextConfig } from "next";

/**
 * Static export (Stage 2 TDR-05, Stage 3 ADR-07).
 *
 * `next build` writes plain HTML, CSS and JS to `out/`. The same artefact is
 * served by Apache (.htaccess), Nginx or Vercel (vercel.json): no Node.js
 * server runs in production. Data is always fetched from the Laravel API in
 * the browser, so pages hold no private data.
 *
 * Record pages use query parameters (`/desk/order/?id=12`) because a static
 * export cannot pre-render pages for records created after the build.
 */
const nextConfig: NextConfig = {
  output: "export",
  trailingSlash: true,
  images: {
    unoptimized: true,
  },
  turbopack: {
    rules: {
      "*.css": {
        loaders: ["@tailwindcss/turbopack"],
        as: "*.css",
      },
    },
  },
};

export default nextConfig;
