Business Overview and Software Development Requirements

1. Background

I operate a salon business in Nigeria, alongside other businesses, including cleaning services.

Currently, I use MyCantant [https://www.mycantant.com/invoicing] to generate invoices and receipts and to configure the services offered by my salon.

However, as the business grows, I have encountered some operational and management challenges, particularly in tracking staff performance, monitoring revenue, and ensuring accountability in the invoicing process.

I want to develop a software solution that will help me manage these challenges, improve business operations, and provide a foundation that I can continuously expand as the business grows.

2. Current Business Challenges

There are several issues I currently face with managing my salon:

A. Tracking Staff Performance

Different staff members provide different services, including braiding, pedicures, manicures, hair treatments, and other salon services.

Currently, I cannot easily track:

The specific services each staff member performs daily.
The number of customers served by each staff member.
The total value of services performed by each staff member.
How much revenue each staff member contributes to the business.
Staff performance over a specific period.
This information is important because I want to be able to reward hardworking and productive employees through bonuses and other incentives.

B. Preventing Invoice Manipulation and Revenue Leakage

The person responsible for generating invoices currently has the ability to add or remove services from an invoice.

This creates an opportunity for dishonest practices. For example, an employee could remove some of the services actually provided to a customer, generate an invoice with fewer services, collect a lower payment from the customer, and potentially pocket the difference.

I need a system that ensures all services performed by staff are properly recorded and cannot be secretly removed or manipulated by the person generating the final invoice.

C. Handling Services Performed by Multiple Staff Members

Some salon services are performed by one staff member, while others require two or more staff members to work together.

For example:

One staff member may complete a customer's braiding service.
Two or more staff members may work together to complete the same braiding service.
The important thing is that the price of the service remains the same, regardless of how many staff members work on it.

However, I need the system to record all the staff members who contributed to that particular service so that their individual contributions can be tracked.

This will help me understand who performed each service and make fair decisions when calculating staff bonuses and evaluating performance.

3. Current Temporary Solution

To address some of these challenges, I created a Google Form where staff can submit information about the services they perform.

The responses are automatically saved in a Google Spreadsheet.

Here is the spreadsheet:

Google Spreadsheet – Staff Service Records
https://docs.google.com/spreadsheets/d/1MDgqA6SunAX_Q7GRz9zc16l4pkePXkd113YGwLjJHl8/edit?gid=1145415033#gid=1145415033.

Although this has helped me collect some information, I believe a proper software solution would provide better control, automation, reporting, and accountability.

4. My Proposed Software Solution

I want to develop a web-based business management system that allows me to manage customer invoices, staff activities, services rendered, payments, and business performance.

One of the key ideas I have is to introduce a workflow where the invoice creation process and the recording of services actually performed are handled separately.

A. Customer Registration and Initial Invoice Creation

When a customer walks into the salon:

An authorized staff member creates a new customer transaction.
The staff member selects the services the customer intends to receive.
The system automatically adds the configured prices of those services to the invoice.
For each service, the staff member can assign one or more employees who are expected to perform it.
Alternatively, the service can be left unassigned if the staff member who will perform it is not yet known.
The system should then create an initial invoice or service order for that customer.

B. Staff Recording the Services They Actually Perform

Each employee should have access to the system through their own account or staff dashboard.

When an employee performs a service for a customer, they should be able to:

Log in to their account.
Identify the customer or select the customer's active transaction.
Record the service they have actually performed.
Indicate whether they worked alone or alongside other employees.
Submit the service record.
For services involving multiple employees, each employee should be able to record their contribution, or the service should be recorded once with all participating employees identified.

The system must recognize that multiple employees can contribute to one service without increasing the customer's service charge.

For example:

A customer comes in for braiding, which costs ₦20,000.

Staff A works on the braiding.
Staff B assists Staff A.
Staff C also contributes to completing the braid.
The customer should still be charged ₦20,000, not ₦60,000.

However, the system should record that Staff A, Staff B, and Staff C all participated in providing the ₦20,000 service.

This information should be available for staff performance tracking and bonus calculations.

C. Automatic Invoice Reconciliation and Protection Against Manipulation

This is one of the most important features of the proposed system.

The person responsible for creating the initial invoice should not have the ability to secretly remove services that employees have confirmed they performed.

The system should:

Automatically add staff-confirmed services to the customer's transaction.
Compare the services initially requested with the services actually performed.
Highlight any differences between the initial service order and the services recorded by employees.
Prevent unauthorized deletion or alteration of staff-confirmed services.
Maintain an audit trail of changes, including who made them and when.
Require appropriate authorization before any confirmed service can be cancelled, edited, or removed.
At the end of the customer's visit, the system should reconcile all recorded services and calculate the final amount payable.

The final invoice should accurately reflect the services provided to the customer.

D. Payment and Receipt Generation

Once all services have been completed and confirmed:

The system generates the final invoice.
The customer makes payment.
The payment is recorded against the transaction.
The system generates an official receipt.
The receipt can be printed or shared digitally.
Customers should be able to receive their invoices and receipts through different channels, including:

WhatsApp (as an image or PDF).
Email.
Direct PDF download.
Printed copies.
5. Staff Performance and Bonus Management

I want the software to provide detailed reports showing individual and collective staff performance.

For example, I should be able to see:

The total number of services performed by each employee.
The different types of services performed by each employee.
The total value of services each employee contributed to.
The number of customers served.
The services performed individually versus collaboratively.
Daily, weekly, and monthly performance reports.
The total revenue generated by the salon.
The contribution of each employee to the overall business.
I also want the flexibility to introduce a bonus system.

For example, I may decide to reward employees based on:

The total value of services they contributed to.
The number of services they completed.
Their individual performance.
Their contribution to team-based services.
Specific targets or milestones.
The system should be flexible enough to allow me to configure bonus rules as the business evolves.

6. Business Owner and Administrator Dashboard

As the business owner, I need an administrative dashboard that gives me a complete overview of the business.

From this dashboard, I should be able to:

View all customer transactions.
Monitor services requested and services actually performed.
Track staff activities in real time.
View daily, weekly, monthly, and yearly revenue.
Monitor payments and outstanding balances.
Identify discrepancies between requested and completed services.
View staff performance reports.
Calculate and manage staff bonuses.
Manage employees and their access permissions.
Add, edit, and manage services and their prices.
Review transaction histories and audit logs.
I should also have full control over user roles and permissions so that employees can only access the features relevant to their responsibilities.

7. Long-Term Vision and Scalability

Although my immediate need is to solve the operational problems in my salon, I do not want to build software that will only work for the current business.

I also operate other businesses, including cleaning services, and I would like the software to eventually support multiple business types.

My goal is to build a scalable business management platform that can grow over time.

For example, future versions could support:

Multiple business branches and locations.
Different businesses under one account.
Cleaning service management.
Employee attendance and work schedules.
Inventory and stock management.
Customer relationship management.
Expenses and profit tracking.
Payroll and commission management.
Advanced financial and operational reports.
Mobile applications for staff and management.
The system should therefore be designed with scalability and future expansion in mind, without making the initial version unnecessarily complicated.

8. Immediate Development Priorities

I want to approach this project in phases, starting with the features that solve my most pressing business challenges.

The first version (MVP) should prioritize:

Customer and transaction management: Create customer records and initiate service orders.
Service management: Configure services, prices, and categories.
Staff accounts: Give each employee an individual login and dashboard.
Staff service recording: Allow employees to record the services they actually perform.
Multiple staff attribution: Allow multiple employees to be credited for one service without duplicating its price.
Invoice reconciliation: Automatically capture confirmed services and prevent unauthorized removal or manipulation.
Payment management: Record customer payments and outstanding balances.
Invoice and receipt generation: Generate, print, download, and share invoices and receipts.
Staff performance reporting: Track each employee's daily and periodic contributions.
Audit trail: Maintain a record of transactions and changes for accountability.
Owner dashboard: Give me visibility into revenue, staff activities, and business performance.
9. What I Want From This Project

I want to develop a reliable, secure, and easy-to-use software solution that gives me better control over my business operations.

My immediate objective is to eliminate revenue leakage, improve staff accountability, accurately track the services performed by every employee, and make it easier to evaluate and reward staff based on their contributions.

I also want the software architecture to allow me to introduce additional features and support other businesses in the future.

I would like the development process to start with understanding my current challenges, refining the proposed workflow, identifying any gaps in my ideas, and recommending the best system architecture and implementation approach before proceeding with development.



My additional recommendation

There are three important things I would recommend adding to your original idea before development begins:

1. Use a service order rather than relying only on an invoice. An invoice represents what the customer is expected to pay, while a service order tracks what was actually requested, assigned, performed, and completed. Keeping these separate will make your system much harder to manipulate.

2. Separate service value from staff contribution. If three employees work on a ₦20,000 braid, the business records ₦20,000 in revenue, but all three employees receive a contribution record linked to that same ₦20,000 service. Your bonus rules can then determine how that value is shared or credited without inflating revenue.

3. Make confirmed staff records immutable without authorization. Staff-confirmed services should not simply disappear when someone edits an invoice. Any correction should require authorization and leave a permanent audit record.

My biggest recommendation: Build this as a proper salon management system first, but design its foundation as a multi-business management platform. That way, you won't need to rebuild everything when you eventually want to manage your cleaning business using the same software.



I strongly recommend making the application a Progressive Web App (PWA) from the beginning.
Since multiple employees will be using the system, a PWA is a practical option because staff can access itfrom their smartphones without having to download it from the Google Play Store or Apple App Store.
I would also recommend choosing the technology stack based on three priorities:
Ease of use: Staff should be able to use the application easily, even if they are not particularlytechnology-savvy.
Cost efficiency: Minimize development, hosting, maintenance, and recurring subscription costs.
Scalability: Build it in a way that allows you to add more branches, businesses, employees, and featureswithout rebuilding the application.
1. Recommended application approach
The application should have three interfaces:
Business owner/admin interface: For managing the business, employees, services, transactions,reports, and settings.
Front desk/cashier interface: For registering customers, creating service orders, managing invoices,receiving payments, and printing receipts.
Staff interface: A simplified mobile-friendly interface where employees can view assigned customersand record the services they perform.
All three interfaces should connect to the same central database, with permissions controlling what eachuser can access.
PWA functionality
The application should support:
Installation on Android phones as an app.
Installation on iPhones through the browser's Add to Home Screen option.
A home-screen icon and app-like interface.
Full-screen experience without the usual browser address bar when installed.
Push notifications where supported.
Automatic updates when a new version is deployed.
A responsive interface that works on phones, tablets, and computers.
One important consideration: sensitive business transactions should require an active internet connection.We can potentially support limited offline functionality for selected staff activities, with propersynchronization and duplicate prevention when connectivity returns.
2. Recommended programming languages and technologies
For this project, I would recommend two possible technology approaches.


Option A: Laravel + PHP
My recommendation
Best for affordability and cPanel-only deployment
Backend: PHP (Laravel framework).
Frontend: Laravel Blade + Livewire + Alpine.js.
Styling: Tailwind CSS.
Database: MySQL or MariaDB.
PWA: Web App Manifest + Service Worker.
API: Laravel API for future mobile applications and integrations.
Advantages
Works naturally with traditional PHP/MySQL hosting.
Can be deployed on suitable cPanel hosting without requiring Vercel.
One integrated application reduces development and maintenance complexity.
Well suited to invoices, staff management, reporting, permissions, and business workflows.
Additional requirements to include in the software development brief
I would add the following section to your original project requirements:
Technology, PWA and Deployment Requirements
The application must be developed as a responsive Progressive Web App (PWA), allowing employees andadministrators to access it from smartphones, tablets, and desktop computers.
The application should be installable on Android and iOS devices without requiring publication on theGoogle Play Store or Apple App Store.
The development team should prioritize cost efficiency and recommend technologies that minimize hostingand maintenance expenses.
My preferred technology and deployment approach is:
Backend: PHP using Laravel.
Frontend: Laravel Livewire, Alpine.js and Tailwind CSS.
Database: MySQL.
Application type: Progressive Web App.
Hosting: cPanel shared hosting, where the hosting specifications permit.
Deployment: cPanel-only deployment should be supported.
Source code: Maintain the complete application source code in a Git repository.
Architecture: Modular and scalable to support multiple businesses and branches in the future.
Vercel may be considered as an alternative deployment option if there is a compelling technical orperformance advantage, but it should not be a mandatory dependency.
The application must be designed so that moving from cPanel to a VPS or cloud hosting provider in thefuture will not require rebuilding the software.
Development should prioritize affordability, security, maintainability, and ease of use.

Laravel + Next.js + TypeScript + MySQL + PWA

I would choose this architecture over Laravel + Livewire if you are comfortable with the additional initial development cost and intend to keep expanding the software.

I would also make these three requirements non-negotiable:

Laravel must own all critical business logic, financial calculations, permissions, and transaction validation. The frontend must never be trusted to enforce security.

The Next.js frontend must be independently deployable, and the Laravel API must be reusable by future applications.

The entire project must be maintained in GitHub, with proper documentation, database migrations, automated tests, and deployment instructions.

I recommend Laravel on cPanel + Next.js on Vercel + MySQL on cPanel, with a shared GitHub repository or coordinated repositories for source control.

Important things I would insist on before development
Build the application as a modular monolith, not microservices. It is easier and cheaper to maintain.

Use a single database initially.

Keep the frontend and backend independently deployable.

Use database migrations so the application can move between hosting providers.

Include automated tests for financial calculations, service attribution, and permissions.

Keep production and development environments separate.

Implement database backups and test database restoration.

Never cache private customer, payment, or staff records in the PWA service worker. Cache application assets instead, and use IndexedDB for carefully controlled offline submissions.