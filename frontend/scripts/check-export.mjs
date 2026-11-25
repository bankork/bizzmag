#!/usr/bin/env node
/**
 * Verifies the static export is deployable on any static host
 * (Stage 4 v1.2 §10.3): every app route exists as a file in out/, the PWA
 * files are present, the service worker never mentions the API, and
 * vercel.json is valid and points at out/.
 */
import { existsSync, readFileSync, readdirSync, statSync } from "node:fs";
import { join, relative, sep } from "node:path";
import { fileURLToPath } from "node:url";

const root = fileURLToPath(new URL("..", import.meta.url));
const out = join(root, "out");
const failures = [];

function check(condition, message) {
  if (!condition) {
    failures.push(message);
  }
}

if (!existsSync(out)) {
  console.error("out/ not found. Run `pnpm build` first.");
  process.exit(1);
}

function pageRoutes(directory) {
  return readdirSync(directory).flatMap((entry) => {
    const path = join(directory, entry);

    if (statSync(path).isDirectory()) {
      // Folders starting with "_" are private in the App Router and never become routes.
      return entry.startsWith("_") ? [] : pageRoutes(path);
    }

    return entry === "page.tsx" ? [relative(join(root, "src", "app"), directory).split(sep).join("/")] : [];
  });
}

for (const route of pageRoutes(join(root, "src", "app"))) {
  const segments = route.split("/").filter((segment) => segment !== "" && !segment.startsWith("("));
  check(!segments.some((segment) => segment.startsWith("[")), `Dynamic route "${route}" cannot be statically exported. Use a query parameter instead.`);

  const file = segments.length === 0 ? "index.html" : `${segments.join("/")}/index.html`;
  check(existsSync(join(out, file)), `Route "/${segments.join("/")}" has no ${file} in out/.`);
}

for (const required of ["404.html", "sw.js", "offline.html", "manifest.webmanifest", ".htaccess", "icons/icon-192.png", "icons/icon-512.png"]) {
  check(existsSync(join(out, required)), `Missing ${required} in out/.`);
}

if (existsSync(join(out, "sw.js"))) {
  const serviceWorker = readFileSync(join(out, "sw.js"), "utf8");
  check(!/\/api\/v1/.test(serviceWorker.replace(/startsWith\("\/api\/"\)/g, "")), "sw.js must not precache API URLs.");
  check(!serviceWorker.includes("__PRECACHE__"), "sw.js precache list was not generated.");
}

const vercel = JSON.parse(readFileSync(join(root, "vercel.json"), "utf8"));
check(vercel.outputDirectory === "out", 'vercel.json outputDirectory must be "out".');
check(vercel.buildCommand === "pnpm build", 'vercel.json buildCommand must be "pnpm build" (it also generates sw.js).');
check(vercel.trailingSlash === true, "vercel.json trailingSlash must be true to match next.config.ts.");
check(Array.isArray(vercel.headers) && vercel.headers.some((rule) => rule.source === "/sw.js"), "vercel.json must set no-cache headers for /sw.js.");

const html = readFileSync(join(out, "index.html"), "utf8");
check(html.includes('http-equiv="Content-Security-Policy"'), "index.html is missing the Content-Security-Policy meta tag.");
check(html.includes("manifest.webmanifest"), "index.html does not link the web app manifest.");

if (failures.length > 0) {
  console.error(`Static export check failed:\n- ${failures.join("\n- ")}`);
  process.exit(1);
}

console.log("Static export check passed: every route, PWA file and host config is in place.");
