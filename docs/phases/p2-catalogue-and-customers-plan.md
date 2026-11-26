# Phase P2: Catalogue and Customers, Detailed Plan

**Version:** 1.0
**Date:** 2026-11-26
**Status:** Approved by the product owner on 2026-11-26. Implemented; see the [report](p2-catalogue-and-customers-report.md).

**Approval notes:** Q1 and Q2 use the recommended defaults (variants share their parent's settings; catalogue credit split defaults are Equal or Primary and assistants). Q3: no real price list yet, so the demo list stays.

**Roadmap reference:** Stage 4 v1.2, P2. **Requirements:** SRS v1.2 §4.3 (except FR-CUS-004 to 007 and FR-CUS-010, which need visits), §4.4, FR-ATTR-004 to 008 (catalogue defaults only), BR-041, SEC-021, §7.4, §7.7.3, §14.18 (fixture only), §14.23 (catalogue part).

**Estimate:** about **5 to 6 working days** (1 week, as in the roadmap).

---

## 1. Objective

Let the owner set what the salon sells and at what price, and let the front desk register customers. From this phase on, prices come from the catalogue and are never typed at the till.

## 2. What you will be able to see at the end

1. **Admin › Catalogue.** Categories in your own display order. Under each category, services and products with their price, taxable flag and active status.
2. **Services with variants.** For example "Knotless braids" with Small ₦55,000, Medium ₦40,000 and Large ₦30,000. Each variant has its own price. The parent service holds the shared settings.
3. **Credit split default per service.** Choose **Equal** or **Primary and assistants** with the primary's share (for example 60%). A live example shows how ₦40,000 would be credited to 1, 2 and 3 staff. This is what P3 applies when staff record their work.
4. **Price history.** Every price change is recorded with who changed it, when, and the old and new price, and is shown on the item.
5. **Deactivate, never delete.** A deactivated item disappears from new visits but stays on old records. You can activate it again.
6. **Desk › Customers.** Search by name or phone, add a customer, edit a customer.
7. **Duplicate warning.** Entering a phone number that another customer already has shows "This number belongs to Funke Adeyemi. Add anyway?" with the existing record. Typing the number as 0803..., 234803... or +234803... is treated as the same number.
8. **Remove a customer's personal data** (owner only). Name, phone, email and notes are removed for good; the customer's future visit and payment history stays, labelled "Removed customer".
9. **Employees see services without prices.** The API gives employees the list of services they can record in P3, with no prices.
10. **Demo data.** `app:demo` now loads the full Adunni price list (6 categories, variants and products) and about 150 fictional customers, some sharing a phone number so you can try the warning.

## 3. Work breakdown

### 3.1 Database

All migrations are additive, so a rollback to P1 stays safe. Money is stored in kobo (whole numbers) with `CHECK (... >= 0)`. Business records use `ON DELETE RESTRICT`.

| # | Task | Output |
|---|---|---|
| D1 | `catalogue_categories`: business_id, name, type (service or product), sort_order, is_active, version. UNIQUE(business_id, type, name). | Migration |
| D2 | `catalogue_items`: business_id, category_id, parent_id (variant of), type (service or product), name, description, price_kobo (null only on a parent that has variants), taxable, quantity_allowed, allocation_method (equal or primary_assistant), primary_share_bp (CHECK 1 to 10000), sort_order, is_active, version. Uniqueness is UNIQUE(business_id, category_id, parent_key, name), where `parent_key` is a stored generated column `COALESCE(parent_id, 0)`, because MySQL lets NULL repeat in a unique index. INDEX(business_id, is_active). | Migration |
| D3 | `customers`: business_id, name, phone_e164, email, notes, anonymised_at, anonymised_by, created_by, version. INDEX(business_id, phone_e164), not unique, because a duplicate is a warning (FR-CUS-009). INDEX(business_id, name). | Migration |

### 3.2 Catalogue

| # | Task | Output |
|---|---|---|
| C1 | Category actions: create, rename, reorder, deactivate and activate. A category with active items cannot be deactivated; the message says how many items to move or deactivate first. | Actions, tests |
| C2 | Item actions: create, update, deactivate and activate, with the same version check as Settings (a stale save is refused instead of overwriting someone else's change). Prices are entered in naira with up to 2 decimals and stored in kobo. | Actions, tests |
| C3 | Variants: a variant is an item with a parent. It has its own name, price and active status. It takes the taxable flag, quantity flag and credit split from its parent, so they are set in one place. A parent with variants is not orderable itself; only its variants are. Products do not have variants (FR-SVC-009 is for services). | Actions, tests |
| C4 | Rules: price ≥ ₦0; the quantity flag only on services (products always allow a quantity); a credit split only on services; primary share between 1% and 100%, default 50% (FR-ATTR-007). | Form requests, tests |
| C5 | Audit: every create, change, deactivation and activation is an audit event with old and new values. Price changes feed the item's price history. | Audit, tests |
| C6 | Historical price fixture: a test helper and a demo scenario that change a price after a point in time. P3 uses it to prove that visits keep the old price (§14.18). | Test fixture |

### 3.3 Customers

| # | Task | Output |
|---|---|---|
| K1 | Create and update a customer: name required; phone, email and notes optional. The phone is normalised with the existing `PhoneNumber` helper (+234 by default, other countries with a leading +). | Actions, tests |
| K2 | Duplicate warning: when the normalised phone matches another customer in the same salon, the API answers 409 `duplicate_phone` with the matching customers, and saves only when the request is repeated with `confirm_duplicate: true`. This applies to create and to a phone change on update. Merging is out of scope (FR-CUS-009). | Action, tests |
| K3 | Search by name (part of a word) or by phone (any way of writing it, or the last digits), 20 results per page, active customers first. | Query, tests |
| K4 | Contact privacy: users without `customer.view_contact` receive no phone or email, only a `has_phone` flag (SEC-021). | Resource, tests |
| K5 | `AnonymiseCustomer`, owner only, with a typed confirmation: name becomes "Removed customer", and phone, email and notes are cleared. Anonymised customers cannot be edited and are left out of search. | Action, tests |
| K6 | Personal data in the audit log: customer audit events record **which** fields changed, not their values, for name, phone, email and notes. Otherwise anonymisation would leave the old phone number in the log. | Audit, tests |

### 3.4 Employee view (BR-041)

| # | Task | Output |
|---|---|---|
| E1 | One catalogue list for everyone who is signed in. Price fields are present only for users with `order.view_all` or `catalogue.manage`. For anyone else the fields are **absent** from the JSON, not null. | Resource, tests |
| E2 | Customers are not visible to employees at all (they lack `customer.view`). | Permission matrix |

### 3.5 Web app

| # | Task | Output |
|---|---|---|
| W1 | Admin › Catalogue: categories in order, with items and variants under each. Filters for services or products, active or inactive, and a search box. | Page |
| W2 | Item editor: name, category, description, price or variants, taxable, quantity allowed, credit split with a live example, active. A price history panel. | Page |
| W3 | Desk › Customers: search, add and edit, with the duplicate prompt. The owner and supervisors reach it from their menu too. | Page |
| W4 | Customer profile: details, and an owner-only "Remove personal data" button with a typed confirmation. Visits appear here from P3. | Page |
| W5 | Playwright: the owner creates a service with variants and changes a price; the cashier adds a customer, sees the duplicate warning and confirms it; an employee cannot open either screen. | Browser tests |

### 3.6 Demo data and documentation

| # | Task | Output |
|---|---|---|
| M1 | `app:demo` loads `Data/catalogue.php` into the database, safe to run twice. | Seeder |
| M2 | About 150 fictional customers with Nigerian names and `000`-block numbers, a few sharing a number, some with no phone. | Seeder |
| M3 | `docs/demo-data.md` "Try it" steps for P2, `docs/permissions.md` regenerated, OpenAPI updated. | Docs |

## 4. API changes

All under `/api/v1`, cookie session, scoped to the current business.

| Endpoint | Permission |
|---|---|
| `GET /catalogue/categories` | Signed in |
| `POST /catalogue/categories`, `PATCH /catalogue/categories/{id}`, `POST /catalogue/categories/{id}/deactivate`, `/activate`, `POST /catalogue/categories/reorder` | `catalogue.manage` |
| `GET /catalogue/items?filter[type]=&filter[category]=&filter[active]=&filter[q]=` and `GET /catalogue/items/{id}` | Signed in (prices only with `order.view_all` or `catalogue.manage`) |
| `POST /catalogue/items` (with `parent_id` for a variant), `PATCH /catalogue/items/{id}`, `POST /catalogue/items/{id}/deactivate`, `/activate` | `catalogue.manage` |
| `GET /catalogue/items/{id}/history` | `catalogue.manage` |
| `GET /customers?filter[q]=`, `GET /customers/{id}` | `customer.view` (contact fields only with `customer.view_contact`) |
| `POST /customers`, `PATCH /customers/{id}` | `customer.manage` |
| `POST /customers/{id}/anonymise` | `customer.manage` and the Owner role |

No new permissions. All of the above already exist in the P1 catalogue.

## 5. Tests and acceptance criteria

### Tests that must pass in CI

1. Permission matrix: every new endpoint × every default role, allow or deny, and every refusal recorded.
2. Business isolation: a second salon cannot read, change or find the first salon's categories, items or customers, including by guessing an ID.
3. Catalogue rules: negative price, a credit split on a product, a primary share outside 1 to 100%, an orderable parent with variants, duplicate names, a stale version.
4. Variants take their parent's taxable flag, quantity flag and credit split.
5. Price history: old and new price, who and when.
6. Employee view: price keys absent from the JSON for an employee, present for a cashier.
7. Duplicate warning across all ways of writing the number, on create and on update, and saving after confirmation.
8. Search by name, by phone in any format and by the last digits.
9. Contact fields absent without `customer.view_contact` (tested with a custom role).
10. Anonymisation: owner only, values removed, no personal values left in the audit log, record no longer editable or searchable.
11. Demo: runs twice without duplicates; the counts match.
12. Frontend unit tests for the naira input, the credit split example and the duplicate prompt; the Playwright journeys in W5.
13. Pint, Larastan, lint, build, static export check and the em-dash check.

### Acceptance criteria (from the roadmap)

- The owner configures the real price list, including variants such as "Knotless braids, Medium" and products such as attachment hair packs.
- The desk registers a customer and is warned when the phone number already exists.

## 6. Security review

- Prices and customer contact details never reach users who may not see them; the fields are left out on the server, not hidden in the browser (BR-041, SEC-021).
- Anonymisation is owner only, cannot be undone, is audited, and leaves no personal values in the audit log.
- Catalogue changes are owner only by default (`catalogue.manage`) and audited.
- Every query is scoped to the business, and the isolation tests cover each new table.
- Search input is bound as parameters, never built into SQL. Notes are shown as plain text, so nothing typed there can run in the browser.

## 7. Risks and mitigation

| Risk | Mitigation |
|---|---|
| Your real price list is not available yet | The demo list is used until then. You can enter or edit the real one yourself on the Catalogue screen; bulk import arrives in P6. |
| A change of mind on how variants work after P3 has used them | Settle it now (Q1). |
| A busy desk ignores the duplicate warning | The warning shows the existing customer's name, so picking the existing record is one tap away. Merging stays out of scope, as agreed. |

## 8. Not in this phase

- Customer visit, invoice and payment history (FR-CUS-010): the screen space is there, filled from P3 and P5.
- Guest visits and linking a guest to a customer (FR-CUS-003 to 007): P3, with visits.
- Bulk import of customers and the price list: P6.
- Staff screen listing services: P3, where employees record work. The API part is in this phase.
- Merging duplicate customers: out of MVP scope.

## 9. Questions before I start

1. **Variants share their parent's settings.** I plan that a variant has only its own name and price, and takes the taxable flag, quantity flag and credit split from its parent. The alternative is to let each variant override them, which is more flexible but more to check on every edit. Recommended: share.
2. **Custom percentages as a catalogue default.** SRS FR-ATTR-005 allows custom percentages as a catalogue default. A fixed split such as 60 / 25 / 15 only works when exactly that many staff take part, so I plan to offer **Equal** and **Primary and assistants** as defaults, and leave custom percentages to supervisors per visit (`allocation.override`, P3). Recommended: yes. If you need fixed custom splits, tell me which services and how they should behave with a different number of staff.
3. **Your real price list.** If you have it, send it in any form (a photo of the price board is fine) and I will load it as the demo catalogue's replacement. Otherwise the demo list stays.

Open from P1, unchanged unless you say otherwise: the 20-failures-per-address sign-in rule stays; trusted proxies stay unset until you tell me the API sits behind Cloudflare or another proxy; HSTS is planned for P6.

## 10. Definition of Done

- The acceptance criteria are met and shown with screenshots in the P2 report.
- All tests in §5 pass locally, and GitHub Actions passes after your push.
- The demo data, permissions reference and API description are updated.
- A commit plan with ready commit messages is in the report.
- You approve P2 before the P3 plan is written.
