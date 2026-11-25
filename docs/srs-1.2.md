# Salon Operations & Accountability Platform
## Software Requirements Specification (SRS)

**Version:** 1.2
**Status:** **Approved requirements baseline** (product owner approved the Stage 2 recommendations, 2026-11-25)
**Supersedes:** SRS v1.1
**Parent document:** PRD v1.4
**Initial deployment:** One salon
**Primary currency:** NGN (₦)
**Date:** 2026-11-25

---

## Change Log

| Version | Date | Summary |
|---|---|---|
| 1.0 | — | Initial draft derived from PRD 1.2. |
| **1.1** | 2026-11-24 | Incorporates the approved Stage 1 review (`docs/reviews/stage-1-requirements-review.md`) and PRD 1.3. Amendments AM-S-01 to AM-S-18 are applied. |
| 1.1 (approved) | 2026-11-25 | Approved as the baseline. A-03 resolved: payments are recorded against the order, and part payments are allowed before finalisation (FR-PAY-001/007/008 rev; FR-PAY-015, FR-INV-016 new; BR-035 rev; FR-ORD-014 rev; §14.25 added). |
| **1.2** | 2026-11-25 | Stage 2 decisions applied (AM2-S-01 to AM2-S-11). §6 replaced by the approved transition tables. New §7.6 (financial calculation rules), §7.7 (integrity and concurrency) and §7.8 (DR-001 to DR-011). FR-INV-005/007, FR-CASH-002/010, FR-REF-008, FR-BON-007/020 and FR-TAX-001 revised. New FR-ADD-011, FR-BON-021, FR-DASH-016, SEC-023/024 and NFR-PORT-005/006. §9 error codes added. §16 now records how each TD was resolved. |

### Requirement ID changes from v1.0

Requirement IDs from v1.0 are kept wherever the meaning is unchanged. Changed requirements are marked **(rev)** and new ones **(new)**. Removed and merged IDs are listed below so that traceability is preserved.

| v1.0 ID | Disposition in v1.1 |
|---|---|
| FR-IAM-001 | Removed. Duplicates FR-AUTH-001 and SEC-001. |
| FR-IAM-005 | Merged into FR-AUTH-005. |
| FR-ATTR-006 (weighted) | Merged into FR-ATTR-005. Custom percentages are weights. |
| FR-BON-016 | Moved to Constraint C-11. It was a scope statement, not a requirement. |
| Entity `ServiceReconciliation` | Removed. Reconciliation is derived (§4.9). Resolutions are stored as their own records. |
| Entity `BonusApproval` | Merged into `BonusCalculation`. |
| Entity `ContributionAllocation` | Merged into `ExecutionContributor`. |
| §16 TD-003, 004, 007, 008, 009, 011, 012, 013 | Resolved by product decisions D-01 to D-23 (PRD 1.3 Appendix A). Removed from §16. |

---

# 1. Introduction

## 1.1 Purpose

This document defines the functional and non-functional requirements for the Salon Operations & Accountability Platform. It specifies system behaviour, data rules, permissions, lifecycles, validations and constraints needed to implement PRD v1.3.

It is intended for the product owner, architects, developers, testers and deployment engineers.

## 1.2 Scope

The system manages:

- customers;
- service orders;
- service execution;
- staff contributions;
- reconciliation and the finalisation gate;
- invoices;
- discounts and taxes;
- payments, refunds and reversals;
- receipts;
- cashier closing;
- staff performance;
- bonuses;
- notifications;
- audit records;
- data import.

The initial release supports one salon. It keeps a modular foundation for future business types and branches.

## 1.3 Definitions (AM-S-01)

| Term | Definition |
|---|---|
| Customer | A registered person receiving services. |
| Guest | A customer who is not registered. Name and phone are optional. |
| Catalogue item | A configured sellable item of type **Service** or **Product/Material**. |
| Service variant | A separately priced catalogue entry under one service, for example "Knotless braids — Medium". |
| Service order (Visit) | The single business transaction for one customer visit. It has one unique reference. |
| Order item | One line on a service order that refers to a catalogue item, with a price snapshot. |
| Billable item | An order item in status Requested or Approved. Cancelled, Rejected and Pending Approval items are not billable. |
| Execution | The confirmed record that a service item was performed. Each item has at most one *active* execution. |
| Contributor | An employee recorded on an execution. Exactly one contributor is the primary. |
| Allocation | The contributors' percentage shares of an execution. They sum to 100%. |
| Participated value | The net value of every service line an employee contributed to. It does not add up across staff. |
| Allocated contribution | A contributor's share of the line's net value, meaning the value after discount and before tax. |
| Collected contribution | Allocated contribution × (invoice net amount paid ÷ invoice total), measured at the end of the period. |
| Discrepancy | A derived condition found by reconciliation (§4.9). It is either *blocking* or *informational*. |
| Finalisation gate | The conditions that must all hold before ordinary finalisation (BR-030). |
| Finalisation exception | A recorded, permissioned override of the gate, with a reason. |
| Invoice revision | A version of an invoice that cannot be changed once created. An amendment creates a new revision. |
| Payment | Money recorded as received against a finalised invoice. |
| Reversal | Cancellation of a payment recorded in error. The original record is kept. |
| Refund | Money returned to a customer against an invoice. |
| Transfer verification | The owner's (or delegate's) check that a bank transfer was actually received. |
| Business day | A calendar date in the business's configured timezone. |
| Cashier closing | The closing for one user and one business day. It compares expected and declared collections. |
| Billed revenue | Total of finalised invoices, dated by finalisation date. |
| Collected revenue | Payments less refunds and reversals, dated by the date each event occurred. |
| Approval request | A pending request for a second user to approve: an additional service, an over-limit discount, or a correction. |
| Audit event | A record that cannot be changed and that traces an action, or a denied attempt, to an actor. |

## 1.4 Requirement Classification

The requirement prefixes are:

- **FR:** Functional requirement.
- **BR:** Business rule.
- **NFR:** Non-functional requirement.
- **DR:** Data requirement.
- **SEC:** Security requirement.
- **INT:** Integration requirement.
- **UI:** User interface requirement.
- **C:** Constraint.

The priority levels are:

- **Must:** Required for MVP acceptance. This is the default where no priority is stated.
- **Should:** Important. May be scheduled after the core functionality.
- **Could:** Optional.

## 1.5 Referenced Documents

- PRD v1.3: `docs/prd-1.3.md`.
- Stage 1 Requirements Review v1.0: `docs/reviews/stage-1-requirements-review.md`.
- Stage 2 Technical Design Decisions v1.0: `docs/reviews/stage-2-technical-design-decisions.md`.

---

# 2. System Overview

## 2.1 Architecture Direction

The application uses a modular monolithic architecture. The preferred technology direction is:

- **Backend:** Laravel / PHP.
- **Frontend:** Next.js / TypeScript.
- **Database:** MySQL.
- **API:** REST.
- **Client:** responsive Progressive Web App.
- **Source control:** GitHub.

The **backend** owns business rules, validation, permission enforcement, transaction integrity and all authoritative financial calculations.

The **frontend** communicates only through documented, versioned APIs. It is never trusted to enforce security or to calculate authoritative values. Frontend totals are previews only.

The frontend and backend are deployable independently. Deployment must be supported on suitable cPanel hosting and on a VPS. Docker is optional.

## 2.2 Logical Modules

1. Identity and access management.
2. Business configuration.
3. Customer management.
4. Catalogue (services, variants, products).
5. Service orders.
6. Service execution.
7. Contribution and allocation.
8. Approvals (additional services, over-limit discounts, corrections).
9. Reconciliation.
10. Invoicing.
11. Discounts and taxes.
12. Payments, including transfer verification.
13. Receipts and document sharing.
14. Refunds and reversals.
15. Cashier closing.
16. Staff performance.
17. Bonus management.
18. Dashboard and reporting.
19. Notifications.
20. Audit.
21. Data import.

Modules keep clear responsibility boundaries. They may share infrastructure.

---

# 3. Users, Roles and Permissions

## 3.1 Default Roles

| Role | Summary |
|---|---|
| Business Owner | Full control. Exempt from segregation of duties, and every exempt action is audited (BR-040). |
| Administrator / Supervisor | Operational approvals, corrections, discount approval, refunds and reversals, finalisation, closing approval. |
| Front Desk / Cashier | Customers, orders, items, assignments, finalisation, permitted discounts, payments, receipts, own closing. |
| Employee | Submit and join executions, dispute, request additional services, own performance. |

Roles are editable templates. Authorisation is decided by **permissions**, not by role names.

## 3.2 Authorisation Requirements

**FR-IAM-002.** Every user shall have an individual identity.

**FR-IAM-003.** The system shall support configurable roles and permissions, including creating new roles.

**FR-IAM-004.** The backend shall check permissions on every protected operation, including every API request.

**FR-IAM-006.** Changes to roles, permissions and role assignments shall be audited.

**FR-IAM-007.** Users shall access only data within their authorised business scope.

**FR-IAM-008 (new).** A user may hold more than one role. The user's effective permissions are the union of the permissions of all their roles.

**FR-IAM-009 (new).** Discount caps are attached to roles. Where a user holds several roles, the highest cap applies.

## 3.3 Permission Catalogue (AM-S-03)

The default grants for each role are shown below.

- **O** = Owner.
- **S** = Supervisor.
- **C** = Cashier.
- **E** = Employee.

| Permission | Purpose | O | S | C | E |
|---|---|---|---|---|---|
| `user.manage` | Create, edit and disable users | ✓ | | | |
| `role.manage` | Manage roles, permissions and caps | ✓ | | | |
| `settings.manage` | Business settings, tax, numbering, windows | ✓ | | | |
| `customer.view` | Search and view customers | ✓ | ✓ | ✓ | |
| `customer.view_contact` | See phone and email | ✓ | ✓ | ✓ | |
| `customer.manage` | Create and edit customers | ✓ | ✓ | ✓ | |
| `customer.associate` | Link a guest order to a customer | ✓ | ✓ | ✓ | |
| `catalogue.manage` | Categories, services, variants, products, prices, allocation defaults | ✓ | | | |
| `order.create` | Create orders | ✓ | ✓ | ✓ | |
| `order.view_open` | View today's open orders (restricted fields, §4.6) | ✓ | ✓ | ✓ | ✓ |
| `order.view_all` | View all orders, with prices | ✓ | ✓ | ✓ | |
| `order.item.add` | Add items before finalisation | ✓ | ✓ | ✓ | |
| `order.item.cancel_unexecuted` | Cancel an item that has no execution | ✓ | ✓ | ✓ | |
| `order.item.price_override` | Override an item price, with a reason | ✓ | ✓ | | |
| `order.assign` | Assign expected employees | ✓ | ✓ | ✓ | |
| `order.cancel` | Cancel a whole order (abandoned) | ✓ | ✓ | | |
| `execution.submit` | Submit an execution | ✓ | ✓ | | ✓ |
| `execution.join` | Join an existing execution | | | | ✓ |
| `execution.dispute` | Dispute an execution the user is named on | ✓ | ✓ | | ✓ |
| `execution.correct` | Correct, cancel, resolve disputes, decide on an execution against a cancelled item, cancel an executed item | ✓ | ✓ | | |
| `execution.submit_late` | Submit or add contributors after the late window or after finalisation | ✓ | ✓ | | |
| `allocation.override` | Override the split for one execution | ✓ | ✓ | | |
| `additional.request` | Request an additional service | | | | ✓ |
| `additional.decide` | Approve or reject additional-service requests | ✓ | ✓ | | |
| `reconciliation.view` | View reconciliation and discrepancies | ✓ | ✓ | ✓ | |
| `invoice.view` | View invoices | ✓ | ✓ | ✓ | |
| `invoice.finalise` | Ordinary finalisation | ✓ | ✓ | ✓ | |
| `invoice.finalise_with_exception` | Finalise even though the gate conditions are not met | ✓ | ✓ | | |
| `invoice.amend` | Create an invoice revision | ✓ | ✓ | | |
| `invoice.void` | Void an invoice whose net paid is zero | ✓ | | | |
| `discount.apply` | Apply discounts within the role's cap | ✓ | ✓ | ✓ | |
| `discount.approve` | Approve over-limit discount requests | ✓ | ✓ | | |
| `payment.record` | Record payments | ✓ | ✓ | ✓ | |
| `payment.reverse` | Reverse a payment | ✓ | ✓ | | |
| `refund.issue` | Issue a refund | ✓ | ✓ | | |
| `transfer.verify` | Verify or reject a bank transfer | ✓ | | | |
| `document.share` | Print, download and share invoices and receipts | ✓ | ✓ | ✓ | |
| `closing.submit` | Submit one's own closing | ✓ | ✓ | ✓ | |
| `closing.approve` | Approve or return closings | ✓ | ✓ | | |
| `closing.reopen` | Reopen an approved closing | ✓ | | | |
| `report.own` | View one's own performance | ✓ | ✓ | ✓ | ✓ |
| `report.team` | View all staff performance | ✓ | ✓ | | |
| `report.financial` | Revenue, collections, outstanding | ✓ | ✓ | | |
| `dashboard.view` | Owner dashboard | ✓ | ✓ | | |
| `bonus.configure` | Bonus rules | ✓ | | | |
| `bonus.calculate` | Run calculations | ✓ | ✓ | | |
| `bonus.approve` | Approve or void bonuses | ✓ | | | |
| `bonus.pay` | Record bonus payment | ✓ | | | |
| `audit.view` | Search the audit log | ✓ | | | |
| `import.run` | Run imports | ✓ | | | |

**Default discount caps** (FR-DIS-004). These are seed values that the owner can edit:

- **Cashier:** 10% of the line or invoice, and at most ₦5,000 per invoice.
- **Supervisor:** 30%.
- **Owner:** unlimited.

---

# 4. Functional Requirements

Each requirement carries its PRD reference in brackets.

## 4.1 Authentication

| ID | Requirement | Priority |
|---|---|---|
| FR-AUTH-001 | Users shall authenticate with their configured credentials before accessing protected functions. [PRD-9-1] | Must |
| FR-AUTH-002 | The system shall provide authenticated session management with configurable idle timeouts (SEC-018). | Must |
| FR-AUTH-003 | Users with `user.manage` shall create and manage user accounts, and link them to employee profiles. | Must |
| FR-AUTH-004 | Users with `role.manage` shall assign roles to users. | Must |
| FR-AUTH-005 (rev) | Users shall be enabled or disabled, never deleted. Disabled users cannot authenticate. Their historical records stay unchanged in every report. | Must |
| FR-AUTH-006 | The system shall record authentication events: successful login, failed login, lockout, logout, password change. | Must |
| FR-AUTH-007 (new) | Shared-device mode. The owner registers a device as shared. On that device, employees switch identity with a personal 4–6 digit PIN. Each record is attributed to the PIN holder. The device auto-locks after 2 minutes without activity. [PRD-5.3-2] | Should |

## 4.2 Business Settings

**FR-BIZ-001.** The system shall keep the salon's business profile.

**FR-BIZ-002 (rev).** The following shall be configurable:

- business name and logo;
- contact details and address;
- receipt and invoice footer text. [PRD-6.3-1]

**FR-BIZ-003.** The default currency shall be NGN.

**FR-BIZ-004 (rev).** Order references, invoice numbers and receipt numbers shall use configurable formats. Invoice and receipt numbers shall be sequential with no gaps. A voided invoice keeps its number.

**FR-BIZ-005 (rev).** The following shall be configurable without code changes:

- timezone (default Africa/Lagos);
- tax settings;
- discount caps;
- late-submission window (default 48 hours);
- transfer-unverified alert period (default 2 days);
- shared-link expiry (default 7 days).

**FR-BIZ-006.** Changes to financial settings (tax, caps, numbering, windows) shall be audited with their before and after values.

## 4.3 Customers

**FR-CUS-001.** Authorised users shall create customer profiles.

**FR-CUS-002.** Customer records shall support name, phone, email and notes.

**FR-CUS-003.** The system shall support guest orders, which need no customer record.

**FR-CUS-004 (rev).** Every service order shall receive a unique, system-generated reference. Guest orders and registered orders use the same reference scheme. [PRD-7.1-2, PRD-7.3-1]

**FR-CUS-005 (rev).** For guests, a name and a phone number shall be optional. [PRD-7.1-2]

**FR-CUS-006.** Users with `customer.associate` shall link a guest order to an existing customer.

**FR-CUS-007.** Each association shall create an audit event.

**FR-CUS-008.** Customers shall be searchable by name or phone.

**FR-CUS-009 (rev).** Phone numbers shall be stored in normalised form (E.164, +234 by default). When a customer is created or imported with a normalised phone number that matches an existing customer, the system shall warn the user and require them to confirm. Merging records is out of scope. [PRD-7.1-4]

**FR-CUS-010 (new).** Users with `order.view_all` shall view a customer's order, invoice and payment history. [PRD-7.1-5]

## 4.4 Catalogue

**FR-SVC-001.** Users with `catalogue.manage` shall manage categories.

**FR-SVC-002 (rev).** Users with `catalogue.manage` shall manage catalogue items of type **Service** or **Product/Material**. [PRD-7.2-1]

**FR-SVC-003 (rev).** Each item shall have:

- name;
- type;
- category;
- price (≥ ₦0);
- active status;
- taxable or exempt flag;
- for services, a "quantity permitted" flag;
- for services, an allocation default (method, plus the primary percentage where applicable).

**FR-SVC-004.** Items may have descriptions.

**FR-SVC-005.** Inactive items shall not be selectable for new orders.

**FR-SVC-006.** Order items shall keep a snapshot of the item's name, price, type and taxable flag as they were when the item was added.

**FR-SVC-007.** Changing catalogue prices shall not modify existing orders or invoices.

**FR-SVC-008 (rev).** Prices shall be ≥ ₦0. The only way to make an order price differ from the catalogue price is a price override (FR-ORD-011).

**FR-SVC-009 (new).** A service may have **variants**. Each variant is separately priced and is selected when the order item is created. [PRD-7.2-3]

## 4.5 Service Orders

**FR-ORD-001.** Users with `order.create` shall create orders for a customer or a guest.

**FR-ORD-002.** An order shall contain one or more items.

**FR-ORD-003.** Each item shall reference a catalogue item or variant and keep its snapshot (FR-SVC-006).

**FR-ORD-004 (rev).** Quantity greater than 1 is allowed only where the item permits it. Line value = unit price × quantity.

**FR-ORD-005.** A service item may be assigned to one or more expected employees.

**FR-ORD-006.** A service item may stay unassigned.

**FR-ORD-007.** The backend shall calculate order values.

**FR-ORD-008.** Every order shall have a unique reference (FR-CUS-004).

**FR-ORD-009.** The system shall keep order status and history (§6.1).

**FR-ORD-010.** Finalised and cancelled orders shall reject ordinary edits.

**FR-ORD-011 (new).** Users with `order.item.price_override` may override an item's price before finalisation. A reason is mandatory. The original snapshot is kept alongside the override. [PRD-7.2-4]

**FR-ORD-012 (new).** Users with `order.item.add` may add items before finalisation. Each addition is audited. [PRD-7.3-5]

**FR-ORD-013 (new).** Users with `order.item.cancel_unexecuted` may cancel an item that has no active execution, giving a reason. Cancelling an item that has an active execution requires `execution.correct`. That cancellation also cancels the execution, with the same reason. [PRD-7.3-6]

**FR-ORD-014 (rev).** Users with `order.cancel` may cancel an open order, giving a reason. If any item has an active execution, `execution.correct` is also required. An order whose net paid is greater than ₦0 cannot be cancelled until its payments have been refunded or reversed. [PRD-7.3-7, PRD-7.9-2b]

**FR-ORD-015 (new).** Orders may stay open across business days. The system shall show how long each open order has been open. [PRD-7.3-7]

## 4.6 Service Execution

**FR-EXE-001 (rev).** An employee with `order.view_open` shall see every open order with status Open, together with its service items and their execution status. Prices, customer contact details and invoice data shall be hidden from users without `order.view_all`. [PRD-5.1-1, PRD-5.1-2]

**FR-EXE-002.** An employee shall submit an execution for a performed service item.

**FR-EXE-003.** Every execution shall reference exactly one order item of type Service. The item's status must be Requested, Approved or Pending Approval.

**FR-EXE-004 (rev).** Any user with `execution.submit` may submit an execution against any open service item, whether or not they are assigned to it. [PRD-7.4-3]

**FR-EXE-005.** A successful submission shall create the execution in status **Confirmed**.

**FR-EXE-006.** The execution shall record the submitting user and the time of submission. The submitter is a contributor and the primary by default.

**FR-EXE-007 (rev).** Each order item shall have at most one active execution. An active execution is one in status Confirmed or Disputed. If a second user tries to submit while an active execution exists, the system rejects the submission and offers that user "join as contributor". This must hold even when the two submissions arrive at the same moment. [PRD-7.4-4]

**FR-EXE-008.** An execution shall keep its relationship to its order and order item.

**FR-EXE-009 (rev).** Disputes:

- A contributor named on an execution, or a user with `execution.correct`, may dispute it with a reason. The status becomes Disputed.
- A user with `execution.correct` resolves the dispute in one of three ways: dismiss it (back to Confirmed), correct the execution, or cancel it. [PRD-7.4-6]

**FR-EXE-010 (rev).** A correction shall create a new Confirmed execution. The original's status becomes Superseded and it is linked to the replacement. Item, contributors and allocation may change. A reason is mandatory. [PRD-7.4-7]

**FR-EXE-011 (rev).** Contributors on an execution shall receive an in-app notification when it is disputed, corrected or cancelled (§4.22).

**FR-EXE-012.** Confirmed executions shall never be deleted or edited in place.

**FR-EXE-013 (new).** Late submission:

- Submissions are accepted while the order is Open.
- After finalisation, submissions are accepted up to the configured late window, measured from the finalisation time. Users without `execution.submit_late` are rejected after the window.
- An execution submitted after finalisation never changes the invoice. It may raise discrepancy R7 (§4.9). [PRD-7.4-9]

**FR-EXE-014 (new).** One execution covers the item's full quantity. [PRD-7.4-8]

**FR-EXE-015 (new).** An execution against a Pending Approval item is allowed. It is neither billable nor credited until the item is Approved. [PRD-7.6-3]

## 4.7 Contribution and Allocation

**FR-ATTR-001.** An execution shall have one or more contributors.

**FR-ATTR-002 (rev).** An employee shall appear at most once per execution.

**FR-ATTR-003 (rev).** Exactly one contributor shall be the primary. By default the primary is the submitter. The primary can be changed only with `allocation.override`.

**FR-ATTR-004.** The system shall support the **Equal** method.

**FR-ATTR-005 (rev).** The system shall support the **Custom percentages** method. Percentages are given to two decimal places and sum to exactly 100.00. Custom percentages can be set only with `allocation.override` or as a catalogue default.

**FR-ATTR-007 (rev).** The system shall support the **Primary/Assistant** method. The primary receives P%, where P is configured per service (system default 50). The other contributors share (100 − P)% equally. If the primary is the only contributor, they receive 100%.

**FR-ATTR-008 (rev).** When an execution is created, the system applies the method configured on the service. When contributors are added or removed, percentages are recalculated using the method.

**FR-ATTR-009 (rev).** A user with `allocation.override` may change the method or percentages for one execution, giving a reason. The change is audited. Overrides are allowed until the period's bonuses are Approved. After that, the override produces an adjustment (FR-BON-020).

**FR-ATTR-010.** The customer charge for a service shall be independent of the number of contributors.

**FR-ATTR-011.** Contribution values shall never be summed into revenue.

**FR-ATTR-012 (rev).** When the invoice is finalised, the system shall calculate and store, for each contributor:

- their percentage;
- their allocated contribution, from the line's net value (BR-011);
- their participated value.

Before finalisation, the same values are shown as *provisional*, based on the current line values.

**FR-ATTR-013 (new).** Join and add:

- Before finalisation, an employee with `execution.join` may add themselves to an active execution.
- Before finalisation, the submitter may add or remove contributors other than themselves.
- After finalisation, adding or removing contributors requires `execution.submit_late` (within the late window) or `execution.correct`.

Each change is audited and notifies the affected employees. [PRD-7.4-5]

## 4.8 Approvals: Additional Services, Over-limit Discounts, Corrections

**FR-ADD-001.** An employee with `additional.request` may request an additional catalogue service for an open order. The catalogue price applies.

**FR-ADD-002.** The request records the item, the requester, the order and an optional note.

**FR-ADD-003 (rev).** Creating the request creates an order item in status **Pending Approval**. The item stays pending until a user with `additional.decide` approves or rejects it. The requester may withdraw it before a decision is made.

**FR-ADD-004.** Only an Approved item is billable.

**FR-ADD-005.** The decision records the deciding user, the decision, the time and the reason. A reason is mandatory for a rejection.

**FR-ADD-006.** Rejected items shall never be invoiced.

**FR-ADD-007 (rev).** An execution against a Rejected item stays on record. It is non-billable, earns no contribution and is flagged as discrepancy R5.

**FR-ADD-008 (new).** Over-limit discounts. A discount above the requesting user's cap creates an **approval request**. The discount takes effect only after a user with `discount.approve` approves it. [PRD-7.10-3]

**FR-ADD-009 (new).** Approvers shall see pending approval requests in a single queue. Each requester is notified of the decision on their request.

**FR-ADD-010 (new).** Segregation of duties (BR-040) applies to every approval.

**FR-ADD-011 (new).** On a Finalised order, an additional-service request may be created within the late window. The request raises R7. Approving it requires `additional.decide` and `invoice.amend`, and is carried out as an amendment (FR-INV-007). [PRD-7.6-7]

## 4.9 Reconciliation (rev)

**FR-REC-001 (rev).** Reconciliation shall be a **derived** view, calculated from the order items, executions, approval requests and cancellations. Nothing in it is stored as a separate editable record. Results can therefore be reproduced at any time (FR-REC-008).

**FR-REC-002 (rev).** The system shall identify the following discrepancy types.

| Code | Condition | Blocking? |
|---|---|---|
| R1 | A billable service item has no active execution | **Blocking** |
| R2 | An execution is in status Disputed | **Blocking** |
| R3 | An item is Pending Approval | **Blocking** |
| R4 | An active execution exists on a Cancelled item, and no one has decided what to do about it | **Blocking** |
| R5 | An execution exists on a Rejected item | Informational |
| R6 | An execution was submitted by, or includes, an employee who was not assigned to the item | Informational |
| R7 | An execution was submitted after finalisation, against an item that was cancelled or covered by an exception | Informational. Shown on the dashboard as **high priority** |
| R8 | An item's price was overridden | Informational (exceptions report) |
| R9 | The order was finalised with an exception | Informational (exceptions report) |

**FR-REC-003 (rev).** For R4, a user with `execution.correct` shall decide either:

- **reinstate:** the item returns to Requested and becomes billable; or
- **confirm cancellation:** the execution stays on record, non-billable and with no contribution.

**FR-REC-004.** Approved additional items shall be included in reconciliation.

**FR-REC-005.** The reconciliation view shall show items in the following states: rejected, cancelled, disputed, superseded and corrected.

**FR-REC-006.** Users with `reconciliation.view` shall see the reconciliation view.

**FR-REC-007 (rev).** Ordinary finalisation is refused while any blocking discrepancy (R1–R4) exists (BR-030). [PRD-7.7a-3]

**FR-REC-008.** Reconciliation results shall be reproducible from the stored records.

## 4.10 Invoices

**FR-INV-001 (rev).** While an order is Open, the system shall show a **draft invoice**. The draft is a calculated preview of the billable items, discounts and taxes. It has no invoice number.

**FR-INV-002.** Finalisation requires `invoice.finalise`, or `invoice.finalise_with_exception` (FR-INV-014).

**FR-INV-003.** The backend shall calculate all invoice values.

**FR-INV-004.** The invoice shall show:

- items, with quantity, unit price and line discount;
- invoice discount;
- tax;
- totals;
- payments to date;
- balance.

**FR-INV-005 (rev).** Finalisation shall do the following in one transaction that either fully succeeds or fully fails:

- snapshot the billable lines, discounts and tax setting into revision 1;
- assign the next invoice number;
- store the contribution values (FR-ATTR-012);
- set the order status to Finalised.

The request shall include the total that was displayed to the user. If it differs from the server's calculated total, finalisation is refused with `409 TOTAL_CHANGED`, and the response includes the current breakdown. The order shall be locked for the whole operation. (rev v1.2)

**FR-INV-006.** Finalised invoices shall be read-only.

**FR-INV-007 (rev).** A user with `invoice.amend` may create a new revision, giving a reason. Line changes are made only through the controlled actions: correction, cancellation, adding an item, price override and discount. The new revision becomes the current one. Contribution values are recalculated, subject to FR-BON-020. [PRD-7.8-4]

*(rev v1.2)* An amendment is **one atomic command** containing a change set: items to cancel, items to add or reinstate, discount changes, price overrides, a reason, and an optional refund. A preview endpoint runs the same calculation without saving. The displayed-total check of FR-INV-005 applies. After finalisation, every change that affects billing goes through this command.

**FR-INV-008.** Every revision shall be kept unchanged, together with its actor, reason and time.

**FR-INV-009.** Invoice numbers shall be unique and sequential (FR-BIZ-004). Revisions keep the same invoice number, with a revision suffix.

**FR-INV-010.** The system shall generate a PDF of the invoice and a print layout (FR-RCT-008).

**FR-INV-011 (rev).** Users with `document.share` shall be able to download, print, share to WhatsApp and send by email (§4.14).

**FR-INV-012 (new).** Invoice lines shall be generated by the system only. No API or UI shall allow a line to be added or removed directly. [PRD-7.7a-2]

**FR-INV-013 (new).** If a revision's total is below the net amount already paid, the revision shall be saved only together with a refund of the difference, in the same transaction. [PRD-7.8-5]

**FR-INV-014 (new).** A user with `invoice.finalise_with_exception` may finalise while R1–R4 exist, giving a reason. The system records which discrepancies existed at that moment. Billable items remain billed. R1 items are billed. R3 (pending) items are excluded. R4 items are treated as cancelled. [PRD-7.7a-4]

**FR-INV-015 (new).** A user with `invoice.void` may void an invoice whose net paid is ₦0, giving a reason. The order reopens for correction or is cancelled. [PRD-7.8-6]

**FR-INV-016 (new).** Finalisation shall be refused if the order's net paid is greater than the invoice total that finalisation would produce. FR-PAY-015 normally prevents this state. Finalisation shows the part payments already received, the total and the balance. [PRD-7.9-2a]

## 4.11 Discounts

**FR-DIS-001.** Fixed-amount discounts shall be supported.

**FR-DIS-002.** Percentage discounts shall be supported.

**FR-DIS-003 (rev).** Discounts may be applied to a line or to the invoice, by users with `discount.apply`, before finalisation or as part of an amendment.

**FR-DIS-004 (rev).** Caps are set per role (§3.3). A discount within the user's cap applies immediately. A discount above it goes to FR-ADD-008.

**FR-DIS-005.** Every discount shall record its type, value, scope, reason, the user who applied it and the approver if there is one.

**FR-DIS-006 (rev).**

- No line net value may be below ₦0.
- The invoice net subtotal may not be below ₦0.
- Total discounts may not exceed the gross subtotal.

**FR-DIS-007.** Discounts shall be shown transparently on the invoice.

**FR-DIS-008 (new).** An invoice-level discount shall be spread across the lines in proportion to their value. Any rounding remainder goes to the line with the highest value. [PRD-7.10-4]

## 4.12 Taxes

**FR-TAX-001 (rev).** Users with `settings.manage` shall configure, at any time and without code changes: tax enabled (a new installation starts **off**), tax name, rate (%) and mode (inclusive or exclusive). When tax is enabled, it applies automatically to every non-exempt line on invoices finalised from then on. It cannot be removed from an individual invoice. [PRD-7.10-6]

**FR-TAX-002.** Inclusive mode: tax = discounted line value × rate ÷ (100 + rate). The total equals the discounted value.

**FR-TAX-003.** Exclusive mode: tax = discounted line value × rate ÷ 100. The total equals the discounted value plus tax.

**FR-TAX-004.** Catalogue items flagged exempt carry no tax.

**FR-TAX-005.** Each invoice revision shall keep the tax name, rate, mode and the tax calculated on each line.

**FR-TAX-006.** Tax shall be calculated by the backend, line by line, after discounts.

**FR-TAX-007.** The invoice shall show the tax name, rate, mode and amount.

**FR-TAX-008 (new).** Only one tax may apply. Stacking several taxes is out of scope. [PRD-7.10-7]

## 4.13 Payments

**FR-PAY-001 (rev 2).** Users with `payment.record` shall record payments against a **service order** that is either Open or Finalised. Payments recorded while the order is Open are *part payments*. At finalisation they count towards the invoice automatically, with no action by the user. No payment may be recorded on a Cancelled order, or on a Voided invoice while its order is not Open. [PRD-7.9-2]

**FR-PAY-002.** Cash payments shall be supported.

**FR-PAY-003.** Bank transfer payments shall be supported.

**FR-PAY-004.** POS payments shall be supported.

**FR-PAY-005.** Partial payments shall be supported.

**FR-PAY-006.** Full payments shall be supported.

**FR-PAY-007 (rev).** The system shall calculate the order's **net paid** = payments − reversed payments − refunds. The **balance** = current total − net paid. Before finalisation, the current total is the draft invoice total. After finalisation, it is the total of the current invoice revision.

**FR-PAY-008 (rev 2).** A payment amount shall be greater than 0 and no more than the current balance, calculated with the FR-PAY-007 definition, both before and after finalisation. Cash given back as change is not recorded.

**FR-PAY-009.** Each payment shall have a unique reference.

**FR-PAY-010.** Each payment records:

- method;
- amount;
- the date and time it was recorded;
- the business day;
- the recording user;
- an external reference (for transfers: sender name or bank reference; for POS: terminal receipt number), which is optional for cash.

**FR-PAY-011.** Payment history shall be kept unchanged.

**FR-PAY-012.** Invoice total, net paid and balance shall be shown as separate figures.

**FR-PAY-013 (new).** A transfer payment is created with verification status **Unverified**. A user with `transfer.verify` sets it to **Verified** or **Rejected**, and each change is audited. Rejecting a transfer automatically creates a reversal with reason "Transfer not received". [PRD-7.9-3]

**FR-PAY-014 (new).** Duplicate submissions shall be prevented. The same payment request submitted twice shall create only one payment (§9).

**FR-PAY-015 (new).** On an Open order, an action that would reduce the draft total below net paid shall be refused unless a refund of the excess is recorded in the same atomic operation. The refund requires `refund.issue`. Such actions include cancelling an item, adding a discount, a price override, or a decision to confirm a cancellation. [PRD-7.9-2a]

## 4.14 Receipts and Document Sharing

**FR-RCT-001.** Each payment shall generate a receipt.

**FR-RCT-002 (rev).** A receipt shall reference its service order and payment, and the invoice number if the order is finalised. It shall show the balance after the payment. A receipt for a payment made before finalisation shall be labelled "Part payment — order not yet invoiced" and shall show the draft total at that moment.

**FR-RCT-003.** Each receipt shall have a unique, sequential number.

**FR-RCT-004.** Receipts shall be printable and downloadable as PDF.

**FR-RCT-005 (rev).** WhatsApp sharing:

- (a) On devices that support it, the device's share function shares the PDF file.
- (b) Otherwise, the system opens a `wa.me` link, addressed to the customer's phone where one is known. The link contains a signed, expiring URL to the document (SEC-022). [PRD-7.9-5]

**FR-RCT-006.** Historical receipt details shall be kept unchanged.

**FR-RCT-007.** No receipt shall exist without a recorded payment.

**FR-RCT-008 (new).** Print layouts:

- A4 for invoices and receipts (Must).
- 80 mm thermal for receipts (Should).

**FR-RCT-009 (new).** Invoices and receipts shall be sent by email through the configured SMTP account, to an address entered or stored. The system shall record the outcome of each send.

**FR-RCT-010 (new).** A receipt for a reversed payment, when viewed, shall show that the payment was reversed.

## 4.15 Refunds and Reversals

**FR-REF-001 (rev).** Two separate actions shall be supported:

- **Reversal** (`payment.reverse`): cancels the effect of one payment in full.
- **Refund** (`refund.issue`): returns money, in full or in part, against an invoice. It records the method used to return the money.

**FR-REF-002.** Both actions require their permission. The default Cashier role holds neither.

**FR-REF-003.** A reason is mandatory for both.

**FR-REF-004.** The original payment is kept.

**FR-REF-005.** The system records the amount, actor, reason, time and business day.

**FR-REF-006.** The invoice balance shall be recalculated (FR-PAY-007) without erasing any history.

**FR-REF-007 (rev).**

- A payment may be reversed only once.
- The total refunds on an invoice shall not exceed its net amount paid.
- Duplicate submissions shall be prevented.

**FR-REF-008 (new).** Reversals and refunds appear in the closing of the business day **on which they occur**, attributed to the user who performed them, except for transfer-rejection reversals (FR-CASH-002). [PRD-7.11-2]

**FR-REF-009 (new).** A refund does not change contribution values. A user who wants staff credit to change must perform a correction or an amendment. [PRD-7.11-3]

## 4.16 Cashier Closing

**FR-CASH-001 (rev).** One closing shall exist per user for each business day on which that user recorded at least one payment, refund or reversal. [PRD-7.12-1]

**FR-CASH-002 (rev).** The system shall calculate the expected amount per method from the user's own events on that day:

- **Cash:** payments − reversals of cash payments − cash refunds.
- **POS:** payments − reversals.
- **Transfer:** payments − reversals, with the verification status listed for each transfer. Reversals caused by rejecting a transfer are **excluded**. They are listed in the Transfer exceptions report instead. (rev v1.2) [PRD-7.12-6]

**FR-CASH-003 (rev).** The user declares:

- a cash count, with an optional opening float;
- a POS total from the terminal's end-of-day slip;
- confirmation of the transfer list.

**FR-CASH-004.** The system compares expected and declared amounts for cash (expected = declared − float).

**FR-CASH-005.** The system compares expected and declared amounts for POS.

**FR-CASH-006.** The system compares expected and declared amounts for transfers.

**FR-CASH-007.** The system calculates the discrepancy per method and in total. A discrepancy must have a note before submission.

**FR-CASH-008.** Closings and discrepancies shall be visible to users with `closing.approve`, and on the dashboard.

**FR-CASH-009.** A closing records the user, business day, expected amounts, declared amounts, discrepancies, notes, status, submitter, approver and timestamps.

**FR-CASH-010 (rev).** A closing shall be read-only once Submitted. An approver can return it to Draft with a reason. An Approved closing can be reopened only by a user with `closing.reopen`, with a reason. *(rev v1.2)* While a user's closing for a business day is Submitted or Approved, that user shall not be able to record payments, refunds or reversals for that day (`409 CLOSING_SUBMITTED`). [PRD-7.12-5]

**FR-CASH-011 (new).** Where several users share one POS terminal, the approver shall see a total across all users for the business day for POS, to compare with the slip.

## 4.17 Staff Performance

**FR-PERF-001.** The system shall report services performed, by employee and by service or category.

**FR-PERF-002.** The system shall report service counts by employee.

**FR-PERF-003 (rev).** The system shall report **participated value**, **allocated contribution** and **collected contribution** by employee. Each is labelled with its definition. [PRD-7.5-7]

**FR-PERF-004 (rev).** "Customers served" shall be the number of distinct orders on which the employee has a contribution on an active execution. [PRD-7.13-1]

**FR-PERF-005.** The system shall distinguish solo executions (one contributor) from collaborative ones.

**FR-PERF-006.** Daily, weekly and monthly reporting shall be available.

**FR-PERF-007.** Custom date ranges shall be supported.

**FR-PERF-008.** Employees shall see only their own performance unless they hold `report.team`.

**FR-PERF-009.** Team reports shall require `report.team`.

**FR-PERF-010 (rev).** The sum of allocated contribution across all employees shall equal the net service revenue for the same scope. Participated value shall never be summed as revenue.

**FR-PERF-011 (new).** Performance shall be dated by the invoice finalisation date. Provisional figures for open orders may be shown separately and labelled "provisional".

**FR-PERF-012 (new).** Only active executions on billable, finalised lines shall count.

## 4.18 Bonus Management

**FR-BON-001.** Users with `bonus.configure` shall create and edit bonus rules.

**FR-BON-002 (rev).** A rule shall have a period of **weekly** (Monday–Sunday) or **monthly** (calendar month).

**FR-BON-003 (rev).** Basis **allocated contribution** shall be supported.

**FR-BON-004 (rev).** Basis **collected contribution** shall be supported. The collected ratio is measured at calculation time, for invoices finalised within the period.

**FR-BON-005 (rev).** Eligibility filters shall be supported:

- service categories;
- employees or roles;
- optional minimum thresholds (service count, allocated contribution). All thresholds must be met.

**FR-BON-006 (rev).** Basis **service count** shall be supported.

**FR-BON-007 (rev).** Formula types shall be supported:

- (a) percentage of basis. For the service-count basis, this is a fixed amount per service instead (rev v1.2);
- (b) fixed amount when the basis is at least a threshold;
- (c) tiered marginal percentages.

For the service-count basis, each active execution on a finalised billable line counts 1 for every contributor on it. [PRD-7.14-1]

**FR-BON-008.** Each calculation shall keep the rule version, the input totals and links to the records used.

**FR-BON-009.** A new calculation shall have status **Calculated**.

**FR-BON-010 (rev).** A user with `bonus.approve` shall approve calculations.

**FR-BON-011.** An approved calculation shall have status **Approved**.

**FR-BON-012 (rev).** A user with `bonus.pay` shall record one payment of the full approved amount, with date, method and reference.

**FR-BON-013.** A paid calculation shall have status **Paid**.

**FR-BON-014.** The system shall record who approved and paid, and when.

**FR-BON-015 (rev).** Editing a rule shall create a new rule version. Existing calculations keep their version.

**FR-BON-017 (new).** A user with `bonus.approve` may void a Calculated or Approved bonus, giving a reason.

**FR-BON-018 (new).** Recalculating a period for a rule shall void the existing Calculated records automatically (reason "Recalculated") and create new ones. Approved and Paid records are not affected.

**FR-BON-019 (new).** Calculations shall be run on demand by a user with `bonus.calculate`, for a chosen rule and a completed period.

**FR-BON-020 (new).** If a correction, override or amendment changes the inputs of an Approved or Paid bonus, the system shall record the difference as an **adjustment**. The adjustment is included in the next calculation of that rule for that employee. [PRD-7.14-5]

*(rev v1.2)* The adjustment is calculated by re-running the stored rule version with the corrected allocated values and the saved collected ratios (FR-BON-021). If a calculation's result would be negative, the payout is ₦0 and the remainder carries forward as a new adjustment.

**FR-BON-021 (new).** Each calculation shall save the collected ratio it used for each invoice. Money collected after the calculation shall not change an Approved or Paid bonus. [PRD-7.5-7]

## 4.19 Dashboard and Reports

**FR-DASH-001.** An owner dashboard shall be provided to users with `dashboard.view`.

**FR-DASH-002 (rev).** It shall show billed revenue, collected revenue and outstanding for today, this week, this month, this year and a custom range. Each is labelled (BR-050).

**FR-DASH-003.** It shall show payments by method, and outstanding balances by invoice and by age.

**FR-DASH-004.** It shall show service activity: open orders by age, services performed, and services awaiting execution.

**FR-DASH-005.** It shall show discrepancies: blocking items on open orders, and R7 items marked high priority.

**FR-DASH-006.** It shall show a staff performance summary.

**FR-DASH-007.** It shall show a bonus summary by status.

**FR-DASH-008.** Dashboard data shall respect permissions.

**FR-DASH-009.** Date filters shall be available.

**FR-DASH-010.** Dashboard figures shall equal the corresponding report queries.

**FR-DASH-011 (new).** An **exceptions panel** shall show:

- finalisations with exception;
- price overrides;
- over-limit discount approvals;
- cancellations of items;
- reversals and refunds;
- R7 discrepancies.

**FR-DASH-012 (new).** It shall show **unverified transfers** older than the alert period, and rejected transfers.

**FR-DASH-013 (new).** It shall show **cashier closings** by status, with their discrepancies.

**FR-DASH-014 (new).** It shall show a summary of discounts and taxes for the period.

**FR-DASH-015 (new).** It shall show a feed of recent sensitive activity, drawn from the audit log.

**FR-DASH-016 (new).** Users with `dashboard.view` may mark an exception as reviewed, with an optional note (DR-011). Reviewed items leave the "needs attention" list but stay in reports. [PRD-9-6]

## 4.20 Audit Trail

**FR-AUD-001 (rev).** The system shall record every action listed in PRD-9-2.

**FR-AUD-002.** Each event shall record the actor and, where a shared device was used, the device.

**FR-AUD-003.** Each event shall record the affected record's type and identifier.

**FR-AUD-004.** Each event shall record a server timestamp in UTC. It is displayed in the business timezone.

**FR-AUD-005.** Each event shall record before and after values where applicable, and the reason where one is required.

**FR-AUD-006.** Audit records shall be append-only through the application. No API shall update or delete them.

**FR-AUD-007.** Users with `audit.view` shall search the audit log by date, actor, action, record and order reference.

**FR-AUD-008.** The audit log shall support investigation of orders, executions, invoices, payments, refunds, closings, bonuses and permissions.

**FR-AUD-009 (new).** Every denied attempt at a protected action shall be recorded, with the actor, the action and the target (SEC-016).

**FR-AUD-010 (new).** An order's detail view shall show its audit timeline to users with `order.view_all`.

## 4.21 Data Import

**FR-IMP-001.** CSV and XLSX imports shall be supported.

**FR-IMP-002 (rev).** Two import types shall be supported:

- **Customers:** name (required); phone, email and notes (optional).
- **Catalogue items:** name, type, category, price and taxable flag (required); variant parent and description (optional).

The exact column templates are provided as downloadable files.

**FR-IMP-003.** The system shall validate every row before saving any record. The upload is checked first, then the user confirms.

**FR-IMP-004.** The system shall report the row number, field and error for each invalid row.

**FR-IMP-005.** The system shall show an import summary: total, valid, invalid, duplicates and created.

**FR-IMP-006 (rev).** Likely duplicates shall be flagged, by normalised phone for customers and by name within a category for catalogue items. The user chooses to skip them or create them.

**FR-IMP-007.** Imports shall be audited and kept in an import history.

**FR-IMP-008 (new).** An import shall be all-or-nothing. If saving fails, no records from that import are kept.

## 4.22 Notifications (new)

**FR-NOT-001.** In-app notifications shall be provided as a badge and a list, refreshed at least every 30 seconds while the app is open.

**FR-NOT-002.** The following events shall generate notifications:

| Event | Recipients |
|---|---|
| Additional-service request pending | Users with `additional.decide` |
| Over-limit discount request pending | Users with `discount.approve` |
| Decision on a request | The requester |
| Named as a contributor, or removed as one | The employee |
| Execution disputed, corrected or cancelled | Its contributors and users with `execution.correct` |
| R7 discrepancy | Users with `dashboard.view` |
| Closing submitted or returned | The approvers, or the submitter |
| Transfer past the alert period | Users with `transfer.verify` |

**FR-NOT-003 (Should).** Web Push shall be offered where the browser supports it.

**FR-NOT-004.** Notifications shall not contain customer contact details or monetary amounts.

---

# 5. Business Rules

## 5.1 Service and Revenue

**BR-001.** A service is charged once, however many employees contribute.

**BR-002.** Contribution values are not customer charges and not revenue.

**BR-003.** Order items and executions are separate records.

**BR-004.** Submission confirms the execution automatically.

**BR-005.** Confirmed executions are never deleted or overwritten.

**BR-006.** Corrections need `execution.correct` and an audit record. They replace the original through a link to it.

**BR-007.** Additional services need approval before they become billable.

**BR-008 (rev).** Invoice lines are generated by the system from billable items only.

## 5.2 Allocation

**BR-009.** Every contributor belongs to one execution.

**BR-010.** The percentages on an execution sum to exactly 100.00.

**BR-011 (rev).** The net value of line L is the line value − line discount − the invoice discount share spread to L, excluding tax. If tax is inclusive, the tax is removed from this value. A contributor's allocated contribution is the line net value × their percentage ÷ 100, rounded to the kobo. The remainder goes to the primary, so that the allocations sum exactly to the line net value.

**BR-012.** The method, percentages and resulting values are stored for every execution. Reports read the stored values; they do not recalculate them.

**BR-013 (new).** Executions on non-billable items (Rejected, Cancelled, R4 cancellation confirmed) earn zero contribution.

## 5.3 Invoices and Finalisation

**BR-030 (new). Finalisation gate.** Ordinary finalisation requires none of R1–R4 to exist.

**BR-031 (new).** Finalisation with an exception requires `invoice.finalise_with_exception` and a reason. The exception does not change what is billable.

**BR-014.** Finalised invoices are read-only.

**BR-015.** Changes after finalisation are made only as revisions, by users with `invoice.amend`.

**BR-016.** Financial calculations are made by the backend.

**BR-032 (new).** An invoice may be voided only when its net paid is ₦0.

## 5.4 Discounts and Tax

**BR-033 (new).** The calculation order is:

1. Line value.
2. Line discount.
3. Spread of the invoice discount.
4. Line net.
5. Tax per line.
6. Totals.

**BR-034 (new).** A discount above the user's cap requires approval by another user.

## 5.5 Payments

**BR-017.** A receipt represents a recorded payment.

**BR-018.** Partial payments are supported.

**BR-019.** Balance = total − (payments − reversals − refunds).

**BR-020.** Refunds and reversals keep the original history.

**BR-035 (rev).** Payments belong to the service order. They are accepted while the order is Open or Finalised, and only up to the balance (FR-PAY-007). Net paid may never exceed the current total.

**BR-036 (new).** An unverified transfer counts towards the balance. Rejecting the transfer reverses it.

## 5.6 Bonuses

**BR-021.** Calculations use configured rules and eligible stored records.

**BR-022.** A Calculated bonus is not payable.

**BR-023.** An Approved bonus is not Paid until a payment is recorded.

**BR-024.** Rule changes never alter Approved or Paid bonuses. Changes to their inputs become adjustments.

## 5.7 Cashier

**BR-025.** A closing compares expected and declared amounts per method, per user, per business day.

**BR-026.** Discrepancies are kept and are visible to approvers.

## 5.8 Access

**BR-040 (new). Segregation of duties.** For approval requests, refunds, reversals, execution corrections and dispute resolution, the acting or approving user must differ from the user who requested the action. Where an execution is involved, they must also differ from its submitter. The Owner role is exempt, and every exempt action records an audit flag.

**BR-041 (new).** Employees without `order.view_all` never receive prices, customer contact details or invoice data from the API.

## 5.9 Reporting

**BR-050 (new).** Billed revenue is dated at finalisation. Collected revenue is dated at the date each payment, reversal or refund occurred.

---

# 6. Lifecycle and State Requirements (rev, AM2-S-01)

These are the approved transition tables (Stage 2, §6).

"Lock" means the transaction locks the order's row (`SELECT … FOR UPDATE`) before any checks run. Every transition writes an audit event in the same transaction. In the "Reversible?" column, **No** means the transition can only be compensated by another recorded action; it is never undone in place.

## 6.1 Service order

| Transition | Actor / permission | Guards | Side effects | Audit event | Reversible? |
|---|---|---|---|---|---|
| create → **Open** | `order.create` | ≥ 1 active catalogue item. Guest or customer. | Order reference assigned from the daily sequence. Items snapshotted. | `order.created` | — |
| Open → **Finalised** (ordinary) | `invoice.finalise` | Lock. No R1–R4. Client total = server total (FR-INV-005). net_paid ≤ total (FR-INV-016). | Invoice created with revision 1, lines and invoice number. Ledger rows written. Finalisation business day stored. Late-window clock starts. | `invoice.finalised` | Only by void (net paid = 0) |
| Open → **Finalised** (exception) | `invoice.finalise_with_exception` | As above, but R1–R4 may exist. Reason required. | As above. A `FinalisationException` row records the open discrepancies. R3 items excluded. R4 items treated as cancelled. | `invoice.finalised_with_exception` | As above |
| Open → **Cancelled** | `order.cancel` (+ `execution.correct` if any execution is active) | Lock. net_paid = 0. Reason. | Items Cancelled. Active executions Cancelled. | `order.cancelled` | No |
| Finalised → **Open** (void) | `invoice.void` | Lock. net_paid = 0. Reason. Segregation of duties (SoD) applies. | Invoice Voided (its number is kept). Ledger rows for that invoice superseded. Bonus adjustments evaluated. | `invoice.voided` | Refinalise later (new invoice number) |

Payment status (Unpaid / Partially paid / Paid) is calculated (§7.6.5). It is not a state.

## 6.2 Order item

| Transition | Actor / permission | Guards | Side effects | Audit event | Reversible? |
|---|---|---|---|---|---|
| → **Requested** | `order.create` / `order.item.add` | Order Open. Item active. | Snapshot. | `order.item_added` | — |
| → **Pending Approval** | `additional.request` | Order Open, or Finalised within the late window (FR-ADD-011). | Approvers notified. | `additional.requested` | — |
| Pending → **Approved** | `additional.decide` (+ `invoice.amend` if the order is Finalised) | SoD. | Order Finalised → an amendment revision is created. | `additional.approved` | Cancel through the correction path |
| Pending → **Rejected** | `additional.decide` | Reason. SoD. | Any execution on it shows as R5. | `additional.rejected` | No |
| Pending → **Cancelled** (withdraw) | Requester, or `order.item.cancel_unexecuted` | No decision yet. | — | `additional.withdrawn` | No |
| Requested/Approved → **Cancelled** (unexecuted) | `order.item.cancel_unexecuted` | Lock. Order Open. No active execution. Reason. Draft total stays ≥ net_paid, or a refund is recorded atomically. | Shown on the discrepancy report. | `order.item_cancelled` | Reinstate (R4) or re-add |
| Requested/Approved → **Cancelled** (executed) | `execution.correct` | Lock. Reason. SoD against the submitter. If the order is Finalised → done through an amendment. | Execution Cancelled. Ledger updated if the item was billed. | `order.item_cancelled_executed` | Reinstate |
| Cancelled → **Requested** (R4 reinstate) | `execution.correct` (+ `invoice.amend` if Finalised) | An active execution exists on the item. | Item billable again. | `reconciliation.reinstated` | Cancel again |

## 6.3 Execution

| Transition | Actor / permission | Guards | Side effects | Audit event | Reversible? |
|---|---|---|---|---|---|
| → **Confirmed** | `execution.submit` | Lock. Item type Service. Item status is Requested, Approved or Pending. **No other active execution** on the item (unique key, §7.7.3). Within the window, otherwise `execution.submit_late`. Contributors are active employees with no duplicates. | Allocation from the service's default method. Submitter is primary. Named contributors notified. Ledger written if the line is already billed (FR-EXE-013). R7 raised if the item is cancelled and the order is finalised. | `execution.submitted` | Correction or cancellation |
| Confirmed → Confirmed (join) | `execution.join` | Order Open, or within the window. Not already a contributor. | Shares recalculated with the method. Ledger updated if billed. | `execution.joined` | Removed by a supervisor (correction) |
| Confirmed → Confirmed (contributors edited by the submitter) | Submitter | Order Open. Submitter cannot remove themselves. | As for join. | `execution.contributors_changed` | Yes, by a further edit |
| Confirmed → Confirmed (allocation override) | `allocation.override` | Reason. SoD against the submitter. | Ledger updated if billed. Adjustment if the period is Approved or Paid. | `allocation.overridden` | By another override |
| Confirmed → **Disputed** | `execution.dispute` (named contributor), or `execution.correct` | Reason. | Contributors and correctors notified. Blocks ordinary finalisation (R2). | `execution.disputed` | Dismiss |
| Disputed → **Confirmed** (dismiss) | `execution.correct` | Reason. SoD. | Notify. | `execution.dispute_dismissed` | Dispute again |
| Confirmed/Disputed → **Superseded** + new Confirmed | `execution.correct` | Reason. SoD. | New execution linked to the old one through `supersedes_id`. Ledger updated if billed. | `execution.corrected` | Correct again |
| Confirmed/Disputed → **Cancelled** | `execution.correct` | Reason. SoD. | Item becomes R1 again (Open order) or ledger rows are superseded (Finalised). | `execution.cancelled` | Submit a new execution |

## 6.4 Discount approval request (over-limit)

| Transition | Actor / permission | Guards | Side effects | Audit event | Reversible? |
|---|---|---|---|---|---|
| → **Pending** | `discount.apply` | Requested amount is above the user's cap and within the absolute limits (FR-DIS-006). | The discount is *not* applied. Approvers notified. | `discount.approval_requested` | — |
| Pending → **Approved** | `discount.approve` | Lock. SoD. Approver's cap ≥ amount. Recalculated total still ≥ net_paid (or refund recorded). | Discount applied. Requester notified. | `discount.approved` | Remove the discount (another discount action) |
| Pending → **Rejected** | `discount.approve` | Reason. | Notify. | `discount.rejected` | No |
| Pending → **Withdrawn** | Requester | — | — | `discount.approval_withdrawn` | No |

Pending requests expire when the order is finalised or cancelled. They become Withdrawn, with a system reason.

## 6.5 Invoice and revisions

| Transition | Actor / permission | Guards | Side effects | Audit event | Reversible? |
|---|---|---|---|---|---|
| Revision 1 | Finalisation (§6.1) | — | Number assigned. Lines snapshotted. | (as §6.1) | — |
| Revision n → n+1 (amend) | `invoice.amend` | Lock. Change set valid. Reason. SoD for the parts that need it. New total ≥ net_paid, or refund included in the same operation (FR-INV-013). Client total = server total. | New revision becomes current. The old one stays and cannot be changed. Ledger re-written. Bonus adjustments. | `invoice.amended` | Amend again |
| → **Voided** | `invoice.void` | See §6.1. | See §6.1. | `invoice.voided` | No (refinalise issues a new number) |

## 6.6 Payments, transfer verification, reversals, refunds

| Transition | Actor / permission | Guards | Side effects | Audit event | Reversible? |
|---|---|---|---|---|---|
| → **Recorded** | `payment.record` | Lock. Order Open or Finalised. 0 < amount ≤ balance (FR-PAY-008). The user's closing for today is not Submitted or Approved (FR-CASH-010). Idempotency key. | Receipt number assigned and receipt created. Transfers start Unverified. Closing Draft created if missing. | `payment.recorded` | Reversal |
| Recorded → **Reversed** | `payment.reverse` | Lock. Not already reversed. Reason. SoD against the recorder. Actor's closing for today is not Submitted. | `PaymentReversal` row. Balance restored. | `payment.reversed` | No (record a new payment) |
| Transfer: Unverified → **Verified** | `transfer.verify` | Not reversed. | — | `transfer.verified` | → Rejected only with a reason, before any refund |
| Transfer: Unverified → **Rejected** | `transfer.verify` | Reason. | System reversal tagged `transfer_rejected`, excluded from closings (FR-CASH-002). | `transfer.rejected` | No |
| → **Refund** recorded | `refund.issue` | Lock. 0 < amount ≤ net_paid. Reason. Method. SoD. Actor's closing open. Idempotency key. | Balance increases. A cash refund reduces the actor's expected cash. | `refund.issued` | No (a new payment if made in error) |

Verification status is held in a separate `transfer_verifications` table (latest row wins). This keeps the payment row itself unchanged (DR-006).

## 6.7 Reconciliation

Reconciliation is **calculated, not stored** (FR-REC-001). Only three kinds of record are stored:

- **R4 decision** (`ReconciliationDecision`): reinstate, or confirm cancellation. Actor `execution.correct`. Not reversible, but a later reinstate or cancel is possible.
- **Finalisation exception** (`FinalisationException`): written at finalisation (§6.1).
- **Exception review** (`ExceptionReview`, DR-011): Unreviewed → Reviewed, by `dashboard.view`, with an optional note. This is a flag only; it never changes business data.

## 6.8 Cashier closing

| Transition | Actor / permission | Guards | Side effects | Audit event | Reversible? |
|---|---|---|---|---|---|
| → **Draft** | System | First money event by the user on that day. | — | — | — |
| Draft → **Submitted** | Owner of the closing, with `closing.submit` | Lock the closing. Declared amounts given. A note if any discrepancy ≠ 0. | Expected amounts frozen into closing lines. The user's money events for that day are blocked (FR-CASH-010). Approvers notified. | `closing.submitted` | Return |
| Submitted → **Draft** (return) | `closing.approve` | Reason. | Frozen lines discarded and recalculated at the next submission. Money events unblocked. | `closing.returned` | Resubmit |
| Submitted → **Approved** | `closing.approve` | SoD (not your own closing). | — | `closing.approved` | Reopen |
| Approved → **Draft** (reopen) | `closing.reopen` (owner) | Reason. | As for return. | `closing.reopened` | Resubmit |

## 6.9 Bonus calculation and payout

| Transition | Actor / permission | Guards | Side effects | Audit event | Reversible? |
|---|---|---|---|---|---|
| → **Calculated** | `bonus.calculate` | The period has ended. Rule active. | Existing Calculated rows for the same rule and period are Voided ("Recalculated"). Inputs saved. Adjustments linked (not consumed until Paid). | `bonus.calculated` | Recalculate or void |
| Calculated → **Approved** | `bonus.approve` | SoD with the calculator (owner exempt). | — | `bonus.approved` | Void |
| Approved → **Paid** | `bonus.pay` | Amount = approved amount. Date, method and reference. Idempotency key. | `BonusPayment` row. Linked adjustments marked consumed. | `bonus.paid` | **No** (later corrections become adjustments) |
| Calculated/Approved → **Voided** | `bonus.approve`, or the system | Reason. | Linked adjustments released for the next calculation. | `bonus.voided` | Recalculate |
| *(event)* Adjustment created | System | A ledger change touches an Approved or Paid calculation. | `BonusAdjustment` row (FR-BON-020). | `bonus.adjustment_created` | Superseded by later changes |

---

---

# 7. Data Requirements

## 7.1 General Principles

1. A relational database (MySQL) with foreign keys enforced.
2. Appropriate normalisation and referential integrity.
3. Stable, non-reused primary identifiers.
4. Unique business references (order reference, invoice number, receipt number, payment reference).
5. Historical financial snapshots that cannot be changed.
6. Database transactions for multi-record operations: finalisation, amendment with refund, payment with receipt, import, correction.
7. No hard-coded configurable values.
8. Timestamps stored in UTC and shown in the business timezone.
9. Money stored as integer kobo. Percentages and rates stored as integer basis points. Half-up rounding at defined points (§7.6).
10. Audit history for important changes.
11. No hard deletion of business records. Configuration records are deactivated instead of deleted.

## 7.2 Conceptual Entity Inventory (rev)

This is a conceptual list, not the final schema. The final schema is designed in Stage 2.

| Entity | Purpose |
|---|---|
| Business | The salon, and the scope key for future multi-business support. |
| BusinessSetting | Configuration values. |
| User | Login identity. |
| Role, Permission, UserRole, RolePermission | Access control, including role discount caps. |
| SharedDevice | A registered shared device (FR-AUTH-007). |
| EmployeeProfile | Employee details linked to a user. |
| Customer | Registered customer. |
| CatalogueCategory | Category. |
| CatalogueItem | A service or product, including variants (parent link) and allocation defaults. |
| ServiceOrder | A visit or transaction. |
| OrderItem | A line with its snapshot, status, quantity and price override. |
| OrderItemAssignment | Expected employees. |
| Execution | A performed-service record and its status, with the link to the record it supersedes. |
| ExecutionContributor | Employee, primary flag, percentage, and the allocated and participated values stored at finalisation. |
| ApprovalRequest | Over-limit discount requests. Additional-service requests are order items in status Pending Approval. |
| Discount | A line or invoice discount, with scope, value, reason and approver. |
| ReconciliationDecision | R4 decisions (reinstate or confirm cancellation). |
| FinalisationException | The exception record, with a reason and the discrepancies that existed. |
| TaxSetting | The current tax configuration. |
| Invoice | Invoice number, current revision and void status. |
| InvoiceRevision | Immutable totals and tax setting snapshot. |
| InvoiceLine | An immutable line snapshot for each revision, including discount share and tax. |
| Payment | A payment against a service order, before or after finalisation, with transfer verification status. |
| PaymentReversal | A reversal record. |
| Refund | A refund record against a service order. |
| Receipt | A receipt for a payment. |
| NumberSequence | Sequences without gaps for invoices and receipts. |
| CashierClosing | One closing per user and business day. |
| CashierClosingLine | Expected and declared amounts per method. |
| BonusRule, BonusRuleVersion | Rule definitions, version by version. |
| BonusCalculation | Results, with approval, payment and void fields. |
| BonusAdjustment | Differences carried forward. |
| BonusPayment | A recorded bonus payment. |
| Notification | In-app notifications. |
| SharedDocumentLink | A signed, expiring link token. |
| AuditEvent | Append-only audit log, with a hash chain (Should). |
| ContributionLedger | Staff credit in money, superseded rather than edited (DR-007). |
| TransferVerification | Append-only verification history (DR-006). |
| ExceptionReview | Review flags (DR-011). |
| IdempotencyKey | Replay protection (DR-010). |
| BusinessUser | Membership of a user in a business (DR-004). |
| ImportBatch, ImportRowResult | Import history. |

## 7.3 Key Relationships

- A business has many users, customers, catalogue items and orders.
- A customer has many orders. An order has at most one customer, and a guest order has none.
- An order has many items. An item has many executions over its history, but at most one active execution.
- An execution has one or more contributors.
- An order has at most one invoice. An invoice has one or more revisions. A revision has many lines.
- An order has many payments and many refunds. Before finalisation they are linked to the order. After finalisation they are reported against its invoice. A payment has one receipt and at most one reversal.
- A user has at most one closing per business day.
- A bonus rule has many versions. A version has many calculations. A calculation has at most one payment.

## 7.4 Historical Snapshots

The system shall keep snapshots of:

- item name, type, price and taxable flag on each order item;
- price overrides alongside the original price;
- invoice lines, discounts and the tax setting for each revision;
- contribution percentages and values for each execution;
- bonus rule version and inputs for each calculation.

## 7.5 Financial Integrity

The following shall hold:

- **(a)** Sum of allocated contributions for an execution = line net value.
- **(b)** Sum of invoice line nets + tax = invoice total.
- **(c)** Sum of invoice discount shares = invoice discount.
- **(d)** Balance = current total − net paid. It is never negative, either before or after finalisation.
- **(e)** Expected amounts in a closing = the sum of that day's events for that user.

These invariants shall be covered by automated tests (NFR-MNT-004).

### Worked examples (normative)

These examples assume tax is disabled unless stated otherwise.

**E1 — Equal three-way split.** The line is ₦20,000 with no discount. Contributors are A (primary), B and C, with an equal split.

- Each non-primary share = 20,000 ÷ 3, rounded to the kobo = ₦6,666.67.
- The primary receives the remainder: 20,000 − 2 × 6,666.67 = ₦6,666.66.

| Contributor | Allocated |
|---|---|
| A (primary) | ₦6,666.66 |
| B | ₦6,666.67 |
| C | ₦6,666.67 |
| **Total** | **₦20,000.00** |

The percentages stored for display are 33.33%, 33.33% and 33.34%, with the extra 0.01 on the primary. The stored values above are authoritative. Participated value is ₦20,000 for each contributor. Revenue is ₦20,000.

**E2 — Primary/Assistant.** The line is ₦20,000 with P = 50 and two assistants:

| Contributor | Allocated |
|---|---|
| Primary | ₦10,000 |
| Assistant 1 | ₦5,000 |
| Assistant 2 | ₦5,000 |

**E3 — Invoice discount spread, tax exclusive at 7.5%.** The lines are Braids ₦20,000 and Pedicure ₦10,000. A 10% invoice discount of ₦3,000 is spread as ₦2,000 to Braids and ₦1,000 to Pedicure.

| Line | Value | Discount share | Net | Tax (7.5%) |
|---|---|---|---|---|
| Braids | ₦20,000 | ₦2,000 | ₦18,000 | ₦1,350 |
| Pedicure | ₦10,000 | ₦1,000 | ₦9,000 | ₦675 |
| **Total** | **₦30,000** | **₦3,000** | **₦27,000** | **₦2,025** |

The invoice total is ₦29,025. Allocated contribution is based on the ₦18,000 net for Braids.

**E4 — Inclusive tax at 7.5%.** The line is ₦21,500 with no discount.

- Tax = 21,500 × 7.5 ÷ 107.5 = ₦1,500.00.
- Net = ₦20,000.00.
- Total = ₦21,500.

**E5 — Collected contribution.** The invoice total is ₦29,025 and ₦14,512.50 has been paid, so the paid ratio is 0.5. Employee A has ₦6,000 of allocated contribution on that invoice, so A's collected contribution is ₦3,000.

**E6 — Bonus examples.** These are the three examples in PRD-7.14: percentage of basis, fixed amount on threshold, and tiered marginal.

---

## 7.6 Financial Calculation Rules (new, AM2-S-02)

These rules are normative. The worked examples in §7.5 are their test fixtures.

### 7.6.1 Money representation 

- **Money** is stored as a whole number of **kobo** in a `BIGINT` column. For example, ₦20,000.00 is stored as `2000000`.
- **Floating-point numbers are never used for money**, in PHP, SQL or TypeScript.
- **Percentages and rates** are stored as whole-number **basis points**, where `10000` = 100.00%. For example, 7.5% is `750` and 33.33% is `3333`.
- **The API** sends amounts as integer kobo, for example `{ "amount": 2000000, "currency": "NGN" }`. The frontend only formats numbers for display. It never calculates authoritative values.
- **PHP** uses one immutable `Money` value object and one `Percentage` value object. All arithmetic goes through these.
- **Database checks** (`CHECK`) enforce that amounts are ≥ 0. Direction comes from the record type: refunds and reversals are separate rows with positive amounts.

### 7.6.2 Rounding

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

### 7.6.3 Invoice calculation

One pure domain service, `InvoiceCalculator`, performs every invoice calculation. Its input is the order's billable items, line discounts, the invoice discount and the tax setting. Its output is an immutable breakdown. The **same code** produces the draft preview, the finalisation snapshot and the amendment preview .

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

### 7.6.4 Contribution allocation

Allocation applies to each billed service line that has an active execution with contributors C (n contributors, primary p). The base is `base` = net_i, which is after discounts and before tax.

| Method | Shares (bp, stored) | Amounts |
|---|---|---|
| **Equal** | Each non-primary contributor: floor(10000/n). Primary: the remainder. | Each non-primary: round_div(base, n). Primary: base − Σ others. |
| **Primary/Assistant** (P bp) | Primary: P. Assistants: floor((10000−P)/(n−1)) each, with the remainder bp going to the primary. If n = 1, the primary gets 10000. | Each non-primary: round_div(base × bp_c, 10000). Primary: base − Σ others. |
| **Custom** | As entered. Must sum to 10000 and each be ≥ 1 (DR-008). | As for Primary/Assistant. |

**Participated value** for each contributor = base, the full line net value.

**Invariant I-5:** Σ allocated = base, for every line.

The results are written to the contribution ledger (DR-007).

### 7.6.5 Payments and balances

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

### 7.6.6 Cashier closing

Closing figures are calculated for one user U on one business day d:

| Method | Expected amount |
|---|---|
| Cash | Σ cash payments by U on d − Σ reversals by U on d of cash payments − Σ cash refunds by U on d |
| POS | The same calculation, for POS |
| Transfer | Σ transfer payments by U on d − Σ reversals by U on d of transfers, *excluding* reversals caused by rejecting a transfer (FR-CASH-002). Each transfer is listed with its verification status. |

The cashier declares cash (minus any opening float), POS (from the terminal's end-of-day slip) and confirms the transfer list.

**Discrepancy** = declared − expected, for each method and in total.

When the closing is submitted, the expected amounts are **frozen** as `CashierClosingLine` rows. The closing is then compared against those frozen figures.

### 7.6.7 Bonus calculation

- **Period boundaries.**
  - Weekly: Monday to Sunday, using business days.
  - Monthly: the calendar month.
  - A calculation may run only after the period has ended.
- **Which records count.** Ledger rows that are not superseded and whose business day (the invoice finalisation day) falls inside the period. The rule's filters (categories, employees, roles) are then applied.
- **Basis values:**
  - **Allocated:** Σ allocated.
  - **Collected:** Σ round_div(allocated_row × net_paid_inv, total_inv), with net_paid capped at the invoice total. If the total is 0, the result is 0. The ratio for each invoice is saved (FR-BON-021).
  - **Count:** the number of distinct executions (FR-BON-007).
- **Formulas** (amounts in kobo):
  - **(a) Percentage:** round_div(basis × bp, 10000). For the count basis, it is count × a fixed amount per service.
  - **(b) Threshold:** if basis ≥ T, the fixed amount; otherwise 0.
  - **(c) Tiered marginal:** Σ round_div(portion_in_tier × bp_tier, 10000).
  - **Eligibility thresholds:** if any threshold is not met, the bonus is 0.
- **Result.** Bonus = formula result + Σ unconsumed adjustments. If the result is negative, the payout is 0 and the remainder carries forward (FR-BON-020).
- **Saved with each calculation:**
  - the rule version identifier;
  - the period;
  - the basis totals;
  - the identifiers of the ledger rows used;
  - the saved per-invoice ratios;
  - the formula breakdown;
  - the adjustments consumed.

### 7.6.8 Historical preservation

| Fact | Where it is stored and kept |
|---|---|
| Price and name at time of sale | `order_items` snapshot columns, plus override columns |
| Billed lines, discounts and tax | `invoice_lines` and `invoice_revisions` (cannot be changed) |
| Tax setting applied | `invoice_revisions` (name, rate in bp, mode) |
| Contribution credit | `contribution_ledger` (rows are superseded, never updated) |
| Bonus inputs and rule | `bonus_calculations` with its inputs, linked to `bonus_rule_versions` |
| Closing expected amounts | `cashier_closing_lines`, frozen at submission |

---

## 7.7 Data Integrity and Concurrency (new, AM2-S-03)

### 7.7.1 Transaction boundaries

Each command below runs as one database transaction: it either succeeds completely or saves nothing. If MySQL detects a deadlock, the transaction is retried up to 3 times (`DB::transaction($fn, 3)`).

| Command | Locks, in this order | Writes inside the transaction | After the transaction commits |
|---|---|---|---|
| Create order | Daily sequence row | Order, items, assignments, audit | — |
| Add / cancel item; price override; discount | Order | Item or discount, audit, (refund) | Notifications |
| Submit / join / correct / cancel execution | Order | Execution, contributors, (ledger rows), (R7 marker), audit, (adjustments) | Notifications |
| Additional request / decision | Order | Item, (amendment), audit | Notifications |
| Finalise | Order → invoice sequence | Invoice, revision, lines, ledger, exception record, audit, idempotency record | PDF generated when requested (TD-019) |
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

### 7.7.2 Concurrency control

- **Order-level lock.** Every command that changes an order (all of §6.1–3.6) starts with `SELECT … FOR UPDATE` on that order's row. Every check (gate, balance, uniqueness) is then made *after* the lock is held. Two commands on the same order therefore run one after the other. Commands on different orders never wait for each other.
- **Stale screens.**
  - Finalise and amend compare the client's displayed total (FR-INV-005).
  - Configuration screens (catalogue, rules, settings) use an optimistic `version` column. A save made against an outdated version returns `409 STALE`.
- **Second submission for the same item.** The second request waits for the lock. It then finds the active execution and returns `409 EXECUTION_EXISTS` with a "join" option (FR-EXE-007). The unique key below is a database-level backstop.

### 7.7.3 Uniqueness and integrity constraints (database-enforced)

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

### 7.7.4 Idempotency

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

### 7.7.5 Immutability and deletion

| Category | Tables | Rule |
|---|---|---|
| **Append-only** | audit events, invoice revisions, invoice lines, payments, reversals, refunds, receipts, transfer verifications, contribution ledger (supersession flag only), bonus payments, bonus adjustments, closing lines, finalisation exceptions, R4 decisions, import results | No update or delete paths in code. Policies and tests assert this. |
| **Status-controlled** | orders, order items, executions, approval requests, closings, bonus calculations, invoices (current revision and void flag) | Only the status and status-metadata columns change, and only through §6 transitions. The content fields cannot change. |
| **Configuration** | users, roles, catalogue, settings, bonus rules | Deactivate instead of delete (`is_active` / `deactivated_at`). A rule edit creates a new version. |
| **Customer personal data** | customers | No delete. A data-protection removal request (SEC-021) **anonymises** the contact fields and name, and is audited. |

Laravel `SoftDeletes` is **not used**. Records are never "deleted but hidden"; they move through explicit states.

### 7.7.6 Audit generation

- **Who writes audit events.** Domain commands write them explicitly through an `AuditRecorder`, inside the command's transaction. Each event records: action, subject, before and after values, reason, actor, device and SoD-exemption flag. Model observers are not relied on, because they cannot capture intent or reason.
- **Denied attempts.** Authorisation failures are logged by the exception handler (`audit.denied`). Logging is throttled to 1 event per user, action and minute.
- **Tamper detection.** Each event stores `hash = sha256(previous_hash + canonical_payload)`. A scheduled job checks the chain and alerts the owner if it is broken. This detects direct database edits, for example through phpMyAdmin. **Priority: Should.**

### 7.7.7 Failure recovery

| Failure | What happens |
|---|---|
| Client times out or retries | Idempotency (§7.7.4) returns the original result. |
| Server error during a command | The transaction rolls back and nothing is saved. The client may retry with the same key. |
| PDF or email failure | Retried by the queue (3 attempts). The financial record is unaffected. The user can regenerate the document on demand. |
| Scheduled job missed (cron) | All jobs can safely run more than once (pruning, chain check, unverified-transfer alerts, notification cleanup). |
| Database loss | Restore from backup (NFR-REL-005). Quarterly restore drill. |

---

## 7.8 Additional Data Requirements (new, AM2-S-03)

**DR-001.** Money columns shall be `BIGINT` kobo, named `*_kobo`. Percentages and rates shall be integer basis points.

**DR-002.** Timestamps shall be stored in UTC. Each money event, finalisation and closing shall carry a stored `business_day` (DATE), computed in the business timezone.

**DR-003.** No derived totals (order totals, net paid, balances) shall be stored. Only inputs and snapshots are stored.

**DR-004.** `business_id` shall be on top-level tables. Child rows are scoped through their parent. Users are linked to a business through a membership.

**DR-005.** Primary keys shall be `BIGINT` auto-increment. Shared documents use random tokens of at least 128 bits.

**DR-006.** Transfer verification shall be stored in a separate append-only table, so payments remain append-only.

**DR-007.** Staff credit shall be stored in a **contribution ledger**. Each row records the invoice revision, invoice line, execution, employee, share (bp), allocated kobo, participated kobo, business day and `superseded_at`. New rows are written when (a) a revision is created, or (b) the active execution or allocation of a billed line changes. Earlier rows are superseded, never edited. Reports read only rows that have not been superseded.

**DR-008.** The primary contributor shall hold at least 1 bp. Under Custom percentages, no contributor may hold 0 bp.

**DR-009.** Bonus rule versions shall store their definition as validated JSON with a `schema_version`. Calculations reference a version.

**DR-010.** Idempotency keys shall be stored in the same transaction as the operation, and kept for 7 days.

**DR-011.** Exceptions (R7, R8, R9, refunds, reversals, over-limit discounts, transfer rejections) shall support a review flag, with reviewer, time and note. The flag never changes business data.

---

# 8. Interface Requirements

## 8.1 General

**UI-001.** The interface shall be responsive on mobile (from 360 px wide), tablet and desktop.

**UI-002.** It shall be an installable PWA.

**UI-003.** Navigation and actions shall reflect the user's permissions. This is a convenience only; enforcement is on the backend.

**UI-004.** Forms shall give clear validation feedback.

**UI-005.** Financial actions shall show a confirmation step and the outcome.

**UI-006 (rev).** The interface shall visually distinguish the following:

- item states: Requested, Pending Approval, Approved, Rejected, Cancelled;
- execution states: Not executed, Executed, Disputed;
- blocking discrepancies versus informational ones;
- draft invoices versus finalised ones;
- transfers: Verified versus Unverified.

**UI-007 (new).** Values that are not revenue (participated value, and provisional figures) shall be visibly labelled as such.

## 8.2 Owner / Administrator Interface

The owner and administrator interface shall provide:

- dashboard and exceptions panel;
- customers;
- catalogue, including variants, products and allocation defaults;
- users, roles, permissions and caps;
- orders, reconciliation and R4 decisions;
- invoices, amendments and voids;
- approvals queue;
- payments, transfer verification, refunds and reversals;
- closings;
- performance;
- bonus rules, calculation, approval and payment;
- audit;
- settings;
- imports.

## 8.3 Front Desk / Cashier Interface

The front desk interface shall prioritise:

- customer search and registration;
- guest order creation;
- adding items and variants;
- assignment;
- live progress for each item, with blocking discrepancies;
- cancelling unexecuted items;
- discounts and requesting approval for them;
- the draft invoice;
- finalisation;
- payments;
- receipts and sharing;
- the user's own closing.

## 8.4 Employee Interface

**UI-010 (new).** The employee interface shall be mobile-first. From launching the app, an assigned employee shall be able to submit an execution in **three taps or fewer**: open the order, select the item, submit. Adding a collaborator shall need one more step.

It shall show:

- today's open orders, with the user's own assignments first;
- submit, join or dispute;
- requesting an additional service;
- the user's submission history;
- the user's own performance;
- notifications.

It shall never show prices, customer contact details, invoice data or other employees' performance (BR-041).

---

# 9. API Requirements

The backend shall expose versioned REST endpoints under `/api/v1`.

The API shall:

- authenticate protected requests;
- enforce permissions;
- validate input;
- apply business rules;
- return consistent response envelopes;
- use correct HTTP status codes;
- paginate and filter collections;
- not expose internal details.

**Idempotency.** The following operations shall accept an idempotency key supplied by the client, and shall not process the same key twice:

- execution submit;
- payment record;
- reversal;
- refund;
- finalisation;
- amendment;
- bonus payment;
- closing submit.

**Concurrency.** Conflicting operations on the same order, invoice or closing shall be serialised. A stale update shall be rejected with a conflict error. Two finalisations, or two submissions for the same item, must never both succeed.

**Field-level restriction.** Data hidden by BR-041 shall not be sent to clients that lack the permission. Hiding it only in the frontend is not sufficient.

**Conflict and error codes (new, v1.2).**

| Code | Meaning |
|---|---|
| `409 TOTAL_CHANGED` | The displayed total no longer matches the server's total |
| `409 EXECUTION_EXISTS` | The item already has an active execution; the response offers "join" |
| `409 STALE` | A configuration record was changed since it was loaded |
| `409 CLOSING_SUBMITTED` | The user's closing for today is already submitted |
| `409 INVALID_TRANSITION` | The record is not in a state that allows this action |
| `422 IDEMPOTENCY_MISMATCH` | The idempotency key was reused with a different payload |
| `422 GATE_BLOCKED` | Finalisation was refused; the response lists the R1–R4 discrepancies |
| `403 SOD_VIOLATION` | The action breaks segregation of duties |

Idempotency keys are also required for transfer verification. The exact envelope and routes are defined in Stage 3.

The logical API areas are as in v1.0, plus:

- Approvals;
- Transfer verification;
- Notifications;
- Shared document links.

---

# 10. Security Requirements

**SEC-001.** All protected functions shall require authentication.

**SEC-002.** Permissions shall be enforced on the backend for every protected operation.

**SEC-003.** Access shall be limited to the user's business scope.

**SEC-004.** API responses shall not expose data the user is not authorised to see (BR-041).

**SEC-005.** Passwords and PINs shall be stored with a modern adaptive hash (bcrypt or argon2).

**SEC-006.** HTTPS is required in production. HSTS shall be enabled.

**SEC-007.** All untrusted input shall be validated and sanitised.

**SEC-008.** The application shall protect against the OWASP Top 10, including CSRF protection for cookie-based authentication.

**SEC-009.** Sensitive financial actions shall be audited.

**SEC-010.** Confirmed and finalised records shall never be silently altered.

**SEC-011.** Production and development environments, data and secrets shall be separated.

**SEC-012.** Backups shall be taken automatically and kept off the server. Restore shall be tested every quarter (NFR-REL-005).

**SEC-013.** The service worker shall cache only static application assets. It shall never cache API responses that contain customer, payment or staff data.

**SEC-014.** Session cookies shall be Secure, HttpOnly and SameSite, as appropriate to the deployment topology (TD-001).

**SEC-015.** Privileged operations shall be protected against unauthorised and duplicate execution (§9).

**SEC-016 (new).** Denied attempts at privileged actions shall be audited.

**SEC-017 (new).** After 5 failed logins within 15 minutes, the account or IP shall be locked for 15 minutes. After 5 failed PIN attempts, PIN use on that device shall be locked until a supervisor unlocks it.

**SEC-018 (new).** Idle timeouts, configurable:

- 30 minutes for users with any financial permission;
- 7 days for employee-only users on personal devices;
- 2 minutes for the identity switch on shared devices.

**SEC-019 (new).** Passwords shall be at least 8 characters. PINs shall be 4–6 digits, and trivial sequences shall be rejected.

**SEC-020 (new).** Audit records and financial records shall be kept for at least 7 years.

**SEC-021 (new).** Customer personal data, under the Nigeria Data Protection Act 2023:

- it is used only to provide services and receipts;
- contact details are visible only to users with `customer.view_contact`;
- a customer's contact details shall be removable on request. Financial records are kept, with the contact details removed.

**SEC-023 (new, v1.2).** Authentication shall use Laravel Sanctum SPA cookie sessions. The frontend and API shall be served from the same parent domain. Session cookies shall be Secure, HttpOnly and SameSite=Lax, with CSRF protection through `XSRF-TOKEN`. Bearer tokens shall not be kept in browser storage. Role-based idle timeouts (SEC-018) shall be enforced by server middleware.

**SEC-024 (new, v1.2, Should).** Audit events shall carry a hash chain. A scheduled job shall check it and alert the owner if it is broken.

**SEC-022 (new).** Shared document links shall:

- use unguessable tokens of at least 128 bits;
- expire after the configured period (default 7 days);
- be revocable;
- expose only that single document.

---

# 11. Non-Functional Requirements (AM-S-15)

## 11.1 Performance

**NFR-PERF-001 (rev).** Operational API endpoints shall have a p95 response time under 500 ms, at up to 30 concurrent users, on the target hosting.

**NFR-PERF-002.** Financial calculations shall be done on the backend, within the latency in NFR-PERF-001.

**NFR-PERF-003 (rev).** Reports covering up to 12 months of data shall return in under 3 seconds.

**NFR-PERF-004.** Imports of up to 5,000 rows, and PDF generation, shall not block other users.

**NFR-PERF-005 (new).** On a mid-range Android phone over 4G, the employee screens shall become interactive in under 3 seconds when loaded from cache, and under 5 seconds on first load.

## 11.2 Reliability

**NFR-REL-001.** Database transactions shall be used for multi-record operations.

**NFR-REL-002.** A failure shall leave no partial financial or service records.

**NFR-REL-003.** Backup and recovery procedures shall be documented.

**NFR-REL-004.** Duplicate submissions shall be prevented (§9).

**NFR-REL-005 (new).** Backups:

- An automated database backup at least daily.
- Backups kept off the server for 30 days.
- Maximum data loss (RPO) ≤ 24 hours.
- Recovery (RTO) within 4 hours.
- A restore drill every quarter.

## 11.3 Usability

**NFR-USE-001.** Staff workflows shall be easy to use on mobile.

**NFR-USE-002 (rev).** UI-010: an execution can be submitted in three taps or fewer.

**NFR-USE-003.** Statuses and validation shall be clear.

**NFR-USE-004.** Total, paid and balance shall be clearly distinguished.

**NFR-USE-005 (new).** The interface language shall be English, with naira formatting as ₦1,234,567.89.

## 11.4 Maintainability

**NFR-MNT-001.** The backend shall be modular.

**NFR-MNT-002.** Business rules shall be centralised in backend domain or application services.

**NFR-MNT-003.** All schema changes shall be made through migrations.

**NFR-MNT-004 (rev).** Automated tests shall cover:

- the §7.5 invariants;
- the worked examples E1–E6;
- every transition in §6;
- the permission matrix in §3.3;
- the §14 scenarios.

**NFR-MNT-005.** Setup and deployment shall be documented.

**NFR-MNT-006.** Source code shall be kept in GitHub.

## 11.5 Portability

**NFR-PORT-001.** The application shall run on suitable cPanel hosting.

**NFR-PORT-002.** The application shall run on a VPS.

**NFR-PORT-003.** Moving between cPanel and a VPS shall not require changes to business logic.

**NFR-PORT-004.** Docker shall be optional.

**NFR-PORT-005 (new, v1.2).** The database shall be MySQL 8.0.16+ or MariaDB 10.6+. Integration and concurrency tests shall run on the same engine as production.

**NFR-PORT-006 (new, v1.2).** The frontend shall be deployable as a static export, needing no Node.js runtime in production. Moving it to a Node host (a VPS or Vercel) shall need configuration changes only.

## 11.6 PWA

**NFR-PWA-001.** The application shall have a valid web app manifest.

**NFR-PWA-002.** It shall be installable where the browser supports it.

**NFR-PWA-003.** It shall provide a responsive mobile experience.

**NFR-PWA-004.** Static assets only shall be cached (SEC-013).

**NFR-PWA-005.** Sensitive transactions shall require a connection. While offline, the app shall show an offline state and block submission.

**NFR-PWA-006.** Private data shall not be stored offline.

**NFR-PWA-007 (new).** When a new version is deployed, the app shall prompt the user to refresh. It shall never interrupt a form that is being filled in.

## 11.7 Compatibility (new)

**NFR-COMP-001.** Supported browsers:

- Chrome for Android: the latest 2 major versions.
- Safari on iOS 16.4 and later.
- Desktop Chrome, Edge and Safari: the latest 2 major versions.

---

# 12. Integration Requirements

**INT-001.** No online payment gateway is used in the MVP. Payments are recorded manually.

**INT-002 (new).** Email shall be sent through a configurable SMTP account, such as the hosting provider's mail server.

**INT-003 (new).** WhatsApp sharing uses device share and `wa.me` links. No WhatsApp Business API is used in the MVP.

**INT-004.** Future integrations (payment gateways, WhatsApp Business API, accounting systems) must be possible without changing core domain logic.

---

# 13. Validation and Error Handling

The backend shall validate:

- required fields;
- permissions;
- record states and transitions (§6);
- item availability and type;
- employee eligibility (active users only);
- contributor uniqueness;
- percentage totals;
- monetary ranges (≥ 0, discount and balance limits);
- discount caps;
- tax settings;
- payment amount ≤ balance;
- refund ≤ net paid;
- one reversal per payment;
- the late window;
- the finalisation gate;
- segregation of duties;
- bonus transitions;
- import data.

Frontend validation is for convenience only.

Error messages shall be clear to users and shall never reveal internal details. Validation errors shall identify the field and the rule that failed.

---

# 14. Acceptance and Verification Scenarios (AM-S-16)

## 14.1 Collaborative Service

1. Three employees work on a ₦20,000 service.
2. The customer is charged ₦20,000 once.
3. All three employees are recorded as contributors.
4. The allocation follows E1.
5. Revenue is ₦20,000.

## 14.2 Confirmed Service Protection

1. An execution is Confirmed on submission.
2. A cashier cannot remove or cancel it, either through the UI or directly through the API.
3. A correction by a supervisor supersedes the original. The original stays viewable.
4. Every step is audited.

## 14.3 Additional Service Approval

1. A request creates a Pending Approval item.
2. The item is not billable, and finalisation is blocked (R3).
3. The approver is recorded, and BR-040 applies.
4. After approval, the item is billable.

## 14.4 Reconciliation and Gate

1. An order has 3 items. 2 are executed and 1 is not.
2. The system shows R1 for the unexecuted item.
3. Ordinary finalisation is refused.
4. A cashier without exception permission cannot finalise.
5. A supervisor finalises with an exception and a reason. All 3 items are billed.
6. R9 appears on the exceptions panel.

## 14.5 Discounts and Taxes

1. With 7.5% exclusive tax, the result matches E3.
2. With 7.5% inclusive tax, the result matches E4.
3. A cashier capped at 10% applies 10%, which is accepted.
4. The cashier requests 20%. An approval request is created, and the cashier cannot approve it themselves.
5. A supervisor approves it, and the totals are recalculated.
6. After the tax rate is changed, the earlier invoice still shows 7.5%.

## 14.6 Partial Payments

1. The invoice is ₦30,000. A ₦10,000 payment is recorded, leaving a balance of ₦20,000. A receipt is issued for ₦10,000.
2. A ₦20,000 payment is recorded. The balance is ₦0 and the status is Paid.
3. A ₦1 payment is then refused.

## 14.7 Refund and Reversal

1. A cashier cannot reverse or refund a payment.
2. A supervisor reverses a cash payment, giving a reason. The original payment stays visible, and its receipt shows "Reversed".
3. The balance is restored.
4. The reversal appears in the supervisor's closing for that day.
5. A partial refund of ₦5,000 on a fully paid ₦30,000 invoice gives a balance of ₦5,000. Refunds above the net paid are refused.

## 14.8 Cashier Closing

1. A cashier records cash ₦50,000, POS ₦30,000 and transfers ₦20,000.
2. The cashier declares cash ₦48,000, so the cash discrepancy is −₦2,000. A note is required.
3. The closing is submitted, after which it is read-only.
4. A supervisor approves it.
5. Only the owner can reopen it.

## 14.9 Bonus Lifecycle

1. A rule of 5% of allocated contribution is applied to a month with ₦400,000. The bonus is Calculated at ₦20,000.
2. The bonus is Approved by the owner.
3. The bonus is Paid.
4. A Calculated bonus is voided, giving a reason.
5. Recalculating a period voids the Calculated bonuses and creates new ones.

## 14.10 Permissions

1. An employee calls the finalise endpoint and is denied.
2. The invoice is unchanged.
3. The denial is audited (SEC-016).

## 14.11 End-to-end Happy Path

1. A guest order is created with braids (₦20,000) and a pedicure (₦10,000).
2. Employees A and B submit the braids execution together. Employee C submits the pedicure.
3. The order is finalised.
4. ₦20,000 is paid in cash and ₦10,000 by POS.
5. Receipts are shared by WhatsApp and email.
6. The closing is submitted and approved.
7. The audit timeline shows every step.
8. Staff reports sum to ₦30,000 of allocated contribution.

## 14.12 Late Execution

1. An item is cancelled before execution, and the order is finalised.
2. An employee then submits an execution for that item within the window.
3. The submission is accepted.
4. R7 appears as high priority.
5. The invoice is unchanged.

## 14.13 Late Window

1. An employee submits 49 hours after finalisation and is refused.
2. A supervisor with `execution.submit_late` submits and is accepted.

## 14.14 Concurrent Submission

1. Two employees submit the same item at the same time.
2. Exactly one execution is created.
3. The other employee receives the "join" response.

## 14.15 Concurrent Finalisation

1. Two users finalise the same order at the same time.
2. Exactly one invoice and one invoice number are created.
3. The other request is refused with a conflict error.

## 14.16 Idempotent Payment

1. The same payment request is sent twice with the same idempotency key.
2. One payment and one receipt are created.

## 14.17 Unverified and Rejected Transfer

1. A transfer is recorded and shows Unverified.
2. After 2 days it appears on the dashboard.
3. The owner rejects it.
4. An automatic reversal is created and the balance is restored.

## 14.18 Historical Pricing

1. A catalogue price changes from ₦20,000 to ₦25,000 after an order is created.
2. The order and its invoice keep ₦20,000.
3. A new order uses ₦25,000.

## 14.19 Price Override

1. A cashier cannot override a price.
2. A supervisor overrides a price, giving a reason.
3. R8 appears on the exceptions panel.

## 14.20 Amendment Below Paid

1. The invoice is ₦30,000 and fully paid.
2. An amendment cancels the ₦10,000 item through a correction.
3. The revision cannot be saved without a ₦10,000 refund.
4. When saved with the refund, revision 1 stays unchanged and revision 2 is ₦20,000.

## 14.21 Bonus Adjustment

1. A June bonus is Paid.
2. A correction to a June execution reduces A's allocation by ₦2,000.
3. At 5%, this creates an adjustment of −₦100.
4. The adjustment is applied in July's calculation.

## 14.22 Disabled User

1. An employee is disabled.
2. They cannot log in.
3. Their past executions and contributions still appear in reports.
4. They cannot be added as a contributor.

## 14.23 Employee Data Restriction

1. An employee fetches an open order through the API.
2. The response contains no prices, no customer phone and no invoice data.

## 14.24 Segregation of Duties

1. A supervisor requests an over-limit discount and then tries to approve it. The approval is refused.
2. The owner approves the request. This is allowed, and the exemption is flagged in the audit.

## 14.25 Part Payment Before Finalisation

1. An Open order has a draft total of ₦30,000. The cashier records a ₦10,000 cash part payment. The receipt is labelled "Part payment" and shows a balance of ₦20,000.
2. A ₦25,000 payment is then refused, because it exceeds the ₦20,000 balance.
3. A ₦5,000 additional item is approved. The draft total becomes ₦35,000 and the balance ₦25,000.
4. At finalisation, the invoice for ₦35,000 shows ₦10,000 paid and a balance of ₦25,000. No user action is needed to count the part payment.
5. On another order, the total is ₦10,000 and ₦10,000 has been paid. Cancelling a ₦4,000 item is refused unless a ₦4,000 refund is recorded in the same operation.
6. Cancelling an order that has ₦10,000 net paid is refused until the payment is refunded or reversed.
7. The part payment appears in the cashier's closing for the day it was received.

---

# 15. Assumptions and Constraints

**C-1.** The first release serves one salon.

**C-2.** The default currency is NGN, and the default timezone is Africa/Lagos.

**C-3.** Payments are recorded manually: cash, transfer and POS.

**C-4.** Submission confirms execution.

**C-5.** Additional services require approval.

**C-6.** Finalisation is controlled by permission and by the gate.

**C-7.** The system is online-first for sensitive transactions.

**C-8.** The application is a PWA.

**C-9.** cPanel is preferred. VPS deployment must be supported.

**C-10.** The backend is the source of truth.

**C-11.** No accounting, payroll or payment gateway is included in the MVP. The bonus module shall not be presented as payroll. This was FR-BON-016 in v1.0.

**C-12.** Future multi-business and branch capabilities must not complicate the MVP.

**C-13.** The pending assumptions A-01, A-02 and A-04 to A-08 (PRD 1.3 Appendix B) apply. A-03 was resolved on 2026-11-25: part payments before finalisation are supported.

---

# 16. Technical Decisions — Resolution Status (rev v1.2)

| ID | Decision | Resolution |
|---|---|---|
| TD-001 | Authentication strategy | Resolved: Sanctum SPA cookies (SEC-023) |
| TD-002 | Timezone handling | Resolved: UTC storage plus a stored business day (DR-002) |
| TD-005 | Money and rounding | Resolved: integer kobo and basis points, half-up rounding (§7.6) |
| TD-006 | Calculation implementation | Resolved: §7.6.3 |
| TD-010 | Bonus rule versioning | Resolved: JSON versions and adjustments (DR-009, FR-BON-020/021) |
| TD-014 | Hosting topology | Resolved: static frontend export plus Laravel on cPanel (NFR-PORT-006). Detailed in Stage 3. |
| TD-015 | Backup mechanics | Open: Stage 3 |
| TD-016 | Concurrency and idempotency | Resolved: §7.7 |
| TD-017 | Number sequences | Resolved: locked sequence rows, no gaps (§7.7) |
| TD-018 | Business scoping | Resolved: DR-004 |
| TD-019 | PDF and print | Resolved: dompdf for A4, browser print for 80 mm |
| TD-020 | Notification delivery | Resolved: database notifications with polling; Web Push is Should |

---

# 17. Requirements Traceability (AM-S-18)

| PRD ID | SRS requirements | Verification |
|---|---|---|
| PRD-1-2, 1-3 | BR-003, BR-005, BR-008, FR-INV-012 | §14.2, §14.11 |
| PRD-5-1 to 5-6 | §3.1–3.3, FR-IAM-002–009 | Permission matrix tests, §14.10 |
| PRD-5.1-1 to 5.1-3 | FR-EXE-001, BR-041, UI-010 | §14.23 |
| PRD-5.2-1 | BR-040, FR-ADD-010 | §14.24 |
| PRD-5.3-1, 5.3-2 | FR-IAM-002, FR-AUTH-007, SEC-017–019 | Automated tests, demo |
| PRD-6.3-1 | FR-BIZ-001–006 | Demo, automated tests |
| PRD-7.1-1 to 7.1-5 | FR-CUS-001–010 | Automated tests |
| PRD-7.2-1 to 7.2-5 | FR-SVC-001–009, FR-ORD-011 | §14.18, §14.19 |
| PRD-7.3-1 to 7.3-7 | FR-ORD-001–015, §6.1–6.2 | Transition tests |
| PRD-7.4-1 to 7.4-9 | FR-EXE-001–015, §6.3 | §14.2, §14.12–14.14 |
| PRD-7.5-1 to 7.5-8 | FR-ATTR-001–013, BR-009–013, §7.5 E1–E2 | §14.1, invariant tests |
| PRD-7.6-1 to 7.6-6 | FR-ADD-001–007, FR-EXE-015 | §14.3 |
| PRD-7.7-1, 7.7-2 | FR-REC-001–008 | §14.4 |
| PRD-7.7a-1 to 7.7a-5 | BR-030, BR-031, FR-REC-007, FR-INV-012, FR-INV-014 | §14.4, §14.12 |
| PRD-7.8-1 to 7.8-6 | FR-INV-001–015, BR-014–016, BR-032 | §14.15, §14.20 |
| PRD-7.9-1 to 7.9-2b | FR-PAY-001–015, FR-INV-016, FR-ORD-014, BR-017–019, BR-035 | §14.6, §14.16, §14.25 |
| PRD-7.9-3 | FR-PAY-013, BR-036, FR-DASH-012 | §14.17 |
| PRD-7.9-4, 7.9-5 | FR-RCT-001–010, FR-INV-010/011, INT-002/003, SEC-022 | Demo, automated tests |
| PRD-7.10-1 to 7.10-5 | FR-DIS-001–008, FR-ADD-008, BR-033/034 | §14.5, E3 |
| PRD-7.10-6 to 7.10-8 | FR-TAX-001–008 | §14.5, E3/E4 |
| PRD-7.11-1 to 7.11-3 | FR-REF-001–009, BR-020 | §14.7 |
| PRD-7.12-1 to 7.12-4 | FR-CASH-001–011, BR-025/026, §6.7 | §14.8 |
| PRD-7.13-1, 7.13-2 | FR-PERF-001–012 | Report tests, E1/E5 |
| PRD-7.14-1 to 7.14-7 | FR-BON-001–020, BR-021–024, §6.8, C-11 | §14.9, §14.21, E6 |
| PRD-7.15-1, 7.15-2 | FR-DASH-002, BR-050, UI-007 | Report tests |
| PRD-9-1 to 9-4 | FR-AUD-001–010, SEC-001–016 | Audit tests (every PRD-9-2 action creates an event) |
| PRD-9-5 | SEC-021 | Inspection |
| PRD-10-1 to 10-6 | §2.1, NFR-PORT, NFR-PWA, NFR-MNT, SEC-012/013, NFR-REL-005, NFR-COMP-001 | Inspection, restore drill, demo |
| PRD-11-1 to 11-3 | FR-IMP-001–008 | Tests with sample import files |
| PRD-6.1 #25 | FR-NOT-001–004 | Automated tests |
| PRD-12 | §14.1–14.24 | End-to-end tests |

---

# 18. Document Governance

This SRS is subordinate to PRD v1.3. If a conflict is found:

1. Identify the conflicting requirements.
2. Record the conflict and its impact.
3. Recommend a resolution.
4. Obtain product-owner approval if the resolution changes product behaviour.
5. Update the affected documents and their versions.
6. Keep traceability up to date.

No implementation phase may silently reinterpret an approved product decision.

**End of SRS v1.2**
