# Salon Operations & Accountability Platform
## Software Requirements Specification (SRS)

**Version:** 1.0  
**Status:** Initial Draft  
**Parent document:** Product Requirements Document v1.2  
**Initial deployment:** One salon  
**Primary currency:** NGN (₦)

---

# 1. Introduction

## 1.1 Purpose

This document defines the functional and non-functional requirements for the Salon Operations & Accountability Platform.

It specifies the expected system behaviour, data management rules, user interactions, permissions, workflows, validations and technical constraints necessary to implement the product described in PRD v1.2.

It is intended for product owners, software architects, developers, testers and deployment engineers.

## 1.2 Scope

The system will manage salon customers, service orders, service execution, staff contributions, service reconciliation, invoices, payments, receipts, cashier closing, staff performance, bonuses and audit records.

The initial release will support one salon while maintaining a modular foundation for future business types and branches.

## 1.3 Definitions

| Term | Definition |
|---|---|
| Customer | A person receiving salon services |
| Guest transaction | A transaction created without requiring a permanent customer profile |
| Service catalogue | Configured services and default prices |
| Service order | Record of services requested by a customer |
| Service item | An individual requested or approved service within an order |
| Service execution | Record of a service actually performed |
| Contributor | An employee who participated in a service |
| Allocation | The distribution of contribution credit among participants |
| Reconciliation | Comparison of requested, approved, performed and cancelled services |
| Invoice | Final amount payable for authorised services and adjustments |
| Payment | Money recorded as received from a customer |
| Receipt | Evidence of a recorded payment |
| Bonus | Incentive calculated using configured performance rules |
| Cashier closing | Reconciliation of recorded collections against declared collections |
| Audit event | A traceable record of an action affecting business data |

## 1.4 Requirement Classification

Requirements are assigned identifiers for traceability.

- FR: Functional Requirement.
- BR: Business Rule.
- NFR: Non-Functional Requirement.
- DR: Data Requirement.
- SEC: Security Requirement.
- INT: Integration Requirement.
- UI: User Interface Requirement.

Priority levels:

- Must: Required for MVP acceptance.
- Should: Important but may be scheduled after core functionality.
- Could: Optional enhancement.

---

# 2. System Overview

## 2.1 System Architecture Direction

The application shall use a modular monolithic architecture.

Preferred technology direction:

- Backend: Laravel / PHP.
- Frontend: Next.js / TypeScript.
- Database: MySQL.
- Application interface: REST API.
- Client experience: Responsive Progressive Web App.
- Source control: GitHub.

The backend shall own business rules, validation, permission enforcement, transaction integrity and financial calculations.

The frontend shall communicate with the backend through documented APIs. It shall not be trusted to enforce security or calculate authoritative financial values.

The frontend and backend should be independently deployable.

The application shall support deployment on suitable cPanel hosting and VPS environments. Docker support may be provided optionally.

## 2.2 System Components

The application shall be organised into the following logical modules:

1. Identity and access management.
2. Business configuration.
3. Customer management.
4. Service catalogue.
5. Service order management.
6. Service execution.
7. Staff contribution and allocation.
8. Additional-service approval.
9. Service reconciliation.
10. Invoice management.
11. Discount and tax management.
12. Payment management.
13. Receipt management.
14. Refund and reversal management.
15. Cashier closing.
16. Staff performance.
17. Bonus management.
18. Dashboard and reporting.
19. Audit management.
20. Data import.

Modules may share application services and database infrastructure. They should maintain clear responsibility boundaries.

---

# 3. User Classes and Access Requirements

## 3.1 Business Owner

The owner shall be able to:

- View business dashboards and reports.
- Manage users and roles.
- Configure permissions.
- Manage services and prices.
- Configure taxes and discounts.
- Configure bonus rules.
- Review and approve bonuses.
- View all transactions.
- Authorise permitted corrections and refunds.
- Review cashier closing and discrepancies.
- View audit history.
- Manage business settings.

## 3.2 Administrator / Supervisor

The administrator or supervisor shall be able to perform operational activities according to assigned permissions.

Potential permissions include:

- Customer management.
- Service order management.
- Additional-service approval.
- Service correction approval.
- Invoice finalisation.
- Discount approval.
- Refund approval.
- Cashier closing review.
- Performance reporting.

These permissions must be independently configurable.

## 3.3 Front Desk / Cashier

The front desk/cashier shall be able to:

- Create customer and guest transactions.
- Select requested services.
- Assign employees where appropriate.
- View transaction service status.
- Prepare invoices.
- Finalise invoices where permitted.
- Record payments.
- Generate receipts.
- Perform cashier closing where permitted.

## 3.4 Employee

An employee shall be able to:

- Authenticate using an individual account.
- View accessible active transactions and assignments.
- Submit services performed.
- Identify collaborators.
- Submit additional-service requests.
- View permitted personal performance information.
- View the status of submitted records.

Employees shall not receive unrestricted access to financial information or other employees' performance information.

## 3.5 Permission Model

The system shall use permission-based authorisation rather than relying only on role names.

**FR-IAM-001:** The system shall authenticate users before granting access to protected functions.

**FR-IAM-002:** Every user shall have an individual identity.

**FR-IAM-003:** The system shall support configurable roles and permissions.

**FR-IAM-004:** A user's permissions shall be checked on the backend for every protected operation.

**FR-IAM-005:** The system shall support enabling and disabling user accounts.

**FR-IAM-006:** Permission changes shall be auditable.

**FR-IAM-007:** Users shall only access business data within their authorised business scope.

---

# 4. Functional Requirements

## 4.1 Authentication and User Management

| ID | Requirement | Priority |
|---|---|---|
| FR-AUTH-001 | Users shall be able to authenticate using their configured credentials. | Must |
| FR-AUTH-002 | The system shall provide authenticated session management. | Must |
| FR-AUTH-003 | Authorised users shall create and manage employee accounts. | Must |
| FR-AUTH-004 | Authorised users shall assign roles and permissions. | Must |
| FR-AUTH-005 | Disabled users shall not be allowed to authenticate. | Must |
| FR-AUTH-006 | The system shall record relevant authentication and account-security events. | Should |

## 4.2 Business Settings

**FR-BIZ-001:** The system shall maintain the initial salon's business profile.

**FR-BIZ-002:** The system shall support configurable business name, contact details, address and receipt information.

**FR-BIZ-003:** The system shall support NGN as the initial default currency.

**FR-BIZ-004:** The system shall support configurable invoice and receipt numbering formats.

**FR-BIZ-005:** The system shall support configurable business settings without requiring source-code changes for ordinary configuration.

**FR-BIZ-006:** Changes to important financial settings shall be auditable.

## 4.3 Customer Management

**FR-CUS-001:** Authorised users shall create customer profiles.

**FR-CUS-002:** Customer records shall support name and contact details.

**FR-CUS-003:** The system shall support guest transactions without requiring a registered customer.

**FR-CUS-004:** A guest transaction shall have a unique transaction reference.

**FR-CUS-005:** Guest name and phone number shall be optional according to the approved guest transaction policy.

**FR-CUS-006:** An authorised user shall be able to associate a guest transaction with an existing customer.

**FR-CUS-007:** The system shall preserve an audit record when a guest transaction is associated with a customer.

**FR-CUS-008:** The system shall support customer search using available identifying information.

**FR-CUS-009:** The system shall prevent accidental duplicate customer records where possible and provide a controlled duplicate-resolution process.

## 4.4 Service Catalogue

**FR-SVC-001:** Authorised users shall create service categories.

**FR-SVC-002:** Authorised users shall create and manage services.

**FR-SVC-003:** Each service shall have a name, configured price and active status.

**FR-SVC-004:** Services may have descriptions and category associations.

**FR-SVC-005:** Inactive services shall not be available for new service orders.

**FR-SVC-006:** Existing transactions shall preserve the service name and price applicable when the service was added.

**FR-SVC-007:** Changing a service catalogue price shall not silently modify existing transactions.

**FR-SVC-008:** The system shall validate service prices and prevent invalid negative monetary values unless a separately authorised adjustment mechanism explicitly permits them.

## 4.5 Service Order Management

**FR-ORD-001:** Authorised users shall create a service order for a customer or guest.

**FR-ORD-002:** A service order shall contain one or more service items.

**FR-ORD-003:** Each service item shall reference the selected catalogue service and preserve a snapshot of its name and price.

**FR-ORD-004:** The system shall support quantity where the service configuration permits it.

**FR-ORD-005:** Each service item may be assigned to one or more expected employees.

**FR-ORD-006:** A service item may remain unassigned.

**FR-ORD-007:** The system shall calculate the initial requested service value using backend calculations.

**FR-ORD-008:** The system shall provide a unique service order reference.

**FR-ORD-009:** The system shall maintain service order status and transaction history.

**FR-ORD-010:** The system shall prevent unauthorised changes to locked or finalised transactions.

## 4.6 Service Execution

**FR-EXE-001:** An authenticated employee shall be able to access eligible active transactions.

**FR-EXE-002:** An employee shall be able to submit a record of a service actually performed.

**FR-EXE-003:** An employee shall be able to identify the relevant service order and service item.

**FR-EXE-004:** Where permitted, an employee shall be able to submit an execution record even if they were not originally assigned to that service.

**FR-EXE-005:** Successful submission shall automatically confirm the service execution.

**FR-EXE-006:** The system shall record the submitting employee and submission timestamp.

**FR-EXE-007:** The system shall prevent duplicate execution submissions from unintentionally creating duplicate customer charges.

**FR-EXE-008:** The system shall maintain the relationship between execution records and the original service order.

**FR-EXE-009:** The system shall support authorised disputes and corrections of confirmed execution records.

**FR-EXE-010:** Corrections shall preserve the original record and create an auditable correction history.

**FR-EXE-011:** The system should notify the original submitting employee when a correction or dispute affects their submission, subject to configured notification capability.

**FR-EXE-012:** Confirmed execution records shall not be silently deleted or overwritten.

## 4.7 Collaborative Staff Attribution

**FR-ATTR-001:** A service execution shall support one or more employee contributors.

**FR-ATTR-002:** The system shall prevent duplicate contributor entries for the same execution unless an explicitly defined business rule permits them.

**FR-ATTR-003:** The system shall identify a primary contributor where applicable.

**FR-ATTR-004:** The system shall support equal allocation.

**FR-ATTR-005:** The system shall support percentage allocation.

**FR-ATTR-006:** The system shall support weighted allocation.

**FR-ATTR-007:** The system shall support primary contributor and assistant allocation arrangements.

**FR-ATTR-008:** Percentage allocations shall total exactly 100% within the system's defined monetary precision rules.

**FR-ATTR-009:** Allocation changes shall be subject to permissions and audit requirements.

**FR-ATTR-010:** The customer charge for a service shall remain independent of the number of contributors.

**FR-ATTR-011:** Contribution values shall not be added together to inflate service revenue.

**FR-ATTR-012:** The system shall preserve the allocation rule and resulting contribution values used for historical performance and bonus calculations.

## 4.8 Additional-Service Requests

**FR-ADD-001:** An employee shall be able to request an additional service for an active transaction.

**FR-ADD-002:** An additional-service request shall identify the requested service, requesting employee and relevant transaction.

**FR-ADD-003:** The request shall remain pending until an authorised supervisor approves or rejects it.

**FR-ADD-004:** Only approved additional services shall become authorised service items.

**FR-ADD-005:** The system shall record the approving or rejecting user, decision and timestamp.

**FR-ADD-006:** A rejected request shall not be included in the final invoice.

**FR-ADD-007:** The system shall prevent execution of an additional service from silently bypassing its required approval.

## 4.9 Service Reconciliation

**FR-REC-001:** The system shall compare requested services against confirmed service executions.

**FR-REC-002:** The system shall identify requested services that have not been completed.

**FR-REC-003:** The system shall identify completed services not represented in the original order.

**FR-REC-004:** The system shall include approved additional services in reconciliation.

**FR-REC-005:** The system shall identify rejected, cancelled, disputed and corrected service records.

**FR-REC-006:** The system shall provide an understandable reconciliation view to authorised users.

**FR-REC-007:** The system shall prevent invoice finalisation where unresolved conditions violate the configured finalisation rules.

**FR-REC-008:** Reconciliation results shall be reproducible from retained transaction records.

## 4.10 Invoice Management

**FR-INV-001:** The system shall generate an invoice from authorised transaction service items.

**FR-INV-002:** Invoice finalisation shall be controlled by configurable permissions.

**FR-INV-003:** The system shall calculate invoice totals on the backend.

**FR-INV-004:** The invoice shall show applicable service items, quantities, prices, discounts, taxes and totals.

**FR-INV-005:** The system shall support invoice draft and finalised states.

**FR-INV-006:** A finalised invoice shall be locked against ordinary editing.

**FR-INV-007:** Changes to a finalised invoice shall require an authorised amendment procedure.

**FR-INV-008:** Invoice amendments shall preserve the original invoice state and record the actor, reason, timestamp and changes.

**FR-INV-009:** The system shall generate unique invoice numbers.

**FR-INV-010:** The system shall support invoice printing and PDF generation.

**FR-INV-011:** The system shall support downloading and sharing invoices through available device capabilities.

## 4.11 Discounts

**FR-DIS-001:** The system shall support fixed-amount discounts.

**FR-DIS-002:** The system shall support percentage discounts.

**FR-DIS-003:** Discount application shall be permission-controlled.

**FR-DIS-004:** The system shall support configurable discount limits per user or permission level.

**FR-DIS-005:** The system shall record the discount type, value, reason and applying user.

**FR-DIS-006:** The system shall prevent discounts from producing invalid invoice totals.

**FR-DIS-007:** Discounts shall be reflected transparently in invoice calculations.

## 4.12 Taxes

**FR-TAX-001:** Authorised users shall configure tax rates.

**FR-TAX-002:** The system shall support tax-inclusive pricing.

**FR-TAX-003:** The system shall support tax-exclusive pricing.

**FR-TAX-004:** The system shall support applicable tax exemptions.

**FR-TAX-005:** The system shall preserve the tax configuration and calculated tax values applied to historical invoices.

**FR-TAX-006:** Tax calculations shall be performed by the backend.

**FR-TAX-007:** The invoice shall clearly display applicable tax information.

## 4.13 Payment Management

**FR-PAY-001:** Authorised users shall record customer payments against invoices.

**FR-PAY-002:** The system shall support cash payments.

**FR-PAY-003:** The system shall support bank transfer payments.

**FR-PAY-004:** The system shall support POS payments.

**FR-PAY-005:** The system shall support partial payments.

**FR-PAY-006:** The system shall support full payments.

**FR-PAY-007:** The system shall calculate outstanding balances.

**FR-PAY-008:** The system shall prevent recorded payments from exceeding the permitted outstanding amount unless a separately authorised overpayment policy is introduced.

**FR-PAY-009:** Each payment shall have a unique reference.

**FR-PAY-010:** The system shall record the payment method, amount, date, recording user and relevant reference details.

**FR-PAY-011:** The system shall preserve payment history.

**FR-PAY-012:** The system shall distinguish invoice value, amount paid and outstanding balance.

## 4.14 Receipts

**FR-RCT-001:** The system shall generate a receipt for a recorded payment.

**FR-RCT-002:** A receipt shall reference the relevant invoice and payment.

**FR-RCT-003:** Receipts shall have unique receipt references.

**FR-RCT-004:** Receipts shall be printable and downloadable.

**FR-RCT-005:** Receipts shall be suitable for digital sharing.

**FR-RCT-006:** The system shall preserve historical receipt details.

**FR-RCT-007:** A receipt shall not be generated as proof of a payment that has not been recorded.

## 4.15 Refunds and Payment Reversals

**FR-REF-001:** The system shall support authorised refunds or payment reversals.

**FR-REF-002:** Refunds and reversals shall require appropriate permissions.

**FR-REF-003:** The user shall provide a reason for the action.

**FR-REF-004:** The system shall retain the original payment record.

**FR-REF-005:** The system shall record the refund or reversal amount, actor, reason and timestamp.

**FR-REF-006:** The system shall recalculate the relevant invoice payment position without erasing historical transactions.

**FR-REF-007:** The system shall prevent unauthorised or duplicate reversals.

## 4.16 Cashier Closing and Reconciliation

**FR-CASH-001:** The system shall support daily cashier closing.

**FR-CASH-002:** The system shall calculate expected collections by payment method for the selected closing period.

**FR-CASH-003:** The cashier or authorised user shall enter or confirm declared collection amounts.

**FR-CASH-004:** The system shall compare expected and declared cash collections.

**FR-CASH-005:** The system shall compare expected and declared POS collections.

**FR-CASH-006:** The system shall compare expected and declared bank transfer collections.

**FR-CASH-007:** The system shall calculate discrepancies.

**FR-CASH-008:** Discrepancies shall be visible to authorised management users.

**FR-CASH-009:** Closing records shall preserve the responsible cashier, period, amounts, discrepancies and status.

**FR-CASH-010:** A completed closing shall not be silently altered.

## 4.17 Staff Performance

**FR-PERF-001:** The system shall report services performed by employee.

**FR-PERF-002:** The system shall report service counts by employee.

**FR-PERF-003:** The system shall report contribution values by employee.

**FR-PERF-004:** The system shall report customers served where customer information is available.

**FR-PERF-005:** The system shall distinguish individual and collaborative contributions.

**FR-PERF-006:** The system shall support daily, weekly and monthly reporting.

**FR-PERF-007:** The system shall support other date-range filters.

**FR-PERF-008:** Employees shall see their own performance by default.

**FR-PERF-009:** Team performance visibility shall be permission-controlled.

**FR-PERF-010:** Performance reports shall not count collaborative service value as additional business revenue.

## 4.18 Bonus Management

**FR-BON-001:** Authorised users shall configure bonus rules.

**FR-BON-002:** Bonus rules shall support configurable calculation periods.

**FR-BON-003:** Bonus rules shall support completed service contribution as a calculation basis.

**FR-BON-004:** Bonus rules shall support collected revenue as a calculation basis.

**FR-BON-005:** Bonus rules shall support a combination of eligible criteria.

**FR-BON-006:** Bonus rules may support service counts and targets where configured.

**FR-BON-007:** The system shall calculate bonuses from eligible performance records.

**FR-BON-008:** The system shall retain calculation details and the rule version used.

**FR-BON-009:** Calculated bonuses shall have a Calculated state.

**FR-BON-010:** An authorised owner or manager shall approve calculated bonuses before payment.

**FR-BON-011:** Approved bonuses shall have an Approved state.

**FR-BON-012:** The system shall allow recording payment of an approved bonus.

**FR-BON-013:** Paid bonuses shall have a Paid state.

**FR-BON-014:** The system shall record approval and payment actors and timestamps.

**FR-BON-015:** Changes to bonus rules shall not silently rewrite previously approved or paid bonus records.

**FR-BON-016:** The system shall not represent bonus management as a full payroll system.

## 4.19 Dashboard and Reports

**FR-DASH-001:** The system shall provide an owner dashboard.

**FR-DASH-002:** The dashboard shall show revenue summaries for supported periods.

**FR-DASH-003:** The dashboard shall show payment and outstanding-balance summaries.

**FR-DASH-004:** The dashboard shall show service activity.

**FR-DASH-005:** The dashboard shall show service reconciliation discrepancies.

**FR-DASH-006:** The dashboard shall provide staff performance summaries.

**FR-DASH-007:** The dashboard shall provide bonus summaries.

**FR-DASH-008:** Dashboard data shall respect user permissions.

**FR-DASH-009:** Reports shall support appropriate date filtering.

**FR-DASH-010:** Report calculations shall be consistent with underlying transaction records.

## 4.20 Audit Trail

**FR-AUD-001:** The system shall record important business actions.

**FR-AUD-002:** Each audit event shall identify the actor.

**FR-AUD-003:** Each audit event shall identify the affected record.

**FR-AUD-004:** Each audit event shall record its timestamp.

**FR-AUD-005:** Where applicable, audit events shall preserve before-and-after values.

**FR-AUD-006:** Audit records shall not be editable through ordinary application functions.

**FR-AUD-007:** Authorised users shall be able to search and review audit history.

**FR-AUD-008:** Audit history shall support investigation of service, invoice, payment, refund, bonus and permission changes.

## 4.21 Data Import

**FR-IMP-001:** The system shall support importing supported CSV and Excel files.

**FR-IMP-002:** The MVP shall support customer and service catalogue imports.

**FR-IMP-003:** The system shall validate imported data before committing records.

**FR-IMP-004:** The system shall report invalid rows and validation errors.

**FR-IMP-005:** The system shall provide an import summary.

**FR-IMP-006:** The system shall avoid unintended duplicate records.

**FR-IMP-007:** Import operations shall be auditable.

---

# 5. Business Rules

## 5.1 Service and Revenue Rules

**BR-001:** A service is charged to the customer once, regardless of how many employees contribute.

**BR-002:** Staff contribution values are not additional customer charges.

**BR-003:** Requested services and performed services are separate records.

**BR-004:** Employee submission automatically confirms service execution.

**BR-005:** Confirmed service execution cannot be silently deleted or overwritten.

**BR-006:** Corrections to confirmed service records require authorisation and an audit record.

**BR-007:** Additional services require supervisor approval before becoming authorised service items.

**BR-008:** The final invoice must be based on authorised transaction services and adjustments.

## 5.2 Allocation Rules

**BR-009:** Every contribution allocation must be linked to a service execution.

**BR-010:** Percentage allocations must total 100%.

**BR-011:** A primary contributor must be identifiable where the allocation method requires one.

**BR-012:** Historical allocation values must remain reproducible.

## 5.3 Invoice Rules

**BR-013:** Invoice finalisation is permission-controlled.

**BR-014:** A finalised invoice is locked against ordinary editing.

**BR-015:** Amendments to finalised invoices require an authorised process.

**BR-016:** Financial calculations must be performed by the backend.

## 5.4 Payment Rules

**BR-017:** A receipt represents a recorded payment, not merely an invoice.

**BR-018:** Partial payments must be supported.

**BR-019:** Outstanding balances must be derived from invoice value and valid payment activity.

**BR-020:** Refunds and reversals must preserve the original payment history.

## 5.5 Bonus Rules

**BR-021:** Bonus calculations must use configured rules and eligible source records.

**BR-022:** Calculated bonuses are not payable until approved.

**BR-023:** Approved bonuses are not considered paid until payment is recorded.

**BR-024:** Bonus rule changes must not silently rewrite historical approved or paid bonuses.

## 5.6 Cashier Rules

**BR-025:** Cashier closing must compare expected and declared collections by payment method.

**BR-026:** Discrepancies must be retained and visible to authorised users.

---

# 6. Transaction Lifecycle and State Requirements

The system shall use explicit states and controlled transitions.

## 6.1 Service Order

Proposed states:

- Open.
- In Progress.
- Awaiting Reconciliation.
- Reconciled.
- Finalised.
- Cancelled.

The implementation shall define permitted transitions and the permissions required for each transition.

## 6.2 Service Execution

Proposed states:

- Confirmed.
- Disputed.
- Corrected.
- Cancelled through authorised action.

A normal employee submission shall create a Confirmed execution record.

A correction shall preserve the original submission and create a traceable history.

## 6.3 Additional-Service Request

States:

- Pending.
- Approved.
- Rejected.
- Cancelled where permitted.

Only Approved requests become authorised service items.

## 6.4 Invoice

States:

- Draft.
- Finalised.
- Amended through controlled procedure.
- Cancelled or voided through an authorised procedure where permitted.

## 6.5 Payment

Payment records shall distinguish valid recorded payments from reversed or refunded amounts.

The exact payment status model shall be finalised in the detailed design, but historical payment events must remain preserved.

## 6.6 Bonus

States:

- Calculated.
- Approved.
- Paid.

The system shall prevent payment recording for a bonus that has not been approved.

## 6.7 Cashier Closing

Proposed states:

- Open.
- Submitted.
- Reviewed.
- Closed.
- Discrepancy requiring resolution.

The implementation shall define whether a reviewed closing can be reopened and which permissions apply.

---

# 7. Data Requirements

## 7.1 General Data Principles

1. Use a relational database.
2. Maintain appropriate normalisation and referential integrity.
3. Use stable primary identifiers.
4. Use unique business references where required.
5. Preserve historical financial snapshots.
6. Use database transactions for operations that must succeed or fail together.
7. Avoid hardcoded business-configurable values.
8. Store timestamps consistently and present them using the configured business timezone.
9. Use appropriate monetary precision and explicit rounding rules.
10. Maintain audit history for important changes.

## 7.2 Proposed Core Entities

The following is a conceptual entity inventory, not a final physical database schema.

| Entity | Purpose |
|---|---|
| Business | Initial salon business record |
| BusinessSetting | Configurable business preferences |
| User | Authenticated application user |
| Role | Named access role |
| Permission | Individual authorised action |
| UserRole | User-role association |
| RolePermission | Role-permission association |
| EmployeeProfile | Employee-specific information |
| Customer | Registered customer profile |
| ServiceCategory | Service classification |
| Service | Configured service catalogue item |
| ServiceOrder | Customer's service transaction |
| ServiceOrderItem | Requested service item and price snapshot |
| ServiceAssignment | Expected employee assignments |
| ServiceExecution | Actual service performed |
| ServiceContributor | Employees who contributed to an execution |
| ContributionAllocation | Allocation rule and calculated contribution values |
| AdditionalServiceRequest | Employee request for an additional service |
| ServiceReconciliation | Reconciliation result and relevant status |
| Invoice | Final financial document |
| InvoiceItem | Final invoice line items and snapshots |
| Discount | Applied discount details |
| TaxConfiguration | Configured tax rules |
| InvoiceTax | Tax applied to an invoice |
| Payment | Recorded customer payment |
| PaymentReversal | Refund or reversal record |
| Receipt | Payment receipt |
| CashierClosing | Daily cashier closing record |
| CashierClosingLine | Declared and expected amounts by payment method |
| BonusRule | Configurable bonus calculation rule |
| BonusCalculation | Calculated bonus result |
| BonusApproval | Bonus approval record |
| BonusPayment | Recorded bonus payment |
| AuditEvent | Historical activity and change record |
| ImportBatch | Import operation metadata |
| ImportRowResult | Import row validation and processing result |

## 7.3 Important Data Relationships

- One business may have multiple users.
- One customer may have multiple service orders.
- One service order may have multiple service order items.
- One service order item may have one or more execution records where the business workflow permits it.
- One execution may have multiple contributors.
- One contributor may have one allocation record per applicable execution allocation.
- One service order may produce an invoice according to the finalisation rules.
- One invoice may have multiple invoice items.
- One invoice may have multiple payments.
- One payment may have a receipt.
- A payment may have controlled reversal records.
- One bonus rule may produce multiple bonus calculations over different periods.
- One bonus calculation may have an approval and one or more payment records according to the approved payout design.
- Important business records may have multiple audit events.

## 7.4 Historical Snapshots

The system shall preserve historical values where later configuration changes must not alter past transactions.

At minimum, this applies to:

- Service name and price on a transaction.
- Invoice item descriptions and prices.
- Applied discounts.
- Applied tax rates and amounts.
- Contribution allocation rules and resulting values.
- Bonus calculation rule version and calculation inputs.

## 7.5 Financial Data Integrity

Financial values shall use a consistent precision strategy.

The system shall define:

- Currency precision.
- Percentage precision.
- Rounding method.
- Calculation order for discounts and taxes.
- Allocation rounding and residual handling.

The authoritative calculation shall be performed on the backend.

The detailed design shall ensure that allocated contribution amounts reconcile to the amount being allocated, without creating or destroying value through rounding.

---

# 8. Interface Requirements

## 8.1 General Interface

**UI-001:** The application shall be responsive across mobile, tablet and desktop form factors.

**UI-002:** The application shall provide a PWA experience.

**UI-003:** Navigation and available actions shall reflect the authenticated user's permissions.

**UI-004:** Forms shall provide clear validation feedback.

**UI-005:** Important financial and operational actions shall display confirmation and outcome information.

**UI-006:** The interface shall clearly distinguish requested, performed, pending, approved, disputed and finalised records.

## 8.2 Owner / Administrator Interface

The interface shall provide:

- Dashboard.
- Customer management.
- Service catalogue management.
- User and permission management.
- Service orders and reconciliation.
- Invoice and payment management.
- Discounts and taxes.
- Cashier closing.
- Performance reports.
- Bonus configuration and approvals.
- Audit history.
- Business settings.

## 8.3 Front Desk / Cashier Interface

The interface shall prioritise:

- Customer search and registration.
- Guest transaction creation.
- Service order creation.
- Employee assignment.
- Service progress visibility.
- Reconciliation.
- Invoice preparation and finalisation.
- Payment recording.
- Receipt generation.
- Cashier closing.

## 8.4 Employee Interface

The employee interface shall be mobile-first and simple.

It shall prioritise:

- Active assignments and accessible transactions.
- Service execution submission.
- Collaborator identification.
- Additional-service requests.
- Submission history.
- Personal performance.

The employee interface shall not expose unauthorised invoice, payment, bonus or management information.

---

# 9. API Requirements

## 9.1 General

The backend shall expose versioned API endpoints.

The API shall:

- Authenticate protected requests.
- Enforce permissions.
- Validate input.
- Apply business rules.
- Return consistent response structures.
- Use appropriate HTTP status codes.
- Prevent duplicate processing of sensitive operations where idempotency is required.
- Provide pagination and filtering for collections.
- Avoid exposing internal implementation details.

A consistent response envelope should be adopted, for example:

- success
- message
- data
- errors
- meta

The exact envelope and error-code conventions shall be documented before implementation.

## 9.2 Logical API Areas

| API area | Responsibility |
|---|---|
| Authentication | Login, logout, session and account operations |
| Users and permissions | User, role and permission administration |
| Customers | Customer and guest transaction identity |
| Services | Categories and service catalogue |
| Service orders | Requested services and assignments |
| Executions | Employee service submissions |
| Contributions | Collaborators and allocation |
| Additional services | Requests and approvals |
| Reconciliation | Requested-versus-performed comparison |
| Invoices | Drafting, finalisation and amendments |
| Discounts and taxes | Financial adjustments |
| Payments | Cash, transfer and POS recording |
| Receipts | Receipt generation and retrieval |
| Refunds | Controlled reversals |
| Cashier closing | Daily reconciliation |
| Performance | Employee and team reporting |
| Bonuses | Rules, calculation, approval and payment |
| Audit | Search and review of permitted audit records |
| Imports | File validation and import processing |

Exact routes, payload schemas and error codes shall be defined in the API design phase.

---

# 10. Security Requirements

**SEC-001:** All protected application functions shall require authentication.

**SEC-002:** Backend permission checks shall be enforced on every protected operation.

**SEC-003:** Users shall only access records within their authorised business scope.

**SEC-004:** Sensitive data shall not be exposed through unauthorised API responses.

**SEC-005:** Passwords and authentication secrets shall be stored using secure industry-standard mechanisms.

**SEC-006:** Application traffic shall use HTTPS in production.

**SEC-007:** The system shall validate and sanitise untrusted input.

**SEC-008:** The system shall protect against common web application vulnerabilities.

**SEC-009:** Sensitive financial actions shall be auditable.

**SEC-010:** Confirmed service records and finalised financial records shall not be silently altered.

**SEC-011:** Production and development environments shall be separated.

**SEC-012:** Database backups shall be implemented and restoration tested.

**SEC-013:** The PWA service worker shall not cache private customer, payment or staff records.

**SEC-014:** The application shall use secure session and cookie configuration appropriate to its deployment architecture.

**SEC-015:** Privileged operations shall be protected against unauthorised or duplicate execution.

---

# 11. Non-Functional Requirements

## 11.1 Performance

**NFR-PERF-001:** Common operational screens should load within an acceptable response time under normal expected usage.

**NFR-PERF-002:** Financial calculations shall be performed efficiently on the backend.

**NFR-PERF-003:** Reports shall support date filters and pagination or aggregation appropriate to their data volume.

**NFR-PERF-004:** Long-running imports and report generation should not unnecessarily block ordinary user requests.

Specific numeric performance targets shall be established after expected user volume, hosting capacity and workload are confirmed.

## 11.2 Reliability

**NFR-REL-001:** Critical transaction operations shall use database transactions where multiple records must remain consistent.

**NFR-REL-002:** Failed operations shall not leave partially committed financial or service records.

**NFR-REL-003:** The application shall provide backup and recovery procedures.

**NFR-REL-004:** Important operations shall be protected against accidental duplicate submissions.

## 11.3 Usability

**NFR-USE-001:** Staff workflows shall be easy to use on mobile devices.

**NFR-USE-002:** The application shall minimise unnecessary steps in routine service recording.

**NFR-USE-003:** The application shall provide clear transaction status and validation feedback.

**NFR-USE-004:** Financial totals and outstanding balances shall be clearly distinguishable.

## 11.4 Maintainability

**NFR-MNT-001:** The backend shall use modular organisation and separation of concerns.

**NFR-MNT-002:** Business rules shall be centralised in appropriate backend services or domain components.

**NFR-MNT-003:** The system shall use database migrations.

**NFR-MNT-004:** Critical business calculations shall have automated tests.

**NFR-MNT-005:** The application shall have documented setup and deployment instructions.

**NFR-MNT-006:** Source code shall be maintained in GitHub.

## 11.5 Portability

**NFR-PORT-001:** The application shall support suitable cPanel hosting.

**NFR-PORT-002:** The application shall support VPS deployment.

**NFR-PORT-003:** Moving between supported hosting environments shall not require rewriting business logic.

**NFR-PORT-004:** Docker may be supported as an optional deployment method.

## 11.6 PWA Requirements

**NFR-PWA-001:** The application shall provide a valid web app manifest.

**NFR-PWA-002:** The application shall support installation where supported by the user's browser and operating system.

**NFR-PWA-003:** The application shall provide a responsive mobile experience.

**NFR-PWA-004:** The application shall support safe application asset caching.

**NFR-PWA-005:** Sensitive business transactions shall require an active connection in the MVP.

**NFR-PWA-006:** The application shall not expose private transaction data through inappropriate offline caching.

---

# 12. Integration Requirements

## 12.1 Initial Release

The initial release shall not depend on an online payment gateway.

Payments will be recorded manually using cash, bank transfer and POS methods.

## 12.2 Future Integrations

The architecture should allow future integration with:

- Payment gateways.
- WhatsApp sharing or messaging services.
- Email delivery providers.
- External accounting or business systems.

These are future integration opportunities and are not required for MVP acceptance.

---

# 13. Validation and Error Handling

The system shall validate:

- Required fields.
- User permissions.
- Transaction state.
- Service availability.
- Employee eligibility.
- Contributor uniqueness.
- Percentage allocation totals.
- Service and invoice amounts.
- Discount limits.
- Tax configuration.
- Payment amounts.
- Outstanding balances.
- Refund and reversal eligibility.
- Bonus approval and payment transitions.
- Import data integrity.

Validation shall occur on the backend even when the frontend also provides immediate validation.

Errors shall be understandable to users and shall not disclose sensitive internal implementation information.

---

# 14. Acceptance and Verification Requirements

The following scenarios form the initial system verification baseline.

## 14.1 Collaborative Service

Given a ₦20,000 service performed by three employees:

- The customer shall be charged ₦20,000 once.
- All three employees shall be recorded as contributors.
- The allocation rule shall determine contribution credit.
- Contribution credit shall not inflate revenue.

## 14.2 Confirmed Service Protection

Given an employee has submitted a service execution:

- The execution shall be confirmed automatically.
- An unauthorised user shall not be able to remove it.
- An authorised correction shall preserve the original record.
- The correction shall be auditable.

## 14.3 Additional Service Approval

Given an employee requests an additional service:

- The request shall be pending.
- It shall not become an authorised invoice item before approval.
- Approval or rejection shall identify the responsible supervisor.
- An approved item shall be available for reconciliation.

## 14.4 Invoice Reconciliation

Given a transaction contains requested and performed services:

- The system shall show the differences.
- Approved additional services shall be included.
- Unresolved discrepancies shall follow finalisation rules.
- The final invoice shall reflect authorised services.

## 14.5 Discounts and Taxes

Given a configured discount and tax rule:

- The system shall apply the correct rule.
- The system shall respect user permissions and limits.
- The invoice shall show the resulting values.
- Historical calculations shall remain reproducible.

## 14.6 Partial Payments

Given a final invoice of ₦30,000:

- A payment of ₦10,000 shall be recordable.
- The outstanding balance shall be ₦20,000.
- A receipt shall be generated for the ₦10,000 payment.
- A later valid payment shall update the balance correctly.

## 14.7 Refund or Reversal

Given a recorded payment:

- An unauthorised user shall not be able to reverse it.
- An authorised reversal shall require a reason.
- The original payment shall remain in history.
- The invoice payment position shall be updated correctly.

## 14.8 Cashier Closing

Given recorded collections across cash, POS and transfer:

- The system shall calculate expected totals by method.
- The cashier shall declare or confirm actual amounts.
- Discrepancies shall be calculated.
- Management shall be able to review the closing record.

## 14.9 Bonus Lifecycle

Given eligible performance records:

- The system shall calculate a bonus.
- The bonus shall initially be Calculated.
- An authorised owner or manager shall approve it.
- The status shall become Approved.
- Payment recording shall move it to Paid.
- The calculation and approval history shall remain available.

## 14.10 Permissions

Given an employee without invoice-finalisation permission:

- The employee shall not be able to finalise an invoice.
- Direct API requests shall also be rejected.
- The denied operation shall not modify the invoice.

---

# 15. Assumptions and Constraints

1. The first release serves one salon.
2. The default currency is NGN.
3. Cash, transfer and POS payments are manually recorded.
4. Service execution submissions automatically confirm execution.
5. Additional services require supervisor approval.
6. Invoice finalisation is permission-controlled.
7. The system is online-first for sensitive transactions.
8. The system is a PWA.
9. cPanel is preferred, but VPS deployment must be supported.
10. The backend is the source of truth for business logic and financial calculations.
11. The MVP does not include full accounting, payroll or payment gateway integrations.
12. Future multi-business and branch capabilities must not unnecessarily complicate the initial release.

---

# 16. Open Technical and Detailed Design Decisions

The following decisions do not invalidate the approved product baseline, but must be resolved before the affected implementation work begins.

| ID | Decision | Required design outcome |
|---|---|---|
| TD-001 | Authentication strategy | Select session or token approach appropriate to the frontend/backend deployment |
| TD-002 | Business timezone | Define the salon's configured timezone and timestamp presentation policy |
| TD-003 | Invoice finalisation rules | Define exact conditions that block or permit finalisation |
| TD-004 | Service execution granularity | Define whether repeated execution records can exist for one requested service item and under what conditions |
| TD-005 | Allocation rounding | Define precision, rounding and residual distribution |
| TD-006 | Tax calculation order | Define interaction between discounts, inclusive taxes and exclusive taxes |
| TD-007 | Payment overpayment | Decide whether overpayments are rejected or recorded as customer credit |
| TD-008 | Refund limits | Define partial/full refund rules and approval thresholds |
| TD-009 | Cashier closing | Define shift boundaries, multiple cashiers and closing review/reopen permissions |
| TD-010 | Bonus rule versioning | Define how rule changes affect open calculation periods |
| TD-011 | Bonus payout | Define whether one approved bonus may be paid in instalments |
| TD-012 | Notifications | Define MVP notification channels and events |
| TD-013 | Data import | Confirm exact source columns and duplicate-resolution policy |
| TD-014 | Hosting topology | Confirm where Next.js and Laravel will run under cPanel and VPS deployments |
| TD-015 | Backup policy | Define backup frequency, retention and recovery objectives |

These decisions should be handled through explicit design decisions, not silently assumed in implementation.

---

# 17. Requirements Traceability

| PRD capability | SRS sections |
|---|---|
| Customer and guest transactions | 4.3 |
| Service catalogue | 4.4 |
| Service orders | 4.5 |
| Staff service recording | 4.6 |
| Collaborative contribution | 4.7 |
| Additional-service approval | 4.8 |
| Service reconciliation | 4.9 |
| Invoice finalisation | 4.10 |
| Discounts and taxes | 4.11–4.12 |
| Payments and receipts | 4.13–4.14 |
| Refunds and reversals | 4.15 |
| Cashier closing | 4.16 |
| Staff performance | 4.17 |
| Bonus management | 4.18 |
| Dashboard and reports | 4.19 |
| Auditability | 4.20 |
| Data import | 4.21 |
| Security and PWA | 10–11 |

---

# 18. Document Governance

This SRS is subordinate to PRD v1.2.

If a conflict is discovered:

1. Identify the conflicting requirements.
2. Record the conflict and its impact.
3. Recommend a resolution.
4. Obtain product-owner approval where the resolution changes product behaviour.
5. Update the affected document and version.
6. Maintain traceability between the PRD and SRS.

No implementation phase should silently reinterpret an approved product decision.

**End of SRS v1.0 Draft**