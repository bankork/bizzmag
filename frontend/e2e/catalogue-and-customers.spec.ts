import { expect, test } from "@playwright/test";
import { API, signIn, signInAndWait } from "./support";

const BROWSER_HEADERS = { Accept: "application/json", Origin: "http://127.0.0.1:3100", Referer: "http://127.0.0.1:3100/" };

test("the owner adds a service with variants, changes a price and sees it in the history", async ({ page }) => {
  await signInAndWait(page, "adunni@adunni.test");
  await page.getByRole("navigation", { name: "Owner and admin" }).getByRole("link", { name: "Catalogue" }).click();
  await expect(page.getByRole("heading", { name: "Braids" })).toBeVisible();
  await expect(page.getByText("3 variants from ₦30,000").first()).toBeVisible();

  await page.getByRole("link", { name: "Add service" }).click();
  await page.getByLabel("Name", { exact: true }).fill("Fulani braids");
  await page.getByLabel("Category").selectOption({ label: "Braids" });
  await page.getByRole("switch", { name: "This service has variants" }).click();
  await page.getByLabel("Primary and assistants").check();
  await page.getByLabel("Primary's share").fill("60");
  await page.getByRole("button", { name: "Add service" }).click();

  await expect(page.getByRole("heading", { name: "Fulani braids" })).toBeVisible();
  await page.getByLabel("Variant name").fill("Medium");
  await page.getByLabel("Variant price in naira").fill("35,000");
  await page.getByRole("button", { name: "Add variant" }).click();
  await expect(page.getByLabel("Name of Medium")).toBeVisible();
  await expect(page.getByText("Example on ₦35,000")).toBeVisible();
  await expect(page.getByText("3 people: primary ₦21,000, each assistant ₦7,000")).toBeVisible();

  await page.getByLabel("Price of Medium in naira").fill("38,000");
  await page.getByRole("button", { name: "Save", exact: true }).click();
  await expect(page.getByText("Medium: Price ₦35,000 to ₦38,000")).toBeVisible();
});

test("the desk adds a customer and is warned when the number is already used", async ({ page }) => {
  await signInAndWait(page, "kemi@adunni.test");
  await page.getByRole("navigation", { name: "Front desk" }).getByRole("link", { name: "Customers" }).click();

  for (const name of ["Ireti Balogun", "Ronke Balogun"]) {
    await page.getByRole("button", { name: "Add customer" }).click();
    const dialog = page.getByRole("dialog");
    await dialog.getByLabel("Name").fill(name);
    await dialog.getByLabel("Phone (optional)").fill(name === "Ireti Balogun" ? "0817 000 7070" : "+234 817 000 7070");
    await dialog.getByRole("button", { name: "Add customer" }).click();

    if (name === "Ronke Balogun") {
      await expect(dialog.getByText("This number already belongs to Ireti Balogun. Add anyway?")).toBeVisible();
      await dialog.getByRole("button", { name: "Add anyway" }).click();
    }

    await expect(page.getByRole("heading", { name })).toBeVisible();
    await page.getByRole("link", { name: "All customers" }).click();
  }

  await page.getByLabel("Search customers").fill("08170007070");
  await page.getByRole("button", { name: "Search" }).click();
  await expect(page.getByRole("link", { name: /Ireti Balogun/ })).toBeVisible();
  await expect(page.getByRole("link", { name: /Ronke Balogun/ })).toBeVisible();
});

test("an employee cannot open the catalogue or customers, and the API gives no prices", async ({ page }) => {
  await signIn(page, "chiamaka@adunni.test");
  await expect(page).toHaveURL(/\/staff\/$/);

  for (const path of ["/admin/catalogue/", "/desk/customers/"]) {
    await page.goto(path);
    await expect(page.getByRole("heading", { name: "You do not have access to this page" })).toBeVisible();
  }

  const customers = await page.request.get(`${API}/customers`, { headers: BROWSER_HEADERS });
  expect(customers.status()).toBe(403);

  const catalogue = await page.request.get(`${API}/catalogue/items`, { headers: BROWSER_HEADERS });
  expect(catalogue.status()).toBe(200);
  const body = await catalogue.text();
  expect(body).toContain("Knotless braids");
  expect(body).not.toContain("price_kobo");
});
