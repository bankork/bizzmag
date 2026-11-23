# Salon Operations & Accountability Platform
## Product Requirements Document (PRD)

**Version:** 1.2  
**Status:** Approved product baseline  
**Initial market:** Nigeria  
**Initial business:** One salon  
**Currency default:** Nigerian Naira (₦)

---

## 1. Product Overview

The Salon Operations & Accountability Platform is a web-based business management application designed to improve salon operations, staff accountability, revenue visibility, service tracking and financial control.

Its central purpose is to ensure that services requested by customers, services actually performed, employees who contributed, amounts invoiced and payments received are accurately recorded and traceable.

The application must prevent unauthorized removal or manipulation of confirmed services while providing management with reliable staff performance, revenue and bonus information.

The first release will serve one salon. Its architecture should permit future expansion to additional branches and other service-based businesses, without making the first release unnecessarily complex.

## 2. Problem Statement

The current business process relies on separate invoicing and staff service-recording activities. This creates gaps in:

- Tracking the services each employee performs.
- Identifying individual and collaborative contributions.
- Reconciling requested services against services actually delivered.
- Preventing unauthorized changes to invoices.
- Monitoring revenue, payments and outstanding balances.
- Calculating and managing staff bonuses.
- Reviewing business activity and historical changes.

The product must establish a reliable operational record from customer arrival through service completion, invoicing and payment.

## 3. Product Vision

To provide a reliable, easy-to-use business operations platform that gives service businesses control over their transactions, staff activities, service delivery and financial performance.

## 4. Product Goals

1. Improve staff accountability and service-record accuracy.
2. Reduce revenue leakage and invoice manipulation.
3. Accurately record individual and collaborative staff contributions.
4. Reconcile requested services against completed services.
5. Improve revenue, payment and outstanding-balance visibility.
6. Support configurable and auditable staff bonus calculations.
7. Provide useful operational reports and management dashboards.
8. Establish a scalable foundation for future business types.

## 5. Users and Roles

### Business Owner
Has overall business oversight and controls business settings, users, permissions, financial operations, reports, bonus rules and approvals.

### Administrator / Supervisor
Manages operational activities and performs authorised approvals, corrections, discounts and transaction actions.

### Front Desk / Cashier
Registers customers, creates service orders, manages invoices, records payments, issues receipts and performs permitted closing activities.

### Employee / Service Provider
Views relevant assignments, records services performed, identifies collaborators and views permitted personal performance information.

### Guest Customer
May receive services without creating a permanent customer account. Guest transactions must still have a traceable transaction reference.

The system must support configurable roles and permissions. The role names above are default operational roles, not an inflexible permission model.

## 6. Product Scope

### 6.1 MVP Functional Modules

1. Authentication and user management.
2. Role-based access control.
3. Business settings.
4. Customer management and guest transactions.
5. Service categories and service catalogue.
6. Service orders and requested services.
7. Employee assignment.
8. Service execution recording.
9. Collaborative staff attribution.
10. Additional-service request and approval.
11. Service reconciliation.
12. Invoice generation and finalisation.
13. Discount and tax configuration.
14. Manual payment recording.
15. Receipts.
16. Refund and payment reversal controls.
17. Cashier daily closing and reconciliation.
18. Staff performance reporting.
19. Basic configurable bonus calculations.
20. Bonus approval and payment tracking.
21. Owner dashboard.
22. Audit trail.
23. CSV/Excel data import.
24. Responsive PWA experience.

### 6.2 Future Scope

The following are not required for the initial release:

- Payment gateway integrations.
- Full payroll processing.
- Advanced commission and incentive engines.
- Inventory and stock management.
- Employee attendance and scheduling.
- Cleaning-service-specific workflows.
- Full accounting and expense management.
- Native mobile applications.
- Advanced offline transaction submission.
- Multi-branch operational management.
- Advanced CRM and marketing automation.

The architecture should not prevent these capabilities from being introduced later.

## 7. Core Business Concepts

### 7.1 Customer

A customer is a person receiving services. A customer profile may contain name, phone number and other relevant details.

Guest transactions are permitted. Customer name and phone number may be optional for a guest transaction, but the transaction must receive a unique reference.

A guest transaction may later be associated with a registered customer. Such an association must be audited.

### 7.2 Service Catalogue

The service catalogue defines the services offered by the salon.

Each service should support:

- Name and description.
- Category.
- Configured price.
- Active/inactive status.
- Applicable tax configuration.
- Other configurable attributes required by the business.

The price applied to a transaction must be preserved as a historical snapshot. Changing the catalogue price must not silently change existing transactions.

### 7.3 Service Order

A service order records what a customer requested.

It contains one or more requested service items, their prices, quantities where applicable, and expected employee assignments.

A service order is not itself proof that the services were performed.

### 7.4 Service Execution

Service execution records what was actually performed.

Employees submit their service records. Submission automatically confirms the execution; routine supervisor confirmation is not required.

An employee may submit a service against an accessible transaction, including where they were not originally assigned. The system must preserve who submitted the record and support authorised dispute or correction.

### 7.5 Staff Contribution

A service may have one or multiple contributors.

The customer is charged for the service once, regardless of the number of contributing employees.

Contribution allocation rules must support:

- Equal allocation.
- Percentage allocation.
- Weighted allocation.
- Primary contributor and assistant arrangements.
- Other configurable allocation approaches where approved by the business.

Where percentage allocation is used, allocations must total 100%. The primary contributor must be identifiable.

Contribution values are performance and bonus calculation inputs. They must not be added together as additional customer charges or treated as additional revenue.

### 7.6 Additional Services

Employees may request additional services not included in the original order.

A supervisor or another authorised user must approve the request before the additional service becomes an authorised service item.

Approval, rejection and subsequent execution must be traceable.

### 7.7 Service Reconciliation

The system compares:

- Services initially requested.
- Services actually performed.
- Additional services requested and approved.
- Services cancelled or corrected through authorised procedures.

The reconciliation identifies differences and provides the basis for invoice finalisation.

### 7.8 Invoice

The final invoice represents the amount payable by the customer for authorised services and applicable adjustments.

Invoice finalisation permissions must be configurable.

After finalisation, the transaction is locked against ordinary editing. Corrections require an authorised amendment procedure with an audit record.

### 7.9 Payments and Receipts

The system supports manual recording of:

- Cash payments.
- Bank transfers.
- POS payments.

Partial and full payments must be supported. The system must calculate outstanding balances and generate receipts for recorded payments.

Invoices and receipts must be printable, downloadable and shareable digitally.

### 7.10 Discounts and Taxes

Discounts must support fixed amounts and percentages, with permission-controlled authorisation limits.

Taxes must be configurable, including:

- Tax rates.
- Tax-inclusive or tax-exclusive pricing.
- Applicable exemptions.
- Applicable service or transaction scope.

Tax and discount calculations must be reproducible and auditable.

### 7.11 Refunds and Reversals

Refunds and payment reversals must be controlled actions.

They must require appropriate permission, capture a reason, retain the original payment history and record the user responsible.

A refund or reversal must not silently erase the original payment.

### 7.12 Cashier Closing

The platform must support daily cashier closing.

Closing should reconcile expected collections against amounts declared or verified for:

- Cash.
- POS.
- Bank transfer.

Discrepancies must be recorded and made visible to authorised management users.

### 7.13 Staff Performance

Performance reports should include:

- Number of services performed.
- Types of services performed.
- Value of services contributed to.
- Number of customers served.
- Individual and collaborative contributions.
- Performance over daily, weekly and monthly periods.

Employees see their own performance by default. Team-wide performance visibility is permission-controlled.

### 7.14 Bonus Management

The first release includes basic configurable bonus calculations.

Bonus rules should support configurable periods and criteria based on:

- Completed service contributions.
- Collected revenue.
- A combination of contribution and collected-revenue criteria.
- Service counts and targets where supported by the configured rule.

Bonus records must have distinct states:

1. Calculated.
2. Approved.
3. Paid.

Owner or authorised management approval is required before a calculated bonus becomes payable. Payment must be separately recorded.

The bonus engine is not a full payroll system.

## 8. Core Business Workflow

1. Front desk creates a customer or guest transaction.
2. Requested services are selected from the service catalogue.
3. Expected employees may be assigned, or the service may remain unassigned.
4. Employees perform services and submit execution records.
5. Submission automatically confirms the execution.
6. Collaborative participants and allocation rules are recorded.
7. Employees may request additional services; authorised supervisors approve or reject them.
8. The system reconciles requested services against completed services and approved additions.
9. Authorised users resolve discrepancies through controlled procedures.
10. An authorised user finalises the invoice.
11. The system calculates discounts, taxes and final payable amount.
12. Cashier records one or more payments.
13. Receipts are generated.
14. Cashier performs daily closing and reconciliation.
15. Management reviews performance and bonus calculations.

## 9. Security, Accountability and Auditability

The platform must enforce role-based permissions on the backend.

Important actions must be auditable, including:

- Service execution submission.
- Contribution changes.
- Additional-service approvals.
- Service cancellations and corrections.
- Invoice finalisation and amendments.
- Discounts and tax adjustments.
- Payment recording.
- Refunds and reversals.
- Cashier closing.
- Bonus calculation, approval and payment.
- User and permission changes.

Audit records must preserve the actor, action, affected record, timestamp and relevant before/after values.

Confirmed service records must not be silently deleted or overwritten.

## 10. Platform and Deployment Requirements

The application must be a responsive Progressive Web App.

It should work on smartphones, tablets and desktop computers, and support installation through supported browser mechanisms.

The preferred technology direction is:

- Backend: Laravel / PHP.
- Frontend: Next.js / TypeScript.
- Database: MySQL.
- Application architecture: Modular monolith.
- API: Reusable backend API.
- Source control: GitHub.
- Deployment: cPanel preferred; VPS also acceptable.

The frontend and backend should be independently deployable. Critical business logic, financial calculations and permission enforcement belong to the backend.

Docker may be supported as an optional development/deployment convenience, not a mandatory production dependency.

The application should use database migrations, automated tests, documented deployment procedures and reliable backups.

Sensitive customer, payment and staff records must not be cached in the PWA service worker. The MVP is online-first for sensitive business transactions.

## 11. Data Import

The MVP should support controlled CSV/Excel imports for relevant existing data, particularly customers and service catalogue records.

Imports must validate data, report errors, avoid unintended duplicates and provide an import history.

## 12. MVP Acceptance Outcomes

The MVP is acceptable when:

- A customer or guest transaction can be created.
- Requested services can be recorded and assigned.
- Employees can submit completed service records.
- A service can have multiple contributors without duplicating its customer price.
- Additional services require approval.
- Service discrepancies are visible and traceable.
- Confirmed records cannot be silently removed.
- Authorised users can finalise invoices.
- Discounts and taxes are calculated according to settings.
- Cash, transfer and POS payments can be recorded.
- Partial payments and balances work correctly.
- Receipts can be generated and shared.
- Refunds and reversals are controlled and audited.
- Cashier closing identifies collection discrepancies.
- Staff performance reports are available.
- Bonus calculations can be configured, approved and marked paid.
- Role permissions are enforced by the backend.
- Important business actions have an audit trail.
- The application works responsively as a PWA.

## 13. Product Risks

| Risk | Product response |
|---|---|
| Staff fail to submit service records | Simple mobile-first staff workflow and operational monitoring |
| Invoice manipulation | Separate execution records, reconciliation and transaction locking |
| Incorrect collaborative attribution | Configurable allocation rules and validation |
| Unauthorised discounts | Permission-controlled limits and audit trail |
| Incorrect payment recording | Payment history, reversals and cashier closing |
| Bonus disputes | Transparent rules, calculation details and approval states |
| Hosting limitations | cPanel-compatible deployment with VPS portability |
| Poor data migration | Validated imports with error reporting |

## 14. SRS Handoff

The Software Requirements Specification must translate this PRD into:

- Detailed functional requirements.
- User roles and permission requirements.
- Business rules and validation rules.
- Detailed workflows and state transitions.
- Data requirements and entity relationships.
- API and integration requirements.
- Interface requirements.
- Security requirements.
- Performance and reliability requirements.
- Acceptance and verification criteria.

The SRS must not introduce product capabilities that contradict this PRD without an explicit product decision.

**End of PRD v1.2**