import { expect, test } from "@playwright/test";
import { signIn, signInAndWait, signOut } from "./support";

test("the owner adds a cashier, who signs in and must choose a new password", async ({ page }) => {
  await signIn(page, "adunni@adunni.test");
  await expect(page).toHaveURL(/\/admin\/$/);

  await page.getByRole("navigation", { name: "Owner and admin" }).getByRole("link", { name: "Staff" }).click();
  await expect(page.getByText("Kemi Adeyemi")).toBeVisible();

  await page.getByRole("button", { name: "Add staff" }).click();
  const dialog = page.getByRole("dialog");
  await dialog.getByLabel("Full name").fill("Amaka Obi");
  await dialog.getByLabel("Phone number").fill("0813 000 4242");
  await dialog.getByLabel("Front desk / Cashier").check();
  await dialog.getByLabel("Does salon work").uncheck();
  const temporary = await dialog.getByLabel("Temporary password").inputValue();
  await dialog.getByRole("button", { name: "Add staff" }).click();

  await expect(dialog.getByText("Amaka Obi has been added")).toBeVisible();
  await expect(dialog.getByText(temporary)).toBeVisible();
  await dialog.getByRole("button", { name: "Done" }).click();
  await signOut(page);

  await signIn(page, "+234 813 000 4242", temporary);
  await expect(page).toHaveURL(/\/account\/$/);
  await expect(page.getByText("Choose a new password to continue.")).toBeVisible();

  await page.getByLabel("Temporary password").fill(temporary);
  await page.getByLabel("New password", { exact: true }).fill("Amaka-desk-2026");
  await page.getByLabel("New password again").fill("Amaka-desk-2026");
  await page.getByRole("button", { name: "Change password" }).click();

  await expect(page).toHaveURL(/\/desk\/$/);
});

test("the owner turns VAT on and the change appears in the history", async ({ page }) => {
  await signInAndWait(page, "adunni@adunni.test");
  await page.goto("/admin/settings/tax/");

  await expect(page.getByText("Off", { exact: true })).toBeVisible();
  await page.getByRole("switch", { name: "Charge tax" }).click();
  await page.getByLabel("Rate").fill("7.5");
  await expect(page.getByText("Example: a ₦10,000.00 service becomes ₦10,750.00 (₦750.00 tax).")).toBeVisible();
  await page.getByRole("button", { name: "Save tax setting" }).click();

  await expect(page.getByText("Tax setting saved and recorded in the history below.")).toBeVisible();
  await expect(page.getByText("On", { exact: true })).toBeVisible();
  await expect(page.getByText("Switched on: VAT 7.5% added on top")).toBeVisible();
  await expect(page.getByText("Adunni Bakare ·").first()).toBeVisible();
});

test("the owner sees a live preview of a new invoice number format", async ({ page }) => {
  await signInAndWait(page, "adunni@adunni.test");
  await page.goto("/admin/settings/");

  const invoice = page.getByLabel("Invoice number");
  await invoice.fill("ADU/{YY}/{SEQ}");
  await expect(page.getByText("Include the running number exactly once, for example {SEQ:5}.")).toBeVisible();

  await invoice.fill("ADU/{YY}/{SEQ:4}");
  await expect(page.getByText(/Next one looks like ADU\/\d{2}\/0001/)).toBeVisible();
});
