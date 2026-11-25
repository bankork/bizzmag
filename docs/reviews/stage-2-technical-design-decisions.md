# Stage 2 — Technical Design Decisions and Resolution

**Project:** Salon Operations & Accountability Platform
**Version:** 1.0
**Date:** 2026-11-25
**Status:** Approved by the product owner on 2026-11-25, "with recommendations". Applied as PRD v1.4 and SRS v1.2.
**Baseline:** PRD v1.3 and SRS v1.1, both approved on 2026-11-25.

**Repository facts this document relies on:**

| Area | Current setup |
|---|---|
| Backend | PHP ^8.3, `laravel/framework` ^13.17, `laravel/sanctum` ^4.0, PHPUnit |
| Frontend | `next` 16.4.6, React 19.3, TypeScript, Tailwind, shadcn (Base UI) |
| Database | The local `backend/.env` uses `DB_CONNECTION=mysql`, as confirmed by the owner on 2026-11-25. `.env.example` still defaults to SQLite. The production engine and version still need confirming (§8.2). |

**Scope of this stage:** this document sets rules, constraints and decisions. It is *not* the full architecture. Stage 3 covers the module layout, endpoint catalogue and full ERD.

---

## 0. Summary

### 0.1 What Stage 2 settles

- How money is stored and rounded, with every calculation defined step by step (§2).
- The full lifecycles of orders, items, executions, invoices, payments, approvals, closings and bonuses (§3). This includes guards, side effects, audit events and whether each step can be undone.
- Transaction boundaries, locking, idempotency, uniqueness and immutability, so that two users cannot corrupt the same order (§4).
- Data-model boundaries: which record owns each financial fact (§5).
- Security and authentication arrangements (§6).
- 24 Technical Decision Records (§7).

### 0.2 Two design choices worth understanding before you read further

1. **Invoice lines and staff credit are snapshots that can never be edited.**
   - A finalised invoice stores its own lines.
   - Staff credit is written to a **contribution ledger**. Old rows are marked as replaced, never edited.
   - Reports read only these stored records, so totals cannot drift after the fact.
2. **Every action that changes an order happens one at a time.** Adding items, recording work, applying discounts, taking payment, finalising and amending all lock that order while they run. Two cashiers can never finalise the same visit twice. Two stylists can never both "own" the same braid.

### 0.3 Decisions that need your approval

There are 12 decisions in §8. The most important are:

- **S2-D01 Hosting.** Build the Next.js app as a static site, served from cPanel next to Laravel. No Node.js server is needed in production.
- **S2-D03 New packages.** Four packages to add.
- **S2-D04 Database engine.** MySQL 8.0+ or MariaDB 10.6+.
- **S2-D07 Closing.** A cashier cannot take more money after submitting their closing.

Six facts about your hosting are also needed (§8.2).

---

## 1. Deliverable A — Resolved Business and Technical Rules

These rules follow from the approved requirements, or are the minimum detail needed to implement them. Rules marked **[confirm]** change visible behaviour slightly and are also listed in §8.

| ID | Rule | Source |
|---|---|---|
| RR-01 | Every action that changes an order runs inside a single database transaction. The transaction first locks that order's row (§4.2). | SRS §9, NFR-REL-001 |
| RR-02 | Finalisation requires the client to send the **total that was displayed to the user**. If the server's calculated total differs, the server rejects the request with a conflict error (`409 TOTAL_CHANGED`) and returns the new breakdown. A cashier therefore never finalises a total they did not see. | FR-INV-005, PRD-7.7a-5 |
| RR-03 | Amendments are **one atomic command** containing a change set: items to cancel or add, discount changes, price overrides, a reason, and an optional refund. A preview endpoint runs the same calculation without saving. No order is ever left "half-amended". | FR-INV-007, FR-INV-013 |
| RR-04 | After finalisation, any change that affects billing goes through the amendment command. This includes cancelling an executed item, reinstating an item (R4), adding an item and overriding a price. Changes to contributors or allocation that do not affect billing are made directly, by permission. | FR-INV-006/007 |
| RR-05 **[confirm]** | **Additional-service requests after finalisation.** Within the late window, an employee may still request an additional service on a finalised order. The request shows as R7. Approving it is an amendment, so it needs `additional.decide` plus `invoice.amend`. | PRD-7.4-9, PRD-7.6 |
| RR-06 **[confirm]** | **Partially completed services** have no special status. The work is recorded as an execution. The price is reduced by a line discount or price override with the reason "Partially completed", within the normal caps and approvals. | PRD-7.10 |
| RR-07 | Disputes may be raised after finalisation. They never change billing. If a dispute is resolved by a correction that changes contributors on a finalised line, the contribution ledger is updated (RR-12). This may create a bonus adjustment. | FR-EXE-009, FR-BON-020 |
| RR-08 | A late execution against a line billed by exception (R1 at finalisation) attaches to that line and writes ledger rows. It raises no discrepancy. A late execution against a cancelled line raises R7. | FR-EXE-013 |
| RR-09 | **Business day** is stored as a calendar date (`DATE`), computed in the business timezone when the event happens. It is stored on every payment, reversal, refund, finalisation and closing. Reports and closings group by this stored value. They never convert timezones in SQL. | TD-002 |
| RR-10 | **Collected contribution** follows the approved definition (PRD-7.5-7). The collected ratio for each invoice is measured when the bonus is calculated and **saved with the calculation**. Later collections do not change approved bonuses (see S2-D06). | PRD-7.5-7 |
| RR-11 | For the **service count** basis, formula (a) means a *fixed amount per service*. A percentage of a count would be meaningless. Each active execution on a finalised billable line counts **1 for every contributor**, whether solo or collaborative. | FR-BON-006/007 |
| RR-12 | Staff credit is stored in a **contribution ledger**. Each row records: invoice line, execution, employee, share, allocated amount, participated amount and business day. New rows are written when (a) an invoice revision is created, or (b) the active execution or allocation of a billed line changes. Earlier rows are marked `superseded_at`; they are never edited. Reports read only rows that have not been superseded. | FR-ATTR-012, BR-012 |
| RR-13 | A **bonus adjustment** is calculated by re-running the stored rule version for the affected employee and period. It uses the new allocated values together with the *saved* collected ratios. The difference from the approved amount becomes the adjustment. | FR-BON-020 |
| RR-14 **[confirm]** | If adjustments would make a bonus negative, the payout is **₦0** and the remaining negative amount carries forward to the next period. | FR-BON-020 |
| RR-15 **[confirm]** | Once a user's closing for a business day is **Submitted** or **Approved**, that user cannot record further payments, refunds or reversals for that day. An approver can return the closing to Draft, or another cashier can take the payment. *This replaces the "flag and resubmit" wording in FR-CASH-010.* | FR-CASH-010 |
| RR-16 **[confirm]** | Reversals caused by **rejecting a transfer** move no physical money. They are excluded from cashier closings and listed in a **Transfer exceptions** report. Cash and POS reversals and refunds stay in the closing of the user who performed them. | FR-PAY-013, FR-REF-008 |
| RR-17 | Before finalisation, the payment limit uses **billable items only**. Pending Approval items do not count. | FR-PAY-008 |
| RR-18 | The invoice-level discount is spread across **all lines, including products**, in proportion to their value. Products earn no contribution, so the discount on product lines does not affect staff. | FR-DIS-008 |
| RR-19 | **No derived totals are stored.** Order totals, net paid and balances are always calculated. The only stored financial values are inputs (prices, discounts, payments) and snapshots (invoice lines, ledger rows, bonus calculations). | DRY, data integrity |
| RR-20 | The **primary contributor must hold at least 0.01%**. Under Custom percentages, no contributor may have 0%. Remove that contributor instead. | BR-010/011 |
| RR-21 **[confirm]** | **Exception review.** The owner (or a user with `dashboard.view`) can mark an exception as *reviewed*, with an optional note. The exception types are R7, R8, R9, refunds, reversals, over-limit discounts and transfer rejections. Reviewed items leave the "needs attention" panel but stay in reports. Without this, the exceptions panel grows forever. | FR-DASH-011 |
| RR-22 **[confirm]** | **Reference formats.** All numbering is configurable. Proposed defaults:<br>• **Orders:** `V-YYMMDD-NNN`, a daily sequence. Staff call it "Visit 007".<br>• **Invoices:** `INV-YYYY-NNNNN`, restarting each year, with no gaps.<br>• **Receipts:** `RCT-YYYY-NNNNN`, restarting each year, with no gaps.<br>• **Revisions:** an `/R2`, `/R3` suffix. | FR-BIZ-004 |
| RR-23 | An order with net paid greater than ₦0 cannot be voided. This follows BR-032. Refund or reverse the payments first. | BR-032 |

---

## 2. Deliverable B — Financial Integrity Specification

### 2.1 Money representation (TDR-01)

- **Money** is stored as a whole number of **kobo** in a `BIGINT` column. For example, ₦20,000.00 is stored as `2000000`.
- **Floating-point numbers are never used for money**, in PHP, SQL or TypeScript.
- **Percentages and rates** are stored as whole-number **basis points**, where `10000` = 100.00%. For example, 7.5% is `750` and 33.33% is `3333`.
- **The API** sends amounts as integer kobo, for example `{ "amount": 2000000, "currency": "NGN" }`. The frontend only formats numbers for display. It never calculates authoritative values.
- **PHP** uses one immutable `Money` value object and one `Percentage` value object. All arithmetic goes through these.
- **Database checks** (`CHECK`) enforce that amounts are ≥ 0. Direction comes from the record type: refunds and reversals are separate rows with positive amounts.

### 2.2 Rounding

**Rounding method.** All values are non-negative. Rounding is half-up to the nearest kobo:

```
round_div(a, b) = intdiv(2a + b, 2b)     for a ≥ 0, b > 0
```

**When rounding happens.** Values are rounded at defined points only:

1. each line-discount amount;
2. each line's share of the invoice discount;
3. each line's tax;
4. each contributor's allocated amount;
5. each bonus amount.

Invoice totals are exact sums of rounded line values. Totals are never rounded again.

**Where remainders go.** A remainder always goes to one named target, so value is never created or lost:

| Calculation | Who receives the remainder |
|---|---|
| Invoice-discount spread | The highest-value line. On a tie, the earliest line. |
| Contribution allocation | The primary contributor. |
| Tiered bonus | No remainder arises; each tier is rounded separately and then summed. |

### 2.3 Invoice calculation

One pure domain service, `InvoiceCalculator`, performs every invoice calculation. Its input is the order's billable items, line discounts, the invoice discount and the tax setting. Its output is an immutable breakdown. The **same code** produces the draft preview, the finalisation snapshot and the amendment preview (TDR-03).

The steps, applied to each billable line *i* (services and products):

| Step | Calculation |
|---|---|
| 1. Unit price | `unit` = override price if one exists, otherwise the snapshot price. |
| 2. Line value | `value_i` = unit × quantity. |
| 3. Line discount | Fixed: `ld_i` = the amount. Percentage: `ld_i` = round_div(value_i × bp, 10000). Constraint: ld_i ≤ value_i. |
| 4. After line discount | `v_i` = value_i − ld_i. Then V = Σ v_i. |
| 5. Invoice discount | Fixed: D = the amount. Percentage: D = round_div(V × bp, 10000). Constraint: D ≤ V. |
| 6. Spread the discount | For every line except the remainder line R: `s_i` = round_div(D × v_i, V). Then s_R = D − Σ others. If V = 0, every s_i = 0. |
| 7. Discounted line amount | `g_i` = v_i − s_i. |
| 8. Tax | Tax disabled, or line exempt: tax_i = 0 and net_i = g_i.<br>**Exclusive** (rate r bp): tax_i = round_div(g_i × r, 10000); net_i = g_i; line total = g_i + tax_i.<br>**Inclusive:** tax_i = round_div(g_i × r, 10000 + r); net_i = g_i − tax_i; line total = g_i. |
| 9. Invoice totals | Subtotal = Σ value_i. Discounts = Σ ld_i + D. Tax = Σ tax_i. **Total = Σ line totals.** |

The invariants below must hold. Automated tests check them, including property-based tests over random inputs.

| Invariant | Statement |
|---|---|
| I-1 | Σ s_i = D |
| I-2 | Σ (net_i + tax_i) = Total, in exclusive mode |
| I-3 | Σ g_i = Total, in inclusive mode |
| I-4 | Every value is ≥ 0 |

### 2.4 Contribution allocation

Allocation applies to each billed service line that has an active execution with contributors C (n contributors, primary p). The base is `base` = net_i, which is after discounts and before tax.

| Method | Shares (bp, stored) | Amounts |
|---|---|---|
| **Equal** | Each non-primary contributor: floor(10000/n). Primary: the remainder. | Each non-primary: round_div(base, n). Primary: base − Σ others. |
| **Primary/Assistant** (P bp) | Primary: P. Assistants: floor((10000−P)/(n−1)) each, with the remainder bp going to the primary. If n = 1, the primary gets 10000. | Each non-primary: round_div(base × bp_c, 10000). Primary: base − Σ others. |
| **Custom** | As entered. Must sum to 10000 and each be ≥ 1 (RR-20). | As for Primary/Assistant. |

**Participated value** for each contributor = base, the full line net value.

**Invariant I-5:** Σ allocated = base, for every line.

The results are written to the contribution ledger (RR-12).

### 2.5 Payments and balances

```
net_paid(order) = Σ payments − Σ amounts of reversed payments − Σ refunds
current_total   = draft total                (order Open)
                = current invoice revision total   (order Finalised)
balance         = current_total − net_paid    // never negative (I-6)
```

**Validation rules:**

- A payment must satisfy 0 < amount ≤ balance.
- A refund must satisfy 0 < amount ≤ net_paid.
- Each payment can have at most one reversal. A unique key on `payment_id` enforces this.
- An action that would make current_total < net_paid is refused, unless the same atomic operation also records a refund of the difference (FR-PAY-015, FR-INV-013).

**Payment status** is calculated, not stored: Unpaid, Partially paid or Paid.

### 2.6 Cashier closing

Closing figures are calculated for one user U on one business day d:

| Method | Expected amount |
|---|---|
| Cash | Σ cash payments by U on d − Σ reversals by U on d of cash payments − Σ cash refunds by U on d |
| POS | The same calculation, for POS |
| Transfer | Σ transfer payments by U on d − Σ reversals by U on d of transfers, *excluding* reversals caused by rejecting a transfer (RR-16). Each transfer is listed with its verification status. |

The cashier declares cash (minus any opening float), POS (from the terminal's end-of-day slip) and confirms the transfer list.

**Discrepancy** = declared − expected, for each method and in total.

When the closing is submitted, the expected amounts are **frozen** as `CashierClosingLine` rows. The closing is then compared against those frozen figures.

### 2.7 Bonus calculation

- **Period boundaries.**
  - Weekly: Monday to Sunday, using business days.
  - Monthly: the calendar month.
  - A calculation may run only after the period has ended.
- **Which records count.** Ledger rows that are not superseded and whose business day (the invoice finalisation day) falls inside the period. The rule's filters (categories, employees, roles) are then applied.
- **Basis values:**
  - **Allocated:** Σ allocated.
  - **Collected:** Σ round_div(allocated_row × net_paid_inv, total_inv), with net_paid capped at the invoice total. If the total is 0, the result is 0. The ratio for each invoice is saved (RR-10).
  - **Count:** the number of distinct executions (RR-11).
- **Formulas** (amounts in kobo):
  - **(a) Percentage:** round_div(basis × bp, 10000). For the count basis, it is count × a fixed amount per service.
  - **(b) Threshold:** if basis ≥ T, the fixed amount; otherwise 0.
  - **(c) Tiered marginal:** Σ round_div(portion_in_tier × bp_tier, 10000).
  - **Eligibility thresholds:** if any threshold is not met, the bonus is 0.
- **Result.** Bonus = formula result + Σ unconsumed adjustments. If the result is negative, the payout is 0 and the remainder carries forward (RR-14).
- **Saved with each calculation:**
  - the rule version identifier;
  - the period;
  - the basis totals;
  - the identifiers of the ledger rows used;
  - the saved per-invoice ratios;
  - the formula breakdown;
  - the adjustments consumed.

### 2.8 Historical preservation

| Fact | Where it is stored and kept |
|---|---|
| Price and name at time of sale | `order_items` snapshot columns, plus override columns |
| Billed lines, discounts and tax | `invoice_lines` and `invoice_revisions` (cannot be changed) |
| Tax setting applied | `invoice_revisions` (name, rate in bp, mode) |
| Contribution credit | `contribution_ledger` (rows are superseded, never updated) |
| Bonus inputs and rule | `bonus_calculations` with its inputs, linked to `bonus_rule_versions` |
| Closing expected amounts | `cashier_closing_lines`, frozen at submission |

---

## 3. Deliverable C — Lifecycle and State Transition Specification

This section adds detail to SRS §6. "Lock" means the transaction locks the order's row (`SELECT … FOR UPDATE`) before any checks run. Every transition writes an audit event in the same transaction. In the "Reversible?" column, **No** means the transition can only be compensated by another recorded action; it is never undone in place.

### 3.1 Service order

| Transition | Actor / permission | Guards | Side effects | Audit event | Reversible? |
|---|---|---|---|---|---|
| create → **Open** | `order.create` | ≥ 1 active catalogue item. Guest or customer. | Order reference assigned from the daily sequence. Items snapshotted. | `order.created` | — |
| Open → **Finalised** (ordinary) | `invoice.finalise` | Lock. No R1–R4. Client total = server total (RR-02). net_paid ≤ total (FR-INV-016). | Invoice created with revision 1, lines and invoice number. Ledger rows written. Finalisation business day stored. Late-window clock starts. | `invoice.finalised` | Only by void (net paid = 0) |
| Open → **Finalised** (exception) | `invoice.finalise_with_exception` | As above, but R1–R4 may exist. Reason required. | As above. A `FinalisationException` row records the open discrepancies. R3 items excluded. R4 items treated as cancelled. | `invoice.finalised_with_exception` | As above |
| Open → **Cancelled** | `order.cancel` (+ `execution.correct` if any execution is active) | Lock. net_paid = 0. Reason. | Items Cancelled. Active executions Cancelled. | `order.cancelled` | No |
| Finalised → **Open** (void) | `invoice.void` | Lock. net_paid = 0. Reason. Segregation of duties (SoD) applies. | Invoice Voided (its number is kept). Ledger rows for that invoice superseded. Bonus adjustments evaluated. | `invoice.voided` | Refinalise later (new invoice number) |

Payment status (Unpaid / Partially paid / Paid) is calculated (§2.5). It is not a state.

### 3.2 Order item

| Transition | Actor / permission | Guards | Side effects | Audit event | Reversible? |
|---|---|---|---|---|---|
| → **Requested** | `order.create` / `order.item.add` | Order Open. Item active. | Snapshot. | `order.item_added` | — |
| → **Pending Approval** | `additional.request` | Order Open, or Finalised within the late window (RR-05). | Approvers notified. | `additional.requested` | — |
| Pending → **Approved** | `additional.decide` (+ `invoice.amend` if the order is Finalised) | SoD. | Order Finalised → an amendment revision is created. | `additional.approved` | Cancel through the correction path |
| Pending → **Rejected** | `additional.decide` | Reason. SoD. | Any execution on it shows as R5. | `additional.rejected` | No |
| Pending → **Cancelled** (withdraw) | Requester, or `order.item.cancel_unexecuted` | No decision yet. | — | `additional.withdrawn` | No |
| Requested/Approved → **Cancelled** (unexecuted) | `order.item.cancel_unexecuted` | Lock. Order Open. No active execution. Reason. Draft total stays ≥ net_paid, or a refund is recorded atomically. | Shown on the discrepancy report. | `order.item_cancelled` | Reinstate (R4) or re-add |
| Requested/Approved → **Cancelled** (executed) | `execution.correct` | Lock. Reason. SoD against the submitter. If the order is Finalised → done through an amendment. | Execution Cancelled. Ledger updated if the item was billed. | `order.item_cancelled_executed` | Reinstate |
| Cancelled → **Requested** (R4 reinstate) | `execution.correct` (+ `invoice.amend` if Finalised) | An active execution exists on the item. | Item billable again. | `reconciliation.reinstated` | Cancel again |

### 3.3 Execution

| Transition | Actor / permission | Guards | Side effects | Audit event | Reversible? |
|---|---|---|---|---|---|
| → **Confirmed** | `execution.submit` | Lock. Item type Service. Item status is Requested, Approved or Pending. **No other active execution** on the item (unique key, §4.3). Within the window, otherwise `execution.submit_late`. Contributors are active employees with no duplicates. | Allocation from the service's default method. Submitter is primary. Named contributors notified. Ledger written if the line is already billed (RR-08). R7 raised if the item is cancelled and the order is finalised. | `execution.submitted` | Correction or cancellation |
| Confirmed → Confirmed (join) | `execution.join` | Order Open, or within the window. Not already a contributor. | Shares recalculated with the method. Ledger updated if billed. | `execution.joined` | Removed by a supervisor (correction) |
| Confirmed → Confirmed (contributors edited by the submitter) | Submitter | Order Open. Submitter cannot remove themselves. | As for join. | `execution.contributors_changed` | Yes, by a further edit |
| Confirmed → Confirmed (allocation override) | `allocation.override` | Reason. SoD against the submitter. | Ledger updated if billed. Adjustment if the period is Approved or Paid. | `allocation.overridden` | By another override |
| Confirmed → **Disputed** | `execution.dispute` (named contributor), or `execution.correct` | Reason. | Contributors and correctors notified. Blocks ordinary finalisation (R2). | `execution.disputed` | Dismiss |
| Disputed → **Confirmed** (dismiss) | `execution.correct` | Reason. SoD. | Notify. | `execution.dispute_dismissed` | Dispute again |
| Confirmed/Disputed → **Superseded** + new Confirmed | `execution.correct` | Reason. SoD. | New execution linked to the old one through `supersedes_id`. Ledger updated if billed. | `execution.corrected` | Correct again |
| Confirmed/Disputed → **Cancelled** | `execution.correct` | Reason. SoD. | Item becomes R1 again (Open order) or ledger rows are superseded (Finalised). | `execution.cancelled` | Submit a new execution |

### 3.4 Discount approval request (over-limit)

| Transition | Actor / permission | Guards | Side effects | Audit event | Reversible? |
|---|---|---|---|---|---|
| → **Pending** | `discount.apply` | Requested amount is above the user's cap and within the absolute limits (FR-DIS-006). | The discount is *not* applied. Approvers notified. | `discount.approval_requested` | — |
| Pending → **Approved** | `discount.approve` | Lock. SoD. Approver's cap ≥ amount. Recalculated total still ≥ net_paid (or refund recorded). | Discount applied. Requester notified. | `discount.approved` | Remove the discount (another discount action) |
| Pending → **Rejected** | `discount.approve` | Reason. | Notify. | `discount.rejected` | No |
| Pending → **Withdrawn** | Requester | — | — | `discount.approval_withdrawn` | No |

Pending requests expire when the order is finalised or cancelled. They become Withdrawn, with a system reason.

### 3.5 Invoice and revisions

| Transition | Actor / permission | Guards | Side effects | Audit event | Reversible? |
|---|---|---|---|---|---|
| Revision 1 | Finalisation (§3.1) | — | Number assigned. Lines snapshotted. | (as §3.1) | — |
| Revision n → n+1 (amend) | `invoice.amend` | Lock. Change set valid. Reason. SoD for the parts that need it. New total ≥ net_paid, or refund included in the same operation (FR-INV-013). Client total = server total. | New revision becomes current. The old one stays and cannot be changed. Ledger re-written. Bonus adjustments. | `invoice.amended` | Amend again |
| → **Voided** | `invoice.void` | See §3.1. | See §3.1. | `invoice.voided` | No (refinalise issues a new number) |

### 3.6 Payments, transfer verification, reversals, refunds

| Transition | Actor / permission | Guards | Side effects | Audit event | Reversible? |
|---|---|---|---|---|---|
| → **Recorded** | `payment.record` | Lock. Order Open or Finalised. 0 < amount ≤ balance (RR-17). The user's closing for today is not Submitted or Approved (RR-15). Idempotency key. | Receipt number assigned and receipt created. Transfers start Unverified. Closing Draft created if missing. | `payment.recorded` | Reversal |
| Recorded → **Reversed** | `payment.reverse` | Lock. Not already reversed. Reason. SoD against the recorder. Actor's closing for today is not Submitted. | `PaymentReversal` row. Balance restored. | `payment.reversed` | No (record a new payment) |
| Transfer: Unverified → **Verified** | `transfer.verify` | Not reversed. | — | `transfer.verified` | → Rejected only with a reason, before any refund |
| Transfer: Unverified → **Rejected** | `transfer.verify` | Reason. | System reversal tagged `transfer_rejected`, excluded from closings (RR-16). | `transfer.rejected` | No |
| → **Refund** recorded | `refund.issue` | Lock. 0 < amount ≤ net_paid. Reason. Method. SoD. Actor's closing open. Idempotency key. | Balance increases. A cash refund reduces the actor's expected cash. | `refund.issued` | No (a new payment if made in error) |

Verification status is held in a separate `transfer_verifications` table (latest row wins). This keeps the payment row itself unchanged (TDR-12).

### 3.7 Reconciliation

Reconciliation is **calculated, not stored** (FR-REC-001). Only three kinds of record are stored:

- **R4 decision** (`ReconciliationDecision`): reinstate, or confirm cancellation. Actor `execution.correct`. Not reversible, but a later reinstate or cancel is possible.
- **Finalisation exception** (`FinalisationException`): written at finalisation (§3.1).
- **Exception review** (`ExceptionReview`, RR-21): Unreviewed → Reviewed, by `dashboard.view`, with an optional note. This is a flag only; it never changes business data.

### 3.8 Cashier closing

| Transition | Actor / permission | Guards | Side effects | Audit event | Reversible? |
|---|---|---|---|---|---|
| → **Draft** | System | First money event by the user on that day. | — | — | — |
| Draft → **Submitted** | Owner of the closing, with `closing.submit` | Lock the closing. Declared amounts given. A note if any discrepancy ≠ 0. | Expected amounts frozen into closing lines. The user's money events for that day are blocked (RR-15). Approvers notified. | `closing.submitted` | Return |
| Submitted → **Draft** (return) | `closing.approve` | Reason. | Frozen lines discarded and recalculated at the next submission. Money events unblocked. | `closing.returned` | Resubmit |
| Submitted → **Approved** | `closing.approve` | SoD (not your own closing). | — | `closing.approved` | Reopen |
| Approved → **Draft** (reopen) | `closing.reopen` (owner) | Reason. | As for return. | `closing.reopened` | Resubmit |

### 3.9 Bonus calculation and payout

| Transition | Actor / permission | Guards | Side effects | Audit event | Reversible? |
|---|---|---|---|---|---|
| → **Calculated** | `bonus.calculate` | The period has ended. Rule active. | Existing Calculated rows for the same rule and period are Voided ("Recalculated"). Inputs saved. Adjustments linked (not consumed until Paid). | `bonus.calculated` | Recalculate or void |
| Calculated → **Approved** | `bonus.approve` | SoD with the calculator (owner exempt). | — | `bonus.approved` | Void |
| Approved → **Paid** | `bonus.pay` | Amount = approved amount. Date, method and reference. Idempotency key. | `BonusPayment` row. Linked adjustments marked consumed. | `bonus.paid` | **No** (later corrections become adjustments) |
| Calculated/Approved → **Voided** | `bonus.approve`, or the system | Reason. | Linked adjustments released for the next calculation. | `bonus.voided` | Recalculate |
| *(event)* Adjustment created | System | A ledger change touches an Approved or Paid calculation. | `BonusAdjustment` row (RR-13). | `bonus.adjustment_created` | Superseded by later changes |

---

## 4. Deliverable D — Data Integrity and Concurrency Specification

### 4.1 Transaction boundaries

Each command below runs as one database transaction: it either succeeds completely or saves nothing. If MySQL detects a deadlock, the transaction is retried up to 3 times (`DB::transaction($fn, 3)`).

| Command | Locks, in this order | Writes inside the transaction | After the transaction commits |
|---|---|---|---|
| Create order | Daily sequence row | Order, items, assignments, audit | — |
| Add / cancel item; price override; discount | Order | Item or discount, audit, (refund) | Notifications |
| Submit / join / correct / cancel execution | Order | Execution, contributors, (ledger rows), (R7 marker), audit, (adjustments) | Notifications |
| Additional request / decision | Order | Item, (amendment), audit | Notifications |
| Finalise | Order → invoice sequence | Invoice, revision, lines, ledger, exception record, audit, idempotency record | PDF generated when requested (§7 TDR-07) |
| Amend | Order → invoice | Revision, lines, ledger supersession and new rows, items and discounts, (refund + receipt), adjustments, audit, idempotency record | Notifications |
| Void | Order → invoice | Invoice status, ledger supersession, adjustments, audit | — |
| Record payment | Order → closing (user, day) → receipt sequence | Payment, receipt, (verification row), audit, idempotency record | Email or WhatsApp sharing when requested |
| Reverse / refund | Order → payment → closing | Reversal or refund, audit, idempotency record | — |
| Verify / reject transfer | Order → payment | Verification row, (system reversal), audit | — |
| Submit / return / approve / reopen closing | Closing | Status, frozen lines, audit | Notifications |
| Calculate bonus | Bonus rule row | Calculations, inputs, voids, audit | — |
| Approve / pay bonus | Calculation | Status, payment, adjustments consumed, audit, idempotency record | — |
| Import | (none) | All rows from the batch, results, audit | — |

**Fixed lock order:** order → invoice → payment → closing → sequences. Locks are always taken in this order, which prevents deadlocks.

**Work done outside transactions.** Notifications, emails and PDF generation run only **after commit**. Laravel's after-commit callbacks and queued jobs ensure this. A failure in them can never roll back a financial record.

### 4.2 Concurrency control

- **Order-level lock.** Every command that changes an order (all of §3.1–3.6) starts with `SELECT … FOR UPDATE` on that order's row. Every check (gate, balance, uniqueness) is then made *after* the lock is held. Two commands on the same order therefore run one after the other. Commands on different orders never wait for each other.
- **Stale screens.**
  - Finalise and amend compare the client's displayed total (RR-02).
  - Configuration screens (catalogue, rules, settings) use an optimistic `version` column. A save made against an outdated version returns `409 STALE`.
- **Second submission for the same item.** The second request waits for the lock. It then finds the active execution and returns `409 EXECUTION_EXISTS` with a "join" option (FR-EXE-007). The unique key below is a database-level backstop.

### 4.3 Uniqueness and integrity constraints (database-enforced)

| Constraint | How it is implemented |
|---|---|
| One active execution per item | A stored generated column `active_item_key` = `IF(status IN ('confirmed','disputed'), order_item_id, NULL)`, with a **unique index**. InnoDB allows multiple NULLs, so only one non-NULL (active) value can exist per item. |
| One contributor appearance per execution | `UNIQUE(execution_id, employee_id)` |
| One invoice per order | `UNIQUE(service_order_id)` on `invoices` |
| Unique revision numbers | `UNIQUE(invoice_id, revision_no)` |
| One reversal per payment | `UNIQUE(payment_id)` on `payment_reversals` |
| One closing per user per day | `UNIQUE(business_id, user_id, business_day)` |
| Unique business references | `UNIQUE` on order reference, invoice number and receipt number, per business |
| Idempotency | `UNIQUE(user_id, idempotency_key)` |
| Non-negative money | `CHECK(amount >= 0)` on every money column |
| Valid percentages | `CHECK(bp BETWEEN 1 AND 10000)` |
| Referential integrity | Foreign keys everywhere. `ON DELETE RESTRICT` on all business tables, because business records are never deleted. |

Rules that cannot be expressed in SQL are enforced in domain services and covered by tests. Examples: Σ shares = 10000, and Σ allocated = base.

### 4.4 Idempotency (TDR-09)

**Which endpoints.** The client generates an `Idempotency-Key` (a UUID) once per user intent and sends it as a header. It is required on:

- execution submit;
- finalise;
- amend;
- payment;
- reversal;
- refund;
- transfer verification;
- closing submit;
- bonus pay.

**How it works.**

- The server stores the key, a hash of the request and the response *in the same transaction* as the operation.
- A replay with the same key and payload returns the stored response.
- The same key with a different payload returns `422 IDEMPOTENCY_MISMATCH`.
- Keys are kept for 7 days, and a scheduled task removes older ones.
- Buttons are also disabled while a request is in flight. This is a convenience; it is not the protection.

### 4.5 Immutability and deletion

| Category | Tables | Rule |
|---|---|---|
| **Append-only** | audit events, invoice revisions, invoice lines, payments, reversals, refunds, receipts, transfer verifications, contribution ledger (supersession flag only), bonus payments, bonus adjustments, closing lines, finalisation exceptions, R4 decisions, import results | No update or delete paths in code. Policies and tests assert this. |
| **Status-controlled** | orders, order items, executions, approval requests, closings, bonus calculations, invoices (current revision and void flag) | Only the status and status-metadata columns change, and only through §3 transitions. The content fields cannot change. |
| **Configuration** | users, roles, catalogue, settings, bonus rules | Deactivate instead of delete (`is_active` / `deactivated_at`). A rule edit creates a new version. |
| **Customer personal data** | customers | No delete. A data-protection removal request (SEC-021) **anonymises** the contact fields and name, and is audited. |

Laravel `SoftDeletes` is **not used**. Records are never "deleted but hidden"; they move through explicit states.

### 4.6 Audit generation (TDR-11)

- **Who writes audit events.** Domain commands write them explicitly through an `AuditRecorder`, inside the command's transaction. Each event records: action, subject, before and after values, reason, actor, device and SoD-exemption flag. Model observers are not relied on, because they cannot capture intent or reason.
- **Denied attempts.** Authorisation failures are logged by the exception handler (`audit.denied`). Logging is throttled to 1 event per user, action and minute.
- **Tamper detection.** Each event stores `hash = sha256(previous_hash + canonical_payload)`. A scheduled job checks the chain and alerts the owner if it is broken. This detects direct database edits, for example through phpMyAdmin. **Priority: Should.**

### 4.7 Failure recovery

| Failure | What happens |
|---|---|
| Client times out or retries | Idempotency (§4.4) returns the original result. |
| Server error during a command | The transaction rolls back and nothing is saved. The client may retry with the same key. |
| PDF or email failure | Retried by the queue (3 attempts). The financial record is unaffected. The user can regenerate the document on demand. |
| Scheduled job missed (cron) | All jobs can safely run more than once (pruning, chain check, unverified-transfer alerts, notification cleanup). |
| Database loss | Restore from backup (NFR-REL-005). Quarterly restore drill. |

---

## 5. Data Model Decisions (input to the Stage 3 ERD)

### 5.1 Boundaries and ownership of financial facts

Each financial fact has exactly one owner:

| Fact | Owner (single source of truth) | Never stored elsewhere |
|---|---|---|
| What was asked for, and at what price | `order_items` (snapshot + override) | — |
| Pre-finalisation discounts | `discounts` (scope: line or order) | — |
| What was billed | `invoice_lines` of the **current** `invoice_revision` | Order totals are not stored (RR-19) |
| What was done, and by whom | `executions` + `execution_contributors` (shares in bp) | — |
| Staff credit in money | `contribution_ledger` (rows not superseded) | Not on contributors, not on lines |
| Money in and out | `payments`, `payment_reversals`, `refunds` | Balances are not stored |
| Expected collections at closing | `cashier_closing_lines` (frozen at submission) | — |
| Bonus amounts | `bonus_calculations` (+ adjustments, payments) | — |

### 5.2 Entity decisions

| # | Decision |
|---|---|
| DM-01 | **Business scoping (TDR-13).** `business_id` is placed on the *top-level* tables only: customers, catalogue, orders, payments, closings, settings, bonus rules, audit, sequences and imports. Child rows inherit the business through their parent. Users join a business through a membership row (`business_user`), and roles are scoped to that business. **There is no branch table in the MVP.** Adding `branch_id` later is a migration with a default branch. |
| DM-02 | **Keys (TDR-14).** `BIGINT UNSIGNED` auto-increment primary keys. Sharing uses random 128-bit tokens (`shared_document_links`). Internal IDs are never used as security. |
| DM-03 | **Additional-service requests** *are* order items in status Pending Approval, with requester and decision columns. There is no separate request table, so no fact is duplicated. |
| DM-04 | **Approval requests** table: over-limit discounts only in the MVP. It has a polymorphic `subject` so it can grow later. |
| DM-05 | **Catalogue variants**: `catalogue_items.parent_id` (one level deep). The service's allocation default is stored on the parent and can be overridden per variant. |
| DM-06 | **Employee profile** is 1:1 with a user. An employee is any user with an active profile. Only employees can be contributors. |
| DM-07 | **Payments** belong to `service_orders`. The invoice relationship is derived. |
| DM-08 | **Contribution ledger** rows reference the invoice revision, invoice line, execution, employee, share in bp, allocated kobo, participated kobo, business day, `superseded_at` and `superseded_by_reason`. |
| DM-09 | **Bonus rule versions** store the rule definition as validated JSON (`schema_version`, period, basis, formula, filters, thresholds), so the rule types can grow without migrations. Calculations reference a version. |
| DM-10 | **Number sequences**: `number_sequences(business_id, key, period, next_value)`, locked for update when used. Because the sequence is incremented inside the business transaction, a rollback also rolls back the number. Invoice and receipt numbers therefore have **no gaps** (TDR-10). |
| DM-11 | **Timestamps** are stored in UTC (`APP_TIMEZONE=UTC`). The business timezone is a setting (default Africa/Lagos). `business_day` columns follow RR-09. |
| DM-12 | **Notifications** use Laravel's database notifications table, scoped per user. Push subscriptions go in a separate table (Should). |
| DM-13 | **Import** uses `import_batches` and `import_row_results`. Rows are validated in a dry run and saved in a single transaction (FR-IMP-008). |

---

## 6. Security and Access Decisions

| Topic | Decision |
|---|---|
| **Authentication (TDR-04)** | **Laravel Sanctum SPA (cookie) authentication.** The session is a Secure, HttpOnly, SameSite=Lax cookie. CSRF is protected through Sanctum's `XSRF-TOKEN`. **Requirement:** the frontend and API must be served from the same parent domain, for example `app.<salon-domain>` and `api.<salon-domain>`, or the same host (S2-D01). No tokens are kept in `localStorage`. Sanctum bearer tokens are reserved for future native apps. |
| **Session policy** | A server-side middleware enforces idle timeouts by role (SEC-018): 30 minutes for users with financial permissions, 7 days for employee-only users. `SESSION_LIFETIME` is set to the longest value, and the middleware applies the shorter limits. |
| **Shared device mode** (Should) | The owner registers a device, which stores an HttpOnly, signed device cookie. On that device, `POST /pin-switch` re-authenticates as the PIN holder. It is throttled (SEC-017) and the device auto-locks after 2 minutes. |
| **Roles and permissions (TDR-21)** | `spatie/laravel-permission`, with its *teams* feature keyed to `business_id`. Discount caps are columns on the role. A `Gate::before` gives the Owner role full access **except** where segregation of duties applies; SoD is enforced in domain services, not in the gate. |
| **Authorisation layering** | Requests are checked in this order: (1) route middleware (authenticated, business scope); (2) a Policy per aggregate (permission plus record state); (3) the domain service checks the business rules and SoD again inside the lock. The frontend reads `/me/permissions` only to decide what to *display*. |
| **Business scoping** | A global scope on top-level models uses the current business from the user's membership. Child tables are always reached through their parent. Tests assert that data from another business is never visible. |
| **Field-level restriction** (BR-041) | API resources have *views*: an employee view with no prices, contacts or invoice data, and a staff view. The view is chosen by permission on the server. |
| **Privileged operation protection** | Permission + state guards + SoD + idempotency + order lock + audit, including denied attempts. |
| **Audit access** | `audit.view` (owner by default). Read-only API. The export is audited too. |
| **Sensitive data** | Customer phone and email are visible only with `customer.view_contact`. No personal data in notifications (FR-NOT-004) or logs. Logs record IDs, not personal data. |
| **PWA caching** | The service worker precaches static build assets and an offline page only. API requests always go to the network (`NetworkOnly`). No IndexedDB storage in the MVP. A new version is announced to the user (NFR-PWA-007). |
| **Shared links** | 128-bit random token, expiry (default 7 days), revocable. The link shows one PDF and no other data (SEC-022). |
| **Secrets** | `.env` stays outside the web root on cPanel. Separate production database user. Debug off in production. |

---

## 7. Deliverable E — Technical Decision Records

Each TDR uses the same fields: context and problem, alternatives, decision, justification, consequences, affected requirements, and impact on the data model (DM), financial integrity (FI) and implementation. "**Approval**" marks the TDRs listed in §8.

### TDR-01 — Money and percentage representation
- **Context and problem.** Money must never be created or lost by floating-point arithmetic. Neither PHP nor JavaScript has a native decimal type.
- **Alternatives.**
  - (a) `DECIMAL(15,2)` with a BCMath library.
  - (b) **Integer kobo (`BIGINT`) and integer basis points.**
  - (c) Floats. Rejected.
- **Decision.** (b).
- **Justification.** Integers are exact in PHP (64-bit) and in SQL. JSON stays safe below 2^53 kobo (about ₦90 trillion). No extra library is needed. Rounding happens only where §2.2 says.
- **Consequences.** Every money column is `*_kobo`. Formatting happens only at the edges. Developers must use the `Money` value object.
- **Affected requirements.** §7.1(9), TD-005. **DM:** all money columns. **FI:** exact. **Implementation:** a value-object library inside the project.

### TDR-02 — Rounding method and remainder allocation
- **Problem.** The SRS left the rounding method and the treatment of remainders open.
- **Alternatives.** Banker's rounding, half-up, or truncation. For remainders: largest remainder, or a named target.
- **Decision.** Half-up to the kobo at the defined points. Remainders go to named targets (§2.2).
- **Justification.** Easy to explain to staff. Matches PRD-7.5-6 (remainder to the primary). Deterministic.
- **Consequences.** The worked examples E1–E6 become exact test fixtures.
- **Affected requirements.** BR-011, FR-DIS-008, §7.5. **FI:** no value created or lost (I-1 to I-5).

### TDR-03 — One calculation engine for preview and commit
- **Problem.** A draft preview and a final invoice calculated by different code could disagree.
- **Alternatives.** Calculate in the frontend for the preview. Or one backend engine used everywhere.
- **Decision.** One pure backend service `InvoiceCalculator`, plus an `AllocationCalculator`. They have no I/O, and their inputs and outputs are immutable. Preview, finalise and amend call the same code.
- **Justification.** One source of truth, and it is fully unit-testable.
- **Consequences.** The frontend displays the backend's breakdown and never calculates totals itself.
- **Affected requirements.** BR-016, FR-INV-003, FR-TAX-006.

### TDR-04 — Authentication strategy · **Approval** (depends on TDR-05)
- **Problem.** A Next.js frontend and a Laravel API need secure authentication that works on cPanel.
- **Alternatives.**
  - (a) **Sanctum SPA cookie sessions.**
  - (b) Bearer tokens in `localStorage`. Exposed to XSS.
  - (c) A Next.js backend-for-frontend (BFF) proxy holding tokens. This needs a Node server.
- **Decision.** (a).
- **Justification.** HttpOnly cookies are safe from XSS. Laravel's sessions and CSRF are built in. No Node server is needed. It is supported out of the box by the installed Sanctum 4.
- **Consequences.** The frontend and API must share a parent domain. CORS must allow credentials. Native apps later can use Sanctum tokens.
- **Affected requirements.** TD-001, SEC-014, SEC-018. **Implementation:** CORS, session domain and stateful-domain configuration.

### TDR-05 — Hosting topology and frontend rendering · **Approval**
- **Problem.** Running Next.js on cPanel normally needs a Node.js application server. Many shared plans lack one or limit it heavily. Next 16 needs Node ≥ 20.
- **Alternatives.**

  | Option | Description | Assessment |
  |---|---|---|
  | **A** | **Next.js static export** (`output: 'export'`), served as static files on cPanel, with Laravel on the same account | Cheapest and most portable. No Node server runs in production. |
  | B | Next.js server on cPanel through "Setup Node.js App" | Plan-dependent, resource-limited and harder to operate. |
  | C | Next.js on Vercel, Laravel on cPanel | Good, but adds a vendor dependency, and Vercel is "not mandatory". |
  | D | Both on a VPS (Nginx + PHP-FPM + Node) | Most capable; costs more to run. |

- **Decision.** **A**. B, C and D stay as possible migration paths, with no code changes because the app is client-rendered.
- **Justification.** The app is an authenticated SPA. It needs no SEO and no server rendering, and all data comes from the Laravel API. A static export removes the Node runtime from production while keeping Next.js, TypeScript and the existing shadcn setup.
- **Consequences.**
  - *What static export rules out.* No server components that fetch data. No Next middleware or route handlers. No `next/image` optimisation. No ISR.
  - *Detail pages.* Dynamic detail pages use query parameters (`/orders/view?id=…`) or a client-side router fallback. An `.htaccess` rewrite sends deep links to `index.html`.
  - *Auth redirects* happen on the client. This is acceptable because the backend enforces every permission.
  - *Builds* run in CI or on a local machine, never on the server.
- **Affected requirements.** TD-014, NFR-PORT-001–004, C-9. **Implementation:** affects the Stage 3 frontend architecture and the PWA service worker (hand-written or Serwist).

### TDR-06 — Background work on cPanel
- **Problem.** cPanel has no supervisor to keep queue workers running.
- **Alternatives.** A cron job running `schedule:run`, which in turn runs `queue:work --stop-when-empty`. Or synchronous processing only. Or Redis + Horizon, which needs a VPS.
- **Decision.** Cron every minute runs `php artisan schedule:run`. The scheduler starts `queue:work --stop-when-empty --max-time=50` with a lock that prevents overlap. Queue driver: database. On a VPS, the same code runs under a supervisor.
- **Justification.** Works on any cPanel plan that has cron, with no extra services.
- **Consequences.** Queued jobs (email, push, chain check) may take up to about 1 minute to run. Financial operations never depend on the queue.
- **Affected requirements.** NFR-PERF-004, TD-020.

### TDR-07 — PDF generation and printing · **Approval** (new package)
- **Alternatives.**
  - **dompdf** (pure PHP).
  - Browsershot or headless Chrome. Not available on shared hosting.
  - Client-side PDF generation. Inconsistent results, and unsuitable for email.
- **Decision.** `barryvdh/laravel-dompdf` produces A4 PDFs for invoices and receipts, generated on request and cached briefly. **80 mm thermal printing** uses a print-optimised HTML layout and the browser's print dialog, with no server involvement.
- **Justification.** Works on cPanel, and the layouts are simple.
- **Consequences.** Templates are limited to the CSS dompdf supports. The design is kept simple.
- **Affected requirements.** FR-INV-010, FR-RCT-004/008, TD-019.

### TDR-08 — Concurrency control
- **Alternatives.** Optimistic version columns everywhere. Or **pessimistic locking per order** plus unique-key backstops.
- **Decision.** Pessimistic `FOR UPDATE` on the order row for all order-scoped commands, with a fixed lock order (§4.1). Optimistic versioning for configuration only.
- **Justification.** Salon volume is low, so contention is negligible. Pessimistic locking makes guard checks reliable and race-free.
- **Consequences.** Commands are short. No external calls are made inside transactions.
- **Affected requirements.** FR-EXE-007, §14.14–14.15, TD-016.

### TDR-09 — Idempotency
- **Decision.** As in §4.4. A database table `idempotency_keys`, written in the same transaction as the operation.
- **Alternatives.** Cache-based keys, which are not transactional and so were rejected. Or relying on the client only, which was rejected.
- **Affected requirements.** FR-PAY-014, SEC-015, NFR-REL-004, §14.16.

### TDR-10 — Gap-free document numbering · **Approval** (formats, RR-22)
- **Alternatives.** Auto-increment IDs, which leave gaps on rollback. Or **a locked sequence row inside the business transaction.**
- **Decision.** A sequence table (DM-10) with yearly periods. Invoice numbers are assigned at finalisation. Receipt numbers are assigned when a payment is recorded. Order references use a daily sequence, where gaps are acceptable.
- **Consequences.** Finalisations are serialised per business, which is negligible at salon volume.
- **Affected requirements.** FR-BIZ-004, FR-INV-009, FR-RCT-003, TD-017.

### TDR-11 — Audit implementation
- **Decision.** As in §4.6: an explicit recorder inside transactions, denied-attempt logging, and a hash chain (Should).
- **Alternatives.** An activity-log package based on observers, which cannot capture reasons or intent. Or database triggers, which need privileges that cPanel MySQL users often lack.
- **Affected requirements.** FR-AUD-001–010, SEC-016.

### TDR-12 — Immutability model
- **Decision.** As in §4.5. Transfer verification is stored in its own table, so payments stay append-only.
- **Affected requirements.** SEC-010, FR-PAY-011, FR-RCT-006. **DM:** adds `transfer_verifications`.

### TDR-13 — Business scoping
- **Decision.** As in DM-01: `business_id` on top-level tables, membership-based scoping, no branch table.
- **Alternatives.** No scoping at all, which would mean rework when a second business arrives. Or full multi-tenancy with a database per tenant, which is over-engineering.
- **Affected requirements.** FR-IAM-007, SEC-003, TD-018.

### TDR-14 — Primary keys
- **Decision.** `BIGINT` auto-increment keys, plus random share tokens.
- **Alternatives.** ULIDs or UUIDs everywhere. They give no meaningful benefit at this scale, and authorisation already prevents enumeration.
- **Affected requirements.** DM-02.

### TDR-15 — Time and business day
- **Decision.** UTC storage, a business timezone setting, and a stored `business_day` (RR-09).
- **Affected requirements.** TD-002, FR-CASH-001, BR-050.

### TDR-16 — Contribution ledger
- **Problem.** Staff credit must be historically reproducible after amendments and corrections. It must also never be edited in place.
- **Alternatives.**
  - (a) Monetary fields on contributor rows, updated in place. Loses history.
  - (b) Monetary fields per invoice line. Breaks when contributors change after finalisation.
  - (c) **A ledger with supersession.**
- **Decision.** (c) (RR-12, DM-08).
- **Justification.** Gives full history. Reports are simple ("not superseded"). Bonus inputs are traceable to rows.
- **Affected requirements.** FR-ATTR-012, BR-012, FR-PERF-010–012, FR-BON-008/020.

### TDR-17 — Bonus rule versioning and adjustments
- **Decision.** Versions are immutable JSON definitions (DM-09). A calculation uses the version that is current when it runs, and saves the version identifier. Adjustments follow RR-13. Negative results carry forward (RR-14).
- **Alternatives.** Versions effective by date. Rejected as more complex with no current benefit.
- **Affected requirements.** TD-010, FR-BON-015/018/020.

### TDR-18 — Reconciliation: calculated, not stored
- **Decision.** A query service calculates R1–R9 on demand for one order, or as a dashboard aggregate. Only decisions, exceptions and reviews are stored (§3.7).
- **Justification.** Nothing to keep in sync. Reproducible by construction (FR-REC-008).
- **Consequences.** Dashboard queries need indexes on the item and execution status columns.

### TDR-19 — State machine implementation
- **Decision.** PHP backed enums for states. One transition table per aggregate inside its domain service. No state-machine package.
- **Justification.** Explicit, testable and free of dependencies. A test runs every transition against every pair of state and permission.
- **Affected requirements.** SRS §6, NFR-MNT-004.

### TDR-20 — Notifications delivery
- **Decision.** Laravel database notifications. The client polls every 30 seconds. Web Push uses `minishlink/web-push` with VAPID keys through the queue (Should, and it needs package approval). Server-sent events are not used, because long-lived PHP processes are unsuitable on shared hosting.
- **Affected requirements.** FR-NOT-001–004, TD-020.

### TDR-21 — Permission storage · **Approval** (new package)
- **Alternatives.** **`spatie/laravel-permission`**, or hand-built role and permission tables.
- **Decision.** Spatie, with teams enabled (team key = `business_id`). Caps are columns on the roles table. Permissions are seeded from the §3.3 catalogue.
- **Justification.** Mature and widely used. It covers multiple roles per user (FR-IAM-008) and business scoping.
- **Affected requirements.** FR-IAM-002–009.

### TDR-22 — Import parsing · **Approval** (new package)
- **Alternatives.** `openspout/openspout`, which streams and has a small footprint. Or `maatwebsite/excel`, which is heavier and loads PhpSpreadsheet. Or CSV only.
- **Decision.** `openspout/openspout` for CSV and XLSX.
- **Affected requirements.** FR-IMP-001–008.

### TDR-23 — Database engine and test database · **Approval**
- **Problem.** The design relies on InnoDB row locks, enforced `CHECK` constraints, indexed stored generated columns, JSON columns and window functions in reports. Hosts may run MySQL or MariaDB. The repository currently defaults to SQLite.
- **Decision.**
  - **Minimum version:** MySQL 8.0.16+ or MariaDB 10.6+.
  - **Development and CI:** use the same engine as production. Integration and concurrency tests run against MySQL or MariaDB, not SQLite. SQLite may be used only for fast unit tests that never touch the database.
  - **`.env.example`:** change the default to `mysql`.
- **Justification.** Locking and constraint behaviour must be tested on the real engine.
- **Affected requirements.** NFR-MNT-004, §4.3.

### TDR-24 — PWA implementation constraints
- **Decision.** A web manifest. A service worker that precaches only the static export's assets and an offline page. Network-only for `/api/*`. An update prompt when a new version is detected. No offline storage of business data.
- **Affected requirements.** NFR-PWA-001–007, SEC-013.

### Decision classification

| Class | TDRs |
|---|---|
| Follow directly from the approved requirements | 01, 02, 03, 06, 08, 09, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20 (polling part), 24 |
| **Need your approval** | 04, 05, 07, 10 (formats), 21, 22, 23, plus rules RR-05, 06, 14, 15, 16, 21, 22 |
| Can safely be deferred | Web Push delivery (20, Should). Hash-chain audit (11, Should). Shared-device PIN mode (Should). Branch support. Offline submissions. WhatsApp Business API. |

---

## 8. Deliverable F — Updated Outstanding Decisions Register

### 8.1 Decisions for your approval

| ID | Subject | Options and consequences | Recommended | Affects |
|---|---|---|---|---|
| **S2-D01** | Hosting topology (TDR-05) | **A** Static Next.js export on cPanel + Laravel on cPanel: no Node server, lowest cost. **B** Node app on cPanel: plan-dependent. **C** Vercel + cPanel: extra vendor. **D** VPS: more cost, more control. | **A** | ARCH |
| **S2-D02** | Authentication (TDR-04) | Cookie sessions; requires the frontend and API on the same parent domain. | Sanctum SPA cookies | SEC, ARCH |
| **S2-D03** | New packages | `spatie/laravel-permission`, `barryvdh/laravel-dompdf`, `openspout/openspout`; later `minishlink/web-push` (Should). | Approve all four | ARCH |
| **S2-D04** | Database engine (TDR-23) | MySQL 8.0.16+ or MariaDB 10.6+. Tests run on the real engine. The SQLite default is replaced. | Approve | DM, ARCH |
| **S2-D05** | Negative bonus carry-forward (RR-14) | **A** Pay ₦0 and carry the negative amount forward. **B** Allow negative and deduct manually. **C** Write the negative amount off. | **A** | FI |
| **S2-D06** | Collected contribution and late collections (RR-10) | **A** As approved: ratio measured at calculation time; a balance collected later is **not** credited. **B** Cash basis: each payment credits contributions in the period it is received. Fairer to staff, but more complex. | **A** for the MVP | FI, DM |
| **S2-D07** | Money events after a closing is submitted (RR-15) | **A** Block that user for that day; the approver can return the closing. **B** Allow them, and flag the closing for resubmission (the current FR-CASH-010 wording). | **A** | FI, SEC |
| **S2-D08** | Transfer-rejection reversals kept out of closings (RR-16) | **A** Exclude them and show them in a Transfer exceptions report. **B** Include them in the verifier's closing. | **A** | FI |
| **S2-D09** | Exception review flag (RR-21) | **A** Add "mark reviewed" with a note. **B** No review, so the panel grows forever. | **A** | DM |
| **S2-D10** | Additional services after finalisation (RR-05) | **A** Allowed within the late window and approved as an amendment. **B** Not allowed; the work goes unbilled and appears only as R7. | **A** | FI |
| **S2-D11** | Partial completion (RR-06) | **A** Execution + discount or price override with a reason. **B** A dedicated "partial" status with a percentage price. | **A** | DM |
| **S2-D12** | Reference formats (RR-22) | `V-YYMMDD-NNN`, `INV-YYYY-NNNNN`, `RCT-YYYY-NNNNN`, all restarting yearly or daily as shown, and all configurable. | Approve | DM |

### 8.2 Hosting facts needed before Stage 3

Please send these from your cPanel provider, or tell me the provider and plan name:

1. PHP version available, and whether 8.3 or later can be selected (MultiPHP).
2. Database engine and version (MySQL or MariaDB, and which version).
3. Whether cron jobs can run every minute.
4. Whether SSH or Git deployment is available (Git Version Control in cPanel).
5. The domain you plan to use, and whether you can create subdomains such as `app.` and `api.`.
6. Whether email (SMTP) is included with the plan.

### 8.3 Carried over from Stage 1, still open (non-blocking)

| ID | Assumption | Status |
|---|---|---|
| A-01 | Prices vary by variant | Default kept |
| A-02 | Product items included | Default kept |
| A-04 | Tax off | Configuration only |
| A-05 | An approver is reachable | Default kept |
| A-06 | Shared-device PIN mode is Should | Default kept |
| A-07 | Thermal printing is Should | Default kept |
| A-08 | Bonus schemes fit the three formula types | Default kept |

---

## 9. Deliverable G — Proposed PRD/SRS Amendments

These would be applied **after** you approve, as PRD v1.4 and SRS v1.2. Nothing has been changed yet.

### 9.1 PRD v1.4 (small behavioural clarifications)

| ID | Change | From |
|---|---|---|
| AM2-P-01 | Additional services may be requested after finalisation, within the late window, and are approved as an amendment | RR-05 |
| AM2-P-02 | Partial completion is handled by a discount or price override with a reason | RR-06 |
| AM2-P-03 | Negative bonus carry-forward | RR-14 |
| AM2-P-04 | After a cashier submits their closing, they cannot take more money that day | RR-15 |
| AM2-P-05 | Transfer rejections are kept out of cashier closings and reported separately | RR-16 |
| AM2-P-06 | Exceptions can be marked as reviewed | RR-21 |
| AM2-P-07 | Collected contribution: late collections are not credited (clarifies 7.5-7) | RR-10 |
| AM2-P-08 | For the service-count basis, the formula is an amount per service; collaborative work counts 1 per contributor | RR-11 |

### 9.2 SRS v1.2

| ID | Change |
|---|---|
| AM2-S-01 | §6 replaced by the detailed transition tables in §3 of this document. |
| AM2-S-02 | §7.1(9) and new §7.6: money in integer kobo, basis points, half-up rounding, remainder targets, invariants I-1 to I-6 (§2). |
| AM2-S-03 | New DRs: contribution ledger, transfer verifications, exception reviews, number sequences, idempotency keys, frozen closing lines, bonus rule versions as JSON (§5). |
| AM2-S-04 | FR-INV-005/007: client total must match (RR-02); atomic amendment change set with preview (RR-03/04). |
| AM2-S-05 | FR-CASH-010 rewritten to match RR-15. FR-CASH-002 / FR-REF-008 updated for RR-16. |
| AM2-S-06 | FR-BON-007 amended for RR-11. FR-BON-020 amended for RR-13/14. New FR-BON-021 for the saved collected ratios (RR-10). |
| AM2-S-07 | New FR-DASH-016 for exception review (RR-21). |
| AM2-S-08 | §9: idempotency header list, `409` conflict codes (`TOTAL_CHANGED`, `EXECUTION_EXISTS`, `STALE`), `422 IDEMPOTENCY_MISMATCH`. |
| AM2-S-09 | §10: authentication arrangement (Sanctum SPA, same parent domain), role-based idle-timeout middleware, audit hash chain (Should). |
| AM2-S-10 | §16: TD-001, 002, 005, 006, 010, 016, 017, 018 resolved. TD-014, 019, 020 resolved subject to S2-D01/D03. TD-015 detailed in Stage 3. |
| AM2-S-11 | §11.5: minimum database engine (TDR-23). Static-export frontend deployment (TDR-05). |

---

## 10. Approval Request

Please respond with one of the following:

- **"Approve Stage 2 with recommendations"**, plus the hosting facts in §8.2 if you have them. I will then apply AM2-P and AM2-S to create PRD v1.4 and SRS v1.2, and start Stage 3 (architecture). Without the hosting facts, Stage 3 assumes a typical cPanel plan: PHP 8.3, MariaDB 10.6+, cron, Git deployment and subdomains, and lists that as a risk.
- **Approve with changes.** Name the S2-D numbers where you want a different option.
- **Request clarification** on any rule or TDR.

I will not start Stage 3 until you approve.
