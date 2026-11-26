# Roles and permissions

The four default roles every salon starts with (SRS §3.3). They are created by `php artisan app:install`, and the owner can change all of them in **Admin > Roles** except the Owner role, whose permissions are fixed.

Authorisation is always decided by permission, never by the name of a role. A person can hold several roles; they can do anything any of their roles allows, and the highest discount limit applies.

This page is generated from `backend/app/Domain/Identity/Permissions/PermissionCatalogue.php`. A test checks the catalogue against the SRS table.

## Default discount limits

| Role | Limit without approval |
|---|---|
| Owner | No limit |
| Supervisor | Up to 30% |
| Front desk / Cashier | Up to 10%, and at most ₦5,000 per bill |
| Employee | No discounts |

## Sign-out after inactivity

- Anyone with a permission beyond the Employee set: **30 minutes** (owner can set 5 to 120).
- Employee-only staff on their own phones: **7 days** (owner can set 1 to 30).

## Default permissions

✓ means the role has the permission when the salon is installed.

| Area | What it allows | Permission | Owner | Supervisor | Cashier | Employee |
|---|---|---|:-:|:-:|:-:|:-:|
| Staff and access | Create, edit and disable staff accounts | `user.manage` | ✓ |  |  |  |
| Staff and access | Manage roles, permissions and discount limits | `role.manage` | ✓ |  |  |  |
| Settings | Change business settings, tax, numbering and time limits | `settings.manage` | ✓ |  |  |  |
| Customers | Search and view customers | `customer.view` | ✓ | ✓ | ✓ |  |
| Customers | See customer phone numbers and emails | `customer.view_contact` | ✓ | ✓ | ✓ |  |
| Customers | Add and edit customers | `customer.manage` | ✓ | ✓ | ✓ |  |
| Customers | Link a walk-in visit to a customer | `customer.associate` | ✓ | ✓ | ✓ |  |
| Services and products | Manage services, products, prices and credit splits | `catalogue.manage` | ✓ |  |  |  |
| Visits | Start a visit | `order.create` | ✓ | ✓ | ✓ |  |
| Visits | See today's open visits (no prices or contact details) | `order.view_open` | ✓ | ✓ | ✓ | ✓ |
| Visits | See all visits with prices | `order.view_all` | ✓ | ✓ | ✓ |  |
| Visits | Add services or products to a visit | `order.item.add` | ✓ | ✓ | ✓ |  |
| Visits | Remove a service nobody has started | `order.item.cancel_unexecuted` | ✓ | ✓ | ✓ |  |
| Visits | Change a price on a visit, with a reason | `order.item.price_override` | ✓ | ✓ |  |  |
| Visits | Assign staff to services | `order.assign` | ✓ | ✓ | ✓ |  |
| Visits | Cancel a whole visit | `order.cancel` | ✓ | ✓ |  |  |
| Work done | Record work done | `execution.submit` | ✓ | ✓ |  | ✓ |
| Work done | Join work someone else recorded | `execution.join` |  |  |  | ✓ |
| Work done | Dispute work they are named on | `execution.dispute` | ✓ | ✓ |  | ✓ |
| Work done | Correct or cancel recorded work and resolve disputes | `execution.correct` | ✓ | ✓ |  |  |
| Work done | Record work after the late window or after the bill is final | `execution.submit_late` | ✓ | ✓ |  |  |
| Work done | Change how credit for shared work is split | `allocation.override` | ✓ | ✓ |  |  |
| Approvals | Ask to add an extra service | `additional.request` |  |  |  | ✓ |
| Approvals | Approve or reject extra service requests | `additional.decide` | ✓ | ✓ |  |  |
| Bills | See mismatches between work done and the bill | `reconciliation.view` | ✓ | ✓ | ✓ |  |
| Bills | View bills | `invoice.view` | ✓ | ✓ | ✓ |  |
| Bills | Make a bill final | `invoice.finalise` | ✓ | ✓ | ✓ |  |
| Bills | Make a bill final even when checks fail | `invoice.finalise_with_exception` | ✓ | ✓ |  |  |
| Bills | Correct a final bill with a new revision | `invoice.amend` | ✓ | ✓ |  |  |
| Bills | Void a bill with nothing paid on it | `invoice.void` | ✓ |  |  |  |
| Discounts | Give discounts up to their limit | `discount.apply` | ✓ | ✓ | ✓ |  |
| Discounts | Approve discounts above someone's limit | `discount.approve` | ✓ | ✓ |  |  |
| Payments | Record payments | `payment.record` | ✓ | ✓ | ✓ |  |
| Payments | Reverse a payment | `payment.reverse` | ✓ | ✓ |  |  |
| Payments | Give a refund | `refund.issue` | ✓ | ✓ |  |  |
| Payments | Confirm or reject bank transfers | `transfer.verify` | ✓ |  |  |  |
| Receipts | Print, download and share bills and receipts | `document.share` | ✓ | ✓ | ✓ |  |
| End of day | Submit their own end-of-day closing | `closing.submit` | ✓ | ✓ | ✓ |  |
| End of day | Approve or return closings | `closing.approve` | ✓ | ✓ |  |  |
| End of day | Reopen an approved closing | `closing.reopen` | ✓ |  |  |  |
| Reports | See their own performance | `report.own` | ✓ | ✓ | ✓ | ✓ |
| Reports | See every staff member's performance | `report.team` | ✓ | ✓ |  |  |
| Reports | See revenue, collections and amounts owed | `report.financial` | ✓ | ✓ |  |  |
| Reports | See the owner dashboard | `dashboard.view` | ✓ | ✓ |  |  |
| Bonuses | Set up bonus rules | `bonus.configure` | ✓ |  |  |  |
| Bonuses | Calculate bonuses | `bonus.calculate` | ✓ | ✓ |  |  |
| Bonuses | Approve or void bonuses | `bonus.approve` | ✓ |  |  |  |
| Bonuses | Record bonus payments | `bonus.pay` | ✓ |  |  |  |
| Records | Search the record of every change | `audit.view` | ✓ |  |  |  |
| Records | Import data | `import.run` | ✓ |  |  |  |

Totals: Owner 48, Supervisor 36, Cashier 18, Employee 6 of 50 permissions. The Owner role does not include the two employee-only actions (joining someone else's work, and asking for an extra service).

## Owner-only actions

A few actions need the **Owner role** on top of a permission, so they cannot be handed to another role:

| Action | Permission | Plus |
|---|---|---|
| Remove a customer's personal data (SEC-021) | `customer.manage` | Owner role |

## Prices and contact details

- Prices in the catalogue are sent only to people with `order.view_all` or `catalogue.manage`. Everyone else, including employees, gets the services without any price field (BR-041).
- Customer phone numbers and emails are sent only to people with `customer.view_contact`, and only they can search by phone number (SEC-021).
