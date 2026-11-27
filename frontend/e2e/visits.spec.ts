import { expect, test, type Browser, type Page } from "@playwright/test";
import { API, signInAndWait } from "./support";

const BROWSER_HEADERS = { Accept: "application/json", Origin: "http://127.0.0.1:3100", Referer: "http://127.0.0.1:3100/" };

/** Each person on their own device: a separate browser context and session. */
async function personOnOwnPhone(browser: Browser, identifier: string): Promise<Page> {
  const context = await browser.newContext({ viewport: { width: 412, height: 915 }, isMobile: true, hasTouch: true });
  const page = await context.newPage();
  await signInAndWait(page, identifier);

  return page;
}

async function openGuestVisit(desk: Page, guest: string, service: string): Promise<string> {
  await desk.goto("/desk/visits/new/");
  await desk.getByLabel("Guest name").fill(guest);
  await desk.getByLabel("Find a service or product").fill(service);
  await desk.getByRole("button", { name: `Add ${service}` }).click();
  await desk.getByRole("button", { name: "Open visit" }).click();
  await expect(desk.getByRole("heading", { name: guest })).toBeVisible();

  return new URL(desk.url()).searchParams.get("id") ?? "";
}

async function openOnPhone(phone: Page, visitId: string, service: string) {
  await phone.goto(`/staff/visit/?id=${visitId}`);
  await phone.getByRole("button", { name: new RegExp(service) }).click();
}

test("three stylists on their own phones make one record with an equal split, and the second is offered Join", async ({ page, browser }) => {
  test.setTimeout(90_000);
  await signInAndWait(page, "kemi@adunni.test");
  const visitId = await openGuestVisit(page, "Amarachi", "Cornrows (all back)");

  const chiamaka = await personOnOwnPhone(browser, "chiamaka@adunni.test");
  const funmilayo = await personOnOwnPhone(browser, "funmilayo@adunni.test");
  const blessing = await personOnOwnPhone(browser, "blessing@adunni.test");

  await openOnPhone(chiamaka, visitId, "Cornrows");
  await openOnPhone(funmilayo, visitId, "Cornrows");

  await chiamaka.getByRole("button", { name: "I did this" }).click();
  await expect(chiamaka.getByText("Recorded. Thank you.")).toBeVisible();

  await funmilayo.getByRole("button", { name: "I did this" }).click();
  await expect(funmilayo.getByText("Someone has just recorded this. You can join them below.")).toBeVisible();
  await funmilayo.getByRole("button", { name: "Join" }).click();
  await expect(funmilayo.getByText("You are on this service now.")).toBeVisible();

  await openOnPhone(blessing, visitId, "Cornrows");
  await blessing.getByRole("button", { name: "Join" }).click();
  await expect(blessing.getByText("You are on this service now.")).toBeVisible();

  await page.reload();
  const people = page.getByRole("list", { name: "People on this service" });
  await expect(people).toContainText("Chiamaka (main) 33.34%");
  await expect(people).toContainText("Funmilayo 33.33%");
  await expect(people).toContainText("Blessing 33.33%");
  await expect(page.getByText("Done", { exact: true })).toBeVisible();
});

test("a stylist asks for an extra service, it waits for approval, and a supervisor approves it", async ({ page, browser }) => {
  test.setTimeout(90_000);
  await signInAndWait(page, "kemi@adunni.test");
  const visitId = await openGuestVisit(page, "Folake", "Silk press");

  const hauwa = await personOnOwnPhone(browser, "hauwa@adunni.test");
  await hauwa.goto(`/staff/visit/?id=${visitId}`);
  await hauwa.getByRole("button", { name: "Ask for an extra service" }).click();
  const dialog = hauwa.getByRole("dialog");
  await dialog.getByLabel("Which service?").fill("Deep conditioning");
  await dialog.getByRole("button", { name: "Add Deep conditioning treatment" }).click();
  await dialog.getByLabel("Note (optional)").fill("Her ends are dry");
  await dialog.getByRole("button", { name: "Ask for Deep conditioning treatment" }).click();
  await expect(hauwa.getByText("Waiting for approval")).toBeVisible();

  await page.reload();
  await expect(page.getByText("Waiting for approval")).toBeVisible();

  const ngozi = await personOnOwnPhone(browser, "ngozi@adunni.test");
  await ngozi.goto("/admin/approvals/");
  const request = ngozi.getByRole("listitem").filter({ hasText: "Folake" });
  await expect(request).toContainText("Deep conditioning treatment");
  await expect(request).toContainText("Her ends are dry");
  await request.getByRole("button", { name: "Approve" }).click();
  await ngozi.getByRole("dialog").getByRole("button", { name: "Approve" }).click();
  await expect(ngozi.getByText("Nothing waiting for approval.").or(ngozi.getByRole("listitem").filter({ hasText: "Folake" }))).toBeVisible();
  await expect(ngozi.getByRole("listitem").filter({ hasText: "Folake" })).toHaveCount(0);

  await page.reload();
  await expect(page.getByText("Waiting for approval")).toHaveCount(0);
  await hauwa.getByRole("button", { name: /^Notifications/ }).click();
  await expect(hauwa.getByText("Extra service approved: Deep conditioning treatment")).toBeVisible();
});

test("a cashier cannot remove a recorded service, in the screen or through the API", async ({ page, browser }) => {
  await signInAndWait(page, "kemi@adunni.test");
  const visitId = await openGuestVisit(page, "Damilola", "Classic manicure");

  const zainab = await personOnOwnPhone(browser, "zainab@adunni.test");
  await openOnPhone(zainab, visitId, "Classic manicure");
  await zainab.getByRole("button", { name: "I did this" }).click();
  await expect(zainab.getByText("Recorded. Thank you.")).toBeVisible();

  await page.reload();
  await expect(page.getByText("Done", { exact: true })).toBeVisible();
  await expect(page.getByRole("button", { name: "Remove" })).toHaveCount(0);

  const visit = await (await page.request.get(`${API}/orders/${visitId}`, { headers: BROWSER_HEADERS })).json();
  const itemId = visit.data.items[0].id as number;
  const csrf = (await page.context().cookies()).find((cookie) => cookie.name === "XSRF-TOKEN")?.value ?? "";
  const refused = await page.request.post(`${API}/order-items/${itemId}/cancel`, {
    headers: { ...BROWSER_HEADERS, "X-XSRF-TOKEN": decodeURIComponent(csrf), "Content-Type": "application/json" },
    data: { reason: "Customer changed her mind" },
  });
  expect(refused.status()).toBe(403);

  await page.reload();
  await expect(page.getByText("Done", { exact: true })).toBeVisible();
});

test("an employee's view of a visit has no prices or contact details, on screen or from the API", async ({ browser }) => {
  const efe = await personOnOwnPhone(browser, "efe@adunni.test");
  await expect(efe.getByRole("heading", { name: "Today" })).toBeVisible();
  await expect(efe.getByRole("list", { name: "Open visits" })).toBeVisible();
  await expect(efe.getByText(/₦/)).toHaveCount(0);

  const response = await efe.request.get(`${API}/orders`, { headers: BROWSER_HEADERS });
  expect(response.ok()).toBe(true);
  const body = await response.text();
  expect(body).not.toMatch(/kobo|price|"phone|email|subtotal/);
  expect(JSON.parse(body).data.length).toBeGreaterThan(0);
});
