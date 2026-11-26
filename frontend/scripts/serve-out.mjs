#!/usr/bin/env node
/**
 * Serves the static export in out/ the way Apache (.htaccess), Nginx and
 * Vercel do: /path/ -> path/index.html, unknown paths -> 404.html with 404.
 * Used by the browser tests; not for production.
 *
 * Usage: node scripts/serve-out.mjs [port] [host]
 */
import { createReadStream, existsSync, statSync } from "node:fs";
import { createServer } from "node:http";
import { extname, join, normalize } from "node:path";
import { fileURLToPath } from "node:url";

const root = fileURLToPath(new URL("../out", import.meta.url));
const port = Number(process.argv[2] ?? 3000);
const host = process.argv[3] ?? "127.0.0.1";

const TYPES = {
  ".html": "text/html; charset=utf-8",
  ".js": "text/javascript; charset=utf-8",
  ".css": "text/css; charset=utf-8",
  ".json": "application/json",
  ".webmanifest": "application/manifest+json",
  ".png": "image/png",
  ".ico": "image/x-icon",
  ".svg": "image/svg+xml",
  ".woff2": "font/woff2",
  ".txt": "text/plain; charset=utf-8",
};

function resolveFile(urlPath) {
  const safe = normalize(decodeURIComponent(urlPath)).replace(/^(\.\.[/\\])+/, "");
  const candidate = join(root, safe);

  if (!candidate.startsWith(root)) {
    return null;
  }

  if (existsSync(candidate) && statSync(candidate).isFile()) {
    return candidate;
  }

  const index = join(candidate, "index.html");

  return existsSync(index) ? index : null;
}

createServer((request, response) => {
  const path = new URL(request.url ?? "/", "http://localhost").pathname;

  if (!path.endsWith("/") && !extname(path) && resolveFile(`${path}/`)) {
    response.writeHead(308, { Location: `${path}/` }).end();

    return;
  }

  const file = resolveFile(path);
  const target = file ?? join(root, "404.html");

  response.writeHead(file ? 200 : 404, {
    "Content-Type": TYPES[extname(target)] ?? "application/octet-stream",
    "Cache-Control": "no-cache",
  });
  createReadStream(target).pipe(response);
}).listen(port, host, () => {
  console.log(`Serving out/ at http://${host}:${port}`);
});
