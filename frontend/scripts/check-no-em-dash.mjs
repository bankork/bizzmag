#!/usr/bin/env node
/**
 * Fails if an em-dash (U+2014) appears in application design: UI copy,
 * PDF and email templates, demo data and backend messages. Owner rule,
 * 2026-11-25. Use a colon, comma, hyphen or a new sentence instead.
 *
 * Usage: node scripts/check-no-em-dash.mjs <path> [<path> ...]
 */
import { existsSync, readdirSync, readFileSync, statSync } from "node:fs";
import { join } from "node:path";

const EM_DASH = "—";
const checkedExtensions = /\.(?:tsx?|jsx?|mjs|css|html|json|webmanifest|php|blade\.php)$/;
const skippedDirectories = new Set(["node_modules", ".next", "out", "vendor", "storage"]);

function walk(path) {
  if (!existsSync(path)) {
    return [];
  }

  if (statSync(path).isFile()) {
    return [path];
  }

  return readdirSync(path).flatMap((entry) => (skippedDirectories.has(entry) ? [] : walk(join(path, entry))));
}

const roots = process.argv.slice(2);
const offences = [];

for (const file of roots.flatMap(walk).filter((file) => checkedExtensions.test(file))) {
  readFileSync(file, "utf8")
    .split("\n")
    .forEach((line, index) => {
      if (line.includes(EM_DASH)) {
        offences.push(`${file}:${index + 1}: ${line.trim()}`);
      }
    });
}

if (offences.length > 0) {
  console.error(`Em-dash found in ${offences.length} place(s). Use a colon, comma, hyphen or new sentence:\n`);
  console.error(offences.join("\n"));
  process.exit(1);
}

console.log(`No em-dashes found (${roots.join(", ")}).`);
