import { expect, type Page } from "@playwright/test";

/** Every demo account uses this password (docs/demo-data.md). */
export const DEMO_PASSWORD = "Adunni@2026";

export const API = "http://127.0.0.1:8100/api/v1";

export async function signIn(page: Page, identifier: string, password = DEMO_PASSWORD) {
  await page.goto("/login/");
  await page.getByLabel("Email or phone number").fill(identifier);
  await page.getByLabel("Password", { exact: true }).fill(password);
  await page.getByRole("button", { name: "Sign in" }).click();
}

/** Sign in and wait until the app has left the sign-in page. */
export async function signInAndWait(page: Page, identifier: string, password = DEMO_PASSWORD) {
  await signIn(page, identifier, password);
  await expect(page).not.toHaveURL(/\/login\//);
}

export async function signOut(page: Page) {
  await page.getByRole("button", { name: "Sign out" }).first().click();
  await expect(page).toHaveURL(/\/login\/$/);
}
