# PRODUCT REQUIREMENTS DOCUMENT (PRD)
## Business Operations Management Platform
### Initial Product: Salon Management System

| Document Information | Details |
|---|---|
| Product | Business Operations Management Platform |
| Initial Business Vertical | Salon Management |
| Initial Market | Nigeria |
| Base Currency | Nigerian Naira (NGN) |
| Version | 1.1 |
| Status | Revised Draft – Awaiting Approval |
| Companion Document | Software Requirements Specification (SRS) |

---

# 1. Executive Summary

The Business Operations Management Platform is a web-based business management solution designed to improve operational efficiency, financial accountability, employee performance monitoring, and revenue visibility for service-based businesses.

The initial implementation will focus on salon operations.

The application will provide a centralized environment for managing customers, service orders, service execution, employee contributions, invoices, payments, receipts, staff performance, and business reporting.

A fundamental product principle is the separation of service ordering, actual service execution, financial reconciliation, invoicing, and payment processing.

Employees will independently record the services they perform. Submitting a service execution record will automatically confirm that submission as a confirmed service record, while maintaining traceability and controlled correction procedures.

The application will support multiple employees contributing to a single service without multiplying the customer's service charge. Configurable contribution allocation rules will determine how service value is attributed to participating employees.

The platform will also provide basic configurable bonus calculations based on employee performance and contribution data.

The first release will support one salon business, while establishing reusable foundations for future expansion into additional businesses and branches.

The application will be developed as a responsive Progressive Web Application (PWA), accessible through smartphones, tablets, and desktop computers.

The preferred initial hosting environment is cPanel-compatible hosting, with VPS deployment also supported.

# 2. Product Background and Problem Statement

The business currently uses an invoicing solution to manage services, invoices, and receipts.

However, existing processes do not provide sufficient control over employee activities, service execution, revenue collection, and transaction accountability.

A temporary Google Forms and Google Sheets solution has been introduced to capture services performed by employees.

The new application will replace these fragmented processes with an integrated management system.

## 2.1 Problems to Solve

### Staff performance visibility

The business cannot easily determine the services performed by individual employees, the number of customers served, the value of their contributions, or their performance over time.

### Revenue leakage

The person generating invoices may remove services that were actually performed, potentially resulting in underbilling and diversion of business revenue.

### Collaborative service attribution

Multiple employees sometimes work together on one service. The system must recognize every contributor without multiplying the customer's charge.

### Fragmented operations

Customer records, service records, invoices, payments, and employee activities are not centrally managed.

### Limited management intelligence

The owner lacks a consolidated view of business revenue, employee contributions, outstanding balances, operational discrepancies, and business performance.

# 3. Product Vision

To build a reliable, secure, and scalable business operations platform that gives business owners visibility and control over their business activities, employees, services, revenue, and performance.

## 3.1 Product Mission

To eliminate operational blind spots, strengthen financial accountability, improve employee performance visibility, and simplify the management of service-based businesses through integrated software.

## 3.2 Product Positioning

The platform combines:

1. Business operations management.
2. Service execution tracking.
3. Employee accountability.
4. Revenue protection.
5. Customer invoicing and payment management.
6. Employee contribution allocation.
7. Performance and bonus management.
8. Business analytics.

# 4. Product Goals and Objectives

The product must:

1. Establish reliable records of services requested and performed.
2. Track employee contributions accurately.
3. Prevent unauthorized removal or alteration of confirmed services.
4. Reconcile actual services against customer invoices.
5. Improve revenue and payment accountability.
6. Provide reliable employee performance reporting.
7. Support configurable contribution allocation.
8. Support basic configurable employee bonus calculations.
9. Support guest and registered customer transactions.
10. Support discounts and configurable taxes.
11. Provide comprehensive business dashboards.
12. Establish foundations for future business expansion.

## 4.1 Success Indicators

| Indicator | Desired Outcome |
|---|---|
| Service recording | Every performed service has a traceable record |
| Service attribution | Every service identifies its contributors |
| Reconciliation | Confirmed services are accounted for or have authorized exceptions |
| Unauthorized deletion | No confirmed service can be silently removed |
| Payment accountability | Every payment is traceable |
| Performance reporting | Employee contributions can be reviewed by period |
| Bonus calculations | Bonuses can be calculated using approved configurable rules |
| Revenue visibility | Management can view financial performance by period |
| Auditability | Critical actions are attributable to users |

# 5. Target Users and User Roles

The application will support four primary user categories.

## 5.1 Business Owner

The owner has overall control over the salon.

Responsibilities include:

- Business dashboard.
- Employee management.
- Service catalogue management.
- Pricing configuration.
- Financial monitoring.
- Transaction oversight.
- Permission management.
- Discrepancy review.
- Performance reporting.
- Contribution allocation configuration.
- Bonus rule configuration.
- Audit history.

## 5.2 Administrator / Supervisor

Authorized administrators support daily operations.

Responsibilities may include:

- Transaction supervision.
- Employee assignments.
- Additional service approval.
- Service discrepancy resolution.
- Authorized corrections.
- Invoice approval or finalization.
- Discount authorization.
- Payment oversight.
- Performance monitoring.

Permissions will be configurable.

## 5.3 Front Desk / Cashier

Responsibilities include:

- Customer registration.
- Guest customer transactions.
- Service order creation.
- Service selection.
- Employee assignments.
- Service progress monitoring.
- Invoice preparation.
- Payment recording.
- Receipt generation.

Cashiers must not be able to silently remove employee-confirmed services.

## 5.4 Service Employees

Each employee has an individual account.

Responsibilities include:

- Viewing assigned services.
- Identifying active customer transactions.
- Recording services performed.
- Recording participating employees.
- Submitting additional service requests.
- Viewing personal service history.
- Viewing permitted performance information.

Submitting a service execution record automatically confirms the submission.

Confirmed records remain protected from unauthorized alteration.

# 6. Core Product Concepts

## 6.1 Customer Management

The application must support registered customers and guest/walk-in transactions.

Registered customers should have customer profiles containing relevant identification and contact information.

Guest transactions must be possible without requiring full customer registration.

The application should support associating a guest transaction with a registered customer later, subject to appropriate controls and audit history.

## 6.2 Service Catalogue

The service catalogue defines services offered by the salon.

Each service should support:

- Name.
- Description.
- Category.
- Configured price.
- Active/inactive status.
- Relevant service settings.

Prices must be preserved as historical snapshots in existing transactions.

## 6.3 Service Orders

A service order represents the services requested by a customer.

Authorized users can:

- Create service orders.
- Select multiple services.
- Assign expected employees.
- Leave employees unassigned.
- Track service progress.
- Review the order.
- Request additional services through controlled procedures.

## 6.4 Service Execution

Employees record services they actually perform.

A submission must capture:

- Customer transaction.
- Service item.
- Submitting employee.
- Participating employees.
- Submission timestamp.
- Relevant service details.

**Confirmation rule:** A valid employee submission automatically confirms the submitted service execution record.

This removes the requirement for routine supervisor verification of every employee submission.

However, the system must maintain the ability to investigate, correct, cancel, or dispute confirmed records through authorized procedures.

## 6.5 Collaborative Service Attribution

Multiple employees can contribute to one service.

Example:

A braiding service costs ₦20,000.

Three employees participate.

The system records one ₦20,000 customer service item and three employee contribution records.

The customer is charged ₦20,000, not ₦60,000.

The system must support configurable allocation rules, including:

- Equal contribution allocation.
- Percentage-based allocation.
- Weighted allocation.
- Primary employee and assisting employee allocation.
- Other authorized allocation arrangements.

Allocation calculations must be separate from the original customer charge.

## 6.6 Additional Service Requests

Employees must be able to request additional services when customers request work beyond the original service order.

The process is:

1. Employee identifies the active transaction.
2. Employee submits an additional service request.
3. The requested service and applicable price are identified.
4. An authorized supervisor reviews the request.
5. The supervisor approves or rejects it.
6. Approved services become part of the transaction.
7. The system records the request and approval history.

Unapproved additional services must not automatically become authorized invoice items.

## 6.7 Service Reconciliation

The system compares:

- Original requested services.
- Approved additional services.
- Employee-confirmed services.
- Cancelled services.
- Outstanding service items.

The system must highlight discrepancies and prevent ordinary invoice finalization while unresolved discrepancies remain.

Authorized users may resolve discrepancies or approve documented exceptions.

## 6.8 Invoice Management

Invoices represent the customer's financial obligations.

The system must support:

- Invoice generation.
- Unique invoice numbering.
- Invoice items.
- Service pricing.
- Discounts.
- Configurable taxes.
- Invoice totals.
- Payment status.
- Outstanding balances.
- Invoice history.

Invoice finalization permissions must be configurable.

An authorized cashier may finalize an invoice after successful reconciliation if the relevant permission is granted.

The invoice must not replace or silently modify service execution records.

## 6.9 Discounts

The application must support configurable discounts.

Potential discount types include:

- Fixed amount.
- Percentage-based.

Discounts must be traceable and associated with the responsible user.

Authorization limits and applicable restrictions will be defined in the SRS.

## 6.10 Taxes

The application must support configurable tax settings.

Tax configuration should accommodate applicable rates and business requirements without hardcoding tax values into application logic.

The treatment of inclusive and exclusive taxes, tax exemptions, and rounding will be specified in the SRS following confirmation of the business's requirements.

## 6.11 Payment Management

The MVP must support manual recording of:

- Cash payments.
- Bank transfers.
- POS payments.

The system must support:

- Full payments.
- Part payments.
- Multiple payments per invoice.
- Payment references.
- Payment dates.
- Outstanding balances.
- Payment history.
- Authorized reversals and refunds.

Payment gateway integration is not required for the first release.

## 6.12 Receipt Management

The application must generate receipts for recorded payments.

Receipts should support:

- PDF download.
- Printing.
- WhatsApp sharing.
- Email delivery.

Manual sharing and download must remain available even when automated messaging integrations are not configured.

## 6.13 Audit Management

The system must maintain a traceable history of important business operations.

Audit records should capture:

- User responsible.
- Action performed.
- Affected record.
- Previous and updated values.
- Timestamp.
- Reason for sensitive modifications.
- Authorization details where applicable.

Confirmed service records, payments, and financial records must not be silently erased.

# 7. Core Business Workflows

## 7.1 Customer and Service Order Creation

1. Customer arrives.
2. Front desk identifies an existing customer, registers a new customer, or opens a guest transaction.
3. A service order is created.
4. Requested services are selected.
5. Prices are retrieved from the service catalogue.
6. Expected employees are assigned where known.
7. Service order is saved.
8. Employees access relevant transactions.

## 7.2 Service Execution

1. Employee logs in.
2. Employee identifies an active transaction.
3. Employee selects the relevant service.
4. Employee records participation.
5. Other participating employees are identified.
6. Employee submits the service record.
7. The submission is automatically confirmed.
8. The record becomes available for reconciliation and performance reporting.

## 7.3 Additional Service Request

1. Employee identifies a customer request for additional work.
2. Employee submits the request.
3. Supervisor reviews it.
4. Supervisor approves or rejects it.
5. Approved requests become authorized service items.
6. The request and approval remain traceable.

## 7.4 Reconciliation

1. System retrieves the service order.
2. System retrieves approved additional services.
3. System retrieves confirmed service execution records.
4. System compares requested and performed services.
5. Discrepancies are identified.
6. Authorized users resolve discrepancies.
7. The transaction becomes eligible for invoicing.

## 7.5 Invoice Finalization

1. Authorized cashier or supervisor opens a reconciled transaction.
2. System prepares the final invoice.
3. Authorized discounts are applied.
4. Applicable configurable taxes are calculated.
5. Invoice is finalized.
6. Invoice becomes available for payment.

## 7.6 Payment and Receipt

1. Customer pays using cash, bank transfer, or POS.
2. Authorized cashier records the payment.
3. System updates payment allocation and outstanding balance.
4. Receipt is generated.
5. Customer receives the receipt.

# 8. Functional Requirements

The MVP must include:

### Customer management
- Registered customer records.
- Guest/walk-in transactions.
- Customer search.
- Customer transaction history.
- Guest-to-customer association where applicable.

### Service management
- Service categories.
- Service catalogue.
- Service pricing.
- Historical pricing.
- Active/inactive services.

### Employee management
- Employee accounts.
- Individual authentication.
- Employee profiles.
- Role and permission management.
- Employee assignments.

### Service orders
- Order creation.
- Multiple service items.
- Employee assignment.
- Unassigned services.
- Additional service requests.
- Controlled cancellation.

### Service execution
- Employee service submissions.
- Automatic confirmation on valid submission.
- Individual contributions.
- Collaborative participation.
- Submission timestamps.
- Authorized correction procedures.

### Reconciliation
- Requested-versus-performed comparison.
- Additional service reconciliation.
- Missing service identification.
- Discrepancy management.
- Exception authorization.

### Financial operations
- Invoice generation.
- Discounts.
- Configurable taxes.
- Invoice finalization permissions.
- Cash, bank transfer and POS payment recording.
- Part payments.
- Multiple payments.
- Outstanding balances.
- Receipts.

### Performance and bonuses
- Employee service reports.
- Individual and collaborative contributions.
- Configurable contribution allocation.
- Basic configurable bonus rules.
- Bonus calculations.
- Bonus reports.

### Management
- Owner dashboard.
- Operational monitoring.
- Financial reporting.
- Employee performance reporting.
- Audit history.

# 9. Staff Performance and Bonus Management

## 9.1 Performance Reporting

The system must provide:

- Number of services performed.
- Number of customers served.
- Service categories.
- Individual contributions.
- Collaborative contributions.
- Contribution value.
- Daily, weekly, monthly and custom-period reports.

## 9.2 Contribution Allocation

The business owner must be able to configure how the value of collaborative services is distributed among participating employees.

The allocation mechanism must not change customer billing or business revenue.

Allocation rules should be configurable and historically traceable.

## 9.3 Basic Bonus Management

Unlike the original product proposal, basic bonus calculations are now included in the MVP.

The application must support configurable bonus criteria, such as:

- Contribution value.
- Number of completed services.
- Number of customers served.
- Performance targets.
- Individual performance.
- Collaborative performance.

The owner should be able to configure bonus rules and review calculated bonus amounts.

The system must preserve the underlying performance records and calculation history.

Advanced payroll, salary processing, statutory deductions, and comprehensive commission management remain outside the MVP.

# 10. Business Owner Dashboard and Reporting

The owner dashboard must provide visibility into:

### Financial
- Total invoiced amount.
- Total collected payments.
- Outstanding balances.
- Daily revenue.
- Weekly revenue.
- Monthly revenue.
- Yearly revenue.
- Payment method breakdown.
- Discounts and taxes.

### Operations
- Active service orders.
- Services performed.
- Services awaiting execution.
- Additional service requests.
- Unresolved discrepancies.
- Cancelled services.

### Employees
- Service counts.
- Customer counts.
- Contribution values.
- Individual and collaborative performance.
- Calculated bonuses.

### Accountability
- Recent activities.
- Service modifications.
- Invoice changes.
- Payment modifications.
- Authorized exceptions.

# 11. Application Interfaces

The application will provide three primary interfaces.

## Owner / Administrator Interface

Designed for management, configuration, monitoring, reporting, and authorization.

## Front Desk / Cashier Interface

Designed for customer management, service orders, reconciliation, invoicing, payments, and receipts.

## Employee Interface

Designed for mobile-friendly service assignments, service submissions, additional service requests, and performance visibility.

All interfaces will connect to the same backend and business records, with access controlled by permissions.

# 12. Product and Platform Requirements

The application must:

- Be responsive.
- Support PWA installation.
- Work on Android, iOS, tablets, and desktops.
- Provide secure authentication.
- Support automatic application updates.
- Support push notifications where configured.
- Prioritize reliable online transactions.
- Avoid exposing private customer or payment data through public caches.

Sensitive business operations must require server communication.

Offline service submission may be considered in a later release.

# 13. Security and Business Integrity

The application must support:

- Individual user accounts.
- Configurable role-based access control.
- Backend-enforced authorization.
- Protected confirmed service records.
- Authorized invoice changes.
- Traceable payment modifications.
- Audit history.
- Secure authentication.
- Controlled access to customer and employee information.

A confirmed service submission must not be silently deleted.

Corrections and cancellations must follow authorized procedures and preserve the original activity history.

# 14. Business Configuration

The application should support:

- Business name.
- Logo.
- Contact information.
- Address.
- Currency.
- Service catalogue.
- Service prices.
- Tax configuration.
- Discount configuration.
- Invoice settings.
- Receipt settings.
- User roles and permissions.
- Contribution allocation rules.
- Bonus rules.

# 15. Scope and Delivery Priorities

## 15.1 MVP — First Release

| Module | Priority |
|---|---|
| Customer and guest management | MVP |
| Service catalogue | MVP |
| Employee management | MVP |
| Authentication and permissions | MVP |
| Service orders | MVP |
| Employee service recording | MVP |
| Automatic service confirmation | MVP |
| Collaborative attribution | MVP |
| Configurable contribution allocation | MVP |
| Additional service requests and approval | MVP |
| Service reconciliation | MVP |
| Audit trail | MVP |
| Invoice generation | MVP |
| Discounts | MVP |
| Configurable taxes | MVP |
| Manual cash, transfer and POS payments | MVP |
| Part payments and balances | MVP |
| Receipts | MVP |
| Staff performance reporting | MVP |
| Basic bonus calculations | MVP |
| Owner dashboard | MVP |
| PWA support | MVP |

## 15.2 Subsequent Releases

- Advanced bonus and incentive engine.
- Employee attendance.
- Employee scheduling.
- Automated WhatsApp delivery.
- Automated email delivery.
- Push notification enhancements.
- Controlled offline submissions.
- Advanced analytics.
- Payment gateway integration.

## 15.3 Long-Term Expansion

- Multiple businesses.
- Multiple branches.
- Cleaning service management.
- Inventory management.
- CRM.
- Expense management.
- Profitability reporting.
- Payroll and commission management.
- Dedicated mobile applications.
- Additional business-specific modules.

# 16. Non-Functional Requirements

The application must be:

- Reliable.
- Secure.
- Responsive.
- Maintainable.
- Scalable.
- Testable.
- Portable across supported hosting environments.

Specific performance, availability, backup, recovery, and security acceptance thresholds will be defined in the SRS.

# 17. Recommended Technical Direction

| Component | Preferred Technology |
|---|---|
| Backend | Laravel / PHP |
| Frontend | Next.js / TypeScript |
| Database | MySQL |
| Application | PWA |
| Architecture | Modular monolith |
| Backend interface | Reusable API |
| Source control | GitHub |
| Database changes | Version-controlled migrations |
| Testing | Automated tests |
| Initial hosting preference | cPanel-compatible hosting |
| Alternative hosting | VPS |

Laravel will own business rules, financial calculations, authorization, and transaction validation.

The frontend and backend should be independently deployable.

The architecture should support migration from cPanel to VPS or cloud infrastructure without a complete application rebuild.

# 18. Dependencies and Assumptions

1. The initial deployment supports one salon business.
2. Employees have individual accounts.
3. Employee submissions automatically confirm their service execution records.
4. The owner or authorized supervisors manage sensitive exceptions.
5. Additional services require supervisor approval.
6. Contribution allocation rules are configurable.
7. Basic bonus calculations are included in the MVP.
8. Guest transactions are permitted.
9. Cash, bank transfer, and POS payments are manually recorded.
10. Discounts and configurable taxes are supported.
11. The initial operating currency is NGN.
12. The system prioritizes online operation.

# 19. Risks and Mitigation

| Risk | Mitigation |
|---|---|
| Employees submit inaccurate service records | Traceable submissions, exception handling and audit history |
| Cashier removes services | Independent confirmed service records and reconciliation |
| Duplicate collaborative submissions | Shared service item and duplicate prevention |
| Incorrect contribution allocation | Configurable, validated and traceable allocation rules |
| Unauthorized cancellations | Permission controls and audit history |
| Unapproved additional services | Supervisor approval workflow |
| Incorrect discounts or taxes | Configurable rules and controlled permissions |
| Payment discrepancies | Payment references, history and reconciliation |
| Poor connectivity | Online-first architecture and future controlled offline support |
| Overengineering | Phased modular development |

# 20. MVP Acceptance Criteria

The application must successfully demonstrate the following:

1. A cashier can create a registered or guest customer transaction.
2. Multiple services can be included in one order.
3. Employees can submit their completed service records.
4. Valid submissions automatically become confirmed records.
5. Three employees can contribute to one ₦20,000 service without increasing the customer charge.
6. Configurable allocation rules correctly distribute contribution value.
7. Employees can request additional services and supervisors can approve or reject them.
8. The system identifies differences between requested and confirmed services.
9. Unauthorized removal of confirmed services is prevented.
10. Authorized corrections preserve audit history.
11. Authorized users can finalize reconciled invoices.
12. Discounts and applicable configurable taxes are calculated correctly.
13. Cash, bank transfer and POS payments can be recorded.
14. Partial and multiple payments update balances correctly.
15. Receipts can be generated and shared.
16. Employee performance reports reflect individual and collaborative contributions.
17. Basic bonus rules can be configured and calculated.
18. Owners can review financial and operational dashboards.
19. Critical operations are traceable through audit history.
20. Historical service prices and transaction records remain accurate after catalogue changes.

# 21. Product Roadmap

| Phase | Focus | Deliverable |
|---|---|---|
| 1 | Platform foundation | Business configuration, authentication, users and permissions |
| 2 | Customer operations | Customers, guest transactions and service orders |
| 3 | Service execution | Assignments, service submissions and collaboration |
| 4 | Accountability | Automatic confirmation, additional service approval, reconciliation and audit |
| 5 | Financial operations | Invoices, discounts, taxes, payments and receipts |
| 6 | Performance intelligence | Allocation, performance reports and basic bonus calculations |
| 7 | Management | Owner dashboard and consolidated reporting |
| 8 | Validation and deployment | End-to-end testing, production deployment and business acceptance |

Every phase must produce a tangible working increment.

Review and approval are required before moving to the next phase.

# 22. Future Expansion Strategy

The platform should separate reusable business capabilities from business-specific operational modules.

Shared capabilities will include:

- Business configuration.
- Users and permissions.
- Customers.
- Employees.
- Service catalogues.
- Transactions.
- Invoicing.
- Payments.
- Audit history.
- Reporting.

Business-specific capabilities will be implemented through appropriate modules.

For example, cleaning services may introduce job scheduling, cleaning teams, service locations, job assignments, and completion evidence.

The initial salon application must remain complete and independently useful without requiring these future modules.

# 23. Outstanding Product Decisions

The following matters remain subject to clarification before final PRD approval:

1. **Automatic service confirmation:** Should employees be able to confirm a service they were not originally assigned to, provided they have access to the transaction?

2. **Service corrections:** Who can dispute or correct an automatically confirmed employee submission, and should the original employee be notified?

3. **Collaborative attribution:** Must the allocation percentages always total 100%, and should the primary employee be identifiable?

4. **Bonus calculations:** Should bonuses be calculated daily, weekly, monthly, or using configurable periods? Should the owner approve them before they become payable?

5. **Bonus eligibility:** Should bonuses be based on completed service contribution, collected revenue, or both?

6. **Discount authorization:** Should different users have configurable discount limits, such as a cashier being permitted to offer only a limited discount?

7. **Tax treatment:** Should prices be tax-inclusive or tax-exclusive, and should tax exemptions be supported?

8. **Guest transactions:** Should guest customers be identifiable using a name, phone number, or simply a transaction reference?

9. **Payments and reconciliation:** Should the application include a daily cashier closing process for reconciling recorded cash, POS, and transfer collections against actual receipts?

10. **Refunds:** Should refunds be included in the first release, or should the MVP initially support authorized payment reversals only?

11. **Employee visibility:** Should employees see only their personal performance, or should they also see team performance?

12. **Existing data migration:** Should existing customer and service records from MyCantant and Google Sheets be migrated?

These are the remaining matters that may materially affect product behaviour, business rules, and the SRS.

# 24. PRD Approval and SRS Handoff

This document defines the approved product direction once the owner accepts it.

The next document will be the Software Requirements Specification (SRS).

The SRS will translate this PRD into detailed implementation requirements, including:

- Functional requirement identifiers.
- User roles and permission matrices.
- Business rules.
- State machines and transaction lifecycles.
- Database entities and relationships.
- API contracts.
- Financial calculations.
- Contribution allocation algorithms.
- Bonus calculation rules.
- Authentication and authorization.
- Audit and security requirements.
- Tax and discount validation.
- Reporting definitions.
- Error handling.
- Infrastructure and deployment specifications.
- Testing and acceptance criteria.

The SRS must remain consistent with the approved PRD.

Any additional requirements identified during SRS preparation must be documented, explained, and submitted for approval before implementation.

---

**END OF PRODUCT REQUIREMENTS DOCUMENT**

**Version:** 1.1

**Status:** Awaiting Final Product Owner Approval.

**Next Deliverable:** Software Requirements Specification (SRS), following approval of this PRD.