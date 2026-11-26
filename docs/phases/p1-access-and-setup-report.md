# Phase P1: Access and Business Setup, Report

**Date:** 2026-11-25
**Plan:** [p1-access-and-setup-plan.md](p1-access-and-setup-plan.md) v1.0 (approved; Q1 email or phone, Q2 and Q3 defaults)
**Branch:** `development`
**Status:** Complete locally. Waiting for your review, your push, the GitHub Actions results, and your approval before P2.

---

## 1. What you can do now

Every person has their own sign-in. What they can do is decided by permissions, and every API request checks them.

| # | Try this | Screenshot |
|---|---|---|
| 1 | Open the web app on a phone and sign in with an **email or a phone number**. Each person lands in their own area: owner and supervisor in Admin, cashiers in Front desk, employees in Staff. | [sign-in](p1-screenshots/01-sign-in-phone.png), [employee on a phone](p1-screenshots/10-employee-staff-phone.png), [cashier](p1-screenshots/09-cashier-desk.png) |
| 2 | As the owner, open **Admin > Staff**. Search, add staff (a phone number is enough), assign roles, reset a password, disable or enable someone. The temporary password is shown once, on screen. | [overview](p1-screenshots/02-admin-overview.png), [staff](p1-screenshots/03-staff-list.png), [add staff](p1-screenshots/04-add-staff.png) |
| 3 | **Admin > Roles.** The four default roles, permissions in plain English, discount limits in % and naira, custom roles. | [roles](p1-screenshots/05-roles.png), [cashier role](p1-screenshots/06-role-cashier.png) |
| 4 | **Admin > Settings.** Salon details and logo, numbering with a live preview, time limits and idle sign-out. | [settings](p1-screenshots/07-settings.png) |
| 5 | **Admin > Settings > Tax.** Off on a new installation. Switch VAT on at 7.5%, choose "added on top" or "already in the price", see a worked example, and see every change in the history. | [tax on, with history](p1-screenshots/08-tax-on-with-history.png) |
| 6 | Sign in as an employee and open an admin page: "You do not have access to this page". The API refuses the same request with 403 and records it. | [refused](p1-screenshots/11-employee-refused-admin.png) |
| 7 | On a fresh server: `php artisan app:install` creates the salon, the default roles, tax off, and the owner. | Runbook [deploy-cpanel.md, B6](../runbooks/deploy-cpanel.md#b6-create-the-salon-and-the-owner-account-first-install-only) |

The demo accounts, their phone numbers and five things to try are in [docs/demo-data.md](../demo-data.md). The permission table for each role is in [docs/permissions.md](../permissions.md).

## 2. Results

All run on this machine against MySQL 8.4.

| Check | Result |
|---|---|
| Backend tests (unit, feature, architecture, concurrency) | **303 passed**, 95,522 assertions (P0: 164) |
| Pint (code style), Larastan level 6 | Pass, 0 errors |
| OpenAPI contract up to date | Pass (19 paths) |
| Frontend lint, type check | Pass |
| Frontend unit tests | **54 passed** (P0: 18) |
| Static build and export check | Pass: 13 pages, 65 files precached |
| No em-dashes in the design | Pass |
| **Browser tests** (Chromium, desktop and phone size, real API, real cookie sessions) | **10 passed** |
| Local deployment rehearsal with the real release package | Pass (section 6) |
| Container deployment verification (cPanel-like, VPS-like) | **Runs on GitHub after your push.** It now also installs a salon and signs in with real cookies. |

## 3. Acceptance criteria

| Criterion (roadmap P1) | Proven by |
|---|---|
| The owner can create a cashier and an employee | `UserManagementTest` (API); browser test "the owner adds a cashier, who signs in and must choose a new password" |
| An employee calling an admin endpoint receives 403, and the attempt is audited | `PermissionMatrixTest` (every protected endpoint against every role), `test_an_employee_refused_an_admin_action_changes_nothing`; browser test |
| After 30 idle minutes, a cashier is signed out | `IdleTimeoutTest`: allowed at 29 minutes, refused at 30 minutes 1 second, recorded |
| Turning tax on or off is audited and visible in Settings | `TaxSettingsTest`; browser test "the owner turns VAT on and the change appears in the history" |
| All CI jobs green | Locally green. GitHub results come after your push. |

## 4. Security review

Reviewed against the SRS security requirements in scope.

| Requirement | How it is met | Test |
|---|---|---|
| SEC-001 Authentication on all protected functions | Every route outside a short public list (meta, login, logo) is in the `signed-in` group | `RouteAccessTest`, `SignInTest::test_me_requires_a_signed_in_user` |
| SEC-002 Backend permission checks | `permission:` middleware on every protected route. Authorisation is by permission name, never role name. | `PermissionMatrixTest` (19 endpoints × 4 roles + signed out), `RouteAccessTest` fails the build if a route forgets |
| SEC-003 Business scope | `EnsureBusinessScope` sets the business per request. Business-owned models use a scope that **fails closed** (no business, no rows). Users and roles are looked up inside the business; another salon's records answer "not found". | `BusinessIsolationTest` (6 tests with a second salon) |
| SEC-004 No unauthorised data in responses | A refusal returns only the error, never data. `/me` exposes no other user's data. | `PermissionMatrixTest` asserts no `data` on 403 |
| SEC-005 Password hashing | bcrypt (Laravel default); rehash on sign-in when the cost changes | `PasswordTest` |
| SEC-006 HTTPS and HSTS | Unchanged from P0: preflight fails production without https and secure cookies. HSTS is sent for the web app by `.htaccess` (cPanel) and `vercel.json`. **Gap:** the VPS Nginx config and the API responses do not send HSTS yet; I will add it in the P6 release hardening unless you want it sooner. | P0 preflight tests |
| SEC-007 Input validation | Form Requests on every write; phone numbers normalised before uniqueness checks; logo checked by real content type, size and dimensions | `UserManagementTest`, `SettingsTest`, `TaxSettingsTest` |
| SEC-008 OWASP and CSRF | Sanctum CSRF (`XSRF-TOKEN`) on every write; no bearer tokens in the browser; search input escaped for LIKE; login messages do not reveal whether an account exists (same message, same timing) | `SignInTest::test_a_wrong_password_and_an_unknown_account_get_the_same_answer`; smoke test signs in through the real CSRF flow |
| SEC-014, SEC-023 Cookie settings | HttpOnly, SameSite=Lax, Secure in production, database sessions; session regenerated at sign-in and on password change | Preflight checks; container smoke test |
| SEC-015 Duplicate and unauthorised privileged actions | Settings and tax saves carry a version: a save based on stale data is refused (409 STALE) instead of overwriting | `SettingsTest`, `TaxSettingsTest` |
| SEC-016 Denied attempts audited | The exception handler records `access.denied` with who, which permission, method, path and route | `PermissionMatrixTest` checks the record for every refusal |
| SEC-017 Lockout | 5 failures for an account from one address lock that pair for 15 minutes; 20 failures from one address across accounts lock the address. Recorded. | `SignInTest` (5 lockout tests with simulated time) |
| SEC-018 Idle timeouts | Server middleware; 30 minutes for anyone beyond the Employee permissions, 7 days for employee-only users; both editable within bounds | `IdleTimeoutTest` (6 tests) |
| SEC-019 Password rules | At least 8 characters and not on a list of commonly guessed passwords (checked locally; passwords are never sent to an outside service) | `PasswordTest`, `InstallCommandTest` |
| FR-AUTH-005, §14.22 Disabled users | Refused on their next request; every session ended at once; nothing deleted | `DisabledUserTest` |
| FR-AUTH-006, FR-IAM-006, FR-BIZ-006 Audit | Sign-in, failure, lockout, sign-out, idle expiry, password change and reset; user, role and assignment changes with before and after; settings and tax with before and after. Passwords are never written to the audit log. | Tests across the suites, including `test_a_reset_password_signs_the_user_out_and_forces_a_change` |

## 5. Plan tasks

All plan tasks D1 to L4 are done. Notes on a few of them:

| Task | Note |
|---|---|
| D4 | Roles also have `system_key`, so code recognises the Owner role even after it is renamed. |
| S1, S2 | The catalogue is in code (`PermissionCatalogue`), and a test checks the counts against the SRS table: 50 permissions; Owner 48, Supervisor 36, Cashier 18, Employee 6. |
| S3 | `app:install` is interactive, or scripted with the password in `BIZZMAG_OWNER_PASSWORD` so it never appears in shell history. Two more commands: `app:user:reset-password` (owner locked out) and `app:permissions:sync` (run by the deploy script after migrations, so a release that adds permissions gives them to the Owner role automatically). |
| A3 | Lockout counters live in the application cache, which is the database by default, so every PHP process shares them. |
| W8 | 10 Playwright tests run against the real API on a fresh database loaded with the demo salon. `pnpm e2e` runs them locally; CI has a new **Browser tests** job. |
| L1 | Local sign-in works with the API on `localhost:8000` and the web app on `localhost:3000`, with no extra tools. |
| L2 | The container smoke test now installs a salon, signs in through the real cookie and CSRF flow, creates an employee, checks that the employee is refused an admin endpoint, and signs out. |

## 6. Local deployment rehearsal

The real API package (`0.2.0-rc1`) was installed with the real `deploy-api.sh`, the same way as in P0:

1. First install stopped and created `shared/.env`, as designed.
2. The install migrated 6 new tables, ran `app:permissions:sync`, and passed preflight. One new warning, as intended: "No business has been set up yet. Run: php artisan app:install".
3. `app:install` (scripted) created the salon. After it, preflight showed **Business installed: PASS**, **Permissions: 50**, **Session lifetime: PASS**.
4. The smoke test's API section passed in full, including the new cookie sign-in steps. The web app checks need the containers and run on GitHub.
5. Redeploy: "Nothing to migrate", "Permissions are up to date for 1 business(es)", preflight passed. Rollback worked and the API kept serving.

The throwaway database and files were removed afterwards.

## 7. Where I changed the plan

1. **The Owner role holds its SRS permissions, not every permission.** The plan said "the Owner role always keeps every permission". The approved SRS §3.3 table does not give the Owner two employee-only actions: joining someone else's recorded work, and asking for an extra service. I followed the SRS. The Owner role is locked to its 48 permissions and cannot be edited down.
2. **UI components were written by hand.** The shadcn installer hung, probably on the network, so I wrote the 10 components P1 needed (input, dialog, switch and so on) directly on the Base UI library P0 already installs. **No new packages were added** to either the frontend or the backend.
3. **Browser tests use ports 3100 and 8100**, because Grafana is using port 3000 on your machine. I did not touch it. They use their own `bizzmag_e2e` database and never read your `.env`.
4. **`TaxMode` moved to the shared kernel.** The tax mode is now used by both Settings and the invoice calculator, so it moved out of Billing. The behaviour is unchanged and the P0 calculator tests pass.

## 8. Bugs found and fixed

1. **The page could reload during sign-in (P0 bug).** On a first visit, the offline worker took control of the page, which made the page reload, and that cut off a sign-in in progress. It now reloads only after someone taps "Update". A test covers it.
2. **A password could have reached the address bar.** Tapping Sign in before a slow phone had finished loading the page would have submitted the form the browser's own way, with the details in the URL. The button now stays disabled until the page is ready, and the form uses POST in any case.
3. **The wrong font everywhere (P0 bug).** A circular theme setting made every page fall back to a serif font. Pages now use Geist as designed. The screenshots show the fixed version.
4. **The tax "Saved" message disappeared** straight after saving. Fixed.
5. **After choosing to sign out**, the sign-in page offered to return to the page you had just left. It no longer does.
6. **The smoke test only worked on port 8080.** It now works on any port, which made the local rehearsal possible. CI is unchanged.

## 9. Things you should know

1. **One salon Wi-Fi shares one internet address.** The rule "20 wrong passwords from one address lock that address" could, in the worst case, lock everyone in the salon out for 15 minutes if many people mistype at once. That is unlikely with one owner and a dozen staff. If it ever happens, waiting 15 minutes clears it. If you would rather raise the limit or drop the address rule (keeping only the per-account rule), tell me.
2. **Behind a proxy or Cloudflare**, Laravel must be told to trust it, or every visitor appears to come from the proxy's address and the lockout above becomes much more likely. This is not set yet. It belongs with your hosting setup; tell me if you put the API behind Cloudflare.
3. **Your local `backend/.env`** still has `APP_URL=http://bizzmag-api.test` and `SESSION_LIFETIME=120`. I did not change your file. To sign in locally, follow the new "Signing in locally" section of the README. Preflight also warns about both.
4. **Not in this phase, as agreed:** shared-tablet PIN mode, "forgot password" by email (it needs working email), and the full audit log screen. Settings and tax already show their own change history.
5. **The two cashiers in the demo have no employee profile**, because they don't do salon work. Everyone else can be credited for services from P3.

## 10. Commit plan

Everything is unstaged except the `TaxMode` move, which is already staged as a rename. Commit in this order, then push.

1. **Backend**

   ```bash
   git add backend
   git commit -m "feat(backend): sign-in, roles and permissions, business settings and tax

   - Cookie sign-in with email or phone, lockout after 5 failures, idle
     sign-out by role, password rules, forced change after a reset
   - Permission catalogue and default roles from SRS 3.3, roles per
     business with discount caps, every route guarded by a permission and
     every refusal recorded
   - Business scope that fails closed, verified with a second salon
   - Staff, roles, settings, numbering and tax endpoints with audit of
     every change and version checks against overwrites
   - app:install, app:user:reset-password and app:permissions:sync;
     preflight checks for installation and sessions
   - Demo salon staff get roles, phone numbers and employee profiles
   - 303 tests including a permission matrix for every endpoint"
   ```

2. **Frontend**

   ```bash
   git add frontend
   git commit -m "feat(frontend): sign-in, staff, roles, settings and tax screens

   - Sign-in by email or phone, session bootstrap from /me, landing by
     role, forced password change and My account
   - Navigation and pages shown by permission; refused pages make no
     API calls
   - Admin screens for staff, roles and discount limits, salon settings
     with numbering preview, and tax with its change history
   - Playwright browser tests against the real API with cookie sessions
   - Fix: first install of the offline worker no longer reloads the page
   - Fix: pages use the Geist font instead of a serif fallback"
   ```

3. **CI and deployment**

   ```bash
   git add .github infrastructure
   git commit -m "ci: browser tests, permission sync on deploy and cookie sign-in smoke test

   - New CI job runs the browser tests against the API on MySQL
   - Deploy script adds new permissions after migrations
   - Deployment verification installs a salon and signs in through the
     real CSRF and cookie flow on the cPanel-like and VPS-like stacks"
   ```

4. **Docs**

   ```bash
   git add README.md docs
   git commit -m "docs: P1 plan and report, permissions reference and install runbooks

   - Phase 1 plan (approved) and report with screenshots
   - Roles and permissions reference generated from the code
   - app:install and owner lockout steps in the cPanel and VPS runbooks
   - Demo sign-in by email or phone, local sign-in setup"
   ```

**After pushing,** check GitHub > Actions. **CI** now has three jobs (Backend, Frontend, Browser tests), and **Verify deployment** has the cPanel and VPS runs. All should be green.
