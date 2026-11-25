# Stage 1 — Requirements Review and Validation

**Project:** Salon Operations & Accountability Platform
**Document:** Stage 1 Requirements Review Report
**Version:** 1.0
**Date:** 2026-11-24
**Status:** Approved by the product owner on 2026-11-24, "with recommendations". Incorporated into PRD v1.3 and SRS v1.1. The factual questions in §7.1 were left unanswered and are recorded as assumptions A-01 to A-08 in PRD v1.3 Appendix B.

---

## 0. Documents Reviewed

| Document | File | Version | Stated status | How this review treats it |
|---|---|---|---|---|
| Business Overview & Requirements | `docs/business-overview.md` | Unversioned | Source brief | Original statement of business intent. Used to check whether the PRD lost anything. |
| PRD | `docs/prd-1.0.md` | 1.0 | Superseded | Used only to check for decisions or content that were dropped. |
| PRD | `docs/prd-1.1.md` | 1.1 | Superseded ("Awaiting final approval") | Used only to check for decisions or content that were dropped. |
| **PRD** | `docs/prd-1.2.md` | **1.2** | **Approved product baseline** | **The product baseline under review.** |
| **SRS** | `docs/srs-1.0.md` | **1.0** | **Initial draft** (parent: PRD 1.2) | **The technical interpretation under review.** |

The documents do not contain any separate decision log. The decisions recorded in PRD 1.2 are taken to be the answers to the "Outstanding Product Decisions" in PRD 1.1 §23. Section 3 (X-01) lists them.

---

## 1. Executive Summary and Verdict

### 1.1 What the product is (my understanding)

A web-based PWA for one Nigerian salon. Its main job is to make the following four facts **independently recorded and reconcilable**:

1. **What the customer asked for.** This is the service order, created by the front desk.
2. **What was actually done, and by whom.** These are execution records submitted by the employees who did the work. A submission confirms itself.
3. **What the customer was charged.** This is the invoice, produced from authorised services plus controlled discounts and taxes.
4. **What money was actually received.** These are payments, receipts and the daily cashier closing.

Because these facts are kept apart, the person who produces the invoice cannot quietly drop a service that staff have recorded. That stops revenue leakage. The same records give fair, auditable staff performance figures and bonus calculations. When several employees share one service, each of them gets credit, and the customer is still charged only once.

The first release is for one salon. The design should leave room for branches and other businesses (cleaning services) without building them now.

### 1.2 Overall assessment

| Document | Assessment |
|---|---|
| **PRD v1.2** | **The intent is sound and the scope is focused.** The vision, goals, roles and MVP boundary are clear. The documents settle the key decisions from PRD 1.1 §23: automatic confirmation, collaborative attribution, bonus approval, guest transactions, cashier closing and refunds. **However, the central anti-leakage rule is not stated.** The PRD never says what gets billed when requested services and performed services differ, or when finalisation is blocked. That rule *is* the product, and it cannot be left to technical design. Several financial concepts are named but not defined: contribution value, collected revenue, refund versus reversal, and bonus rule types. A few PRD 1.1 requirements were dropped without a recorded decision. |
| **SRS v1.0** | **It reflects the PRD faithfully, but it is not yet a specification.** Many of its requirements restate PRD sentences instead of making them precise. Examples are "where possible", "appropriate", "proposed states" and "according to configured rules". Every lifecycle is marked "proposed", and transitions are not defined. The 15 open TD items include product decisions (TD-003, 004, 007, 008, 009, 011, 012, 013), not only technical ones. Traceability is mapped section to section, not requirement to requirement. Non-functional requirements have no measurable targets. |

### 1.3 Verdict

**Neither document is sufficient as an implementation baseline yet.** The gaps are concentrated, not spread everywhere. Two pieces of work would make both documents a sound baseline:

1. Your decisions on the **12 blocking decisions** in Section 6 (marked **BLOCKING**). Six of them depend on facts only you know about how the salon operates.
2. The amendments in Section 5, which would produce PRD v1.3 and SRS v1.1.

This is not a rewrite. The structure, scope and most requirements stay as they are.

### 1.4 The five most important findings

| # | Finding | Why it matters |
|---|---|---|
| 1 | **The billing basis and finalisation gate are undefined** (P-01, S-01). If a service was requested but no one recorded doing it, is it billed? If it was performed but never requested, is it billed? Can the cashier finalise while differences remain? PRD 1.1 said finalisation is blocked until differences are resolved or an exception is approved. PRD 1.2 softened this, and the SRS sends it to "configurable rules" (TD-003). | This is the main control against invoice manipulation. Without it, the system can be built "correctly" and still let a cashier under-bill. |
| 2 | **Timing: staff may submit after the customer has paid** (P-02). Nothing covers executions recorded *after* the invoice was finalised. | This is the exact path for leakage the product exists to stop: finalise quickly, before staff submit. |
| 3 | **The collaboration and execution model contradict each other** (P-03, S-03, X-04). The PRD allows "each employee records their own contribution *or* one record with all participants". The SRS models one execution with many contributors, but also allows independent submissions. Neither document says who chooses the allocation split. | This causes duplicate records and credit employees can manipulate themselves (the split feeds bonuses, so it is effectively money), and it blocks the data model. |
| 4 | **Financial terms are named but not defined** (P-05, P-09, P-10). These include contribution *value* (gross or after discount?), "collected revenue" per employee, refund versus reversal, and bonus rule types. | You cannot test bonus or performance figures, and disputes with staff become likely. |
| 5 | **Real salon operations are missing** (P-06, P-07, P-08, P-11). These include variable prices (for example braid size or length), products and hair attachments sold on the invoice, deposits, and checking bank transfers against fake transfer alerts. | If the system cannot handle these, staff will handle them outside the system, and that is where leakage comes back. |

---

## 2. Deliverable A — PRD v1.2 Review Report

### 2.1 Strengths (no change needed)

- A clear problem statement and goals, traceable to the business overview.
- Requests and executions are separated correctly, and invoices and payments are separated correctly (PRD §7.3–7.9). This follows the recommendation in the business overview about using a service order.
- Contribution is explicitly kept apart from revenue (§7.5). This is the single most important modelling rule, and it is stated well.
- Good MVP discipline. Gateways, payroll, inventory, attendance and multi-branch are all deferred (§6.2).
- Online-first, with no sensitive data in the service-worker cache (§10).
- Roles are default templates, not a fixed permission model (§5).
- Bonus states Calculated → Approved → Paid, with approval required (§7.14).

### 2.2 Findings

Severity: **Critical** = blocks correct implementation of the core purpose. **High** = blocks a module or financial correctness. **Medium** = causes rework or ambiguity. **Low** = editorial or clarity.

#### P-01 — Billing basis and finalisation conditions are undefined · Critical

- **Affected:** PRD §7.7, §7.8, §8 steps 8–10, §12 ("Service discrepancies are visible and traceable").
- **Problem:** The PRD says the invoice reflects "authorised services" and that discrepancies are resolved "through controlled procedures". It does not say:
  - (a) whether a requested item with no execution is billable;
  - (b) whether an execution that does not match a requested or approved item is billable;
  - (c) whether finalisation is *blocked* while discrepancies remain;
  - (d) who can make an exception.

  PRD 1.1 §6.7 said "prevent ordinary invoice finalisation while unresolved discrepancies remain … authorised users may approve documented exceptions." PRD 1.2 dropped this. No decision records the change.
- **Why it matters:** This rule decides whether a cashier can reduce a bill. Under "bill only executed items", the cashier needs no permission to under-bill if staff have not yet submitted. Under "bill all requested items", forgetting to submit does not reduce revenue.
- **Impact:** Financial integrity, the data model (item states, exception records), UX at the till, and every acceptance test for reconciliation.
- **Recommendation:** **D-01.** Bill every *authorised* item: original items plus approved additions, minus authorised cancellations. Block finalisation until every authorised item is either (i) executed, (ii) cancelled through an authorised action, or (iii) covered by a permission-controlled "finalise with exception" carrying a mandatory reason. The cashier never edits invoice lines directly, because lines are generated by the system.
- **Product-owner decision needed:** **Yes, BLOCKING.**

#### P-02 — Executions submitted after invoice finalisation · Critical

- **Affected:** PRD §7.4, §7.8, §8.
- **Problem:** The PRD does not say what happens when an employee submits after the invoice is finalised or paid. Examples: the employee forgot, the phone was offline, or the customer left early.
- **Why it matters:** One fraud pattern is "finalise before staff submit, then collect the smaller amount". Another is staff gaming credit by adding contributions to old orders.
- **Recommendation:** **D-02.** Accept late executions; staff work is never lost. If the execution matches an item that is already billed, attach it with no financial effect. If it matches no billed item, raise a **post-finalisation discrepancy** that the owner reviews on the dashboard. Allow a submission window (for example the same business day or 48 hours). After the window, require supervisor permission.
- **Product-owner decision needed:** **Yes, BLOCKING.**

#### P-03 — Collaboration model: one record or many · Critical

- **Affected:** PRD §7.4, §7.5, §8 step 6. The business overview §4B says "each employee … record their contribution, *or* the service should be recorded once".
- **Problem:** The PRD keeps the business overview's "or" without choosing. It also does not say:
  - who names collaborators;
  - whether a named collaborator can dispute;
  - whether someone missed can add themselves;
  - who chooses the allocation method or percentages for an execution.
- **Why it matters:** Contribution feeds bonuses, so it is money. If the submitter alone chooses collaborators and percentages, credit can be inflated or withheld. If everyone submits separately, you get duplicate executions.
- **Recommendation:** **D-03 and D-04.**
  - One execution per order item.
  - Any participant can create it and name collaborators.
  - Staff who were missed can add themselves until finalisation.
  - Named collaborators see the record and can raise a dispute.
  - The **allocation method comes from the service configuration set by the owner**, not from the employee.
  - A supervisor can override the split per execution, with a reason.
- **Product-owner decision needed:** **Yes, BLOCKING.**

#### P-04 — Allocation methods are open-ended and overlap · High

- **Affected:** PRD §7.5 ("equal, percentage, weighted, primary/assistant, *other configurable approaches*").
- **Problem:** Percentage allocation and weighted allocation are mathematically the same idea, because a percentage is just a set of weights that add up to 100. "Other configurable approaches" cannot be tested or estimated.
- **Recommendation:** **D-04.** Use one model, *weights per contributor*, with three presets:
  - **Equal.**
  - **Primary/Assistant.** The primary gets a configured percentage and the assistants share the rest equally.
  - **Custom percentages,** which must total 100%.

  Remove "other approaches" from the MVP. The result is the same capability with less to build.
- **Product-owner decision needed:** **Yes.** This changes PRD wording.

#### P-05 — "Contribution value" and "collected revenue" per employee are undefined · High

- **Affected:** PRD §7.13 ("value of services contributed to"), §7.14 (bonus on "contributions" and "collected revenue").
- **Problem:** The PRD does not say what value is allocated:
  - the catalogue or snapshot price;
  - the line after discounts;
  - with or without tax;
  - the amount actually collected.

  "Collected revenue" is a property of the invoice, not of an employee. If the invoice is half paid, what has each contributor "collected"? The business overview also asks for "total value of services each employee *contributed to*". That is a different metric from an allocated share: it is the full ₦20,000 for each of three braiders, which does not add up across staff.
- **Why it matters:** These definitions drive bonuses and staff disputes. They also decide whether staff totals reconcile to salon revenue.
- **Recommendation:** **D-05.** Define three metrics and label them clearly in reports:
  - **Participated value.** The full value of services the employee took part in. It is informational and does *not* add up across staff.
  - **Allocated contribution.** The employee's share of the line's net value, after discounts and before tax. Across staff it adds up exactly to service revenue.
  - **Collected contribution.** Allocated contribution × (amount collected on the invoice ÷ invoice total), measured at the close of the period.

  Each bonus rule chooses its basis.
- **Product-owner decision needed:** **Yes, BLOCKING** (default basis and the effect of discounts on staff credit).

#### P-06 — Variable pricing and price overrides · High

- **Affected:** PRD §7.2 (fixed "configured price"). SRS FR-SVC-008 hints at an "authorised adjustment mechanism".
- **Problem:** Salon prices often vary by length, size or hair type, for example "Knotless braids: small / medium / large". The PRD allows only one fixed price per service. If staff cannot record the real price, they will use discounts or work outside the system. A free-form price override is a manipulation route as powerful as a discount.
- **Recommendation:** **D-06.** Model variants as separate catalogue services or service options, each with a fixed price. Allow a price override only with a specific permission and a mandatory reason. Every override appears on the exceptions report.
- **Product-owner decision needed:** **Yes, BLOCKING.** I need to know how your prices actually vary.

#### P-07 — Non-service items on the invoice (hair attachments, products) · High

- **Affected:** PRD §6.1 and §7.2. The catalogue holds services only, and inventory is out of scope.
- **Problem:** Braiding often includes attachment hair, and salons sell products. If these cannot go on the invoice, the cashier collects for them off-system. That reopens the leakage gap.
- **Recommendation:** **D-07.** Add a catalogue item type, **Product/Material**. It has a price and goes on the invoice. It needs no execution and earns no staff contribution. There is no stock tracking, because inventory stays deferred.
- **Product-owner decision needed:** **Yes, BLOCKING.** Do you sell hair or products at the till?

#### P-08 — Payment timing and deposits · High

- **Affected:** PRD §7.9, §8 (payment comes after finalisation).
- **Problem:** The PRD does not say whether payment is allowed before finalisation, such as a deposit or prepayment before a long braid. SRS FR-PAY-001 records payments "against invoices", and FR-PAY-008 blocks overpayment, so prepayment has nowhere to go.
- **Recommendation:** **D-08.** MVP: payments are recorded only against a finalised invoice. If deposits are common, add a simple **order deposit** that is applied automatically when the invoice is finalised. Deposits are recorded, receipted and appear in the cashier closing.
- **Product-owner decision needed:** **Yes, BLOCKING.**

#### P-09 — Refund and reversal are not distinguished · High

- **Affected:** PRD §7.11.
- **Problem:** The PRD treats refunds and reversals as one control. They are different events:
  - A **reversal** corrects a payment recorded in error. The money never arrived.
  - A **refund** pays money back to a customer, usually because of a service complaint.

  They affect the cashier closing differently: a reversal removes the expected amount, while a refund pays cash out of the drawer. They may also affect staff credit differently: should a refunded braid still earn bonus? A cash reversal by the same cashier who recorded it is also a classic leakage pattern: record the cash, issue a receipt, reverse it later and keep the money.
- **Recommendation:** **D-09.**
  - Define both terms.
  - Reversals and refunds both require a permission that the default Cashier role does *not* hold.
  - Both require a reason.
  - Both are visible in the cashier closing and on the dashboard.
  - A refund does not change staff credit unless it is paired with an authorised service correction.
- **Product-owner decision needed:** **Yes, BLOCKING.**

#### P-10 — The bonus engine cannot be tested as specified · High

- **Affected:** PRD §7.14.
- **Problem:** "Configurable periods and criteria … a combination … service counts and targets where supported" has no defined rule types. The PRD also does not say:
  - what happens when an owner *does not approve* a calculated bonus, because there is no Rejected or Void state;
  - what happens when executions are corrected after a bonus is calculated or paid;
  - whether a bonus can be paid in instalments (SRS TD-011).
- **Recommendation:** **D-14.** Fix the MVP rule types:
  - **(a) Percentage of a basis.** The basis is allocated contribution, collected contribution or service count × a fixed amount.
  - **(b) Fixed amount when a threshold is met.**
  - **(c) Tiered percentage.**

  Each rule also has filters (service categories, employees or roles) and a period (weekly or monthly).

  Add a **Voided** state with a reason. Recalculation replaces bonuses that are still Calculated. Corrections after a bonus is Approved or Paid go into the next period as an adjustment. Payment is a single full payment, with no instalments in the MVP.
- **Product-owner decision needed:** **Yes, BLOCKING** (which rule types you actually intend to use).

#### P-11 — Bank-transfer and POS verification · High

- **Affected:** PRD §7.12 ("declared *or verified*").
- **Problem:** The PRD does not say who verifies transfers or how. In Nigeria, fake transfer alerts and screenshots are a well-known fraud. A transfer recorded on the strength of a screenshot is not money received. POS has a separate settlement from the terminal's end-of-day slip.
- **Recommendation:** **D-11.** Transfers are recorded as **Unverified**. An authorised user (the owner by default) marks them **Verified** after checking the bank statement or app. A receipt can be issued at recording time. Unverified transfers older than N days show on the dashboard. POS closing compares against the declared figure from the terminal's end-of-day slip.
- **Product-owner decision needed:** **Yes.**

#### P-12 — Cancelling a requested item before execution · Medium

- **Affected:** PRD §7.7 ("Services cancelled … through authorised procedures").
- **Problem:** The PRD covers correcting *confirmed* services. It does not cover cancelling a service the customer asked for but then changed their mind about, before anyone did it. If the front desk can cancel freely, it can cancel a service that was actually performed before staff submit it.
- **Recommendation:** **D-19.** The front desk may cancel an unexecuted item, with a reason. The cancellation is audited and appears on the discrepancy report. If a late execution then arrives for that item, it raises a high-priority discrepancy (see P-02). Once any execution exists, cancelling requires the correction permission.
- **Product-owner decision needed:** **Yes.**

#### P-13 — How additional services enter the order · Medium

- **Affected:** PRD §7.6.
- **Problem:** The PRD covers employee requests only. It does not cover:
  - (a) the front desk adding a service when the customer asks at the desk;
  - (b) an employee doing the extra service before approval, which is common when the customer asks the stylist directly;
  - (c) the price of the additional service;
  - (d) what happens to an execution of a service that was then rejected.
- **Recommendation:** **D-20.**
  - (a) The front desk can add items directly before finalisation. This only *raises* the bill, and it is audited.
  - (b) An employee can record execution against a *pending* request. It is not billable and earns no credit until approved.
  - (c) The catalogue price applies.
  - (d) A rejected request's execution stays on record, flagged, with no customer charge and no contribution credit.
- **Product-owner decision needed:** **Yes.**

#### P-14 — Discount scope and over-limit handling · Medium

- **Affected:** PRD §7.10.
- **Problem:** The PRD does not say whether discounts apply per line or per invoice. It also does not say what happens when a cashier needs a discount above their limit: is it blocked, or does it go to someone for approval? It does not say whether a discount reduces staff contribution (linked to P-05).
- **Recommendation:** **D-12.**
  - Both line-level and invoice-level discounts are allowed.
  - Each permission tier has a percentage cap and a fixed-amount cap.
  - A reason is mandatory.
  - Above the cap, the discount becomes an **approval request** to a supervisor, using the same pattern as additional services. Supervisor credentials are not typed on the cashier's device.
  - An invoice-level discount is spread across the lines in proportion to their value.
- **Product-owner decision needed:** **Yes.**

#### P-15 — Tax applicability is not confirmed · Medium

- **Affected:** PRD §7.10 (rates, inclusive or exclusive, exemptions, scope).
- **Problem:** The PRD requires a full tax engine without confirming that the salon charges tax at all. Many small Nigerian businesses below the statutory turnover threshold do not charge VAT. Please confirm with your accountant. A full engine with both inclusive and exclusive pricing, exemptions and scoping is a large part of the financial-calculation and testing work.
- **Recommendation:** **D-13.** MVP: a business-level tax setting that is **off by default**. When it is on, it uses one rate, one business-wide mode (inclusive *or* exclusive), and a per-service exemption flag. Discounts apply before tax. Multiple tax types stacked on one another are deferred.
- **Product-owner decision needed:** **Yes, BLOCKING.** Do you charge VAT today?

#### P-16 — Invoice amendment after payment · Medium

- **Affected:** PRD §7.8.
- **Problem:** "Authorised amendment procedure" is not defined. The PRD does not say:
  - whether an amendment creates a new version;
  - what happens to payments already received if the amended total falls below the amount paid;
  - when voiding is allowed.
- **Recommendation:** **D-15.**
  - An amendment creates a new invoice **revision**. Earlier revisions are kept and cannot be changed, and payments carry forward.
  - If the new total falls below the amount paid, the amendment must be paired with a refund. Customer credit is not in the MVP.
  - Voiding is allowed only when net payments are zero.
- **Product-owner decision needed:** **Yes.**

#### P-17 — Sharing channels were weakened from the business overview · Medium

- **Affected:** PRD §7.9 ("shareable digitally"). The business overview §4D asks for **WhatsApp (image/PDF), email, PDF download and print**. PRD 1.1 §6.12 kept WhatsApp and email.
- **Problem:** The commitment in PRD 1.2 is vaguer than the owner's original requirement. The printer type also matters: A4 or an 80 mm thermal receipt printer.
- **Recommendation:** **D-16.** MVP channels:
  - PDF download;
  - print, in A4 and/or 80 mm thermal layout;
  - **WhatsApp**, using the phone's share sheet to share the PDF, or a `wa.me` link to a signed, expiring receipt URL;
  - **email** via the hosting provider's SMTP.

  Automated WhatsApp Business API messaging stays deferred.
- **Product-owner decision needed:** **Yes.** Which printer do you use?

#### P-18 — "Customers served" with anonymous guests · Low

- **Affected:** PRD §7.13, §7.1.
- **Problem:** Guests may have no identity, so "customers served" cannot mean distinct people.
- **Recommendation:** Define it as **distinct service orders (visits)** in which the employee contributed. The metric then works the same for guests and registered customers.
- **Product-owner decision needed:** You can accept the recommendation.

#### P-19 — Staff device access · Medium

- **Affected:** PRD §5 (Employee), §10.
- **Problem:** The PRD assumes every employee logs in on their own device. If some staff have no smartphone or no data, submissions will fail, which is product risk #1 in PRD §13.
- **Recommendation:** **D-17.** Individual accounts are mandatory. Optionally add a **shared salon tablet** mode with a fast personal PIN switch. Records are still attributed to the individual.
- **Product-owner decision needed:** **Yes.** Do all your staff have smartphones?

#### P-20 — What employees can see · Medium

- **Affected:** PRD §5, §7.13. SRS §3.4 ("accessible active transactions").
- **Problem:** "Accessible" is not defined. The PRD does not say whether employees see all open orders, prices, or customer phone numbers. Customer phone numbers are personal data under the Nigeria Data Protection Act 2023.
- **Recommendation:** **D-18.**
  - Employees see all open orders for the current business day: reference, customer first name or "Guest", and services.
  - They do **not** see prices on orders, phone numbers or invoices.
  - In "My performance" they see their own counts and their own contribution values.
- **Product-owner decision needed:** **Yes.**

#### P-21 — Notifications dropped without a decision · Low

- **Affected:** PRD 1.2 omits notifications. The business overview and PRD 1.1 §12 had "push notifications where supported". SRS FR-EXE-011 and TD-012 cover notifications.
- **Problem:** Supervisors need to learn quickly about pending additional-service and discount approvals, or the customer waits.
- **Recommendation:** **D-22.** MVP: in-app notifications, meaning a badge and a list refreshed by periodic polling. Web Push is a "Should" requirement. Web Push works on Android, and on iOS 16.4+ only when the app is installed to the home screen.
- **Product-owner decision needed:** You can accept the recommendation.

#### P-22 — Orders left open across days, and abandoned orders · Medium

- **Affected:** PRD §8.
- **Problem:** Long braiding can run past closing time or continue the next day. Customers also sometimes walk out. The PRD does not say how long an order can stay open, or which date revenue counts against.
- **Recommendation:** **D-23.**
  - Orders may stay open across days.
  - The dashboard shows open orders by age.
  - Abandoned orders are cancelled through the authorised cancellation route, with a reason.
  - Revenue is dated by **invoice finalisation date**, and collections by **payment date**.
- **Product-owner decision needed:** You can accept the recommendation.

#### P-23 — Two meanings of "revenue" · Medium

- **Affected:** PRD §7.13, §4 goal 5. Business overview §6.
- **Problem:** "Revenue" can mean billed (finalised invoices) or collected (payments net of refunds). These differ whenever balances are outstanding.
- **Recommendation:** **D-24.** The dashboard shows both, labelled **Billed** and **Collected**, plus **Outstanding**.
- **Product-owner decision needed:** You can accept the recommendation.

#### P-24 — Segregation of duties · Medium

- **Affected:** PRD §9.
- **Problem:** The PRD does not say whether one user can both request and approve the same sensitive action, such as their own over-limit discount, their own refund or their own correction.
- **Recommendation:** **D-25.** The approver must be a different person from the requester for discount overrides, refunds, reversals and execution corrections. The Owner role is exempt, and the exemption is audited.
- **Product-owner decision needed:** **Yes.** This matters if the salon has only one supervisor.

#### P-25 — Content dropped between PRD 1.1 and 1.2 without a recorded decision · Medium

PRD 1.2 is a condensed version of 1.1. These items disappeared with no recorded decision:

- (a) The finalisation gate (→ P-01).
- (b) WhatsApp and email (→ P-17).
- (c) Push notifications (→ P-21).
- (d) The detailed dashboard metric list (PRD 1.1 §10: payment-method breakdown, services awaiting execution, cancelled services, recent activity, and so on).
- (e) The business configuration list (logo, invoice and receipt settings).
- (f) Customer transaction history.
- (g) Success indicators (PRD 1.1 §4.1).
- (h) Logging of *attempted* unauthorised actions (PRD 1.0 scenario 3).
- (i) The historical-pricing acceptance scenario (PRD 1.1 #20).

**Recommendation:** Restore (a)–(f), (h) and (i) in PRD 1.3. Restore (g) as a short appendix. **Decision:** confirm whether any omission was intentional.

#### P-26 — Data import scope · Low

- **Affected:** PRD §11.
- **Problem:** The PRD names "customers and service catalogue", but the existing Google Sheet holds staff *service history*. The PRD does not say whether that history is imported. The MyCantant export format is also unknown.
- **Recommendation:** **D-21.** Import customers and services only. Historical Google Sheet records stay as a read-only archive and are not loaded into performance or bonus data, because they have no reconciled prices. Detect duplicate customers by normalised phone number (+234). Provide a sample MyCantant export before the import phase.
- **Product-owner decision needed:** Confirm.

#### P-27 — Non-functional targets missing · Medium

- **Affected:** PRD §10. SRS §11 ("acceptable", "efficiently").
- **Problem:** There are no measurable targets for performance, availability, backups (how much data you can afford to lose, and how quickly you must recover), supported browsers, data retention or data protection.
- **Recommendation:** **D-26.** Proposed defaults are in Section 6.
- **Product-owner decision needed:** You can accept the recommended defaults.

---

## 3. Deliverable B — SRS v1.0 Review Report

### 3.1 Strengths

- Faithful to the PRD. It contains almost no unsupported features.
- Requirement IDs exist for functional requirements, business rules, security and UI.
- It correctly states that the backend is the source of truth (§2.1, BR-016, §13).
- It recognises the open questions honestly (§16 TD list).
- The acceptance scenarios (§14) are a good start, written in "given" style.

### 3.2 Findings

#### S-01 — Hard product rules are pushed into technical "TD" items · Critical

- **Affected:** SRS §16 TD-003, TD-004, TD-007, TD-008, TD-009, TD-011, TD-012, TD-013.
- **Problem:** These decide *product behaviour*: billing basis, collaboration model, overpayment, refund limits, closing model, bonus payout, notifications and import policy. They are listed as detailed-design items, and FR-REC-007 depends on "configured finalisation rules" that do not exist.
- **Recommendation:** Move these to the product decision register (Section 6). Their outcomes become FR or BR text in SRS v1.1. TD-001, TD-002, TD-005, TD-006, TD-010, TD-014 and TD-015 stay technical (Stage 2).
- **Decision:** Covered by D-01 to D-23.

#### S-02 — State machines are "proposed" with no transitions · High

- **Affected:** SRS §6.1–6.7.
- **Problem:** No transition tables, actors, guards or side effects. The order states mix lifecycle with derived conditions: "Awaiting Reconciliation" and "Reconciled" are calculated results, not lifecycle stages. Several entities have no states at all:
  - **service order items** (Requested, Pending approval, Executed, Cancelled), which UI-006 needs;
  - **payments** ("finalised in detailed design");
  - **deposits;**
  - **transfer verification.**

  Bonus has no Voided state, and cashier closing mixes "Discrepancy" as a state with "Reviewed" as a state.
- **Recommendation:** Stage 2 produces the full transition tables. In SRS v1.1, add the *product-level* state lists that follow from your decisions. Initial proposal:
  - **Order:** Open → Ready to finalise → Invoiced → Closed (fully paid), plus Cancelled. "Reconciled" is a derived flag, not a state.
  - **Order item:** Requested | Pending approval | Rejected | Cancelled, with execution status (Not started / Executed) kept as a separate dimension.
  - **Execution:** Confirmed | Disputed | Superseded (replaced by a correction) | Cancelled.
  - **Invoice:** Draft → Finalised, then Voided. Amendment creates a new revision; it is not a separate state.
  - **Payment:** Recorded | Reversed. Transfer verification is a separate flag: Unverified / Verified / Rejected.
  - **Cashier closing:** Draft → Submitted → Approved, plus Reopened by the owner only. Discrepancy is an *attribute*, not a state.
  - **Bonus:** Calculated → Approved → Paid, plus Voided.
- **Decision:** No owner decision, except where linked to D-items.

#### S-03 — Execution and attribution requirements conflict · High

- **Affected:** FR-EXE-003 (an execution identifies an order item), FR-EXE-004 (unassigned employees may submit), FR-REC-003 ("completed services *not represented* in the original order"), FR-ADD-007, FR-ATTR-001/002, §7.3 ("one item may have one or more executions *where the business workflow permits*").
- **Problem:** If every execution must reference an order item (FR-EXE-003), then FR-REC-003's "completed services not in the original order" can only come from rejected or pending additional requests or cancelled items. The SRS does not say this. "One or more executions per item" conflicts with FR-EXE-007's duplicate prevention, unless the SRS defines when several are allowed. Repeated executions only make sense with quantity greater than 1 or with corrections.
- **Recommendation:**
  - At most one *active* execution per order item.
  - Collaborators are contributor rows on that execution.
  - Corrections replace the original through a link to it; nothing is edited in place.
  - An item with quantity greater than 1 is covered by one execution.
  - Redefine FR-REC-003 as "executions against pending, rejected or cancelled items".
- **Decision:** Covered by D-03.

#### S-04 — Many requirements are not testable · High

| Requirement | Untestable wording | Proposed testable rewrite (after decisions) |
|---|---|---|
| FR-CUS-005 | "according to the approved guest transaction policy" (no such policy exists) | "Guest name and phone are optional. Every order receives a system-generated unique reference." |
| FR-CUS-009 | "where possible" plus an undefined "duplicate-resolution process" | "On create or import, the system warns when the normalised phone number matches an existing customer. Customer merging is deferred." |
| FR-EXE-001 | "eligible active transactions" | Use the D-18 definition. |
| FR-EXE-004 | "Where permitted" | "Users with permission `execution.submit` may submit against any open order item, whether or not they are assigned to it." |
| FR-EXE-007 | "unintentionally" | "A second execution for an item that already has an active execution is rejected. The second user is offered 'join as collaborator'." |
| FR-ATTR-002 | "unless an explicitly defined business rule permits" | "An employee appears at most once per execution." |
| FR-REC-007 | "configured finalisation rules" | Use the D-01 rule. |
| FR-DIS-006 | "invalid invoice totals" | "No line or invoice total may be below ₦0. Total discount may not exceed the pre-discount subtotal." |
| FR-PAY-008 | "permitted outstanding amount unless … policy is introduced" | Use the D-08 / TD-007 outcome. |
| FR-CASH-002 | "selected closing period" | Use the D-10 definition. |
| FR-BON-003 to 006 | "support … as a basis" | Use the D-14 rule types with worked examples. |
| NFR-PERF-001 | "acceptable response time" | Use the D-26 targets. |
| NFR-USE-002 | "minimise unnecessary steps" | "An assigned employee can submit an execution in ≤ 3 taps from opening the app." |

#### S-05 — Duplicate and overlapping requirements · Low

- FR-IAM-001 to 007 (§3.5) overlap with FR-AUTH-001 to 006 (§4.1). Examples: FR-IAM-005 ≈ FR-AUTH-005, and FR-IAM-004 ≈ SEC-002. FR-IAM-007 ≈ SEC-003.
- **Recommendation:** Consolidate. Keep FR-IAM for authorisation and FR-AUTH for authentication. SEC items should reference them, not restate them.

#### S-06 — Traceability is section-level and the PRD has no IDs · Medium

- **Affected:** SRS §17.
- **Problem:** You cannot prove that every PRD statement is covered.
- **Recommendation:** Give PRD 1.3 requirement IDs (`PRD-<section>-<n>`). SRS 1.1 maps every FR to a PRD ID and to a verification method. The matrix in Section 5 is the starting point.

#### S-07 — Entity inventory: one too many, several missing · Medium

- **Affected:** SRS §7.2.
- **Unnecessary as separate entities:**
  - **ServiceReconciliation:** reconciliation should be a *calculated view* over order items and executions. Only the resolutions need storing: cancellation records, exception approvals.
  - **BonusApproval:** approval fields on BonusCalculation are enough.
  - **ContributionAllocation** separate from **ServiceContributor:** the allocated weight and value can live on the contributor row.
- **Missing:**
  - an approval request for discount over-limit and corrections (a generic pattern shared with additional services);
  - a finalisation exception record;
  - an invoice revision;
  - an order deposit (if D-08);
  - transfer verification;
  - a business-day or cashier-session record;
  - a bonus adjustment;
  - a number sequence for references without gaps;
  - notifications;
  - a product item type (if D-07).
- **Recommendation:** Final modelling happens in Stage 2. SRS 1.1 updates the conceptual inventory.

#### S-08 — Financial precision rules are delegated without examples · Medium

- **Affected:** SRS §7.5, TD-005, TD-006.
- **Problem:** The rule "allocated amounts reconcile without creating or destroying value" is correct, but there is no worked example to test it against.
- **Recommendation:** Add a mandatory acceptance example. ₦20,000 split equally three ways gives ₦6,666.67 + ₦6,666.67 + ₦6,666.66 = ₦20,000.00, with the leftover kobo going to the primary contributor, or the first contributor by order. Precision and storage are a Stage 2 decision, recommended as integer kobo.

#### S-09 — Missing security and accountability requirements · Medium

The SRS omits:

- (a) audit logging of **denied** attempts at privileged actions (PRD 1.0);
- (b) login brute-force protection and lockout;
- (c) a password policy, and PIN rules if D-17 is chosen;
- (d) session idle timeout for the shared front-desk device;
- (e) an audit **retention** period;
- (f) Nigeria Data Protection Act 2023 basics (lawful purpose, retention, access restriction for customer phone and email);
- (g) signed, expiring URLs for any shared receipt link.

**Recommendation:** Add these as SEC-016 to SEC-022.

#### S-10 — Acceptance scenarios are incomplete · Medium

- **Affected:** SRS §14.
- **Missing scenarios:**
  - the full end-to-end happy path;
  - a cashier attempting to remove or cancel an executed item;
  - finalising while an item is unexecuted (blocked, then exception);
  - late execution after finalisation;
  - two employees submitting the same item at the same moment;
  - two users finalising the same invoice at the same moment;
  - a three-way rounding split;
  - a discount over the limit, then approval;
  - a cash reversal appearing in the closing;
  - an unverified transfer;
  - a catalogue price change after order creation (historical pricing);
  - a bonus recalculated after a correction;
  - a disabled user's history being kept.
- **Recommendation:** Add them as §14.11 to §14.23 in SRS 1.1. The full list is in Section 5.3.

#### S-11 — Scope added in the SRS without PRD support · Low

| SRS item | Issue | Recommendation |
|---|---|---|
| FR-CUS-009 "controlled duplicate-resolution process" | Implies customer merging, which the PRD does not ask for | A warning only; defer merging |
| FR-EXE-011 notifications | The PRD is silent (see P-21) | Keep as "Should" once D-22 is decided |
| §6.7 "Reviewed" closing state | Extra state | Fold into Approved (S-02) |
| FR-BON-016 "shall not represent as payroll" | Not testable, and it is a scope statement, not a requirement | Move to §15 Constraints |

#### S-12 — Business scope and branches · Low (Stage 2)

- **Affected:** FR-IAM-007, SEC-003.
- **Problem:** The SRS requires a "business scope" but defines only one business. The data-model approach is undecided: a business ID column, a branch ID column, or neither.
- **Recommendation:** Defer to Stage 2. The provisional recommendation is a `business_id` on core tables now, with no branch entity in the MVP.

---

## 4. Deliverable C — Cross-Document Consistency Matrix

Sources: **BO** = business overview, **P1.1** = PRD 1.1, **P1.2** = PRD 1.2, **S** = SRS 1.0.

| ID | Topic | BO | P1.1 | P1.2 | SRS 1.0 | Inconsistency | Resolution | PO decision |
|---|---|---|---|---|---|---|---|---|
| X-01 | PRD 1.1 §23 decisions | — | Open questions | Settled in the text | Restated as Must | Consistent, but the answers are implicit, not logged | Record them as a decision log in PRD 1.3 | No |
| X-02 | Finalisation gate | "reconcile … at end of visit" | **Blocked** until resolved or exception | "resolve through controlled procedures" | "configured rules" (TD-003) | Weakened, then delegated | D-01 | **Yes** |
| X-03 | Transaction vs service order | "customer transaction" | "service order" | Both terms, used interchangeably | FR-CUS-004 "transaction reference" vs FR-ORD-008 "order reference" | Two references for one thing? | One business object, **Service Order** (shown to users as "Visit"), with one reference | No |
| X-04 | One or many execution records | "each records, *or* recorded once" | Shared service item | Undecided | One execution, many contributors, plus "one or more executions per item" | Unresolved | D-03 | **Yes** |
| X-05 | Who sets allocation | — | **Owner configures** (§9.2) | Silent | Silent | Owner-config intent lost | D-04 | **Yes** |
| X-06 | Allocation methods | — | 4 + "other authorised" | 4 + "other configurable" | 4 separate FRs | Percentage and weighted are redundant; "other" cannot be tested | D-04 | **Yes** |
| X-07 | Sharing channels | WhatsApp, email, PDF, print | WhatsApp, email, PDF, print | "shareable digitally" | FR-INV-011 "device capabilities"; §12.2 WhatsApp/email **future** | Requirement downgraded | D-16 | **Yes** |
| X-08 | Push notifications | "where supported" | "where configured" | Absent | FR-EXE-011 "should" | Dropped, then partly reintroduced | D-22 | Accept recommendation |
| X-09 | Bonus criteria | Value, count, individual, team, targets | Value, count, customers, targets, individual, collaborative | Contribution, collected revenue, combination, counts | Same as P1.2 | "Customers served" and collaborative-only bonus criteria were dropped; "collected revenue" was added | D-14 | **Yes** |
| X-10 | Approver role naming | Owner | Owner | "Owner or authorised management" | FR-BON-010 "owner or **manager**" | "Manager" is not a defined role | Use "user with permission `bonus.approve`" | No |
| X-11 | Refunds | — | Open question: reversals only? | Refunds **and** reversals | FR-REF treats them as one | Not distinguished | D-09 | **Yes** |
| X-12 | Overpayment | — | — | Silent | FR-PAY-008 rejects; TD-007 open | SRS decides, then reopens | D-08 | **Yes** |
| X-13 | Data import scope | Google Sheet of staff records | Migration question | Customers and services "particularly" | FR-IMP-002 customers and services | "particularly" vs exactly | D-21 | Confirm |
| X-14 | Execution states vs SRS | — | "investigate, correct, cancel, dispute" | "dispute or correction" | Confirmed / Disputed / Corrected / Cancelled | "Corrected" as a state on the *original* is ambiguous | Use "Superseded" plus a link (S-02) | No |
| X-15 | Employee visibility | — | Open question | Own by default; team by permission | FR-PERF-008/009 | Consistent. What employees see of *orders* is undefined | D-18 | **Yes** |
| X-16 | Deployment | cPanel preferred; Vercel optional | cPanel-compatible; VPS | cPanel preferred; VPS | Same | Next.js on cPanel is unresolved (TD-014) | Stage 2/3 | No (Stage 2) |
| X-17 | Logging denied attempts | — | — (P1.0 scenario 3) | Silent | Silent | Lost requirement | SEC-016 | No |
| X-18 | Dashboard metric list | Revenue by period, payments, discrepancies, staff | Detailed list (§10) | Generic | FR-DASH generic | Detail lost | Restore the P1.1 §10 list as FR-DASH-011+ | Confirm |
| X-19 | Taxes | — | "following confirmation of business requirements" | Full engine required | Full engine required | The business need was never confirmed | D-13 | **Yes** |
| X-20 | Duplicate identity rules | — | — | "avoid unintended duplicates" (imports) | FR-CUS-009 adds merging | Scope creep | S-11 | No |

---

## 5. Deliverables D and E — Traceability Matrix and Proposed Corrections

### 5.1 Requirements traceability matrix

Verification methods:

- **AT** = automated test (unit or feature);
- **E2E** = end-to-end scenario;
- **INSP** = inspection or review;
- **DEMO** = demonstration to the owner.

| PRD ref | PRD capability | SRS requirements | Gap / issue | Action | Verification |
|---|---|---|---|---|---|
| §5, §6.1 #1–2 | Auth, users, RBAC | FR-IAM-001–007, FR-AUTH-001–006, SEC-001–002, 005, 014 | Overlap (S-05); no lockout or idle timeout (S-09) | Consolidate; add SEC-017/018 | AT (permission matrix tests), E2E |
| §6.1 #3 | Business settings | FR-BIZ-001–006 | Logo and receipt or invoice settings dropped (P-25e); timezone (TD-002) | Restore list; Stage 2 timezone | DEMO, AT |
| §7.1 | Customers and guests | FR-CUS-001–009 | FR-CUS-005 refers to a non-existent policy; FR-CUS-009 scope creep; transaction history missing | Rewrite (S-04); restore history | AT, E2E |
| §7.2 | Catalogue and price snapshot | FR-SVC-001–008, FR-ORD-003 | Variable pricing (P-06); products (P-07) | D-06, D-07 | AT (historical price scenario) |
| §7.3 | Service order | FR-ORD-001–010 | Item states; front-desk add/cancel (P-12, P-13); multi-day (P-22) | D-19, D-20, D-23; S-02 | AT (state transitions), E2E |
| §7.3 | Assignment | FR-ORD-005/006 | Meaning of assignment versus who may submit | Assignment = expectation only; reconciliation flags "performed by unassigned" as information | AT |
| §7.4 | Execution, auto-confirm | FR-EXE-001–012, BR-004/005 | One vs many (S-03); late submission (P-02); "eligible" (S-04) | D-02, D-03, D-18 | AT (duplicate and concurrency), E2E |
| §7.4 | Dispute and correction | FR-EXE-009–012, BR-006 | Who may dispute; whether a dispute blocks finalisation | D-03: named collaborators and supervisors can dispute; an open dispute blocks finalisation unless there is an exception | AT |
| §7.5 | Collaborative contribution | FR-ATTR-001–012, BR-001/002/009–012 | Who sets allocation (X-05); redundant methods (X-06); value basis (P-05); rounding (S-08) | D-04, D-05; TD-005 | AT (3-way rounding; sums = line value) |
| §7.6 | Additional services | FR-ADD-001–007, BR-007 | Front-desk add; execution before approval; price (P-13) | D-20 | AT, E2E |
| §7.7 | Reconciliation | FR-REC-001–008 | Billing basis and gate (P-01); FR-REC-003 meaning (S-03) | D-01; rewrite FR-REC-003/007 | AT (each discrepancy type), E2E |
| §7.8 | Invoice and finalisation | FR-INV-001–011, BR-013–015 | Amendment model (P-16); line generation authority | D-15; "invoice lines generated by the system only" | AT |
| §7.9 | Payments | FR-PAY-001–012 | Deposits and overpayment (P-08) | D-08 | AT (partial payment scenarios) |
| §7.9 | Receipts and sharing | FR-RCT-001–007, FR-INV-010/011 | Channels downgraded (X-07); printer format | D-16 | DEMO (print, WhatsApp, email), AT |
| §7.10 | Discounts | FR-DIS-001–007 | Scope, over-limit flow, effect on contributions (P-14) | D-12 | AT (limits, spreading discounts over lines) |
| §7.10 | Taxes | FR-TAX-001–007 | Applicability unconfirmed (P-15); order of calculation (TD-006) | D-13 | AT (inclusive and exclusive examples) |
| §7.11 | Refunds and reversals | FR-REF-001–007, BR-020 | Not distinguished (P-09) | D-09 | AT, E2E |
| §7.12 | Cashier closing | FR-CASH-001–010, BR-025/026 | Period, multiple cashiers, reopen, transfer verification (P-11) | D-10, D-11 | AT, E2E |
| §7.13 | Staff performance | FR-PERF-001–010 | Value definitions (P-05); customers served (P-18) | D-05; P-18 definition | AT (report totals = source records) |
| §7.14 | Bonus | FR-BON-001–016, BR-021–024 | Rule types, Voided, recalculation, instalments (P-10) | D-14 | AT (worked examples per rule type) |
| §5, §6.1 #21 | Owner dashboard | FR-DASH-001–010 | Metric list lost (X-18); billed vs collected (P-23) | Restore list; D-24 | DEMO, AT (dashboard = report queries) |
| §9 | Audit trail | FR-AUD-001–008, SEC-009/010 | Denied attempts (X-17); retention (S-09) | SEC-016, SEC-020 | AT (every listed action emits an audit event) |
| §9 | Segregation of duties | — | Missing (P-24) | D-25 → new BR | AT |
| §10 | PWA, responsive, no sensitive caching | UI-001/002, NFR-PWA-001–006, SEC-013 | Supported browser matrix missing | D-26 | INSP (service-worker cache review), DEMO |
| §10 | Stack, modular monolith, independent deployment | §2.1, NFR-PORT-001–004, NFR-MNT-001–006 | Next.js on cPanel (TD-014) | Stage 2/3 | INSP |
| §10 | Migrations, tests, backups | NFR-MNT-003/004, NFR-REL-003, SEC-012 | No backup targets (D-26) | D-26 | INSP plus restore drill |
| §11 | Data import | FR-IMP-001–007 | Exact columns and duplicate key (P-26) | D-21 | AT (fixture files), DEMO |
| §12 | MVP acceptance | §14 scenarios | Scenarios missing (S-10) | Add §14.11–14.23 | E2E |
| §13 | Risk: staff fail to submit | NFR-USE-001/002 | Not measurable; device access (P-19) | D-17; ≤ 3-tap target | DEMO, usability walkthrough |

**Coverage conclusion.** Every PRD 1.2 capability has *some* SRS coverage, so there are no orphan capabilities. Coverage is *not yet testable* for reconciliation, contribution value, refunds, cashier closing and bonuses until D-01, D-03–D-05, D-09–D-11 and D-14 are decided.

### 5.2 Proposed PRD amendments (would become PRD v1.3)

These are proposals. Nothing in `prd-1.2.md` has been changed. The final wording depends on your decisions.

| ID | Section | Change | Type | Depends on |
|---|---|---|---|---|
| AM-P-01 | New §7.7a "Billing and finalisation rule" | Add the D-01 rule: billable = authorised items; gate; finalise-with-exception permission; lines generated by the system | Add | D-01 |
| AM-P-02 | §7.4 | Add late-submission handling | Add | D-02 |
| AM-P-03 | §7.4, §7.5 | Replace "each employee records … or …" with a single collaboration model: one execution per item, join, dispute | Change | D-03 |
| AM-P-04 | §7.5 | Allocation is set per service by the owner; three presets; remove "other configurable approaches"; supervisor override with reason | Change / Remove | D-04 |
| AM-P-05 | §7.13, §7.14 | Define Participated value, Allocated contribution, Collected contribution | Add | D-05 |
| AM-P-06 | §7.2 | Service variants and permissioned price override; Product/Material item type | Add | D-06, D-07 |
| AM-P-07 | §7.9 | Deposits (if chosen); overpayment rule | Add | D-08 |
| AM-P-08 | §7.11 | Define refund vs reversal; permission not held by Cashier by default; effect on staff credit | Change | D-09 |
| AM-P-09 | §7.12 | Closing = per cashier per business day; transfer verification; POS end-of-day slip; owner-only reopening | Change | D-10, D-11 |
| AM-P-10 | §7.10 | Discount scope, caps, over-limit approval; simplified tax model | Change | D-12, D-13 |
| AM-P-11 | §7.8 | Invoice revisions; void rule | Add | D-15 |
| AM-P-12 | §7.9 | Restore WhatsApp, email, PDF and print channels; printer format | Change (restore) | D-16 |
| AM-P-13 | §7.14 | Bonus rule types; Voided state; recalculation and adjustment; single payout | Change | D-14 |
| AM-P-14 | §5, §9 | Employee visibility; segregation of duties; shared device mode | Add | D-17, D-18, D-25 |
| AM-P-15 | §6.1, §7.6 | Front-desk direct additions; execution against pending request | Change | D-20 |
| AM-P-16 | New §6.3 | Restore dashboard metric list (P1.1 §10), business configuration list, customer history, success indicators | Restore | P-25 |
| AM-P-17 | §11 | Import = customers and services; Google Sheet archive not imported | Change | D-21 |
| AM-P-18 | §12 | Add acceptance outcomes: historical pricing, late submission, exception finalisation, transfer verification | Add | — |
| AM-P-19 | Throughout | Assign requirement IDs (`PRD-x.y-n`); glossary aligned with SRS §1.3 | Editorial | — |
| AM-P-20 | New appendix | Decision log for P1.1 §23 and this register | Add | — |

### 5.3 Proposed SRS amendments (would become SRS v1.1)

| ID | Section | Change |
|---|---|---|
| AM-S-01 | §1.3 | Glossary: Service Order = Visit = the single transaction; Order Item; Execution; Contributor; Participated / Allocated / Collected contribution; Refund vs Reversal; Business Day; Billed vs Collected revenue; Finalisation exception |
| AM-S-02 | §3.5 / §4.1 | Consolidate FR-IAM and FR-AUTH (S-05) |
| AM-S-03 | §3.6 (new) | Permission catalogue: the named permissions (for example `order.create`, `order.item.cancel`, `execution.submit`, `execution.correct`, `invoice.finalise`, `invoice.finalise_with_exception`, `invoice.amend`, `discount.apply.tier1/2`, `payment.record`, `payment.reverse`, `refund.issue`, `transfer.verify`, `closing.submit/approve/reopen`, `bonus.configure/approve/pay`, `audit.view`, `report.team`) plus the default role-to-permission matrix |
| AM-S-04 | §4.x | Rewrite the untestable FRs (S-04 table) |
| AM-S-05 | §4.6, §4.7 | One active execution per item; join; dispute; supersede; quantity rule (S-03) |
| AM-S-06 | §4.9 | Rewrite FR-REC-003/007 per D-01; reconciliation is a derived view |
| AM-S-07 | §4.10 | FR-INV-012: invoice lines are generated by the system from billable items and cannot be added or removed by hand. FR-INV-013: revisions |
| AM-S-08 | §4.13–4.16 | Deposits (if chosen), transfer verification, reversal vs refund, closing model |
| AM-S-09 | §4.18 | Bonus rule types with worked examples; Voided; adjustments |
| AM-S-10 | §4.19 | FR-DASH-011+: restored metric list; billed, collected and outstanding |
| AM-S-11 | §5 | New BRs from decisions: billing basis, late submission, segregation of duties, discount spreading, allocation sums to line value |
| AM-S-12 | §6 | Product-level state lists (S-02). Full transition tables come in Stage 2 |
| AM-S-13 | §7.2 | Entity inventory update (S-07) |
| AM-S-14 | §10 | SEC-016 to SEC-022 (S-09) |
| AM-S-15 | §11 | Numeric NFR targets (D-26) |
| AM-S-16 | §14 | Add scenarios §14.11–14.23 (below) |
| AM-S-17 | §16 | Move product TDs to the decision register; keep TD-001/002/005/006/010/014/015 |
| AM-S-18 | §17 | Requirement-level traceability to PRD IDs (Section 5.1 of this report) |

#### Proposed additional acceptance scenarios (SRS §14.11–14.23)

1. **Happy path end-to-end.** Guest order with 2 services. Two employees each submit one. Finalise. Pay part by cash and part by POS. Receipt. Closing. Audit trail shows every step.
2. **Unauthorised reduction.** A cashier tries to cancel or remove an executed item, through the UI and directly through the API. The action is denied, a denied-attempt audit event is recorded, and the invoice is unchanged.
3. **Gate.** An item has no execution. Ordinary finalisation is blocked. Exception finalisation works only with permission and a reason, and is flagged on the dashboard.
4. **Late execution.** An execution is submitted after finalisation for a cancelled item. A post-finalisation discrepancy is raised.
5. **Concurrent submission.** Two employees submit the same item at the same moment. One execution results; the second is offered "join".
6. **Concurrent finalisation.** Two users finalise at the same moment. Exactly one invoice and one invoice number are produced.
7. **Three-way rounding.** ₦20,000 split equally three ways. Allocations sum to exactly ₦20,000.00. The leftover goes according to the rule. Revenue = ₦20,000.
8. **Discount over limit.** A cashier at a 10% cap requests 20%. An approval request is created and cannot be approved by the requester. After approval, the totals are correct.
9. **Cash reversal.** A reversal by an authorised user is reflected in the closing's expected cash. The original payment is still visible.
10. **Unverified transfer.** A recorded transfer shows Unverified on the dashboard until verified. Verification is audited.
11. **Historical pricing.** The catalogue price changes after the order is created. The order and invoice keep the old price.
12. **Bonus recalculation.** An execution is corrected while the bonus is Calculated, so the bonus is recalculated. If the correction comes after Paid, an adjustment appears in the next period.
13. **Disabled user.** A disabled employee cannot log in. Their historical executions and contributions stay in reports.

---

## 6. Deliverable F — Outstanding Decisions Register

Legend for the "Affects" column:

- **DM** = data model
- **FI** = financial integrity
- **SEC** = security
- **ARCH** = architecture

**BLOCKING** means it must be decided before Stage 2 can design the data model.

### Group A — Core accountability flow

| ID | Subject | Ambiguity | Alternatives and consequences | Recommended | Why | Affects |
|---|---|---|---|---|---|---|
| **D-01** BLOCKING | Billing basis and finalisation gate | What is billed when requested ≠ performed; whether finalisation is blocked | **A.** Bill all authorised items regardless of execution. Revenue is protected, but the gate is weak and an unperformed service could be billed. **B.** Bill only executed items. Fair to the customer, but a cashier can finalise before staff submit and under-bill, which is the leakage route. **C.** Bill authorised items, and *block* finalisation until each item is executed, cancelled through the authorised route, or covered by a permissioned exception with a reason. This is the strongest control, but staff must submit before the customer pays. | **C** | Closes the leakage route while still handling forgotten submissions through an accountable exception. Restores the PRD 1.1 intent. | DM, FI, SEC |
| **D-02** BLOCKING | Executions after finalisation | Rejected or accepted? | **A.** Reject. Staff work is lost and they disengage. **B.** Accept; billed items attach with no financial effect; unbilled matches raise a post-finalisation discrepancy; a time window (for example 48 hours), after which supervisor permission is needed. | **B** | Keeps staff records complete and makes fraud patterns visible. | DM, FI |
| **D-03** BLOCKING | Collaboration model | One record or many; who names collaborators; join; dispute | **A.** Each employee submits separately and the system merges. This is complex and invites duplicates. **B.** One execution; only the submitter names collaborators. Simple, but omitted staff cannot correct it. **C.** One execution per item; any participant creates it; others *join* themselves until finalisation; named collaborators may dispute; a supervisor resolves. | **C** | One source of truth per item and no duplicates. Each employee still records their own participation, which honours the business overview's intent. | DM, FI |
| **D-04** BLOCKING | Allocation method authority and presets | Who sets percentages; redundant methods | **A.** Employees set percentages at submission. Fast, but open to manipulation. **B.** Owner sets a default per service with presets Equal / Primary-Assistant (configured %) / Custom percentages; a supervisor can override per execution with a reason. **C.** Equal only in the MVP. Simplest, but the PRD requires more. | **B**, and drop "other configurable approaches" | Credit is money, so the rule should come from the owner, not from the people who benefit. | DM, FI |
| **D-19** | Cancelling unexecuted requested items | Who can cancel, and when | **A.** Front desk, with a reason; audited; on the discrepancy report; a late execution raises a high-priority flag. **B.** Supervisor only. Safer, but slows the front desk when customers change their minds. | **A**, plus a supervisor permission once any execution exists | Balances day-to-day flow with traceability. | DM, SEC |
| **D-20** | Additional services | Front-desk adding; execution before approval; rejected work | **A.** Front desk adds directly (raises the bill, audited). Employees request; they may execute while the request is pending (not billable or credited until approved). Rejected work stays flagged, with no charge and no credit. **B.** Strict: no execution before approval. Unrealistic on the salon floor. | **A** | Matches how customers ask stylists directly, without bypassing approval. | DM, FI |
| **D-23** | Multi-day and abandoned orders; revenue date | Duration; dating | Allow orders to stay open across days. Show an ageing view. Cancel abandoned orders through D-19. Revenue dated at finalisation; collections dated at payment. | As stated | Simple and auditable. | DM, FI |

### Group B — Pricing and invoicing

| ID | Subject | Ambiguity | Alternatives and consequences | Recommended | Why | Affects |
|---|---|---|---|---|---|---|
| **D-06** BLOCKING | Variable pricing | How prices vary by size or length | **A.** Variants as separate catalogue services. Fully controlled, but the catalogue gets longer. **B.** Free price override. Flexible, but it is a leakage route. **C.** A plus a permissioned override with a reason, shown on the exceptions report. | **C** | Covers real pricing without an uncontrolled override. **Please tell me how your prices vary.** | DM, FI, SEC |
| **D-07** BLOCKING | Products and hair attachments on the invoice | Are they sold? | **A.** Add a Product/Material item type (price only, no stock, no execution or credit). **B.** Exclude, so they are handled outside the system and leakage risk remains. | **A**, if you sell them | Keeps all takings inside the system. | DM, FI |
| **D-12** | Discounts | Scope, caps, over-limit, staff credit | Line-level and invoice-level; a percentage cap and an amount cap per permission tier; reason required; over-limit → approval request; invoice discount spread across lines in proportion to value (it reduces allocated contribution). Alternative: block over-limit with no approval route. That is simpler, but staff will work around it. | As stated | Controlled and transparent. | DM, FI, SEC |
| **D-13** BLOCKING | Tax | Does the salon charge VAT? | **A.** Tax off by default. When on: one rate, business-wide inclusive *or* exclusive, per-service exemption, discount before tax. **B.** A full multi-tax engine as PRD 1.2 implies. Large cost; probably unnecessary. | **A**. **Please confirm with your accountant whether you charge VAT.** | Avoids building complexity that may never be used. | DM, FI |
| **D-15** | Invoice amendment and void | Model | **A.** Revisions that cannot be changed; payments carry forward; reduction below the amount paid requires a refund; void only when net paid = 0. **B.** Edit in place with an audit diff. Weaker evidence. | **A** | The original evidence is never altered. | DM, FI |
| **D-24** | Revenue definitions | Billed vs collected | Show Billed, Collected and Outstanding, each clearly labelled. | As stated | Avoids misleading totals. | FI |

### Group C — Payments and cash

| ID | Subject | Ambiguity | Alternatives and consequences | Recommended | Why | Affects |
|---|---|---|---|---|---|---|
| **D-08** BLOCKING | Deposits and prepayment; overpayment | Can customers pay before finalisation? Can they overpay? | **A.** Payment only after finalisation; overpayment rejected (cash change is not recorded as payment). **B.** A plus order deposits applied automatically at finalisation. **C.** Full customer credit wallet. Deferred. | **A** if deposits are rare; **B** if they are common | **Please tell me whether customers pay deposits.** | DM, FI |
| **D-09** BLOCKING | Refund vs reversal | Definitions, permissions, limits, staff credit | Reversal = payment recorded in error. Refund = money returned, full or partial up to net paid. Both need a permission the Cashier role lacks by default and a reason; both are visible in the closing. A refund affects staff credit only if paired with a service correction. Alternative: the cashier may reverse their own entries the same day. Convenient, but this is the classic cash-skimming route. | As stated | Closes the record-receipt-reverse route. | DM, FI, SEC |
| **D-10** | Cashier closing model | Period, multiple cashiers, review, reopen | One closing per cashier per business day, covering the payments that user recorded. Declared amounts for cash (counted), POS (end-of-day slip) and transfers (list). Discrepancy is an attribute. Submitted → Approved by owner or permitted user; reopening owner-only with a reason. Alternative: shift-based sessions. Deferred unless you run shifts. | As stated | Fits one salon and supports several cashiers. **Do you have more than one person taking payments on the same day?** | DM, FI, SEC |
| **D-11** | Transfer verification | Who verifies, and when | Transfers start Unverified and are Verified by a permitted user against the bank record. Receipt allowed at recording. Unverified transfers appear on the dashboard. | As stated | Defends against fake transfer alerts. | DM, FI, SEC |

### Group D — Performance and bonuses

| ID | Subject | Ambiguity | Alternatives and consequences | Recommended | Why | Affects |
|---|---|---|---|---|---|---|
| **D-05** BLOCKING | Contribution value definitions | What value is credited | Three metrics: Participated value (full, not additive), Allocated contribution (net of discount, before tax, adds up to revenue), Collected contribution (allocated × invoice collected ratio). **Sub-decision:** should discounts reduce staff credit? **Yes** = credit reflects true revenue but staff bear cashier discounts. **No** = staff credit is based on the pre-discount price and no longer sums exactly to revenue. | Three metrics; discounts **do** reduce allocated contribution | Keeps staff totals reconciled to real revenue. Bonus rules can still use gross value if you prefer. | DM, FI |
| **D-14** BLOCKING | Bonus rule types and lifecycle | Which rules; reject or recalc; instalments | Rule types: % of basis; fixed amount when a threshold is met; tiered %. Basis: allocated, collected or service count. Filters: categories, employees or roles. Period: weekly or monthly. States: Calculated → Approved → Paid, plus Voided. Recalculation replaces Calculated; later corrections become next-period adjustments. Single payout. | As stated | Testable, covers the criteria you listed, and is not a payroll engine. **Tell me which bonus schemes you actually plan to run.** | DM, FI |

### Group E — Access and user experience

| ID | Subject | Ambiguity | Alternatives and consequences | Recommended | Why | Affects |
|---|---|---|---|---|---|---|
| **D-16** | Sharing and printing | Channels; printer | PDF download; print (A4 and/or 80 mm thermal); WhatsApp via the device share sheet or a `wa.me` signed expiring link; email via SMTP. WhatsApp Business API deferred. | As stated | Meets the business overview at minimal cost. **Which printer do you use?** | ARCH, SEC |
| **D-17** | Staff devices | Own phones vs shared tablet | **A.** Own phones only. **B.** A plus shared-tablet quick PIN switch. | **B** if any staff lack smartphones; otherwise **A** | Staff not submitting is the top product risk. | ARCH, SEC |
| **D-18** | Employee visibility | Orders and prices | All open orders for today, showing reference, first name or "Guest", and services. No prices, phones or invoices. Own performance with own values. | As stated | Least privilege plus motivation. | SEC |
| **D-22** | Notifications | Channels | In-app (badge and list, polling) in the MVP; Web Push as "Should". | As stated | Low cost; push is limited on iOS. | ARCH |
| **D-25** | Segregation of duties | Self-approval | Approver ≠ requester for discount overrides, refunds, reversals and corrections; Owner exempt and audited. | As stated | Standard anti-fraud control. **Is there always a second authorised person on site?** | SEC |

### Group F — Data and non-functional

| ID | Subject | Ambiguity | Alternatives and consequences | Recommended | Why | Affects |
|---|---|---|---|---|---|---|
| **D-21** | Import scope | What to import | Customers and services only. Google Sheet history archived, not imported. Duplicate key = normalised phone number. Sample MyCantant export needed. | As stated | Unreconciled history would distort reports. | DM |
| **D-26** | Non-functional targets | No numbers | Proposed defaults: operational API p95 < 500 ms and page interactive < 3 s on mid-range Android over 4G at up to 30 concurrent users; daily automated database backup kept off-server for 30 days (a maximum of 24 hours of data lost, and recovery within 4 hours on cPanel; tighter on a VPS); quarterly restore drill; browsers: Chrome Android (latest 2), Safari iOS 16.4+, desktop Chrome/Edge/Safari (latest 2); audit and financial records kept ≥ 7 years; customer personal data handled under the Nigeria Data Protection Act 2023 (purpose, access restriction, deletion on request where no financial record depends on it). | As stated | Makes the NFRs testable. Values can be adjusted. | ARCH, SEC |

### Deferred to Stage 2 (technical; no owner decision needed now)

- TD-001 authentication strategy.
- TD-002 timezone (Africa/Lagos proposed).
- TD-005 rounding precision (integer kobo proposed).
- TD-006 tax and discount calculation order (detailed rules).
- TD-010 bonus rule versioning mechanics.
- TD-014 hosting topology (Next.js on cPanel feasibility).
- TD-015 backup mechanics.
- Business and branch scoping (S-12).
- Document numbering sequences.
- Idempotency and locking.

---

## 7. What I Need From You

### 7.1 Facts only you can provide

These change the recommendations:

1. **Prices (D-06).** How do prices vary? For example braid size, length, or hair type. Do staff ever agree a price on the spot?
2. **Products and hair (D-07).** Do you sell attachment hair or products at the till?
3. **Deposits (D-08).** Do customers ever pay a deposit or pay before the service is finished?
4. **VAT (D-13).** Does the salon charge VAT on invoices today? Your MyCantant invoices will show this.
5. **Cashiers and supervisors (D-10, D-25).** How many people take payments on the same day? Is there always a second authorised person (supervisor or owner) available to approve?
6. **Devices and printer (D-16, D-17).** Do all staff have smartphones with data? Do you print receipts on A4 or on a small thermal printer?
7. **Bonus schemes (D-14).** Describe one or two bonus schemes you actually intend to run, for example "5% of monthly allocated contribution above ₦300,000".

### 7.2 Approval requested

Please respond with one of the following:

- **"Approve Stage 1 with recommendations"**, plus answers to the seven questions in 7.1. I will then prepare **PRD v1.3** and **SRS v1.1** incorporating the amendments in Section 5 and your decisions, and present them as the requirements baseline before starting Stage 2.
- **Approve with changes.** List the decision IDs where you choose a different option.
- **Reject or request clarification** on specific findings.

I will not begin Stage 2 until you explicitly approve the requirements baseline.
