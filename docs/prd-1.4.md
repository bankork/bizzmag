# Salon Operations & Accountability Platform
## Product Requirements Document (PRD)

**Version:** 1.4
**Status:** **Approved requirements baseline** (product owner approved the Stage 2 recommendations, 2026-11-25)
**Supersedes:** PRD v1.3
**Initial market:** Nigeria
**Initial business:** One salon
**Currency default:** Nigerian Naira (₦)
**Date:** 2026-11-25

---

## Change Log

| Version | Date | Summary |
|---|---|---|
| 1.0 | — | Initial PRD. |
| 1.1 | — | Automatic confirmation, basic bonuses, guest transactions, discounts and taxes added to the MVP. Outstanding decisions listed. |
| 1.2 | — | Condensed approved baseline. Resolved the PRD 1.1 §23 decisions. |
| **1.3** | 2026-11-24 | Incorporates the Stage 1 Requirements Review (`docs/reviews/stage-1-requirements-review.md`), approved by the product owner "with recommendations". It adds requirement IDs, the billing and finalisation rule, the collaboration model, contribution value definitions, variants and products, the refund/reversal distinction, transfer verification, bonus rule types, sharing channels, segregation of duties, and restored content dropped in 1.2. Each change is tagged with its amendment ID `[AM-P-xx]` and decision ID `[D-xx]`. |
| 1.3 (approved) | 2026-11-25 | Approved as the requirements baseline. Before approval, A-03 was resolved: part payments are allowed before finalisation (D-08 revised; PRD-7.9-2, 7.9-2a, 7.9-2b). |
| **1.4** | 2026-11-25 | Stage 2 clarifications approved "with recommendations" (`docs/reviews/stage-2-technical-design-decisions.md`). Amendments AM2-P-01 to AM2-P-08 are tagged `[AM2-P-xx]`. The tax wording in 7.10-6 is also clarified: tax is fully supported and configurable, and simply starts switched off. Decisions S2-D01 to S2-D12 are added to Appendix A.3. |

**How to read the change tags:** Text without a tag carries over from v1.2 with no change in meaning. Tagged text is new or changed. Appendix A lists every decision. Appendix B lists the assumptions that still need your confirmation.

**Requirement IDs** `[AM-P-19]`: `PRD-<section>-<n>`. The SRS traces to these IDs.

---

## 1. Product Overview

**PRD-1-1.** The Salon Operations & Accountability Platform is a web-based business management application. It is designed to improve salon operations, staff accountability, revenue visibility, service tracking and financial control.

**PRD-1-2.** Its central purpose is to make the following accurately recorded and traceable:

- the services customers request;
- the services actually performed;
- the employees who contributed;
- the amounts invoiced;
- the payments received.

**PRD-1-3.** The application must prevent unauthorised removal or manipulation of confirmed services. It must also give management reliable staff performance, revenue and bonus information.

**PRD-1-4.** The first release will serve one salon. Its architecture should allow later expansion to more branches and other service-based businesses, without making the first release unnecessarily complex.

## 2. Problem Statement

The current business process keeps invoicing and staff service recording as separate activities. This creates gaps in:

- tracking the services each employee performs;
- identifying individual and collaborative contributions;
- reconciling requested services against services actually delivered;
- preventing unauthorised changes to invoices;
- monitoring revenue, payments and outstanding balances;
- calculating and managing staff bonuses;
- reviewing business activity and historical changes.

The product must establish a reliable operational record from customer arrival through service completion, invoicing and payment.

## 3. Product Vision

To provide a reliable, easy-to-use business operations platform that gives service businesses control over their transactions, staff activities, service delivery and financial performance.

## 4. Product Goals

1. Improve staff accountability and service-record accuracy.
2. Reduce revenue leakage and invoice manipulation.
3. Accurately record individual and collaborative staff contributions.
4. Reconcile requested services against completed services.
5. Improve visibility of revenue, payments and outstanding balances.
6. Support configurable and auditable staff bonus calculations.
7. Provide useful operational reports and management dashboards.
8. Establish a scalable foundation for future business types.

### 4.1 Success Indicators `[AM-P-16, restored from PRD 1.1 §4.1]`

| Indicator | Desired outcome |
|---|---|
| Service recording | Every performed service has a traceable execution record. |
| Service attribution | Every executed service identifies its contributors. |
| Reconciliation | Every billable service is executed, cancelled through the authorised route, or covered by an authorised exception. |
| Unauthorised deletion | No confirmed service can be silently removed. |
| Payment accountability | Every payment, reversal and refund is traceable to a user. |
| Performance reporting | Employee contributions can be reviewed by period. |
| Bonus calculations | Bonuses are calculated with approved configurable rules. |
| Revenue visibility | Management can view billed revenue, collected revenue and outstanding balances by period. |
| Auditability | Critical actions, including denied attempts, can be traced to users. |

## 5. Users and Roles

**PRD-5-1 Business Owner.** Has overall business oversight. Controls business settings, users, permissions, financial operations, reports, bonus rules and approvals.

**PRD-5-2 Administrator / Supervisor.** Manages operational activities. Performs authorised approvals, corrections, discounts and transaction actions.

**PRD-5-3 Front Desk / Cashier.** Registers customers, creates service orders, prepares and finalises invoices where permitted, records payments, issues receipts and performs permitted closing activities.

**PRD-5-4 Employee / Service Provider.** Views open orders and their own assignments, records the services they performed, identifies or joins collaborators, requests additional services and views their own performance.

**PRD-5-5 Guest Customer.** May receive services without a permanent customer profile. Guest transactions must still have a traceable reference.

**PRD-5-6.** The system must support configurable roles and permissions. The role names above are default operational roles, not a fixed permission model.

### 5.1 Employee visibility `[AM-P-14, D-18]`

**PRD-5.1-1.** Employees can see every open service order for the current business day. For each order they see the order reference, the customer's first name (or "Guest") and the requested services.

**PRD-5.1-2.** By default, employees do not see:

- prices on orders;
- customer phone numbers or other contact details;
- invoices;
- payments;
- other employees' performance.

**PRD-5.1-3.** Employees can see their own performance, including their own contribution values.

### 5.2 Segregation of duties `[AM-P-14, D-25]`

**PRD-5.2-1.** Where an action needs approval, the approver must be a different user from the requester. This applies to:

- over-limit discounts;
- refunds;
- payment reversals;
- corrections of confirmed executions.

The Business Owner is exempt from this rule, and every exempt action is audited.

### 5.3 Staff device access `[AM-P-14, D-17]`

**PRD-5.3-1.** Every employee has an individual account. All records are attributed to that individual.

**PRD-5.3-2 (Should).** The system should support a *shared salon device* mode. In this mode, employees switch quickly to their own identity using a personal PIN. This serves staff without a personal smartphone.

## 6. Product Scope

### 6.1 MVP Functional Modules

1. Authentication and user management.
2. Role-based access control.
3. Business settings.
4. Customer management and guest transactions.
5. Service categories and catalogue, including service variants and product/material items. `[AM-P-06]`
6. Service orders and requested items.
7. Employee assignment.
8. Service execution recording.
9. Collaborative staff attribution.
10. Additional-service request and approval.
11. Service reconciliation and the finalisation gate. `[AM-P-01]`
12. Invoice generation, finalisation and revisions. `[AM-P-11]`
13. Discount and tax configuration.
14. Manual payment recording, including bank-transfer verification. `[AM-P-09]`
15. Receipts and sharing (PDF, print, WhatsApp, email). `[AM-P-12]`
16. Refund and payment reversal controls.
17. Cashier daily closing and reconciliation.
18. Staff performance reporting.
19. Basic configurable bonus calculations.
20. Bonus approval and payment tracking.
21. Owner dashboard.
22. Audit trail.
23. CSV/Excel data import.
24. Responsive PWA experience.
25. In-app notifications. `[D-22]`

### 6.2 Future Scope

The following are not required for the initial release:

- Payment gateway integrations.
- Automated WhatsApp Business API messaging. `[D-16]`
- Web Push notifications. These are a "Should" item and may arrive in the MVP if time permits. `[D-22]`
- Customer credit wallets and carrying overpayments forward. Part payments before finalisation **are** in scope; see PRD-7.9-2. `[D-08 rev]`
- Customer record merging. `[Stage 1 S-11]`
- Full payroll processing.
- Advanced commission and incentive engines.
- Inventory and stock management. Product/material items have prices but no stock tracking.
- Employee attendance and scheduling.
- Cleaning-service-specific workflows.
- Full accounting and expense management.
- Native mobile applications.
- Advanced offline transaction submission.
- Multi-branch operational management.
- Advanced CRM and marketing automation.

The architecture should not prevent these capabilities from being introduced later.

### 6.3 Business Configuration `[AM-P-16, restored from PRD 1.1 §14]`

**PRD-6.3-1.** The business must be able to configure the following without source-code changes:

- business name;
- logo;
- contact information and address;
- currency;
- timezone;
- invoice and receipt numbering and layout details;
- service catalogue and prices;
- discount limits;
- tax settings;
- allocation defaults;
- bonus rules;
- the transfer-verification alert period;
- the late-submission window;
- users, roles and permissions.

## 7. Core Business Concepts

### 7.1 Customer

**PRD-7.1-1.** A customer is a person receiving services. A customer profile may contain name, phone number and other relevant details.

**PRD-7.1-2.** Guest transactions are permitted. Name and phone number are optional for a guest. Every service order receives a unique, system-generated reference.

**PRD-7.1-3.** A guest order may later be associated with a registered customer. The association must be audited.

**PRD-7.1-4.** When a customer is created or imported with a phone number that matches an existing customer, the system must warn the user. Merging customer records is out of scope for the MVP. `[D-21]`

**PRD-7.1-5.** Authorised users can view a customer's order, invoice and payment history. `[AM-P-16, restored]`

### 7.2 Service Catalogue `[AM-P-06, D-06, D-07]`

**PRD-7.2-1.** The catalogue defines what the salon sells. It contains two item types:

- **Service.** Work performed by employees. A service requires execution and earns staff contribution.
- **Product/Material.** For example attachment hair or retail products. A product has a price and appears on the invoice. It requires no execution, earns no staff contribution and has no stock tracking.

**PRD-7.2-2.** Each catalogue item supports:

- name and description;
- category;
- configured price;
- active or inactive status;
- tax applicability (taxable or exempt);
- for services, an allocation default (see 7.5);
- for services, whether quantity greater than one is permitted.

**PRD-7.2-3.** Where a price depends on size, length, hair type or similar, each priced option is set up as a **service variant**: a separately priced catalogue entry under one service, for example "Knotless braids — Medium".

**PRD-7.2-4.** A user with a specific permission may override an item's price on an order. A reason is mandatory. Every override appears on the exceptions report.

**PRD-7.2-5.** The name and price applied to an order are kept as a historical snapshot. Changing the catalogue price must not change existing orders or invoices.

### 7.3 Service Order

**PRD-7.3-1.** A service order records what a customer requested during one visit. It is the single business transaction for that visit and carries one reference. In the interface it may be shown as a "Visit". `[Stage 1 X-03]`

**PRD-7.3-2.** An order contains one or more items, with their price snapshots, quantities (where permitted) and expected employee assignments.

**PRD-7.3-3.** An order is not proof that services were performed.

**PRD-7.3-4.** Assignment records who is *expected* to perform an item. It does not restrict who may record the execution. The reconciliation view shows "performed by an unassigned employee" as information only.

**PRD-7.3-5** `[AM-P-15, D-20]`. Before finalisation, the front desk may add items to an order directly, for example when a customer asks at the desk. These additions are audited.

**PRD-7.3-6** `[D-19]`. Before finalisation, the front desk may cancel an item that has **no execution**. A reason is mandatory. Each such cancellation is audited and shown on the discrepancy report. Once an item has an execution, cancelling it requires the execution-correction permission.

**PRD-7.3-7** `[D-23]`.

- Orders may stay open across business days.
- The dashboard shows open orders by age.
- An abandoned order is cancelled through the controlled cancellation route, with a reason.

### 7.4 Service Execution `[AM-P-02, AM-P-03, D-02, D-03]`

**PRD-7.4-1.** An execution records what was actually performed for one service item.

**PRD-7.4-2.** An employee who performed or took part in a service submits the execution. Submission automatically confirms it, and routine supervisor confirmation is not required.

**PRD-7.4-3.** An employee may submit an execution for any item on an open order, including items they were not assigned to. The system keeps a record of who submitted it.

**PRD-7.4-4.** Each service item has **one active execution**. That single execution is the record of the work. It lists every contributor.

**PRD-7.4-5.** Contributors are recorded as follows:

- The employee who submits the execution names their collaborators.
- Any other employee who took part can **join** the execution themselves, until the invoice is finalised.
- After finalisation, contributors can be added only by a supervisor.

**PRD-7.4-6.** A contributor who was named, or an authorised supervisor, may **dispute** an execution. An open dispute blocks ordinary invoice finalisation.

**PRD-7.4-7.** Corrections never overwrite the original record. A correction creates a replacement record linked to the original, with an audit trail. Corrections require authorisation.

**PRD-7.4-8.** Where an item allows quantity greater than one, one execution covers the whole item.

**PRD-7.4-9 Late submissions.** An execution submitted after the invoice is finalised is always accepted. The outcome depends on what the execution matches:

- If it matches an item that was already billed, it attaches to that item with no financial effect.
- If it matches an item that was cancelled or covered by an exception, it raises a **post-finalisation discrepancy** for management review.

Executions submitted more than the configured late-submission window after finalisation require supervisor permission. The default window is 48 hours.

### 7.5 Staff Contribution `[AM-P-04, AM-P-05, D-04, D-05]`

**PRD-7.5-1.** A service may have one or more contributors.

**PRD-7.5-2.** The customer is charged for a service once, however many employees contribute.

**PRD-7.5-3.** The owner configures how credit is split between contributors, with a default for each service. Three methods are available:

- **Equal.** Every contributor gets the same share.
- **Primary/Assistant.** The primary contributor gets a configured percentage. The assistants share the remainder equally.
- **Custom percentages.** These must total exactly 100%.

**PRD-7.5-4.** Employees do not set the percentages. A supervisor may override the split for a particular execution, giving a reason.

**PRD-7.5-5.** The primary contributor must be identifiable. By default it is the employee who submitted the execution, unless that is changed.

**PRD-7.5-6.** Allocated amounts must add up exactly to the value being allocated. Any rounding remainder goes to the primary contributor.

**PRD-7.5-7 Contribution value definitions.** These are used in reports and bonus rules:

- **Participated value.** The full net value of every service an employee took part in. It is informational. It is not additive across staff and must never be presented as revenue.
- **Allocated contribution.** The employee's share of the service line's net value, meaning the value after discounts and before tax. Across all contributors it adds up exactly to the line's net value, so totals reconcile to service revenue. Discounts reduce allocated contribution.
- **Collected contribution.** Allocated contribution multiplied by the share of the invoice that has been collected (amount collected ÷ invoice total). The share is measured when the bonus for the period is calculated, and saved with that calculation. Money collected on the invoice after that point is **not** credited to an already-calculated bonus. `[AM2-P-07, S2-D06]`

**PRD-7.5-8.** Contribution values are inputs to performance reports and bonus calculations. They must never be added together as extra customer charges or treated as extra revenue.

**Example.** A ₦20,000 braid is done by three employees with an equal split, and no discount is applied. Each employee's participated value is ₦20,000. Their allocated contributions are ₦6,666.67 for each of the two non-primary contributors and ₦6,666.66 for the primary, who receives the rounding remainder. Together they add up to ₦20,000.00. Service revenue is ₦20,000.

### 7.6 Additional Services `[AM-P-15, D-20]`

**PRD-7.6-1.** An employee may request an additional service that was not on the original order. The catalogue price applies.

**PRD-7.6-2.** The request becomes an authorised, billable item only after a supervisor or another authorised user approves it.

**PRD-7.6-3.** An employee may record the execution while the request is still pending. That execution is neither billable nor credited until the request is approved.

**PRD-7.6-4.** If the request is rejected, the execution stays on record and is flagged. The customer is not charged for it and no contribution credit is given.

**PRD-7.6-5.** A pending request blocks invoice finalisation until it is approved or rejected.

**PRD-7.6-6.** The request, its approval or rejection, and any later execution must be traceable.

**PRD-7.6-7** `[AM2-P-01, S2-D10]`. After an invoice is finalised, an employee may still request an additional service within the late-submission window. The request shows on the dashboard as a post-finalisation discrepancy. Approving it changes the bill, so it is an **invoice amendment**. The approving user needs both the additional-service approval permission and the amendment permission.

### 7.7 Service Reconciliation

**PRD-7.7-1.** The system compares:

- the services originally requested;
- items added at the front desk;
- additional services requested, approved and rejected;
- the services actually executed;
- services cancelled or corrected through authorised procedures.

**PRD-7.7-2.** Reconciliation identifies differences and provides the basis for invoice finalisation. It is recalculated from the stored records at any time.

### 7.7a Billing and Finalisation Rule `[AM-P-01, D-01]`

**PRD-7.7a-1 Billable items.** The billable items on an order are every item that is:

- requested, added by the front desk, or an approved additional service;
- and not cancelled or rejected.

**PRD-7.7a-2 System-generated lines.** Invoice lines are generated by the system from the billable items. No user can add or remove invoice lines directly. Users change what is billed only through the controlled actions in this PRD: adding items, cancellation, correction, price override, discount and amendment.

**PRD-7.7a-3 Finalisation gate.** Ordinary finalisation is permitted only when all of these hold:

- (a) every billable service item has a confirmed execution with no open dispute;
- (b) no additional-service request is pending;
- (c) no execution exists against a cancelled item without a decision on it.

Product/material items do not need an execution.

**PRD-7.7a-4 Finalisation with exception.** A user with the "finalise with exception" permission may finalise even though the gate conditions are not met. They must give a reason. The exception is recorded and appears on the dashboard. The billed amount still includes every billable item.

**PRD-7.7a-5.** A cashier cannot reduce what the customer is billed by finalising before staff have submitted their executions.

### 7.8 Invoice `[AM-P-11, D-15]`

**PRD-7.8-1.** The final invoice is the amount the customer must pay for billable items after applicable adjustments. Each order has one active invoice.

**PRD-7.8-2.** Who may finalise invoices is controlled by permissions.

**PRD-7.8-3.** After finalisation, the order and invoice are locked against ordinary editing.

**PRD-7.8-4.** A correction after finalisation is made through an authorised **amendment**. The amendment creates a new invoice **revision**. Earlier revisions are kept and cannot be changed, and payments carry forward to the new revision.

**PRD-7.8-5.** If an amendment reduces the total below the amount already paid, it must be accompanied by a refund of the difference. Customer credit is not supported in the MVP.

**PRD-7.8-6.** An invoice may be voided only by an authorised user, with a reason, and only when its net amount paid is zero.

### 7.9 Payments and Receipts

**PRD-7.9-1.** The system supports manual recording of cash, bank transfer and POS payments.

**PRD-7.9-2** `[D-08 rev, A-03 resolved]`.

- Payments are recorded against the **service order**. They may be recorded **before finalisation**, as a part payment or deposit, and after it.
- Partial and full payments are supported, in any number of instalments.
- **Before finalisation**, total net payments may not exceed the order's current draft total.
- **After finalisation**, a payment may not exceed the outstanding balance.
- Change given in cash is not recorded as payment.
- At finalisation, every payment already made counts towards the invoice automatically. The system calculates outstanding balances throughout.

**PRD-7.9-2a.** Removing items, cancelling them or adding discounts may reduce the order total below the amount already paid. When that happens, the order cannot be finalised, and the reducing action cannot be completed, until the excess is refunded. Customer credit is not supported in the MVP.

**PRD-7.9-2b.** An order that has net payments cannot be cancelled until those payments are refunded or reversed.

**PRD-7.9-3 Bank transfer verification** `[AM-P-09, D-11]`. A bank transfer is recorded as **Unverified**. A user with the verification permission (the owner by default) marks it **Verified** or **Rejected** after checking the bank statement or banking app. A receipt may be issued when the transfer is recorded. Transfers that stay unverified longer than the configured period (default 2 days) are highlighted on the dashboard.

**PRD-7.9-4.** A receipt is generated for each recorded payment. The invoice shows a summary of payments and the outstanding balance.

**PRD-7.9-5 Sharing and printing** `[AM-P-12, D-16]`. Invoices and receipts must support:

- PDF download;
- printing on A4 (Must);
- printing on an 80 mm thermal receipt printer (Should);
- sharing to **WhatsApp**, either through the device's share function or as a link to a secure, expiring copy of the document;
- sending by **email**.

### 7.10 Discounts and Taxes `[AM-P-10, D-12, D-13]`

**PRD-7.10-1.** Discounts may be applied to a line or to the whole invoice. They may be a fixed amount or a percentage.

**PRD-7.10-2.** Every discount requires a reason and records the user who applied it.

**PRD-7.10-3.** Each permission level has a configurable percentage cap and amount cap. A discount above the user's cap becomes an **approval request** to an authorised user. Segregation of duties (5.2) applies.

**PRD-7.10-4.** An invoice-level discount is spread across the lines in proportion to their value. It therefore reduces allocated contribution (7.5).

**PRD-7.10-5.** Discounts cannot make any line or invoice total negative.

**PRD-7.10-6** `[clarified v1.4]`. Tax calculation is **fully built and included in the MVP**. The owner configures it in business settings. No code change and no developer is needed to switch it on, change the rate or switch it off.

The tax settings are:

- **Tax switched on or off.** A new installation starts with tax switched **off**, until the owner enters the salon's tax details.
- **Tax name and rate**, for example "VAT" at 7.5%.
- **Pricing mode**, for the whole business:
  - *tax-exclusive:* tax is added on top of the price;
  - *tax-inclusive:* the catalogue price already contains the tax.
- **Exemptions.** An individual service or product can be marked exempt, and is then never taxed.

When tax is switched on:

- every invoice finalised from then on is taxed automatically on its non-exempt lines;
- discounts are applied before tax;
- cashiers cannot remove the tax from an individual invoice.

Changing the tax settings never changes invoices that were already finalised. Each invoice keeps the tax setting it was created with (7.10-8).

**PRD-7.10-7.** Applying several taxes on top of one another, and switching tax off for one particular invoice, are out of scope for the MVP.

**PRD-7.10-7a** `[AM2-P-02, S2-D11]`. A service that was only partly completed has no special status. It is recorded as performed. Its price is then reduced through a line discount or price override with the reason "Partially completed". The normal caps and approvals apply.

**PRD-7.10-8.** Tax and discount calculations must be reproducible and auditable. Each invoice keeps the tax setting that applied to it.

### 7.11 Refunds and Reversals `[AM-P-08, D-09]`

**PRD-7.11-1.**

- A **reversal** corrects a payment that was recorded in error, where the money was never received. It cancels the payment record's effect but keeps the payment record.
- A **refund** returns money to a customer, in full or in part, up to the net amount paid. It records the method used to return the money.

**PRD-7.11-2.** Both actions:

- require a permission that the default Cashier role does **not** have;
- require a reason;
- keep the original payment in the history;
- record the responsible user;
- appear in the cashier closing.

**PRD-7.11-3.** A refund does not change staff contribution unless it is accompanied by an authorised correction of the service.

### 7.12 Cashier Closing `[AM-P-09, D-10]`

**PRD-7.12-1.** Each user who recorded payments on a business day performs a closing for that day. The system calculates the expected amount for each method:

- **cash:** payments received, less cash refunds and reversals;
- **POS:** payments received;
- **bank transfer:** payments received, with the verification status shown for each transfer.

**PRD-7.12-2.** The cashier declares what was actually collected:

- **cash** as counted, with an optional opening float;
- **POS** from the terminal's end-of-day slip;
- **bank transfers** by confirming the list.

**PRD-7.12-3.** Discrepancies are recorded and shown to authorised management users.

**PRD-7.12-4.** A closing is submitted by the cashier and approved by an authorised user. Only the owner may reopen an approved closing, and must give a reason.

**PRD-7.12-5** `[AM2-P-04, S2-D07]`. Once cashiers have submitted their closing for a business day, they cannot record further payments, refunds or reversals for that day. To take more money that day, either an approver returns the closing to draft, or another authorised user records the payment.

**PRD-7.12-6** `[AM2-P-05, S2-D08]`. Rejecting a bank transfer reverses the payment, but no physical money moves. These reversals are therefore excluded from cashier closings. They are listed in a separate **Transfer exceptions** report.

### 7.13 Staff Performance

**PRD-7.13-1.** Performance reports include:

- the number of services performed;
- the types of services performed;
- participated value and allocated contribution (7.5);
- the number of customers served, defined as the number of distinct service orders the employee contributed to;
- individual versus collaborative contributions;
- performance over daily, weekly, monthly and custom periods.

**PRD-7.13-2.** By default, employees see their own performance. Visibility of team-wide performance is controlled by permission.

### 7.14 Bonus Management `[AM-P-13, D-14]`

**PRD-7.14-1.** The first release includes basic configurable bonus rules. Each rule has these parts:

| Part | Options |
|---|---|
| **Period** | Weekly or monthly. |
| **Basis** | Allocated contribution, collected contribution, or service count. For service count, every contributor on a collaborative service counts 1, and formula (a) becomes a fixed amount per service. `[AM2-P-08]` |
| **Formula** | (a) a percentage of the basis; (b) a fixed amount when a threshold is met; (c) tiered percentages, applied to the portion of the basis that falls within each tier. |
| **Eligibility filters** | Service categories; employees or roles; optional minimum thresholds on other measures, all of which must be met. |

**PRD-7.14-2.** Bonus records have these states:

1. **Calculated.**
2. **Approved.**
3. **Paid.**
4. **Voided.** Possible from Calculated or Approved, with a reason.

**PRD-7.14-3.** A calculated bonus becomes payable only after approval by the owner or another authorised user.

**PRD-7.14-4.** Each bonus is paid in a single recorded payment. Paying a bonus in instalments is out of scope for the MVP.

**PRD-7.14-5.** Recalculating a period replaces bonuses that are still Calculated. Corrections that affect an Approved or Paid bonus are not applied to it. They create an **adjustment** in the next period. If adjustments would make a bonus negative, the payout is ₦0 and the remaining negative amount carries forward to the following period. `[AM2-P-03, S2-D05]`

**PRD-7.14-6.** Each bonus keeps the rule version and the inputs used to calculate it.

**PRD-7.14-7.** The bonus engine is not a payroll system.

**Examples.**

- **Percentage of basis:** 5% of monthly allocated contribution. ₦400,000 of allocated contribution gives a bonus of ₦20,000.
- **Fixed amount on threshold:** ₦10,000 if the employee performs 60 or more services in the month.
- **Tiered:** 0% on the first ₦300,000, 3% on ₦300,000–₦500,000 and 5% above ₦500,000. ₦600,000 of allocated contribution gives ₦6,000 + ₦5,000 = ₦11,000.

### 7.15 Revenue Definitions `[D-24, D-23]`

**PRD-7.15-1.**

- **Billed revenue** is the total of finalised invoices, dated by finalisation date.
- **Collected revenue** is payments received, less refunds and reversals, dated by payment date.
- **Outstanding** is billed revenue less collected revenue on open invoices.

**PRD-7.15-2.** Reports must label which of these each figure is.

## 8. Core Business Workflow

1. The front desk creates a service order for a registered customer or a guest.
2. Requested services and products are selected from the catalogue. Variants are chosen where applicable.
3. Expected employees are assigned, or items are left unassigned.
4. Employees perform the services. One participant submits the execution; the others are named, or join it themselves.
5. Submission automatically confirms the execution.
6. The owner's configured split is applied to the contributors. A supervisor may override it with a reason.
7. Additional services are added at the front desk, or requested by employees and approved or rejected by an authorised user.
8. The system reconciles billable items against executions.
9. Authorised users resolve discrepancies through controlled actions: cancellation, correction, or a decision on a dispute.
10. An authorised user finalises the invoice. If the finalisation conditions are not met, a permitted user may finalise with a recorded exception.
11. The system calculates discounts, taxes and the final amount payable.
12. The cashier records one or more payments, before finalisation (part payment) or after it. Transfers start as Unverified.
13. Receipts are generated and shared.
14. Each cashier performs a daily closing, which an authorised user approves.
15. Management verifies transfers and reviews performance, discrepancies and bonus calculations.

## 9. Security, Accountability and Auditability

**PRD-9-1.** The platform must enforce role-based permissions on the backend.

**PRD-9-2.** The following actions must be audited:

- service order creation, item additions, cancellations and price overrides;
- execution submission, joining, disputes and corrections;
- contribution allocation overrides;
- additional-service requests, approvals and rejections;
- finalisation, including finalisation with exception, amendments and voids;
- discounts and discount approvals;
- tax setting changes;
- payment recording and transfer verification;
- refunds and reversals;
- cashier closing submission, approval and reopening;
- bonus calculation, approval, voiding, payment and adjustments;
- user, role and permission changes;
- customer association and imports;
- **denied attempts at privileged actions.** `[AM-P-16, restored]`

**PRD-9-3.** Audit records must keep the actor, the action, the affected record, the timestamp, the before and after values where relevant, and the reason where one is required.

**PRD-9-4.** Confirmed service records and finalised financial records must never be silently deleted or overwritten.

**PRD-9-5.** Customer personal data must be handled in line with the Nigeria Data Protection Act 2023. Access must be restricted to users who need it. Data must be kept only for a defined period. `[D-26]`

**PRD-9-6** `[AM2-P-06, S2-D09]`. Management can mark items on the dashboard's exceptions panel as **reviewed**, with an optional note. Reviewed items leave the "needs attention" list but stay in reports and in the audit trail.

## 10. Platform and Deployment Requirements

**PRD-10-1.** The application must be a responsive Progressive Web App. It must work on smartphones, tablets and desktop computers. It must support installation through supported browser mechanisms.

**PRD-10-2.** The preferred technology direction is:

- **Backend:** Laravel / PHP.
- **Frontend:** Next.js / TypeScript.
- **Database:** MySQL.
- **Application architecture:** modular monolith.
- **API:** reusable backend API.
- **Source control:** GitHub.
- **Deployment:** cPanel preferred; VPS also acceptable.

**PRD-10-3.** The frontend and backend should be deployable independently. Critical business logic, financial calculations and permission enforcement belong to the backend.

**PRD-10-4.** Docker may be supported as an optional development or deployment convenience. It must not be a mandatory production dependency.

**PRD-10-5.** The application must use database migrations, automated tests, documented deployment procedures and reliable backups. Backups must be restore-tested.

**PRD-10-6.** Sensitive customer, payment and staff records must not be cached by the PWA service worker. The MVP is online-first for sensitive business transactions.

## 11. Data Import `[AM-P-17, D-21]`

**PRD-11-1.** The MVP supports controlled CSV/Excel import of **customers** and **catalogue items**.

**PRD-11-2.** Imports must:

- validate the data before saving it;
- report errors row by row;
- flag likely duplicates, using the normalised phone number for customers and the name within a category for catalogue items;
- keep an import history.

**PRD-11-3.** Historical staff service records in the existing Google Sheet are kept as a read-only archive. They are **not** imported into performance or bonus data.

## 12. MVP Acceptance Outcomes

The MVP is acceptable when all of the following are true:

- A customer or guest order can be created.
- Requested services and products can be recorded, including variants, and assigned.
- Employees can submit executions. Collaborators can be named or can join.
- A service can have several contributors without its customer price being duplicated. Allocations add up exactly to the line value.
- Additional services need approval. Pending requests block finalisation.
- Service discrepancies are visible and traceable.
- Confirmed records cannot be silently removed.
- Ordinary finalisation is blocked until every billable service is executed or cancelled. Finalisation with exception requires permission and a reason. `[AM-P-18]`
- Executions submitted after finalisation are accepted and raise discrepancies where appropriate. `[AM-P-18]`
- Authorised users can finalise and amend invoices. Earlier revisions are kept.
- Discounts and taxes are calculated according to the settings. Over-limit discounts need approval.
- Cash, transfer and POS payments can be recorded.
- Transfers can be verified. Unverified transfers are highlighted. `[AM-P-18]`
- Partial payments, including part payments before finalisation, and balances are calculated correctly.
- Receipts can be generated, printed, downloaded and shared by WhatsApp and email.
- Refunds and reversals are controlled, kept separate from each other and audited.
- Cashier closing identifies collection discrepancies.
- Historical prices on orders stay the same after a catalogue price change. `[AM-P-18, restored]`
- Staff performance reports are available, and their totals reconcile to revenue.
- Bonus rules can be configured, calculated, approved, voided and marked paid.
- Role permissions are enforced by the backend, and denied attempts are audited.
- Important business actions have an audit trail.
- The application works responsively as a PWA.

## 13. Product Risks

| Risk | Product response |
|---|---|
| Staff fail to submit service records | Simple mobile-first workflow; join-an-execution; shared-device PIN mode (Should); finalisation gate makes missing records visible |
| Invoice manipulation | System-generated invoice lines; finalisation gate; separate execution records; revisions that cannot be changed |
| Under-billing by early finalisation | The gate, plus an audited exception |
| Incorrect collaborative attribution | Owner-configured split; join and dispute; supervisor override with a reason |
| Unauthorised discounts or price overrides | Permission caps, approval requests, segregation of duties, exceptions report |
| Cash skimming through reversal | Reversal permission withheld from cashiers; reversals shown in closing |
| Fake bank-transfer alerts | Transfer verification states and an unverified-transfer alert |
| Incorrect payment recording | Payment history, reversals, cashier closing |
| Bonus disputes | Defined value metrics, transparent rule types, calculation details, approval states |
| Hosting limitations | cPanel-compatible deployment with VPS portability |
| Poor data migration | Validated imports with error reporting |

## 14. SRS Handoff

The SRS (v1.1) must translate this PRD into:

- detailed and testable requirements;
- a permission catalogue;
- business rules;
- state lifecycles;
- data requirements;
- API, interface and security requirements;
- measurable non-functional targets;
- acceptance scenarios.

It must trace each requirement to a `PRD-x-n` identifier.

The SRS must not introduce product capabilities that contradict this PRD without an explicit product decision.

---

## Appendix A — Decision Log `[AM-P-20]`

### A.1 PRD 1.1 §23 decisions (resolved in PRD 1.2)

| # | Question | Decision |
|---|---|---|
| 1 | May unassigned employees submit? | Yes. The submitter is recorded. |
| 2 | Who corrects or disputes confirmed submissions? Is the submitter notified? | Authorised users, with an audit record. Notification is in-app (D-22). |
| 3 | Must allocations total 100%? Must the primary be identifiable? | Yes, and yes. |
| 4 | Bonus periods? Approval required? | Configurable periods. Owner or authorised approval is required. |
| 5 | Bonus basis? | Contribution, collected revenue, or a combination. Refined by D-05 and D-14. |
| 6 | Discount limits per user? | Yes. Refined by D-12. |
| 7 | Tax inclusive or exclusive? Exemptions? | Configurable. Refined by D-13. |
| 8 | Guest identification? | Name and phone are optional; a unique reference is mandatory. |
| 9 | Daily cashier closing? | Yes. Refined by D-10 and D-11. |
| 10 | Refunds in the MVP? | Refunds and reversals are both included. Refined by D-09. |
| 11 | Employee visibility? | Own performance by default; team performance by permission. Refined by D-18. |
| 12 | Migrate existing data? | Customers and catalogue items. Refined by D-21. |

### A.2 Stage 1 decisions (approved 2026-11-24, "with recommendations")

| ID | Decision | PRD reference |
|---|---|---|
| D-01 | Bill authorised items. Finalisation gate with a permissioned exception. Invoice lines generated by the system. | 7.7a |
| D-02 | Accept late executions. Raise a post-finalisation discrepancy. 48-hour window, then supervisor permission. | 7.4-9 |
| D-03 | One active execution per item. Contributors named or joining. Disputes. Corrections replace the original rather than overwrite it. | 7.4 |
| D-04 | The owner configures the split per service. Equal, Primary/Assistant or Custom % presets. Supervisor override. "Other approaches" dropped. | 7.5-3 to 7.5-6 |
| D-05 | Participated, Allocated and Collected contribution defined. Discounts reduce allocated contribution. | 7.5-7 |
| D-06 | Service variants, plus a permissioned price override with a reason. | 7.2-3, 7.2-4 |
| D-07 | Product/Material item type, with no stock tracking. | 7.2-1 |
| D-08 (rev 2026-11-25) | Payments are recorded against the order. Part payments are allowed before finalisation, up to the draft total, and after it, up to the balance. Overpayment is rejected. A total that falls below the amount paid requires a refund. An order with net payments cannot be cancelled. | 7.9-2, 7.9-2a, 7.9-2b |
| D-09 | Refund and reversal distinguished. Permission withheld from Cashier by default. | 7.11 |
| D-10 | Closing per cashier per business day. Submit, then approve. Owner-only reopen. | 7.12 |
| D-11 | Transfers Unverified → Verified or Rejected. Dashboard alert. | 7.9-3 |
| D-12 | Line and invoice discounts. Caps per tier. Over-limit approval. Discounts spread across lines. | 7.10-1 to 7.10-5 |
| D-13 | Tax off by default. One rate, one mode, per-item exemption. | 7.10-6 to 7.10-8 |
| D-14 | Bonus rule types. Voided state. Recalculation and next-period adjustments. Single payout. | 7.14 |
| D-15 | Invoice revisions. Refund when the amended total falls below the amount paid. Void only when net paid = 0. | 7.8 |
| D-16 | PDF, A4 print, 80 mm thermal print (Should), WhatsApp via share or secure link, email. | 7.9-5 |
| D-17 | Individual accounts. Shared-device PIN mode (Should). | 5.3 |
| D-18 | Employees see today's open orders without prices or phone numbers, and their own performance. | 5.1 |
| D-19 | Front desk may cancel an unexecuted item with a reason. Executed items need the correction permission. | 7.3-6 |
| D-20 | Front-desk direct additions. Execution may be recorded while a request is pending. Rejected work is not billed or credited. | 7.3-5, 7.6 |
| D-21 | Import customers and catalogue items only. Google Sheet kept as an archive. Duplicates detected by phone. | 11, 7.1-4 |
| D-22 | In-app notifications in the MVP. Web Push is a "Should". | 6.1 #25, 6.2 |
| D-23 | Orders may stay open across days. Ageing view. Revenue dated at finalisation. | 7.3-7, 7.15 |
| D-24 | Billed, collected and outstanding defined and labelled. | 7.15 |
| D-25 | The approver must differ from the requester. Owner exempt, with an audit record. | 5.2 |
| D-26 | Measurable NFR targets and Nigeria Data Protection Act basics. | 9-5; SRS §11 |

### A.3 Stage 2 decisions (approved 2026-11-25, "with recommendations")

| ID | Decision | Reference |
|---|---|---|
| S2-D01 | The Next.js frontend is built as a static export and served from cPanel next to Laravel. No Node.js server is needed in production. VPS or Vercel remain possible migration paths. | §10 |
| S2-D02 | Laravel Sanctum cookie (SPA) authentication. The frontend and API share one parent domain. | §10 |
| S2-D03 | New packages: spatie/laravel-permission, barryvdh/laravel-dompdf, openspout/openspout, and minishlink/web-push (Should). | §10 |
| S2-D04 | Database: MySQL 8.0.16+ or MariaDB 10.6+. Tests run on the real engine. | §10 |
| S2-D05 | A negative bonus is paid as ₦0 and the remainder carries forward. | 7.14-5 |
| S2-D06 | Collected ratio measured when the bonus is calculated. Later collections are not credited. | 7.5-7 |
| S2-D07 | Money events are blocked after a cashier submits their closing. | 7.12-5 |
| S2-D08 | Transfer-rejection reversals are excluded from closings. | 7.12-6 |
| S2-D09 | Exceptions can be marked as reviewed. | 9-6 |
| S2-D10 | Additional services after finalisation are approved as amendments. | 7.6-7 |
| S2-D11 | Partial completion is handled by a discount or price override with a reason. | 7.10-7a |
| S2-D12 | Default reference formats: V-YYMMDD-NNN, INV-YYYY-NNNNN, RCT-YYYY-NNNNN. All are configurable. | SRS FR-BIZ-004 |

## Appendix B — Assumptions Pending Confirmation

The product owner approved the recommendations without answering the factual questions in Stage 1 §7.1. The baseline therefore uses the defaults below. Each one can be changed before Stage 2 freezes the data model. The table shows what would change if the answer differs.

| ID | Assumption | If the answer differs |
|---|---|---|
| A-01 | Prices vary by size, length or type, and this is handled with variants. Price overrides are rare. | If on-the-spot pricing is common, review the price-override workflow (approval request instead of permission only). |
| A-02 | Attachment hair or products are sometimes sold at the till. The Product/Material type is included. | If they are never sold, the type stays unused at little cost. |
| ~~A-03~~ | **Resolved 2026-11-25.** The product owner confirmed that customers may make part payments. Part payment before finalisation is supported (PRD-7.9-2). | — |
| A-04 | Tax is not currently charged. The tax setting is off, but the simple tax model is still built. | If VAT is charged, enable it in configuration. No change to requirements. |
| A-05 | At least one approver (owner or supervisor) besides the cashier is usually reachable in the app. | If not, approvals may queue. Consider letting the owner approve remotely (already supported) or relaxing segregation of duties for specific actions. |
| A-06 | Most staff have smartphones. The shared-device PIN mode is a "Should". | If many staff lack phones, raise PIN mode to a "Must". |
| A-07 | Printing is on A4 or to PDF. The 80 mm thermal layout is a "Should". | If a thermal printer is the primary printer, raise it to a "Must". |
| A-08 | Bonus schemes will fit the three formula types in 7.14. | If a scheme does not fit, describe it before Stage 2. |

**End of PRD v1.4**
