# Phase P3: Visits and Staff Service Recording, Report

**Date:** 2026-11-27
**Plan:** [p3-visits-and-service-recording-plan.md](p3-visits-and-service-recording-plan.md) v1.0 (approved with the recommended defaults: someone who does no salon work picks the primary and is not credited; staff see the customer's name only; you run the usability check and rehearsal; a recorded service uses the split the item had when it was added)
**Branch:** `development`
**Status:** Complete locally. Waiting for your review, your push, the GitHub Actions results, and your approval before P4.

---

## 1. What you can do now

Every service done in the salon now gets its own record, made by the people who did it.

| # | Try this | Screenshot |
|---|---|---|
| 1 | As **Kemi**, the **Visits board** shows every open visit, oldest first, with how long it has been open and where each service stands. It refreshes by itself. | [board](p3-screenshots/01-desk-visits-board.png) |
| 2 | **New visit**: a customer or a guest, services with their variants, products with a quantity, and the staff expected to do each service. The visit gets its reference, for example V-261127-006. | [new visit](p3-screenshots/02-desk-new-visit.png) |
| 3 | On a phone as **Chiamaka**: **Today** puts visits with her assignments first. | [today](p3-screenshots/03-staff-today-phone.png) |
| 4 | Open the visit, tap the service, tap **I did this**: three taps. **With colleagues** adds the people who worked with her. | [record](p3-screenshots/04-staff-record-with-colleague-phone.png) |
| 5 | A second person who taps **I did this** is told it is already recorded and offered **Join**. This holds even when both tap at the same moment. | [join](p3-screenshots/05-staff-join-phone.png) |
| 6 | Back at the desk: the visit shows who did each service, the split, and the subtotal. A cashier gets no **Remove** button on recorded work. | [visit](p3-screenshots/06-desk-visit-with-recorded-work.png) |
| 7 | As **Ngozi** (supervisor), **Approvals** lists extra services staff asked for. | [approvals](p3-screenshots/07-approvals-queue.png) |
| 8 | A disputed record: Ngozi dismisses the dispute, corrects the record (the original stays, marked "Replaced") or cancels it. | [dispute](p3-screenshots/08-supervisor-disputed-record.png) |
| 9 | The bell: being added to work, decisions on requests, disputes and corrections. No amounts or phone numbers. | [notifications](p3-screenshots/09-notifications-phone.png) |
| 10 | **My work**: everything Chiamaka is credited on, with her share. No prices. | [my work](p3-screenshots/10-my-work-phone.png) |

**On your machine:** run `php artisan migrate` for the three new tables, or `php artisan app:demo` to also get this morning's demo visits. The API documentation at https://bizzmag-api.test/docs/api then shows the new endpoints. Step-by-step things to try are in [demo-data.md](../demo-data.md), and the staff guide and rehearsal script are in [staff-walkthrough.md](../staff-walkthrough.md).

## 2. Results

All run on this machine against MySQL 8.4.

| Check | Result |
|---|---|
| Backend tests (unit, feature, architecture, concurrency) | **457 passed**, 96,834 assertions (P2: 365) |
| Concurrency tests, real parallel PHP processes | 4 stylists submitting one service at once make exactly one record; a cancellation racing a submission never leaves work on a cancelled service. Passed 4 runs out of 4. |
| Pint (code style), Larastan level 6 | Pass, 0 errors |
| OpenAPI contract up to date | Pass (58 paths, P2: 32) |
| Migrations roll back and re-apply cleanly | Pass |
| Frontend lint, type check | Pass |
| Frontend unit tests | **80 passed** (P2: 69) |
| Static build and export check | Pass |
| No em-dashes in the design | Pass |
| **Browser tests** (Chromium, real API, separate sessions per person) | **17 passed** (P2: 13) |
| Container deployment verification (cPanel-like, VPS-like) | **Runs on GitHub after your push.** P3 adds only migrations and a daily clean-up task, both handled by the existing deploy script and cron line. |

## 3. Acceptance criteria

| Criterion (roadmap P3) | Proven by |
|---|---|
| Three employees on one braid, on their own phones, make one execution with three contributors and an equal split | Browser test "three stylists on their own phones..." (three separate sessions, 33.34%, 33.33%, 33.33%); `SubmitExecutionTest::test_three_stylists_on_one_braid...` |
| A second submission returns "Join" | Same browser test; `SubmitExecutionTest::test_a_second_submission_is_refused_and_offered_join`; `ExecutionConcurrencyTest` with 4 simultaneous processes; the database's unique index tested on its own |
| An additional service waits for approval | Browser test "a stylist asks for an extra service..."; `AdditionalServiceTest` (7 tests) |
| A cashier cannot cancel an executed item | Browser test "a cashier cannot remove a recorded service, in the screen or through the API"; `ExecutionLifecycleTest::test_a_cashier_cannot_cancel_a_recorded_service_through_the_api` |

SRS scenarios covered: §14.1 (shares), §14.2, §14.3 (up to the gate, which is P4), §14.14, §14.18, §14.19 (up to R8, shown in P4), §14.22, §14.23, §14.24 (corrections and extra services). §14.13 (late window) is tested with a finalised-visit fixture and is proven end to end once bills exist in P4.

## 4. Security review

| Requirement | How it is met | Test |
|---|---|---|
| BR-041, §14.23 Employees get no prices, contacts or bill data | The visit presenter has two shapes. Without `order.view_all` the price, phone and subtotal keys are **left out**. Employees see open visits only; a closed visit by ID answers "not found". | `EmployeeOrderViewTest` walks every key of every response an employee can get; browser test checks the raw API |
| BR-005, BR-006 Records never edited in place or deleted | Corrections supersede (the original keeps its people and shares); cancellations change status only | `ExecutionLifecycleTest` |
| BR-040 Segregation of duties | Nobody approves their own request, or corrects, cancels, resolves or re-splits their own record or a dispute they raised. The owner may; the audit event is flagged. | `ExecutionLifecycleTest`, `AdditionalServiceTest` |
| §7.7.2 Concurrency | Every command locks the visit first, then reads and checks. The database refuses a second active record even if the code were wrong. | `ExecutionConcurrencyTest`, `SubmitExecutionTest::test_the_database_itself_refuses...` |
| §7.7.4 Idempotent submit | `Idempotency-Key` required; a retry replays the first answer; a different body with the same key is refused | `SubmitExecutionTest`; frontend test that a retry reuses the key |
| FR-NOT-004 Notifications | No amounts or contact details; sent only after the change commits; nothing on rollback; removed after 90 days | `NotificationTest` |
| SEC-003 Business scope | All new tables are scoped; another salon's visits, items, records, staff and notifications cannot be seen, changed or used | `BusinessIsolationTest` (2 new tests) |
| SEC-002, SEC-016 Permissions and refusals | 27 new endpoints in the endpoint catalogue; every refusal recorded, including the action-level ones such as a cashier removing recorded work | `PermissionMatrixTest`, `RouteAccessTest` |

## 5. Plan tasks

All plan tasks D1 to M3 are done. Notes:

| Task | Note |
|---|---|
| D2, D4 | `order_items` and `executions` also carry `business_id`, so looking one up by ID uses the same fail-closed business scope as everything else. |
| O7, E8 | Cancelling an item or a whole visit lives in the Execution module, because it has to cancel recorded work and the Orders module may not depend on Execution. |
| E3 | When the person who recorded the work removes someone, that person's row is removed; the change log keeps the before and after shares. |
| E4 | A main person's share of 100% with assistants gives each assistant 0.01%, because nobody on a record may hold 0% (DR-008). |
| W2 | The board refreshes every 15 seconds; the bell every 30 seconds and whenever it is opened. |
| W4 | After "already recorded", the staff screen reloads the visit at once so **Join** appears without waiting. |
| M1 | The demo adds this morning's visits once per business day. |
| M3 | [staff-walkthrough.md](../staff-walkthrough.md): a one-page phone guide for staff with screenshots, the rehearsal script and a usability check sheet. |

## 6. Where I changed the plan

1. **New rule: a split a supervisor set by hand is not recalculated.** Once a supervisor sets custom percentages, nobody can join or be added by the person who recorded it (`SPLIT_SET_BY_HAND`), because there is no fair way to recalculate hand-set shares. A supervisor can still correct the record. The SRS does not cover this case.
2. **Error codes.** `STAFF_NOT_ELIGIBLE` replaces the planned `CONTRIBUTOR_NOT_ELIGIBLE`, because it also covers assignments. "Not named on the record" is a recorded 403 refusal rather than a separate `NOT_A_CONTRIBUTOR` code. `QUANTITY_NOT_ALLOWED` is new.
3. **`reconciliation_decisions` moved to P4**, as the plan said, together with the screen that uses it.
4. **Browser test server.** The test API now runs 4 PHP workers instead of 1, like PHP-FPM in production. With screens polling, one worker made the browsers queue behind each other. This changes only the test setup, not your deployment.
5. **No new packages** were added to the frontend or the backend.

## 7. Things you should know

1. **Your turn: usability check and rehearsal.** Use the script in [staff-walkthrough.md](../staff-walkthrough.md) on staging with 2 or 3 staff, and send me what was confusing. I adjust the screens before P4.
2. **A bug the screenshots caught.** Products briefly had no **Remove** or **Change price** button on the visit page, because the page used "can work be recorded" (services only) to mean "still on the visit". It is fixed and covered by a test.
3. **Money paid and cancelling a visit.** The rule "a visit with money paid cannot be cancelled until refunded" has nothing to check until payments exist; it joins the cancel action in P5.
4. **Polling load.** Each open screen asks the server for updates every 15 to 30 seconds. With about 10 devices that is under one request a second, well within a cPanel plan.
5. **Still open from P1, unchanged:** the 20-failures-per-address sign-in rule stays; trusted proxies stay unset until you tell me the API is behind Cloudflare or another proxy; HSTS is planned for P6.

## 8. Commit plan

Nothing is staged. Run these from the repository root, in order, then push.

1. **Backend**

   ```bash
   git add backend
   git commit -m "feat(backend): visits, staff service recording, extra-service approvals and notifications

   - Visits for customers or guests with a daily reference, items with a
     catalogue snapshot (price, taxable flag, credit split), assignments,
     price overrides with a reason, linking a guest to a customer
   - Recorded work: submit (idempotent, three people make one record),
     join, edit people, override the split, dispute, dismiss, correct
     (supersedes and keeps the original) and cancel
   - One active record per service enforced by the visit lock and by a
     unique index on a generated column
   - Extra services wait for approval; segregation of duties on every
     approval and correction, owner exemption flagged in the audit log
   - Employees receive no prices, contacts or bill data, and see open
     visits only
   - In-app notifications after commit, without amounts or contacts,
     pruned after 90 days
   - Demo salon gets this morning's visits in every state
   - 457 tests: permission matrix, isolation, concurrency workers"
   ```

2. **Frontend**

   ```bash
   git add frontend
   git commit -m "feat(frontend): visits board, new visit, staff phone recording and approvals

   - Desk: visits board with live progress, new visit with customer or
     guest, catalogue picker and assignments, visit page with supervisor
     actions
   - Staff: today's visits with own assignments first, three-tap
     recording, colleagues, join, dispute, extra-service requests, my work
   - Admin: approvals queue; notifications bell in every area
   - Customer page lists the customer's visits
   - Browser tests with separate sessions per person; test API server
     runs 4 workers"
   ```

3. **Docs**

   ```bash
   git add docs
   git commit -m "docs: P3 plan and report, staff walkthrough, API description, demo and permissions guides

   - Phase 3 plan (approved) and report with screenshots
   - P2 report marked approved
   - Staff phone guide with the rehearsal script and usability check
   - OpenAPI description with the visit, work and notification endpoints
   - Phase 3 steps in the demo guide; rules on top of permissions"
   ```

4. **Check and push**

   ```bash
   git status            # should say "nothing to commit, working tree clean"
   git log --oneline -4
   git push
   ```

## 9. Approval

Please review, push, and share the GitHub Actions results (CI: Backend, Frontend, Browser tests; Verify deployment: cPanel and VPS). When you approve P3, I will write the P4 plan (reconciliation, discounts, tax and invoicing) for your approval before building anything.
