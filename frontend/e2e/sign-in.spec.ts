import { expect, test } from "@playwright/test";
import { API, signIn, signOut } from "./support";

test("an employee signs in with their email and lands in the staff area", async ({ page }) => {
  await signIn(page, "chiamaka@adunni.test");

  await expect(page).toHaveURL(/\/staff\/$/);
  await expect(page.getByRole("heading", { name: "Today" })).toBeVisible();
  await expect(page.getByText("Adunni Hair & Beauty Studio")).toBeVisible();
});

test("a cashier signs in with a phone number written the local way", async ({ page }) => {
  await signIn(page, "0806 000 1917");

  await expect(page).toHaveURL(/\/desk\/$/);
  await expect(page.getByRole("heading", { name: "Visits board" })).toBeVisible();
});

test("an employee cannot open admin pages, and the API refuses them too", async ({ page }) => {
  await signIn(page, "chiamaka@adunni.test");
  await expect(page).toHaveURL(/\/staff\/$/);

  await page.goto("/admin/settings/");
  await expect(page.getByRole("heading", { name: "You do not have access to this page" })).toBeVisible();

  const response = await page.request.get(`${API}/settings`, { headers: { Accept: "application/json", Origin: "http://127.0.0.1:3100", Referer: "http://127.0.0.1:3100/" } });
  expect(response.status()).toBe(403);
  expect((await response.json()).code).toBe("FORBIDDEN");
});

test("wrong details show a message and sign-in is refused", async ({ page }) => {
  await signIn(page, "kemi@adunni.test", "not-her-password");

  await expect(page.getByText("Those details are not correct. Check them and try again.")).toBeVisible();
  await expect(page).toHaveURL(/\/login\/$/);
});

test("signing out returns to the sign-in page, and protected pages need a sign-in again", async ({ page }) => {
  await signIn(page, "kemi@adunni.test");
  await expect(page).toHaveURL(/\/desk\/$/);

  await signOut(page);
  await expect(page.getByText("You have signed out.")).toBeVisible();

  await page.goto("/desk/");
  await expect(page).toHaveURL(/\/login\/\?next=%2Fdesk%2F$/);
});
