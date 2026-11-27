# Phase P2: Catalogue and Customers, Report

**Date:** 2026-11-26
**Plan:** [p2-catalogue-and-customers-plan.md](p2-catalogue-and-customers-plan.md) v1.0 (approved with the recommended defaults: variants share their parent's settings; catalogue credit splits are Equal or Primary and assistants; demo price list until the real one arrives)
**Branch:** `development`
**Status:** Approved by the product owner on 2026-11-26 (commits 89d5fb2, 5a5c42d, 4aeefe3).

---

## 1. What you can do now

Prices come from the catalogue, set by the owner. Customers are registered once and found again by name or phone.

| # | Try this | Screenshot |
|---|---|---|
| 1 | As the owner, open **Admin > Catalogue**. Services and products by category, in your order, with prices, variants and how credit is shared. | [services](p2-screenshots/01-catalogue-services.png), [products](p2-screenshots/02-catalogue-products.png) |
| 2 | **Categories**: add, rename, move up or down, deactivate. A category that still has active items cannot be deactivated. | [categories](p2-screenshots/03-categories.png) |
| 3 | Open **Knotless braids**: each variant has its own price; tax, quantity and credit split are set once on the service. Change a price and it appears in the **change history** with who and when. | [variants and history](p2-screenshots/04-service-with-variants-and-history.png) |
| 4 | **Add service**: choose Equal or Primary and assistants with the primary's share, and see a worked example for 1, 2 and 3 staff. | [new service](p2-screenshots/05-new-service-credit-split.png) |
| 5 | As the cashier, open **Front desk > Customers**: search by name, full number in any form, or the last digits. | [customers](p2-screenshots/06-desk-customers.png) |
| 6 | Add a customer with a number someone already has: the desk sees who has it, can open that customer, or choose **Add anyway**. | [duplicate warning](p2-screenshots/07-duplicate-warning.png) |
| 7 | As the owner, open a customer: **Remove personal data** (type REMOVE). The record stays for visits and payments as "Removed customer". | [customer, owner view](p2-screenshots/08-customer-profile-owner.png) |
| 8 | As an employee: Catalogue and Customers are refused, and the services list the API gives them has no prices at all. | [refused](p2-screenshots/09-employee-refused-catalogue.png) |

**On your machine:** your local database does not have the two new tables yet. Run `php artisan migrate` (or `php artisan app:demo` to also load the price list and the 150 customers). Then the API documentation at https://bizzmag-api.test/docs/api shows the new endpoints.

## 2. Results

All run on this machine against MySQL 8.4.

| Check | Result |
|---|---|
| Backend tests (unit, feature, architecture, concurrency) | **365 passed**, 96,012 assertions (P1: 303) |
| Pint (code style), Larastan level 6 | Pass, 0 errors |
| OpenAPI contract up to date | Pass (32 paths, P1: 19) |
| Migrations roll back and re-apply cleanly | Pass |
| Frontend lint, type check | Pass |
| Frontend unit tests | **69 passed** (P1: 54) |
| Static build and export check | Pass |
| No em-dashes in the design | Pass |
| **Browser tests** (Chromium, real API, real cookie sessions) | **13 passed** (P1: 10) |
| Container deployment verification (cPanel-like, VPS-like) | **Runs on GitHub after your push.** P2 adds only migrations, which the deploy script already runs. |

## 3. Acceptance criteria

| Criterion (roadmap P2) | Proven by |
|---|---|
| The owner configures the real price list, including variants such as "Knotless braids, Medium" and products such as attachment hair packs | `ItemTest` (17 tests); `DemoCommandTest` loads the whole demo list; browser test "the owner adds a service with variants, changes a price and sees it in the history" |
| The desk registers a customer and is warned when the phone number already exists | `CustomerTest` (the warning for 4 ways of writing the same number, on create and on edit); `AddCustomerDialog` unit tests; browser test "the desk adds a customer and is warned when the number is already used" |
| Price fields are absent from the employee view | `EmployeeCatalogueViewTest` (every level, list and detail, and a custom role); browser test checks the raw API response |
| Permission rows | `PermissionMatrixTest`: 18 new endpoints × 4 roles + signed out |
| Historical price snapshot fixture, used in P3 | `BuildsCatalogue::changePrice()` changes a price through the real, audited action |

## 4. Security review

| Requirement | How it is met | Test |
|---|---|---|
| BR-041, §14.23 No prices to employees | `price_kobo` is **left out** of the JSON, not set to null, for anyone without `order.view_all` or `catalogue.manage`. Employees only see items that can be chosen for a visit. | `EmployeeCatalogueViewTest`, browser test |
| SEC-021 Contact details | Phone and email are left out for anyone without `customer.view_contact`, and phone search is switched off for them, so a number cannot be confirmed by guessing | `CustomerTest::test_without_contact_permission_...` |
| SEC-021 Removal on request | Owner only (see §6), typed confirmation, cannot be undone, audited. Removed customers cannot be edited, are not found by search and do not trigger the duplicate warning. | `AnonymiseCustomerTest` |
| SEC-021 Nothing personal left in the log | Customer audit events record **which** fields changed, never their values, so removing a customer really removes their data | `CustomerTest::test_the_audit_log_names_changed_fields_but_never_their_values` |
| SEC-003 Business scope | All three new tables are business-owned with the fail-closed scope. Another salon's records answer "not found" by ID, and cannot be used as a category, parent or reorder target. | `BusinessIsolationTest` (2 new tests) |
| SEC-002, SEC-016 Permissions and refusals | Every new route is in the endpoint catalogue with its permission; every refusal is recorded | `PermissionMatrixTest`, `RouteAccessTest` |
| SEC-015 Overwrites | Categories, items and customers carry a version; a save based on stale data is refused (409 STALE) | `CategoryTest`, `ItemTest`, `CustomerTest` |
| SEC-007, SEC-008 Input | Form Requests on every write; prices from ₦0 to ₦100,000,000; search input escaped for LIKE; notes shown as plain text | Rule tests in `ItemTest` and `CustomerTest` |

## 5. Plan tasks

All plan tasks D1 to M3 are done. Notes:

| Task | Note |
|---|---|
| D2 | Items have a `has_variants` flag, so "a service whose variants are what gets chosen" is explicit and checked by the database (a price is required unless the item has variants). |
| C3 | Variants keep a copy of the parent's taxable flag, quantity flag and credit split, updated in the same transaction whenever the parent changes. In P3 a visit can then copy one row as its snapshot. |
| C4 | The primary's share must be between 1% and 100%; 50% when not given. |
| K3 | "Active customers first" from the plan was dropped: customers have no active state. Removed customers are left out instead. |
| W3 | Customers are under **Front desk** and also under **Admin**, so the owner does not have to switch areas. |
| M2 | 150 customers from a fixed seed (the same on every machine). 6 numbers are shared by two customers, 12 customers have no phone, and none uses a staff member's number. |

## 6. Where I changed the plan

1. **No change in the end to how "owner only" works.** While building, I told you I might add a new owner-only permission, `customer.anonymise`. I did not: that would change the approved SRS permission table. I built what the plan and Stage 3 say instead: the route needs `customer.manage` **and** the Owner role, checked by a small `owner` route guard that recognises the role by its fixed system key. A supervisor who manages customers is refused and the refusal is recorded. The permission matrix and route test know about owner-only routes, so this cannot be dropped by accident.
2. **The demo has 7 categories, not 6** (5 for services and 2 for products), because that is what the P0 price list file holds.
3. **No new packages** were added to the frontend or the backend.

## 7. Things you should know

1. **Running `app:demo` again puts the demo prices back** on the demo items. Customers are added only once. `--fresh` resets everything.
2. **Customer notes are for preferences only.** The form says so. Notes are personal data and are cleared when a customer is removed.
3. **Customer visit history** has a place on the customer page and fills in from P3 (visits) and P5 (payments).
4. **Still open from P1, unchanged:** the 20-failures-per-address sign-in rule stays; trusted proxies stay unset until you tell me the API is behind Cloudflare or another proxy; HSTS is planned for P6.

## 8. Commit plan

Nothing is staged. Run these from the repository root, in order, then push.

1. **Backend**

   ```bash
   git add backend
   git commit -m "feat(backend): catalogue with variants and credit splits, customers with duplicate warning

   - Categories, services, products and variants with prices in kobo,
     taxable and quantity flags, and Equal or Primary and assistants
     credit splits; variants share their service's settings
   - Deactivate, never delete; version checks; every change audited,
     which gives each item its price history
   - Prices left out of the catalogue for staff without visit prices
   - Customers with E.164 phones, search by name or any form of the
     number, and a duplicate-phone warning that needs confirmation
   - Owner-only removal of a customer's personal data; audit records
     changed field names, never personal values
   - Demo salon gets the full price list and 150 fictional customers
   - 365 tests: permission matrix, two-salon isolation, owner-only route"
   ```

2. **Frontend**

   ```bash
   git add frontend
   git commit -m "feat(frontend): catalogue and customer screens

   - Admin catalogue: categories in order, services and products,
     variants with their own prices, credit split with a worked example,
     and the change history
   - Customers under Front desk and Admin: search, add and edit, the
     duplicate warning, and owner-only removal of personal data
   - Phone and email fields hidden without permission to see them
   - Browser tests for the owner, the desk and an employee"
   ```

3. **Docs**

   ```bash
   git add docs
   git commit -m "docs: P2 plan and report, API description, demo and permissions guides

   - Phase 2 plan (approved) and report with screenshots
   - P1 plan and report marked approved
   - OpenAPI description with the catalogue and customer endpoints
   - Phase 2 steps in the demo guide; owner-only actions and price
     visibility in the permissions reference"
   ```

4. **Check and push**

   ```bash
   git status            # should say "nothing to commit, working tree clean"
   git log --oneline -4
   git push
   ```

## 9. Approval

Please review, push, and share the GitHub Actions results (CI: Backend, Frontend, Browser tests; Verify deployment: cPanel and VPS). When you approve P2, I will write the P3 plan (Visits and staff service recording) for your approval before building anything.
