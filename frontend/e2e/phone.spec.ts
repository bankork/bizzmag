import { expect, test } from "@playwright/test";
import { signIn } from "./support";

test("the staff area fits a phone screen with the menu at the bottom", async ({ page }) => {
  await signIn(page, "funmilayo@adunni.test");
  await expect(page).toHaveURL(/\/staff\/$/);

  await expect(page.getByRole("navigation", { name: "Staff" })).toBeVisible();
  const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
  expect(overflow).toBeLessThanOrEqual(0);
});
