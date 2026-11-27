#!/usr/bin/env node
/**
 * Starts the Laravel API for the browser tests: a fresh database loaded
 * with the Adunni demo salon, then PHP's built-in server on 127.0.0.1:8100.
 *
 * Settings come from the environment, which Laravel prefers over .env, so
 * the developer's own .env and database are never touched. Override the
 * database with E2E_DB_* variables (CI uses its MySQL service).
 */
import { spawn, spawnSync } from "node:child_process";
import { fileURLToPath } from "node:url";

const backend = fileURLToPath(new URL("../../backend", import.meta.url));
const php = process.env.PHP_BIN ?? "php";

const env = {
  ...process.env,
  APP_ENV: "local",
  APP_DEBUG: "true",
  APP_URL: "http://127.0.0.1:8100",
  FRONTEND_URL: "http://127.0.0.1:3100",
  SANCTUM_STATEFUL_DOMAINS: "127.0.0.1:3100",
  SESSION_DRIVER: "database",
  SESSION_DOMAIN: "",
  SESSION_SECURE_COOKIE: "false",
  CACHE_STORE: "database",
  QUEUE_CONNECTION: "sync",
  DB_CONNECTION: "mysql",
  DB_HOST: process.env.E2E_DB_HOST ?? "127.0.0.1",
  DB_PORT: process.env.E2E_DB_PORT ?? "3307",
  DB_DATABASE: process.env.E2E_DB_DATABASE ?? "bizzmag_e2e",
  DB_USERNAME: process.env.E2E_DB_USERNAME ?? "root",
  DB_PASSWORD: process.env.E2E_DB_PASSWORD ?? "",
};

for (const args of [["config:clear"], ["migrate:fresh", "--force"], ["app:demo", "--force"]]) {
  const result = spawnSync(php, ["artisan", ...args, "--no-interaction"], { cwd: backend, env, stdio: "inherit" });

  if (result.status !== 0) {
    process.exit(result.status ?? 1);
  }
}

// Several workers, like PHP-FPM in production: screens now poll for live
// updates, and one worker would make every browser wait in a single queue.
const server = spawn(php, ["-S", "127.0.0.1:8100", "../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php"], {
  cwd: `${backend}/public`,
  env: { ...env, PHP_CLI_SERVER_WORKERS: "4" },
  stdio: "inherit",
});

for (const signal of ["SIGINT", "SIGTERM"]) {
  process.on(signal, () => server.kill(signal));
}

server.on("exit", (code) => process.exit(code ?? 0));
