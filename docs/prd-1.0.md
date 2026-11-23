# PRODUCT REQUIREMENTS DOCUMENT (PRD)

## Business Operations Management Platform
### Initial Product: Salon Management System

| Document Information | Details |
|---|---|
| Document | Product Requirements Document |
| Product | Business Operations Management Platform |
| Initial Business Vertical | Salon Management |
| Initial Market | Nigeria |
| Primary Currency | Nigerian Naira (NGN) |
| Version | 1.0 – Draft for Review |
| Status | Awaiting Product Approval |
| Companion Document | Software Requirements Specification (SRS) |

---

# 1. Executive Summary

The Business Operations Management Platform is a web-based business management solution designed to improve operational efficiency, financial accountability, staff performance monitoring, and revenue visibility for service-based businesses.

The initial implementation will focus on salon operations.

The application will provide a centralized environment for managing customers, service orders, service execution, employee contributions, invoices, payments, receipts, and business performance.

A fundamental requirement is the separation of service ordering, actual service execution, financial reconciliation, invoicing, and payment processing.

This separation is intended to prevent unauthorized manipulation of customer invoices, reduce revenue leakage, improve accountability, and provide accurate records of employee contributions.

The platform will initially support a single salon business operation while establishing reusable foundations for future expansion into other service businesses, including cleaning services.

The application will be developed as a responsive Progressive Web Application (PWA), allowing business owners, administrators, cashiers, and employees to access the system through smartphones, tablets, and desktop computers.

The product will prioritize affordability, usability, security, reliability, and maintainability without introducing unnecessary complexity into the first release.

# 2. Product Background and Problem Statement

## 2.1 Business Background

The business owner operates a salon in Nigeria alongside other business activities, including cleaning services.

The current business relies on an invoicing solution to configure services, generate invoices, and issue receipts.

However, existing processes do not provide sufficient control over employee activities, service execution, revenue collection, and transaction accountability.

A temporary Google Forms and Google Sheets solution has been introduced to capture services performed by employees.

Although this provides some visibility, it does not provide the integrated controls, reconciliation, automation, and reporting required for effective business management.

## 2.2 Problems to Solve

### A. Inadequate staff performance visibility

The business cannot easily determine:

- Which services each employee performs.
- How many customers each employee serves.
- The value of services each employee contributes to.
- The employee's individual and collaborative contributions.
- Employee performance over different periods.

### B. Revenue leakage and invoice manipulation

The person generating an invoice can potentially remove services before finalizing the customer's bill.

This creates opportunities for employees to underreport services, collect lower payments, or divert business revenue.

### C. Inaccurate collaborative service attribution

Some services require multiple employees.

The business needs to record every employee who contributed to a service without multiplying the customer's service charge.

### D. Fragmented business operations

Customer records, service records, invoices, payments, and employee activities are not managed through one integrated system.

### E. Limited management reporting

The owner lacks a reliable consolidated view of business revenue, outstanding payments, employee contributions, operational activities, and discrepancies.

## 2.3 Product Problem Statement

The business needs a reliable system that establishes an independently traceable record of services performed, connects those records to customer transactions and financial obligations, prevents unauthorized changes, and provides accurate operational and financial visibility.

# 3. Product Vision

To build a reliable, secure, and scalable business operations platform that gives business owners complete visibility and control over their business activities, employees, services, revenue, and performance.

## 3.1 Product Mission

To eliminate operational blind spots, strengthen financial accountability, improve employee performance visibility, and simplify the management of service-based businesses through integrated software.

## 3.2 Product Positioning

The platform is not simply an invoicing application.

It combines:

1. Business operations management.
2. Service execution tracking.
3. Employee accountability.
4. Transaction and revenue protection.
5. Customer billing and payment management.
6. Performance analytics.
7. Configurable business management capabilities.

## 3.3 Long-Term Vision

The platform should eventually support different service-based business categories without requiring the entire application to be rebuilt.

The salon will serve as the first business implementation and validation environment.

# 4. Product Goals and Objectives

## 4.1 Primary Objectives

The product must:

1. Establish reliable records of services requested and performed.
2. Track employee contributions accurately.
3. Prevent unauthorized removal or alteration of confirmed services.
4. Reconcile actual services against customer invoices.
5. Improve the accuracy of revenue and payment records.
6. Provide clear visibility into employee performance.
7. Give business owners comprehensive operational dashboards.
8. Reduce manual administrative processes.
9. Establish a foundation for future business expansion.

## 4.2 Measurable Success Indicators

The following indicators will be used to evaluate the product after deployment.

| Indicator | Desired Outcome |
|---|---|
| Service recording | All services performed should have traceable records |
| Service attribution | Every recorded service should identify its contributors |
| Invoice reconciliation | All confirmed services must be accounted for or have an authorized exception |
| Unauthorized deletion | No confirmed service can be silently removed |
| Payment accountability | Every recorded payment must have a traceable transaction reference |
| Staff reporting | Employee contributions can be reviewed by selected periods |
| Revenue visibility | Management can view daily, weekly, monthly, and yearly financial summaries |
| Auditability | Critical actions must be attributable to users |
| Usability | Employees can record services using a simple mobile interface |

Actual performance targets, such as reconciliation accuracy percentages and transaction processing times, will be finalized before acceptance testing.

# 5. Target Users and User Roles

The platform will support three primary user categories.

## 5.1 Business Owner

The business owner has overall control and visibility over the business.

Responsibilities include:

- Viewing business performance.
- Monitoring revenue and payments.
- Managing employees.
- Managing services and pricing.
- Reviewing service records.
- Investigating discrepancies.
- Managing permissions.
- Reviewing audit history.
- Managing performance reports.
- Managing bonus configurations when introduced.

## 5.2 Administrator / Supervisor

An authorized administrator or supervisor supports daily business operations.

Depending on assigned permissions, responsibilities may include:

- Monitoring customer transactions.
- Managing employee assignments.
- Reviewing service execution.
- Verifying completed services.
- Handling authorized corrections.
- Reviewing discrepancies.
- Supervising front-desk activities.

## 5.3 Front Desk / Cashier

The front-desk employee manages customer transactions and billing.

Responsibilities include:

- Registering customers.
- Creating service orders.
- Selecting requested services.
- Assigning employees.
- Reviewing service progress.
- Preparing final invoices.
- Recording customer payments.
- Issuing receipts.

The cashier must not be able to silently remove employee-confirmed services or bypass reconciliation.

## 5.4 Service Employees

Employees use individual accounts to access a simplified staff interface.

Responsibilities include:

- Viewing assigned services.
- Identifying active customer transactions.
- Recording services performed.
- Recording participation in collaborative services.
- Submitting service completion records.
- Viewing their own service history and permitted performance information.

Employees must not be able to modify another employee's confirmed contributions without appropriate authorization.

## 5.5 Role and Permission Principles

The platform must support role-based access control.

Permissions must be configurable rather than relying exclusively on fixed role names.

The owner must be able to determine which users can:

- Create transactions.
- Assign services.
- Record service execution.
- Verify services.
- Cancel services.
- Modify invoices.
- Apply discounts.
- Record payments.
- Process refunds.
- View financial reports.
- Access employee performance information.
- Review audit logs.

Sensitive operations must require appropriate authorization.

# 6. Product Architecture and Core Business Concepts

The application will be organized around distinct but interconnected business records.

## 6.1 Customer Management

The system must maintain customer records containing relevant identification and contact information.

Customer management should support:

- New customer registration.
- Existing customer identification.
- Customer search.
- Contact information.
- Transaction history.
- Invoice history.
- Payment history.
- Outstanding balances.

The system should not require unnecessary customer information to create a transaction.

## 6.2 Service Catalogue

The service catalogue defines the services offered by the salon.

Each service should support:

- Service name.
- Description.
- Category.
- Configured price.
- Active or inactive status.
- Relevant service settings.

Service prices should be captured as historical snapshots when transactions are created.

Changes to catalogue pricing must not silently change previously created transactions.

## 6.3 Customer Service Orders

A service order represents the services a customer requests or agrees to receive.

It is the starting point of the salon's service delivery workflow.

The system must allow authorized users to:

- Create a service order.
- Select one or more services.
- Set the applicable service price.
- Assign one or more expected employees.
- Leave employees unassigned.
- Review the order.
- Track service progress.
- Add additional requested services through controlled procedures.

The service order must be separate from the final invoice.

## 6.4 Service Execution Records

Service execution records capture the services actually performed.

Employees must be able to identify an active transaction and record their contributions.

Each service execution record must be associated with the relevant customer transaction and service item.

The system must capture:

- Service performed.
- Employee responsible for the submission.
- Participating employees.
- Submission date and time.
- Relevant completion information.
- Verification status.
- Authorized corrections, where applicable.

The system must support both individual and collaborative service execution.

## 6.5 Collaborative Service Attribution

Multiple employees must be able to contribute to one service item.

Example:

A customer receives a braiding service priced at ₦20,000.

Three employees participate.

The system must record:

- One customer service item.
- One service charge of ₦20,000.
- Three employee contribution records.
- A relationship between all contributions and the same service item.

The number of participating employees must never automatically multiply the customer's charge.

The application should also support future configurable allocation of contribution value for performance and incentive calculations.

## 6.6 Service Reconciliation

Service reconciliation compares the original service order with actual service execution records.

The reconciliation process must identify:

- Requested services that were completed.
- Requested services that were not performed.
- Additional services performed.
- Services that are still in progress.
- Services awaiting verification.
- Cancelled services.
- Confirmed services requiring financial attention.

The application must prevent finalization while unresolved discrepancies remain, except where an authorized user approves an explicitly recorded exception.

## 6.7 Invoice Management

An invoice represents the customer's financial obligation.

The final invoice must be based on reconciled services and authorized financial adjustments.

The system must support:

- Invoice generation.
- Invoice numbering.
- Invoice item descriptions.
- Service quantities and prices where applicable.
- Authorized discounts.
- Additional approved charges where applicable.
- Tax configuration where legally required and enabled.
- Subtotals and totals.
- Amount paid.
- Outstanding balance.
- Invoice status.
- Invoice history.

The invoice must not become an editable substitute for the original service execution records.

## 6.8 Payment Management

Payment records must be separate from invoices.

The system must support:

- Full payment.
- Part payment.
- Multiple payments.
- Payment methods.
- Payment references.
- Payment dates.
- Payment allocation.
- Outstanding balances.
- Refunds.
- Payment reversals through authorized procedures.

A payment reversal must not erase the original payment record.

## 6.9 Receipt Management

The system must generate receipts for recorded payments.

Receipts should contain:

- Business information.
- Receipt number.
- Customer information.
- Related invoice reference.
- Payment amount.
- Payment method.
- Payment date.
- Outstanding balance where applicable.

Receipts must be printable and shareable.

Supported delivery options should include:

- PDF download.
- Printed copies.
- WhatsApp sharing.
- Email delivery.

Where external delivery integrations are not configured, users should still be able to download or share the generated document manually.

## 6.10 Audit Management

The system must maintain traceable records of important business operations.

Audit records should capture:

- User responsible.
- Action performed.
- Relevant business record.
- Previous and updated values where applicable.
- Date and time.
- Reason for sensitive changes.
- Authorization information where applicable.

Audit records must not be silently editable or removable by ordinary users.

# 7. Core Business Workflows

## 7.1 Customer Registration and Service Order Creation

1. A customer arrives at the salon.
2. The front-desk employee identifies or registers the customer.
3. A new service order is created.
4. The requested services are selected.
5. The system retrieves their configured prices.
6. Expected employees are assigned where known.
7. Unassigned services remain available for subsequent assignment.
8. The service order is saved.
9. Employees can access relevant assigned transactions.

## 7.2 Service Execution

1. An employee accesses their staff dashboard.
2. The employee identifies an active customer transaction.
3. The employee selects the relevant service.
4. The employee records their participation.
5. Additional participating employees can be identified.
6. The service execution record is submitted.
7. The system records the submission and timestamp.
8. The service enters the appropriate verification or completion workflow.

For collaborative services, the system must recognize that multiple contributions belong to one customer service item.

## 7.3 Service Verification

1. Submitted service records are made available for review.
2. The responsible authorized user reviews the records.
3. Completion is verified or rejected.
4. Rejected records require an explanation and appropriate correction.
5. Verified records become protected from unauthorized modification.
6. All changes are recorded in the audit history.

## 7.4 Service Reconciliation

1. The system retrieves the original service order.
2. It retrieves all relevant service execution records.
3. It compares requested and actual services.
4. Differences are identified.
5. Outstanding discrepancies are displayed.
6. Authorized users resolve or approve exceptions.
7. The system produces a reconciled transaction.
8. The transaction becomes eligible for final invoicing.

## 7.5 Invoice and Payment Processing

1. The cashier opens an eligible reconciled transaction.
2. The system prepares the final invoice.
3. Authorized discounts and adjustments are applied.
4. The invoice is finalized.
5. The customer makes payment.
6. The cashier records the payment.
7. The system updates the invoice balance.
8. The system generates a receipt.
9. The transaction's financial status is updated.

## 7.6 Transaction Closure

A transaction should only be closed when its operational and financial requirements have been satisfied or an authorized exception has been recorded.

The system must retain the complete history of the transaction after closure.

# 8. Functional Requirements

## 8.1 Customer Management

The application must provide:

- Customer registration.
- Customer profile management.
- Customer search.
- Duplicate identification support.
- Customer transaction history.
- Invoice and payment history.

## 8.2 Service Catalogue Management

The application must provide:

- Service categories.
- Service creation and editing.
- Service pricing.
- Service activation and deactivation.
- Historical price preservation.
- Service search and selection.

## 8.3 Employee Management

The application must provide:

- Employee registration.
- Individual login accounts.
- Employee profiles.
- Employee activation and deactivation.
- Employee assignment.
- Role and permission management.
- Employee service history.

## 8.4 Service Order Management

The application must provide:

- Service order creation.
- Multiple service items.
- Employee assignments.
- Unassigned service items.
- Service order status tracking.
- Controlled addition and cancellation of items.
- Transaction history.

## 8.5 Service Execution Management

The application must provide:

- Employee service submissions.
- Individual service attribution.
- Multiple employee participation.
- Service completion information.
- Submission timestamps.
- Verification workflows.
- Protected confirmed records.
- Authorized correction procedures.

## 8.6 Reconciliation Management

The application must provide:

- Requested-versus-performed comparison.
- Additional service identification.
- Missing service identification.
- Cancelled service identification.
- Unverified service identification.
- Discrepancy resolution.
- Authorized exception handling.
- Reconciliation history.

## 8.7 Invoice Management

The application must provide:

- Invoice generation.
- Final invoice calculation.
- Invoice numbering.
- Invoice printing.
- Invoice PDF generation.
- Authorized discounts.
- Invoice status tracking.
- Invoice history.

## 8.8 Payment Management

The application must provide:

- Payment recording.
- Multiple payment support.
- Part payments.
- Payment methods.
- Payment references.
- Payment allocation.
- Outstanding balances.
- Refund and reversal procedures.
- Payment history.

## 8.9 Receipt Management

The application must provide:

- Automatic receipt generation.
- Unique receipt references.
- Receipt printing.
- PDF download.
- WhatsApp sharing.
- Email delivery.

## 8.10 Staff Performance Reporting

The application must provide reports showing:

- Total services performed.
- Services by category.
- Number of customers served.
- Individual contributions.
- Collaborative contributions.
- Value of services contributed to.
- Daily performance.
- Weekly performance.
- Monthly performance.
- Custom reporting periods.

Reports must distinguish service contribution value from actual collected business revenue.

## 8.11 Business Owner Dashboard

The dashboard must provide visibility into:

- Total transactions.
- Active service orders.
- Services performed.
- Services awaiting verification.
- Outstanding discrepancies.
- Total invoiced amount.
- Total collected payments.
- Outstanding balances.
- Daily, weekly, monthly, and yearly revenue.
- Employee performance.
- Recent activities.
- Important operational exceptions.

## 8.12 Audit and Activity Monitoring

The application must provide:

- Transaction audit history.
- Service execution history.
- Invoice modification history.
- Payment history.
- User activity records.
- Authorized correction history.
- Discrepancy history.

# 9. Staff Performance and Bonus Management

## 9.1 Performance Measurement

The platform must maintain employee contribution records independently of customer billing.

Performance reports should distinguish:

- Number of services performed.
- Number of customers served.
- Value of services contributed to.
- Individual service contributions.
- Team-based service contributions.

## 9.2 Contribution Allocation

The platform should be capable of supporting configurable contribution allocation.

Examples include:

- Equal allocation among participating employees.
- Primary employee receiving a higher allocation.
- Percentage-based allocation.
- Fixed-value allocation.
- Owner-approved manual allocation.

Contribution allocations must not modify the original customer service price or inflate actual business revenue.

## 9.3 Bonus Management

A configurable bonus management module is planned.

Potential bonus criteria include:

- Total contribution value.
- Number of services.
- Number of customers.
- Individual performance targets.
- Collaborative performance.
- Specific milestones.

Bonus calculations must be transparent and traceable.

## 9.4 Delivery Priority

Basic employee performance reporting is part of the initial release.

A complete configurable bonus engine is deferred to a subsequent release unless product review determines that a basic bonus capability is essential for the first deployment.

# 10. Business Intelligence and Reporting

The platform should provide reports that help management understand both business performance and operational efficiency.

Initial reports should include:

### Financial reports

- Daily revenue.
- Weekly revenue.
- Monthly revenue.
- Yearly revenue.
- Total invoiced amount.
- Total collected amount.
- Outstanding balances.
- Payment method breakdown.

### Operational reports

- Services requested.
- Services performed.
- Services cancelled.
- Services awaiting verification.
- Service discrepancies.
- Transaction completion statistics.

### Employee reports

- Employee service counts.
- Employee contribution values.
- Customer counts.
- Individual and collaborative performance.
- Performance trends.

Reports should support filtering by date and relevant employee or service categories.

# 11. Application Interfaces and User Experience

## 11.1 Business Owner Interface

A comprehensive dashboard designed for business oversight and management.

It should prioritize:

- Financial visibility.
- Operational exceptions.
- Employee performance.
- Transaction management.
- Administrative controls.

## 11.2 Front Desk / Cashier Interface

A transaction-oriented interface designed for speed and accuracy.

It should prioritize:

- Customer identification.
- Service order creation.
- Employee assignments.
- Service status visibility.
- Invoice reconciliation.
- Payment recording.
- Receipt generation.

## 11.3 Staff Interface

A simplified, mobile-first interface designed for employees.

It should prioritize:

- Easy authentication.
- Assigned customer transactions.
- Service selection.
- Recording completed services.
- Recording collaborators.
- Viewing submission status.
- Viewing permitted performance information.

## 11.4 User Experience Principles

The application must:

- Be easy to learn.
- Minimize unnecessary steps.
- Work on mobile devices.
- Use clear labels and understandable terminology.
- Provide meaningful success and error feedback.
- Clearly distinguish requested, completed, verified, and cancelled services.
- Protect users from accidental duplicate submissions.
- Provide accessible and consistent navigation.

# 12. Platform and Product Requirements

## 12.1 Application Type

The application must be a responsive web application with Progressive Web App capabilities.

## 12.2 Supported Devices

The application should support:

- Android smartphones.
- iPhones.
- Tablets.
- Desktop computers.
- Modern web browsers.

## 12.3 PWA Requirements

The application should support:

- Installation on supported devices.
- Home-screen application access.
- Application icons.
- Responsive layouts.
- Automatic application updates.
- Push notifications where supported and configured.

## 12.4 Connectivity

The initial release should prioritize online operation.

Sensitive financial operations and transaction finalization must require reliable server communication.

Limited offline activities may be introduced in subsequent releases with controlled synchronization and duplicate-submission prevention.

## 12.5 Data Protection

Private customer, employee, payment, and transaction information must not be exposed through public application caches.

Offline storage, where introduced, must follow appropriate data protection and synchronization controls.

# 13. Security, Accountability and Business Integrity

Security is a fundamental product requirement.

The application must provide:

1. Individual user accounts.
2. Role-based access control.
3. Permission-controlled operations.
4. Server-side authorization.
5. Protected confirmed service records.
6. Authorized invoice modifications.
7. Traceable payment modifications.
8. Immutable audit history for critical actions.
9. Secure authentication.
10. Controlled access to customer and employee information.

The system must not rely on frontend restrictions as its primary security mechanism.

Critical business rules must be enforced by the backend.

No user should be able to silently erase evidence of a previously submitted or confirmed business activity.

# 14. Business Configuration

The application should support configurable business information, including:

- Business name.
- Business logo.
- Business contact information.
- Business address.
- Currency.
- Service categories.
- Service catalogue.
- Pricing.
- Receipt information.
- Invoice settings.
- User roles and permissions.

Business-specific settings should be maintained centrally rather than scattered throughout the application.

# 15. Scope Definition

## 15.1 MVP — First Release

The first release must deliver a functional salon management solution addressing the central problems identified in this document.

| Module | MVP Requirement |
|---|---|
| Customer management | Included |
| Service catalogue | Included |
| Employee management | Included |
| User authentication | Included |
| Role and permission management | Included |
| Service order management | Included |
| Employee service recording | Included |
| Collaborative service attribution | Included |
| Service verification | Included |
| Service reconciliation | Included |
| Invoice generation | Included |
| Payment management | Included |
| Receipt generation | Included |
| Staff performance reports | Included |
| Owner dashboard | Included |
| Audit trail | Included |
| PWA support | Included |
| Basic business settings | Included |

## 15.2 Subsequent Release

The following features should be considered after the MVP is validated:

- Advanced bonus and incentive engine.
- Employee attendance management.
- Employee scheduling.
- Push notifications.
- Advanced analytics.
- Automated WhatsApp delivery.
- Automated email delivery.
- Limited offline staff submissions.
- Refund and financial exception reporting enhancements.

## 15.3 Long-Term Expansion

The following capabilities belong to the platform's longer-term roadmap:

- Multiple business branches.
- Multiple businesses under one account.
- Cleaning service management.
- Inventory and stock management.
- Customer relationship management.
- Expense management.
- Profitability analysis.
- Payroll and commission management.
- Advanced business intelligence.
- Dedicated mobile applications.
- Additional service-business modules.

These features must not unnecessarily complicate the initial salon implementation.

# 16. Non-Functional Product Requirements

The application must satisfy the following product-level quality expectations.

## 16.1 Reliability

Business transactions must be processed consistently.

The system must prevent duplicate financial operations and preserve transaction integrity.

## 16.2 Security

Sensitive business information must be protected through authentication, authorization, secure data handling, and auditability.

## 16.3 Performance

Common activities such as customer lookup, service selection, transaction review, and service submission should feel responsive under normal operating conditions.

Specific performance thresholds will be defined in the SRS.

## 16.4 Scalability

The product must support future growth in:

- Customers.
- Employees.
- Transactions.
- Service categories.
- Branches.
- Business operations.

## 16.5 Maintainability

The application must be modular, documented, testable, and easy to extend.

## 16.6 Portability

The application should support deployment on affordable hosting infrastructure and permit future migration to VPS or cloud environments without requiring a complete rebuild.

## 16.7 Data Backup and Recovery

The system must support reliable database backups and documented restoration procedures.

# 17. Recommended Technical Direction

The following reflects the preferred technical direction expressed in the project brief. Detailed technical specifications belong in the SRS.

| Component | Preferred Direction |
|---|---|
| Backend | Laravel / PHP |
| Frontend | Next.js / TypeScript |
| Database | MySQL |
| Application | Responsive PWA |
| Backend architecture | Modular monolith |
| Backend interface | Reusable API |
| Frontend deployment | Independently deployable |
| Initial hosting consideration | cPanel-compatible |
| Source control | GitHub |
| Database management | Version-controlled migrations |
| Testing | Automated tests |
| Documentation | Technical and deployment documentation |

Laravel must remain responsible for critical business logic, financial calculations, authorization, and transaction validation.

The frontend must not be treated as a trusted source of business decisions.

The architecture should allow future migration from shared hosting to VPS or cloud infrastructure.

The final implementation architecture, infrastructure requirements, deployment topology, and technology-specific decisions will be documented in the SRS.

# 18. Dependencies and Assumptions

The product currently assumes:

1. The salon has identifiable employees.
2. Each employee can access the application through an individual account.
3. The salon maintains a configurable catalogue of services.
4. The business owner has authority to manage employees and permissions.
5. Employees can submit their service activities through smartphones or other supported devices.
6. The business has an internet connection for critical operations.
7. The initial operating currency is NGN.
8. The initial implementation serves the salon before other business types are introduced.

External integrations such as WhatsApp messaging and email delivery may require additional configuration and service providers.

# 19. Risks and Mitigation

| Risk | Product Mitigation |
|---|---|
| Employees fail to record services | Simple mobile interface and operational monitoring |
| Employees submit inaccurate records | Verification and audit controls |
| Cashier manipulates invoices | Independent service records and reconciliation |
| Duplicate service submissions | Duplicate detection and controlled submission |
| Collaborative services inflate performance values | Separate service value and contribution allocation |
| Unauthorized cancellations | Permission controls, authorization and audit records |
| Incorrect payment records | Traceable payment history and reversal procedures |
| Poor internet connectivity | Reliable online-first design and future controlled offline support |
| Product becomes unnecessarily complicated | Modular delivery and phased scope |
| Future business expansion requires major redesign | Reusable business foundations and modular business workflows |

# 20. Product Acceptance Criteria

The MVP will be considered ready for business acceptance when the following scenarios work correctly.

### Scenario 1: Normal service transaction

A cashier creates a customer and records a requested service.

The employee performs the service, records the activity, and the system allows reconciliation, invoice generation, payment recording, and receipt generation.

### Scenario 2: Collaborative service

Three employees participate in one ₦20,000 service.

The system records all three employees as contributors while maintaining one ₦20,000 customer service charge.

### Scenario 3: Unauthorized invoice reduction

A cashier attempts to remove a confirmed service from a customer's transaction.

The system prevents the unauthorized action and records the attempted or rejected operation where appropriate.

### Scenario 4: Authorized service correction

An authorized supervisor approves the cancellation or correction of a confirmed service.

The system records the authorization, reason, affected record, and history of the change.

### Scenario 5: Unrecorded service discrepancy

A service is requested and performed, but the service execution record has not yet been submitted.

The system identifies the discrepancy and prevents ordinary finalization until it is resolved or an authorized exception is recorded.

### Scenario 6: Partial payment

A customer receives an invoice of ₦30,000 and pays ₦20,000.

The system records the payment, generates a receipt, and displays the remaining ₦10,000 balance.

### Scenario 7: Employee performance

The owner selects a reporting period and reviews the services, customers, and contribution value associated with an employee.

### Scenario 8: Audit history

The owner reviews a transaction and can trace its important activities, including service submissions, approvals, invoice changes, and payments.

### Scenario 9: Historical pricing

The salon changes the price of a service.

Previously created transactions retain their original recorded service prices.

### Scenario 10: User permission enforcement

An employee attempts to access an administrative operation outside their assigned permissions.

The system denies access.

# 21. Product Roadmap

The product should be delivered incrementally.

| Phase | Focus | Expected Outcome |
|---|---|---|
| Phase 1 | Product foundation | Business setup, authentication, users, permissions and service catalogue |
| Phase 2 | Customer operations | Customer management and service orders |
| Phase 3 | Service execution | Employee assignments, individual and collaborative service recording |
| Phase 4 | Accountability | Verification, reconciliation, authorization and audit history |
| Phase 5 | Financial operations | Invoices, payments, balances and receipts |
| Phase 6 | Management intelligence | Owner dashboard and employee performance reporting |
| Phase 7 | Business validation | End-to-end testing, deployment and operational acceptance |

Each phase must produce a tangible, working increment.

Development should proceed through documented reviews and approvals before subsequent phases begin.

# 22. Future Product Expansion Strategy

The product should evolve from salon management into a broader business operations platform.

The expansion strategy should distinguish shared platform capabilities from business-specific operational modules.

### Shared platform capabilities

- Business configuration.
- Users and permissions.
- Customers.
- Employees.
- Service catalogues.
- Transaction records.
- Payments.
- Invoicing.
- Audit history.
- Reporting.

### Business-specific capabilities

Salon management may require employee service attribution and collaborative service execution.

Cleaning services may require work orders, service locations, cleaning teams, scheduled jobs, and completion evidence.

Other business categories may introduce their own operational workflows.

The system should accommodate these differences without forcing every business into one rigid workflow.

# 23. Outstanding Product Decisions

The following matters require confirmation before the PRD is finalized and the SRS is prepared.

1. **Business structure:** Will the first deployment support one salon business only, or should multiple businesses under one owner be available from day one?

2. **Employee service verification:** Should an employee's service submission become confirmed automatically, or must a supervisor verify every service?

3. **Collaborative attribution:** Should the system initially record only who participated, or should it also support percentages or weighted contribution allocation from the first release?

4. **Additional services:** If a customer requests an additional service after the initial order, who is authorized to add and approve it?

5. **Service cancellation:** Who should be authorized to cancel a service after an employee has submitted or confirmed its completion?

6. **Invoice finalization:** Should only the owner/supervisor finalize invoices, or can an authorized cashier do so after reconciliation?

7. **Discounts:** Should discounts be available in the first release, and what authorization rules should apply?

8. **Taxes:** Should the initial version support configurable taxes, or should tax calculations be deferred until the business requirements are confirmed?

9. **Payments:** Should the first release record cash, bank transfers, POS and other manually recorded payments, with payment gateway integration deferred?

10. **Bonus management:** Should the MVP provide only performance reports, or should it also calculate basic employee bonuses?

11. **Customer identification:** Should customer registration be mandatory for every transaction, or should the system support walk-in/guest transactions?

12. **Deployment:** Should cPanel-only deployment be a mandatory acceptance requirement, or is it sufficient that the application can run on affordable cPanel-compatible infrastructure while supporting VPS migration?

13. **Existing records:** Is migration of existing customers, services, or Google Sheets records required for the first deployment?

14. **Notifications:** Should employees receive notifications when services are assigned, changed, or awaiting their attention?

These decisions will influence the detailed workflows and technical specifications.

# 24. PRD Approval and SRS Handoff

This PRD establishes the product vision, business objectives, target users, product scope, major workflows, functional capabilities, and delivery roadmap.

Upon approval, the next document will be the Software Requirements Specification (SRS).

The SRS will translate the approved PRD into implementation-ready requirements, including:

- Detailed functional requirements and identifiers.
- User roles and permission matrices.
- Business rules and validation requirements.
- Service and transaction state machines.
- Data entities and relationships.
- Database requirements.
- API contracts.
- Authentication and authorization requirements.
- Audit and security specifications.
- Financial calculations.
- Reporting definitions.
- Error handling.
- Integration requirements.
- Deployment and infrastructure specifications.
- Testing and acceptance requirements.

The SRS must remain consistent with the approved PRD. It must not introduce conflicting business rules or silently expand the approved MVP scope.

Any additional requirements identified during SRS preparation must be documented, explained, and submitted for product approval before being incorporated.

---

**END OF PRODUCT REQUIREMENTS DOCUMENT**

**Document Status:** Draft for Review and Approval.

**Next Deliverable:** Software Requirements Specification (SRS), to be prepared after PRD review and approval.