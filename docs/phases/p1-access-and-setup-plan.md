# Phase P1: Access and Business Setup, Detailed Plan

**Version:** 1.0
**Date:** 2026-11-25
**Status:** Approved by the product owner on 2026-11-25. In progress.

**Approval notes:** Q1 confirmed: staff can sign in with email or phone. Q2 and Q3 use the defaults.

**Roadmap reference:** Stage 4 v1.2, P1. **Requirements:** SRS v1.2 §3, §4.1 (except PIN mode), §4.2, §4.12 (configuration only), SEC-001 to SEC-008, SEC-014 to SEC-019, SEC-023, §14.10, §14.22.

**Estimate:** about **9 to 10 working days** (2 weeks, as in the roadmap).

---

## 1. Objective

Give every person an individual login, decide what each person may do through permissions, and let the owner set up the business and its tax. From this phase on, every API request is checked against a permission and the business it belongs to, and every refusal is recorded.

## 2. What you will be able to see at the end

1. **A sign-in page.** Staff sign in with their email or phone number and password. Each role lands on its own area: the owner and supervisors on Admin, cashiers on Front desk, employees on Staff.
2. **Admin › Users.** Create a cashier or an employee, edit them, assign one or more roles, reset a password, and disable or enable them. A disabled user is signed out at once and cannot sign in again.
3. **Admin › Roles.** See the four default roles, change which permissions a role has, set a role's discount cap, or create a new role, for example "Senior stylist".
4. **Admin › Settings.** Business name, logo, contact details, address, receipt footer, invoice and receipt number formats with a live preview, the late window, transfer alert period, link expiry and idle timeouts.
5. **Admin › Settings › Tax.** Tax is **off** on a new installation. Turn it on, name it (for example VAT), set the rate (7.5%) and choose inclusive or exclusive. Every change is recorded with the old and new values and shown in the change history on the same screen.
6. **Protection you can try yourself:**
   - an employee who tries an admin action gets "You do not have permission" and the attempt is recorded;
   - five wrong passwords lock that sign-in for 15 minutes;
   - a cashier who is idle for 30 minutes is signed out;
   - an employee on their own phone stays signed in for up to 7 days of inactivity.
7. **`php artisan app:install`** creates the business and the first owner account on a fresh server. This is the first step after your first deployment.
8. **Demo data.** `app:demo` now gives every Adunni Hair & Beauty Studio staff member their real role, so you can sign in as each one and compare what they see.

## 3. Work breakdown

### 3.1 Database

All new migrations are additive, so a rollback to P0 stays safe.

| # | Task | Output |
|---|---|---|
| D1 | `businesses`: name, currency (NGN), timezone (Africa/Lagos), contact phone, email, address, logo path, receipt footer, invoice footer | Migration |
| D2 | `business_user`: business_id, user_id, is_active, UNIQUE(business_id, user_id) | Migration |
| D3 | `users` gains: phone (E.164, unique when present), is_active, must_change_password, last_login_at, disabled_at, disabled_by. Email becomes nullable, because some staff have no email. At least one of email or phone is required (validated, and enforced by a CHECK constraint). | Migration |
| D4 | Spatie permission tables with **teams on**, so every role belongs to a business (team = business_id). Roles gain `discount_cap_bp` and `discount_cap_kobo` (both nullable; null means unlimited) and `is_system` (the four default roles cannot be deleted). | Migration, `config/permission.php` |
| D5 | `employee_profiles`: user_id (unique), business_id, display_name, is_active. Only users with a profile can be credited for work in later phases. | Migration |
| D6 | `business_settings`: business_id, key, value JSON, version, UNIQUE(business_id, key) | Migration |
| D7 | `tax_settings`: business_id (unique), enabled (default false), name, rate_bp, mode (inclusive or exclusive), version | Migration |

### 3.2 Permissions, roles and installation

| # | Task | Output |
|---|---|---|
| S1 | `PermissionSeeder`: every permission in SRS §3.3 (50 permissions), each with a plain-English label and group for the Roles screen. It is safe to run again, and later phases add to it. | Seeder |
| S2 | `RoleSeeder`: Owner, Supervisor, Front desk / Cashier and Employee with the §3.3 default grants and the default caps (cashier 10% and at most ₦5,000 per invoice, supervisor 30%, owner unlimited). | Seeder |
| S3 | `app:install`: asks for the business name and the owner's name, email or phone and password, or takes them as options for scripted installs. It creates the business, the default roles, settings with their defaults, tax switched off, and the owner. It refuses to run a second time. | Command, runbook update |
| S4 | `app:demo` update: the Adunni business, the 12 staff with their roles and employee profiles, and the default settings. | Seeder |

### 3.3 Authentication and session security

| # | Task | Output |
|---|---|---|
| A1 | Sanctum SPA cookie sessions (SEC-023): database sessions, Secure, HttpOnly and SameSite=Lax cookies, `XSRF-TOKEN`, no bearer tokens in the browser. The session is regenerated at login and invalidated at logout. | Configuration |
| A2 | `POST /auth/login` with email **or** phone (Nigerian formats such as `0803…` or `+234803…` are both accepted), `POST /auth/logout` and `GET /me` (user, business, effective permissions, roles, discount cap, idle policy, must-change-password flag). | Actions, endpoints |
| A3 | **Lockout (SEC-017):** 5 failed attempts within 15 minutes for the same account from the same IP address lock that pair for 15 minutes. 20 failed attempts from one IP address across any accounts lock that IP address for 15 minutes. Locking the pair, not the whole account, means a stranger cannot lock the owner out from elsewhere. Every lockout is audited. | Login action, tests |
| A4 | **Password rules (SEC-019):** at least 8 characters, and not one of the most common passwords. Passwords are hashed with bcrypt (SEC-005). | Rule |
| A5 | **Password changes:** a user can change their own password. A user with `user.manage` can reset someone else's password to a temporary one, and that person must choose a new one at their next sign-in. Changing a password signs out the user's other sessions. | Actions, endpoints |
| A6 | `EnforceIdleTimeout` middleware (SEC-018): 30 minutes for anyone with a financial permission, 7 days for employee-only users. Both limits are editable in Settings within safe bounds (5 to 120 minutes, and 1 to 30 days). An expired session returns 401 with the code `SESSION_IDLE`, and the web app shows "You were signed out after a period of inactivity". | Middleware, tests |
| A7 | `EnsureActiveUser`: a disabled user is rejected on their next request, and all their sessions are deleted when they are disabled (§14.22). | Middleware, action |
| A8 | `EnsureBusinessScope`: sets the current business from the user's membership, sets the Spatie team, and applies a global scope to business-owned models, so a query can never return another business's rows (FR-IAM-007, SEC-003). | Middleware, scope |
| A9 | Authentication events audited (FR-AUTH-006): successful login, failed login, lockout, logout, password change and password reset. | Audit actions |

### 3.4 Users, roles and settings

| # | Task | Output |
|---|---|---|
| U1 | **Users:** list (search, filter by role and status), create, edit, disable, enable, reset password, assign roles, and create or edit the employee profile. Users are never deleted (FR-AUTH-005). | Actions, policies, endpoints |
| U2 | **Safety rules:** you cannot disable yourself; the last active user who can manage users and roles cannot be disabled or lose those permissions; the Owner role always keeps every permission. | Domain errors, tests |
| U3 | **Roles:** list, create, rename, set permissions, set discount caps, delete a custom role with no users. Effective permissions are the union of a user's roles, and the highest cap applies (FR-IAM-008, FR-IAM-009). | Actions, endpoints |
| U4 | Every change to users, roles, permissions and assignments is audited with before and after values (FR-IAM-006). | Audit |
| B1 | **Business profile:** name, contact details, address, receipt and invoice footer text, and logo (PNG or JPEG, at most 1 MB). The logo is served through the API, so no `storage:link` symlink is needed on cPanel. | Actions, endpoints |
| B2 | **Numbering formats** (FR-BIZ-004): order, invoice and receipt formats built from `{YYYY}`, `{YY}`, `{MM}`, `{DD}` and `{SEQ:n}`, for example `INV-{YYYY}-{SEQ:5}` gives `INV-2026-00001`. The screen shows a live preview. Formats are only stored in P1 and are used from P3 and P4. | Validation, preview |
| B3 | **Windows:** late window (48 hours), transfer unverified alert (2 days), shared link expiry (7 days) and idle timeouts. | Settings |
| B4 | **Tax settings** (FR-TAX-001, FR-TAX-008): enabled, name, rate, mode. Off by default. One tax only. Changes take effect for invoices finalised afterwards. | Action, endpoint |
| B5 | **Version checks:** each settings save sends the version it was based on. If someone else saved first, the save is refused with 409 and the screen asks you to reload. This stops two people overwriting each other's changes without knowing. | Tests |
| B6 | Financial settings (tax, caps, numbering, windows) are audited with before and after values (FR-BIZ-006). | Audit |

### 3.5 Authorisation everywhere

| # | Task | Output |
|---|---|---|
| Z1 | Every `/api/v1` route declares its permission. An **architecture test fails** if any route has neither a permission nor an explicit "public" or "authenticated only" mark. Later phases cannot forget. | Route middleware, test |
| Z2 | **Denied attempts audited** (SEC-016): every 403 records who, what permission, which endpoint and when. The user sees a plain message and the response contains no data. | Exception renderer, tests |
| Z3 | **Permission matrix harness:** one test runs every endpoint against the Owner, Supervisor, Cashier, Employee and a signed-out visitor, and checks for allow or deny against the §3.3 table. Later phases add their endpoints to it. | Test |
| Z4 | Business isolation tests with a **second business** (created by the tests only): its users, roles, settings and audit events are invisible to the first, by list and by ID. | Tests |

### 3.6 Web app

| # | Task | Output |
|---|---|---|
| W1 | **Sign-in page:** email or phone, password, show/hide password, clear messages for wrong details, lockout ("Too many attempts. Try again in 12 minutes.") and disabled accounts. | Page |
| W2 | **Session start:** fetch the CSRF cookie and `/me`; send each role to its area; redirect to sign-in on 401; force the "choose a new password" screen when required. | AuthProvider, guards |
| W3 | **Navigation by permission:** menu items and buttons appear only when the user has the permission. The backend still checks every request; hiding is for clarity only. | Navigation |
| W4 | **Admin › Users:** list, search, create, edit, roles, reset password, disable and enable with confirmation. Usable on a phone. | Screens |
| W5 | **Admin › Roles:** permissions grouped in plain English (Customers, Orders, Payments and so on), discount caps in naira and percent. | Screens |
| W6 | **Admin › Settings:** business profile and logo, numbering with preview, windows and timeouts, and **Tax** with its change history. | Screens |
| W7 | **My account:** change my password, and sign out. | Screen |
| W8 | **Browser tests (Playwright):** sign in as each role, landing page, an employee refused an admin page, the owner creates a cashier who then signs in, tax switched on and the history shows it. | E2E tests |

### 3.7 Local development and deployment

| # | Task | Output |
|---|---|---|
| L1 | **Local sign-in that works:** the API on `http://localhost:8000` (`php artisan serve`) and the web app on `http://localhost:3000`. Browsers share cookies between ports on the same host, so sign-in works with no extra tools. The README and `.env.example` are updated. Using Herd with `api.bizzmag.test` and `app.bizzmag.test` is documented as an alternative. | Docs, config |
| L2 | **Deployment smoke test extended:** in the cPanel-like and VPS-like containers, the smoke test installs a business, signs in through the real cookie flow, calls `/me`, and checks that a refused request returns 403. | `smoke-test.sh` |
| L3 | **Preflight additions:** sessions table present, `SESSION_DOMAIN` and `SANCTUM_STATEFUL_DOMAINS` consistent with `FRONTEND_URL`, secure cookies in production, and a warning when no business has been installed yet. | Checks |
| L4 | Runbooks updated: `app:install` after the first deployment, and how to unlock a user or reset the owner's password from the command line (`app:user:reset-password`) if the owner is locked out. | Runbooks, command |

## 4. API changes

All under `/api/v1`. Mutations need the `X-XSRF-TOKEN` header.

| Method and path | Permission |
|---|---|
| `GET /sanctum/csrf-cookie` | Public |
| `POST /auth/login` | Public (throttled) |
| `POST /auth/logout` | Signed in |
| `GET /me` | Signed in |
| `PUT /me/password` | Signed in |
| `GET /permissions` | role.manage |
| `GET /users`, `POST /users`, `GET /users/{id}`, `PATCH /users/{id}` | user.manage |
| `POST /users/{id}/disable`, `POST /users/{id}/enable`, `POST /users/{id}/reset-password` | user.manage |
| `PUT /users/{id}/roles` | role.manage |
| `GET /roles`, `POST /roles`, `PATCH /roles/{id}`, `DELETE /roles/{id}` | role.manage |
| `PUT /roles/{id}/permissions` | role.manage |
| `GET /settings`, `PATCH /settings`, `POST /settings/logo` | settings.manage |
| `GET /settings/tax`, `PATCH /settings/tax`, `GET /settings/history` | settings.manage |
| `GET /business/logo` | Public (it is printed on receipts) |

The OpenAPI contract in `docs/api/openapi.json` and the generated web app types are regenerated, and CI checks that they match.

## 5. Tests and acceptance criteria

### Tests that must pass in CI

- **Permission matrix:** every endpoint × every default role, plus signed out.
- **§14.10:** an employee calls an admin endpoint, is refused with 403, nothing changes, and the refusal is audited. The same check against finalisation is added in P4 when that endpoint exists.
- **§14.22 (P1 part):** a disabled user cannot sign in, their open sessions end, and their record stays. The parts about reports and contributors are added in P3 and P7.
- **Lockout:** the 5th failure locks the pair, the 6th attempt is refused even with the right password, the lock ends after 15 minutes (time is simulated), a lock from one IP does not affect another, and the IP lock after 20 failures.
- **Idle timeout:** a cashier is refused at 30 minutes and 1 second and accepted at 29 minutes; an employee-only user is accepted after 6 days; edited timeouts take effect.
- **Business isolation** with a second business, by list and by ID.
- **Roles:** union of permissions across roles, the highest cap applies, the Owner role cannot lose permissions, the last administrator cannot be removed, system roles cannot be deleted.
- **Settings:** validation of every field, numbering format preview, the version conflict returns 409, before and after values audited.
- **Tax:** off on install, switching on and off audited, the rate is stored in basis points (7.5% = 750), one tax only.
- **Every route declares a permission** (architecture test).
- **Web app:** unit tests for the session guard, permission-aware navigation and forms; the Playwright journeys in W8.
- **Deployment verification:** sign-in through real cookies on both platform stacks (L2).

### Acceptance criteria (from the roadmap)

1. The owner can create a cashier and an employee, and both can sign in.
2. An employee calling an admin endpoint receives 403, and the attempt is audited.
3. After 30 idle minutes, a cashier is signed out.
4. Turning tax on or off is audited and visible in Settings.
5. All CI jobs, including deployment verification, are green.

## 6. Security review

This phase is security-critical. Before I report it as done, I review it against SEC-001 to SEC-008, SEC-014 to SEC-019 and SEC-023 and include the checklist, with the test that proves each item, in the phase report. Points of note:

- The web app never decides permissions. It only hides what the backend would refuse.
- Login errors do not reveal whether an account exists ("Those details are not correct").
- A refused request returns no data, only the error.
- The logo upload checks the real file type and size, and is stored outside the public folder.
- Session cookies are Secure in production, and preflight fails if they are not.

## 7. Risks and mitigation

| Risk | Likelihood | Mitigation |
|---|---|---|
| Cookie sign-in fails on the real domains (wrong `SESSION_DOMAIN`, or `SANCTUM_STATEFUL_DOMAINS` missing the app address) | Medium | Preflight checks these settings against `FRONTEND_URL`, the container smoke test signs in through real cookies, and the runbooks give the exact values. Run `app:preflight` on your server after deploying. |
| The owner locks themselves out | Low | The lockout is per account and IP and lasts 15 minutes; `app:user:reset-password` on the server is the fallback. |
| An endpoint added later forgets its permission | Medium | The architecture test fails the build. |
| Spatie teams add complexity to every permission check | Low | The team is set once per request by `EnsureBusinessScope`, and the isolation tests cover it. |
| Denied-attempt auditing fills the audit log if a page polls | Low | The web app never polls an endpoint it lacks permission for; a test checks that hidden screens make no requests. |

## 8. Not in this phase

- **Shared-device PIN mode** (FR-AUTH-007, a "Should"). It stays in the roadmap for a later phase.
- **Self-service "forgot password" by email.** This needs working email, which is not confirmed yet. In P1 an owner or supervisor resets passwords, and the command line is the fallback for the owner. Email reset can be added once email works.
- Audit hash chain (SEC-024, a "Should"), as planned.
- The audit log screen for searching all events arrives with Audit in a later phase. P1 shows the settings change history only.

## 9. Questions before I start

| # | Question | Default if you say "approve" |
|---|---|---|
| Q1 | Should staff sign in with **email or phone number**? Many stylists may not use email. | Either one. Each user needs at least one of them. |
| Q2 | Who may reset another person's password? | Anyone with `user.manage` (the owner by default). You can give it to supervisors in Roles. |
| Q3 | Should the owner be able to change the idle timeouts in Settings? | Yes, within 5 to 120 minutes for financial staff and 1 to 30 days for employee-only staff. |

## 10. Definition of Done

All of the following must be true:

- Tasks D1 to L4 are complete.
- The §5 tests are green locally, and the CI and deployment verification are green after your push.
- The §6 security checklist is in the phase report, with the test for each item.
- The permission seeds are documented: which permissions each default role has, in plain English.
- The phase report includes a walkthrough with screenshots of the acceptance criteria, known limitations, any deviations from this plan, and **the commit plan with messages**.

**I will then ask for approval before starting P2.**
