import { defineConfig, devices } from "@playwright/test";

/**
 * Browser tests (P1 W8) against the real API with real cookie sessions.
 *
 * Run with `pnpm e2e`, which builds the static app for http://127.0.0.1:8100
 * first. The API starts on a fresh database with the demo salon.
 */
export default defineConfig({
  testDir: "./e2e",
  fullyParallel: false,
  workers: 1,
  retries: process.env.CI ? 1 : 0,
  reporter: process.env.CI ? [["list"], ["html", { open: "never" }]] : "list",
  use: {
    baseURL: "http://127.0.0.1:3100",
    trace: "retain-on-failure",
  },
  projects: [
    { name: "desktop", use: { ...devices["Desktop Chrome"] } },
    { name: "phone", use: { ...devices["Pixel 7"] }, testMatch: /phone\.spec\.ts/ },
  ],
  webServer: [
    { command: "node scripts/e2e-api.mjs", url: "http://127.0.0.1:8100/up", reuseExistingServer: false, timeout: 180_000 },
    { command: "node scripts/serve-out.mjs 3100", url: "http://127.0.0.1:3100/login/", reuseExistingServer: false },
  ],
});
