#!/usr/bin/env node
/**
 * Post-build step: writes out/sw.js with a precache list of the static
 * export (Stage 3 ADR-08). Runs automatically after `pnpm build`.
 *
 * Only files that exist in out/ are listed, so nothing private can be
 * cached: the export contains no customer, payment or staff data.
 */
import { createHash } from "node:crypto";
import { readdirSync, readFileSync, statSync, writeFileSync, existsSync } from "node:fs";
import { join, relative, sep } from "node:path";
import { fileURLToPath } from "node:url";

const root = fileURLToPath(new URL("..", import.meta.url));
const outDir = join(root, "out");

if (!existsSync(outDir)) {
  console.error("out/ not found. Run `next build` first.");
  process.exit(1);
}

const precacheable = /\.(?:html|js|css|woff2?|png|svg|ico|webmanifest|json)$/;
const excluded = new Set(["sw.js", ".htaccess"]);

function walk(directory) {
  return readdirSync(directory).flatMap((entry) => {
    const path = join(directory, entry);

    return statSync(path).isDirectory() ? walk(path) : [path];
  });
}

const files = walk(outDir)
  .map((path) => relative(outDir, path).split(sep).join("/"))
  .filter((path) => precacheable.test(path) && !excluded.has(path) && !path.endsWith(".txt"))
  .sort();

const urls = files.map((path) => {
  if (path === "index.html") {
    return "/";
  }

  return path.endsWith("/index.html") ? `/${path.slice(0, -"index.html".length)}` : `/${path}`;
});

const hash = createHash("sha256");

for (const file of files) {
  hash.update(file);
  hash.update(readFileSync(join(outDir, file)));
}

const version = hash.digest("hex").slice(0, 12);
const template = readFileSync(join(root, "scripts", "sw-template.js"), "utf8");
const serviceWorker = template.replaceAll("__VERSION__", version).replaceAll("__PRECACHE__", JSON.stringify(urls, null, 2));

writeFileSync(join(outDir, "sw.js"), serviceWorker);
console.log(`sw.js written: version ${version}, ${urls.length} files precached.`);
