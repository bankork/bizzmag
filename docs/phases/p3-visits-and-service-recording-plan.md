# Phase P3: Visits and Staff Service Recording, Detailed Plan

**Version:** 1.0
**Date:** 2026-11-26
**Status:** Approved by the product owner on 2026-11-26. Implemented; see the [report](p3-visits-and-service-recording-report.md).

**Approval notes:** all four questions use the recommended defaults. Q1: someone who records work but does no salon work picks the primary and is not credited. Q2: staff see the customer's name only. Q3: the usability check and the rehearsal are run by the owner with the steps provided. Q4: a recorded service uses the credit split snapshot taken when the item was added to the visit.

**Roadmap reference:** Stage 4 v1.2, P3. **Requirements:** SRS v1.2 §4.5, §4.6, §4.7 (except the stored values at finalisation, FR-ATTR-012, which arrive with invoices in P4), §4.8 additional services (FR-ADD-001 to 007 and 009 to 010), FR-CUS-003 to 007, FR-CUS-010 (visits part), §4.22 for the events that exist in this phase, §6.1 to §6.3 except finalisation and void, §7.7, BR-001 to 006, BR-009 to 010, BR-040, BR-041, UI-010. **Scenarios:** §14.1 (allocation part), §14.2, §14.3 (up to "finalisation is blocked", which is P4), §14.14, §14.18, §14.19 (up to R8, which is shown in P4), §14.22, §14.23, §14.24 (corrections and additional services).

**Estimate:** about **13 to 15 working days** (3 weeks, as in the roadmap). This is the largest phase so far.

---

## 1. Objective

Every service done in the salon gets its own confirmed record, made by the people who did it. The front desk opens a visit; staff record their work on their own phones, together when they worked together; extra services wait for a supervisor's approval. This is the first phase that directly closes the revenue leak.

## 2. What you will be able to see at the end

1. **Desk › New visit.** Pick a customer (search, or add one on the spot) or start a guest visit with an optional name and phone. Add services and products from the catalogue, choosing the variant (for example "Knotless braids, Medium"), with a quantity where the item allows it. Optionally assign the staff expected to do each service. The visit gets its reference, for example `V-261126-007`.
2. **Desk › Visits board.** Every open visit, oldest first, with how long it has been open, and for each service whether it is waiting, done or disputed. It refreshes by itself.
3. **Visit page (desk).** Items with their prices and the running subtotal, who is assigned, who recorded each service and how credit is split. From here the desk adds items, removes a service nobody has done yet, assigns staff and links a guest visit to a customer. A supervisor can also change a price with a reason, or cancel the whole visit.
4. **Staff › Today** on a phone. Open visits with the employee's own assignments first. No prices, no phone numbers, no bill.
5. **Three taps to record work.** Open the visit, tap the service, tap **I did this**. One more step adds the colleagues who worked on it with you.
6. **Two people, one service.** If a colleague has already recorded the service, the second person is told so and offered **Join**. This holds even when both tap at the same moment.
7. **Three on one braid.** Three employees end up on one record with an equal split (33.34%, 33.33%, 33.33%), or the split set on the service in the catalogue.
8. **Dispute.** A named employee who disagrees (for example "I was not on this") disputes it with a reason. A supervisor dismisses the dispute, corrects the record or cancels it. Corrections keep the original visible, marked "Replaced".
9. **Extra service requested by staff.** A stylist on the floor requests an extra service ("customer also wants a wash"). It shows as **Waiting for approval**, is not billable, and appears in the supervisor's **Approvals** queue. The requester is told the decision.
10. **Cashier limits.** A cashier cannot cancel a service that has been recorded, in the screen or directly through the API. That needs a supervisor.
11. **Notifications bell.** Approvals waiting, decisions, being added to or removed from a record, and disputes or corrections that concern you. They refresh at least every 30 seconds and never contain amounts or contact details.
12. **Customer page** now lists the customer's visits.
13. **Demo data.** `app:demo` adds a realistic day at Adunni: open visits in every state (waiting, done by one, done by three, disputed, extra service waiting) so you can try every screen straight away.

## 3. Work breakdown

### 3.1 Database

All migrations are additive. Money is kobo with `CHECK (>= 0)`, shares are basis points with `CHECK (1 to 10000)`, and business records use `ON DELETE RESTRICT` (SRS §7.7.3).

| # | Task | Output |
|---|---|---|
| D1 | `service_orders`: business_id, reference, customer_id (nullable), guest_name, guest_phone_e164, status (open, finalised, cancelled), opened_at, opened_business_day, finalised_at, late_window_ends_at, cancelled_at, cancelled_by, cancel_reason, created_by. UNIQUE(business_id, reference); INDEX(business_id, status, opened_business_day); INDEX(customer_id). The finalisation columns are created now and used from P4. | Migration |
| D2 | `order_items`: service_order_id, catalogue_item_id, type, name_snapshot, unit_price_kobo, taxable_snapshot, quantity, allocation_method_snapshot, primary_share_bp_snapshot, override_price_kobo, override_reason, override_by, override_at, status (requested, pending_approval, approved, rejected, cancelled), origin (order, desk_add, additional_request), requested_by, request_note, decided_by, decided_at, decision_reason, cancelled_by, cancelled_at, cancel_reason. INDEX(service_order_id, status). | Migration |
| D3 | `order_item_assignments`: order_item_id, employee_id, assigned_by. UNIQUE(order_item_id, employee_id). | Migration |
| D4 | `executions`: order_item_id, status (confirmed, disputed, superseded, cancelled), submitted_by, submitted_at, late, supersedes_id, allocation_method, primary_share_bp, override_reason, overridden_by, dispute_reason, disputed_by, disputed_at, resolution_reason, resolved_by, resolved_at, and the stored generated column `active_item_key = IF(status IN ('confirmed','disputed'), order_item_id, NULL)` with a **unique index**. | Migration |
| D5 | `execution_contributors`: execution_id, employee_id, is_primary, share_bp, joined_via (submit, join, edit, override, correction). UNIQUE(execution_id, employee_id); CHECK share_bp 1 to 10000. | Migration |
| D6 | `notifications`: business_id, user_id, type, title, body, link, data (JSON, no amounts or contact details), read_at, created_at. INDEX(user_id, read_at, created_at). Kept for 90 days by a scheduled task. | Migration |

### 3.2 Orders (visits)

| # | Task | Output |
|---|---|---|
| O1 | `OrderLock`: the P0 contract implemented with `SELECT ... FOR UPDATE` on the order row. Every command in this phase takes the lock first and checks afterwards (§7.7.2). | Implementation, tests |
| O2 | `CreateOrder`: customer or guest (guest name and phone optional, phone normalised), one or more active, orderable items, optional assignments. Reference from the daily sequence and the `numbering.order` format from P1. Each item keeps a snapshot of name (with the variant), type, price, taxable flag and credit split, so later catalogue changes never touch it (§14.18). | Action, tests |
| O3 | `AddItem` (`order.item.add`): adds a Requested item to an Open order. Audited. | Action, tests |
| O4 | `AssignStaff` (`order.assign`): set the expected employees for a service item. Only active employees who do salon work can be assigned. Assigned staff are notified. | Action, tests |
| O5 | `CancelUnexecutedItem` (`order.item.cancel_unexecuted`): reason required; refused when the item has an active execution. A recorded service can only be cancelled through E8 by a supervisor (§14.2). | Action, tests |
| O6 | `OverridePrice` (`order.item.price_override`): new price and a mandatory reason; the original snapshot stays alongside. Shown in P4 as R8. | Action, tests |
| O7 | `CancelOrder` (`order.cancel`): reason required; cancels its items; if any service has an active execution, `execution.correct` is also needed and those executions are cancelled with the same reason. The "no money paid" guard is written now and has something to check from P5. | Action, tests |
| O8 | `AssociateCustomer` (`customer.associate`): link a guest visit to a customer. Audited (FR-CUS-006, 007). | Action, tests |
| O9 | Order values calculated on the server: line value = price (or override) × quantity, subtotal of billable items. Discounts and tax arrive in P4. | Calculator, tests |

### 3.3 Execution (recorded work)

| # | Task | Output |
|---|---|---|
| E1 | `SubmitExecution` (`execution.submit`, `Idempotency-Key` required): service items in status Requested, Approved or Waiting for approval, on an Open order. The submitter is a contributor and the primary; named colleagues are added. Shares come from the item's credit split snapshot through the P0 `AllocationCalculator`. A second submission waits for the lock, then gets `409 EXECUTION_EXISTS` with "join". The unique `active_item_key` is the database backstop. A retry with the same key returns the first result. | Action, tests |
| E2 | `JoinExecution` (`execution.join`): add yourself; shares are recalculated with the method. | Action, tests |
| E3 | `EditContributors` (submitter only, Open order): add or remove colleagues; the submitter cannot remove themselves. Added and removed employees are notified. | Action, tests |
| E4 | `OverrideAllocation` (`allocation.override`): change the method, the percentages (Custom, 2 decimals, totalling exactly 100%) or the primary, with a reason. Segregation of duties against the submitter. | Action, tests |
| E5 | `DisputeExecution` (`execution.dispute` for a named contributor, or `execution.correct`): reason required; status Disputed. | Action, tests |
| E6 | `DismissDispute` (`execution.correct`): back to Confirmed, reason required, segregation of duties. | Action, tests |
| E7 | `CorrectExecution` (`execution.correct`): a new Confirmed execution replaces the old one, which becomes Superseded and stays viewable. Contributors, primary and split may change. Reason required, segregation of duties. | Action, tests |
| E8 | `CancelExecution` and `CancelExecutedItem` (`execution.correct`): reason required, segregation of duties. Cancelling the execution makes the service "waiting" again; cancelling the executed item cancels both. These live in the Execution module because the Orders module may not depend on Execution (module rule from P0). | Actions, tests |
| E9 | Contributor rules: active employees with salon work only, no duplicates, exactly one primary. A disabled employee can no longer be added but stays on past records (§14.22). | Rules, tests |
| E10 | Segregation of duties (BR-040): the person correcting, cancelling, resolving a dispute or overriding a split must not be the submitter. The Owner is exempt, and the audit event records the exemption. | Guard, tests |
| E11 | Late window: the check is built now (Open order, or within `windows.late_submission_hours` after finalisation, otherwise `execution.submit_late`). It takes effect when finalisation exists in P4. Tested now with a finalised order fixture. | Guard, tests |
| E12 | Every transition writes its audit event inside the same transaction (§6.3 names). Notifications are sent only after commit. | Audit, tests |

### 3.4 Additional services

| # | Task | Output |
|---|---|---|
| A1 | `RequestAdditional` (`additional.request`): an employee requests a catalogue service for an Open order, with an optional note. It creates an item **Waiting for approval** at the catalogue price, and notifies users with `additional.decide`. | Action, tests |
| A2 | `ApproveAdditional` and `RejectAdditional` (`additional.decide`): reason required for a rejection; segregation of duties against the requester; the requester is notified. Approved items are billable; rejected items never are. | Actions, tests |
| A3 | `WithdrawAdditional`: the requester (or `order.item.cancel_unexecuted`) before a decision. | Action, tests |
| A4 | Approvals queue: pending additional services across all open visits, oldest first. The same queue takes over-limit discounts in P4. | Query, tests |

### 3.5 Notifications

| # | Task | Output |
|---|---|---|
| N1 | Listeners for the events in FR-NOT-002 that exist in this phase: additional service waiting, decision on a request, added or removed as a contributor, assigned to a service, execution disputed, corrected or cancelled. | Listeners, tests |
| N2 | `GET /notifications` (list with unread count) and mark as read. The web app polls every 30 seconds while open. Texts never contain amounts or contact details (FR-NOT-004); a test checks this for every type. | Endpoints, tests |
| N3 | Scheduled clean-up of notifications older than 90 days, safe to run more than once. | Scheduler, tests |

### 3.6 What employees receive (BR-041, §14.23)

| # | Task | Output |
|---|---|---|
| V1 | One order presenter with two shapes. With `order.view_all`: prices, overrides, subtotal, customer contact (with `customer.view_contact`). Without: reference, customer or guest **name**, time open, items with name, quantity, status, assignments and who recorded them. Price, contact and bill fields are **absent** from the JSON, not null. | Presenter, tests |
| V2 | Employees without `order.view_all` see only Open orders; a cancelled or past visit by ID answers "not found". | Query, tests |
| V3 | A staff directory endpoint (names of active employees who do salon work) for choosing colleagues and assignments. No phone numbers or emails. | Endpoint, tests |

### 3.7 Web app

| # | Task | Output |
|---|---|---|
| W1 | Desk › New visit: customer search with inline add (the P2 dialog), or guest; item picker by category with variants and quantity; assignment per service; review and open. | Page |
| W2 | Desk › Visits board: open visits with age and per-service status, refreshed every 15 seconds. Search by reference or name. | Page |
| W3 | Visit page (desk and admin): items, prices, subtotal, status, contributors and split; add item, remove unexecuted service, assign, link to customer; supervisor actions: change price, cancel visit, resolve dispute, correct, cancel a recorded service, override split. | Page |
| W4 | Staff › Today (mobile first): own assignments first, then the other open visits; visit page with **I did this** (3 taps), add colleagues, **Join**, dispute, request extra service. The submit button sends one idempotency key per tap intent, so a double tap or a retry on a weak signal records once. | Pages |
| W5 | Staff › My work: the employee's own recorded services, newest first, with status. No prices. | Page |
| W6 | Admin › Approvals: the queue with approve and reject. | Page |
| W7 | Notifications bell in every area, with the unread badge and the list. | Component |
| W8 | Customer page: the customer's visits (reference, date, status; prices for `order.view_all`). | Page |
| W9 | Playwright, with separate browser sessions for separate people: three employees record one braid and see an equal split; a second submission gets Join; an employee requests an extra service and a supervisor approves it; a cashier cannot cancel a recorded service; the employee's API response has no prices or contacts. | Browser tests |

### 3.8 Demo data and documentation

| # | Task | Output |
|---|---|---|
| M1 | `app:demo` adds today's open visits in every state, with Adunni's staff as contributors. Safe to run twice. | Seeder |
| M2 | `docs/demo-data.md` "Try it" steps for P3, including a script for the live rehearsal; `docs/permissions.md` updated; OpenAPI updated. | Docs |
| M3 | Short staff walkthrough notes (one page, plain language, with screenshots from a phone) for the rehearsal and the usability check. | Doc |

## 4. API changes

All under `/api/v1`, cookie session, scoped to the current business. • = `Idempotency-Key` required.

| Endpoint | Permission |
|---|---|
| `GET /orders?filter[status]=&filter[q]=`, `GET /orders/{id}` | `order.view_open` (Open orders, restricted fields) or `order.view_all` (all, with prices) |
| `POST /orders` | `order.create` |
| `POST /orders/{id}/items` | `order.item.add` |
| `POST /orders/{id}/cancel` | `order.cancel` (+ `execution.correct` when a service is recorded) |
| `POST /orders/{id}/customer` | `customer.associate` |
| `PUT /order-items/{id}/assignments` | `order.assign` |
| `POST /order-items/{id}/cancel` | `order.item.cancel_unexecuted`, or `execution.correct` when recorded |
| `POST /order-items/{id}/price-override` | `order.item.price_override` |
| `POST /orders/{id}/additional-requests` | `additional.request` |
| `POST /order-items/{id}/approve`, `/reject` | `additional.decide` |
| `POST /order-items/{id}/withdraw` | The requester, or `order.item.cancel_unexecuted` |
| `GET /approvals?filter[type]=additional_service` | `additional.decide` |
| `POST /order-items/{id}/executions` • | `execution.submit` |
| `POST /executions/{id}/join` | `execution.join` |
| `PUT /executions/{id}/contributors` | The submitter |
| `POST /executions/{id}/allocation` | `allocation.override` |
| `POST /executions/{id}/dispute` | `execution.dispute` (named contributor) or `execution.correct` |
| `POST /executions/{id}/dismiss-dispute`, `/correct`, `/cancel` | `execution.correct` |
| `GET /executions/mine` | `execution.submit` or `execution.join` |
| `GET /staff-directory` | `order.view_open` |
| `GET /customers/{id}/orders` | `order.view_all` |
| `GET /notifications`, `POST /notifications/read` | Signed in |

No new permissions. All of the above already exist in the P1 catalogue. Error codes added: `EXECUTION_EXISTS` (with `can_join`), `ORDER_NOT_OPEN`, `ITEM_NOT_ACTIVE`, `ITEM_RECORDED`, `NOT_A_CONTRIBUTOR`, `CONTRIBUTOR_NOT_ELIGIBLE`, `SEGREGATION_OF_DUTIES`, `LATE_WINDOW_CLOSED`, `ALREADY_DECIDED`.

## 5. Tests and acceptance criteria

### Tests that must pass in CI

1. Permission matrix: every new endpoint × every default role and signed out, allow or deny, every refusal recorded. The submitter-only and requester-only routes are covered by their own tests.
2. Business isolation: a second salon cannot read, change or find the first salon's visits, items, executions or notifications, including by guessing an ID; its employees cannot be added as contributors.
3. Transition tests for every row of SRS §6.1 to §6.3 in scope: the guards, the side effects and the audit event, and that an illegal transition is refused.
4. **Concurrency (§14.14):** two real database connections submit the same item at the same moment; exactly one execution exists and the other gets `EXECUTION_EXISTS`. Also: join racing a correction; cancelling an item racing a submission.
5. **Database backstop:** inserting a second active execution directly is rejected by the unique index.
6. Idempotency: the same key replays the first result; the same key with a different body returns `IDEMPOTENCY_MISMATCH`.
7. Allocation (§14.1): three contributors on ₦20,000 give 3334, 3333 and 3333 basis points; Primary and assistants with 60%; shares recalculated on join and removal; Custom must total exactly 100.00% with no one at 0.
8. Contributor eligibility (§14.22): a disabled employee, someone who does not do salon work, a duplicate, and an employee of another salon are refused; past records still show the disabled employee.
9. Confirmed protection (§14.2): a cashier cannot cancel a recorded service through the API; a supervisor's correction supersedes the original, which stays viewable; every step is audited.
10. Segregation of duties (§14.24): a supervisor cannot correct, cancel, dismiss or override their own submission, or approve their own request; the Owner can, and the audit event is flagged.
11. Additional services (§14.3): a request creates a Waiting-for-approval item that is not billable; approve, reject with a reason, withdraw; an execution on a pending item is allowed.
12. Historical pricing (§14.18): a visit keeps ₦20,000 after the catalogue changes to ₦25,000 (using the P2 fixture); a new visit uses ₦25,000.
13. Price override: a cashier is refused; a supervisor needs a reason; the original stays.
14. Employee data (§14.23): price, contact and bill keys are absent from every order and execution response an employee can receive, checked recursively; employees cannot see visits that are not open.
15. Notifications: the right people receive each event; nothing is sent when the transaction rolls back; no amounts or phone numbers in any text.
16. Late-window guard with a finalised-order fixture (§14.13 is proven end to end in P4).
17. Demo: runs twice without duplicates.
18. Frontend unit tests for the item picker, the contributor chooser and the split preview; the Playwright journeys in W9.
19. Pint, Larastan, lint, build, static export check and the em-dash check.

### Acceptance criteria (from the roadmap)

- Three employees on one braid, using their own phones, produce one execution with three contributors and an equal split.
- A second submission returns "Join".
- An additional service waits for approval.
- A cashier cannot cancel an executed item.

## 6. Security review

- Employees never receive prices, customer contact details or bill data; fields are left out on the server (BR-041, §14.23), and the tests walk every key of every response.
- Recorded work is never deleted or edited in place; corrections supersede and keep the original (BR-005, BR-006).
- Segregation of duties on corrections, dispute decisions, split overrides and approvals, with the Owner exemption recorded (BR-040).
- Every command locks the visit before it checks anything, and the database refuses a second active record for a service even if the code were wrong.
- The submit endpoint is idempotent, so a weak connection or a double tap cannot create two records.
- Notifications carry no amounts or contact details, and are sent only after the change is saved.
- All new tables are scoped to the business, with isolation tests for each.

## 7. Risks and mitigation

| Risk | Mitigation |
|---|---|
| Staff find the phone flow slow or confusing | Usability check with 2 or 3 of your staff during week 2 (Q3). I adjust the flow before the rehearsal. |
| Concurrency bugs | Real two-connection tests in CI, plus the unique index as a backstop. |
| Poor salon Wi-Fi or mobile data | Idempotent submit with automatic retry; the screen says clearly whether the record was saved. Full offline recording is out of MVP scope. |
| The phase is large | Built in three slices, each tested before the next: visits and desk (week 1), recording and staff phone screens (week 2), approvals, corrections, notifications and the rehearsal (week 3). I will tell you at each slice if the estimate moves. |

## 8. Not in this phase

- Finalisation, invoices, discounts, tax and the reconciliation view with R1 to R9: P4. The gate checks need the records built here; they are shown and enforced in P4.
- R4 decisions (reinstate or confirm a cancellation): P4, with reconciliation. The roadmap listed the `reconciliation_decisions` table under P3; it moves to P4 with the screen that uses it.
- The contribution ledger and the stored money values per contributor (FR-ATTR-012): P4, when a bill exists. Until then the split is shown in percentages.
- Payments, and the "no money paid" check on cancelling a visit having anything to find: P5.
- Web Push (FR-NOT-003, Should) and the shared-device PIN mode: P8.
- Staff performance figures: P7.

## 9. Questions before I start

1. **Recording for someone without a phone, or by a supervisor who does no salon work.** The SRS makes the person who records the work its primary contributor. I plan that anyone who records work but does not do salon work themselves (for example the owner or a supervisor recording for a stylist whose phone is flat) must choose the primary from the staff list, and is not credited. Staff who do salon work are always the primary when they record. Recommended: yes.
2. **What employees see about the customer.** I plan to show the customer's or guest's **name** on the staff screens, so a stylist knows whose visit it is, and never the phone number or email. Recommended: yes. The alternative is the visit reference only.
3. **Usability check and rehearsal.** The roadmap asks for 2 or 3 of your staff to try the phone flow during the phase, and a live rehearsal at the end. Can you name 2 or 3 staff for a 20-minute session in week 2? For the rehearsal I will give you the steps; you run it on your staging deployment when it suits you, and send me what went wrong.
4. **Credit split from the visit or from today's catalogue.** I plan that a recorded service uses the credit split the item had **when it was added to the visit**, the same way the price is kept. A catalogue change during a long visit then changes neither the price nor the split. Recommended: yes.

Open from earlier phases, unchanged unless you say otherwise: the 20-failures-per-address sign-in rule stays; trusted proxies stay unset until you tell me the API sits behind Cloudflare or another proxy; HSTS is planned for P6.

## 10. Definition of Done

- The acceptance criteria are met and shown with screenshots in the P3 report, including phone-sized screenshots of the staff flow.
- All tests in §5 pass locally, and GitHub Actions passes after your push.
- Staff walkthrough notes are written, and the usability findings (if the check took place) are recorded with what changed.
- The demo data, permissions reference and API description are updated.
- A commit plan with ready commit messages is in the report.
- You approve P3 before the P4 plan is written.
