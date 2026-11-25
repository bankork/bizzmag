# Phase P0 — Foundations: Detailed Plan

**Version:** 1.1
**Date:** 2026-11-25
**Status:** Submitted for product-owner approval. Not started.

**v1.1 changes (owner notes, 2026-11-25):**

1. The frontend can also be deployed to **Vercel**, as well as cPanel and VPS. See F8 and R9.
2. **The owner commits and pushes to GitHub.** I make no commits and no pushes. I provide ready-to-use commit messages at the end of the phase (G1–G2 revised).

**Roadmap reference:** Stage 4 v1.1, P0 and §10.

**Estimate:** about **7–8 working days** (1.5 weeks). This is the original 1 week, plus the deployment-verification harness and preflight check added in §10.1.

---

## 1. Objective

Lay the shared engineering base that every later phase depends on:

- the money and rounding engine, proven correct;
- locking, idempotency, gap-free numbering and audit recording;
- the module layout;
- CI on MySQL;
- the frontend shell (static PWA and API client);
- **release artefacts that are verified to deploy** on cPanel-like and VPS-like platforms;
- the Nigerian demo-data foundation.

No business screens yet. Those begin in P1.

## 2. What you will be able to see at the end

1. **A green GitHub Actions run** with these jobs:
   - backend checks (style, static analysis, tests on MySQL);
   - frontend checks (lint, types, tests, static build);
   - **deployment verification**: the release is deployed by the real scripts into an Apache + PHP 8.3 + MariaDB 10.6 stack and an Nginx + PHP-FPM + MySQL 8.4 stack, and smoke-tested on both.
2. **The calculation engine working on demo data.** Running `php artisan app:demo:calc` prints worked invoices in naira using the Nigerian demo catalogue. Example: *Knotless braids, medium, ₦35,000, by Chiamaka (primary) + Funmilayo, with an attachment hair pack at ₦3,500, a 10% discount and VAT 7.5% exclusive.* It shows each line, the discount share, the tax, the total and each stylist's credit, and confirms that every figure reconciles to the kobo.
3. **`php artisan app:preflight`**, which you can run on your own host now, ahead of any real deployment. It gives a plain pass/fail list.
4. **The installable app shell.** On your phone, "Add to Home Screen" works. It opens full-screen to the Employee, Front Desk and Admin placeholder areas, and shows an offline page when there is no connection.
5. **Downloadable release packages** (`bizzmag-api-0.1.0.tar.gz`, `bizzmag-app-0.1.0.tar.gz`), with the cPanel and VPS deploy runbooks.

## 3. Work breakdown

### 3.1 Repository and Git

| # | Task | Output |
|---|---|---|
| G1 | The documents (PRD/SRS versions, reviews, phase plans) are currently untracked. I **do not commit**. The phase report gives you a commit message for them as a separate first commit. | Commit message |
| G2 | I work in the current working tree on `main`, or on a branch you create; tell me if you prefer a branch. At the end of the phase I list the changed files grouped into logical commits, each with a ready commit message (Conventional Commits, no co-author line). You commit and push. | Commit plan |
| G3 | `.github/dependabot.yml`, `pull_request_template.md`, root `README.md` (setup, scripts, structure) | Files |

### 3.2 Backend foundations

| # | Task | Output |
|---|---|---|
| B1 | Install the approved packages at the latest versions compatible with Laravel 13: `spatie/laravel-permission`, `barryvdh/laravel-dompdf`, `openspout/openspout`, `spatie/laravel-backup`, `dedoc/scramble`, and `larastan/larastan` (development only). `minishlink/web-push` waits until P8. **If any package does not yet support Laravel 13, I stop and report alternatives.** | `composer.json`/lock |
| B2 | Test configuration: `phpunit.xml` switched to a MySQL `bizzmag_testing` database; `phpunit.unit.xml` for tests that need no database; `.env.example` switched to MySQL with every variable documented (Stage 3 §12). | Configuration |
| B3 | `app/Domain/` layout with the 17 module folders. A **module dependency architecture test** (Stage 3 §4.2). | Folders, test |
| B4 | **Shared kernel**:<br>• `Money` (integer kobo, immutable, arithmetic, `round_div`, NGN formatting);<br>• `Percentage` (basis points);<br>• `BusinessDay` (Africa/Lagos);<br>• `Sequence` (locked, gap-free, `number_sequences` table);<br>• `Idempotency` (`idempotency_keys` table, replay and mismatch, header middleware);<br>• `OrderLock` contract;<br>• `AuditRecorder` (`audit_events` table, no hash chain yet). | Code + migrations |
| B5 | API foundations:<br>• the response envelope (success, data, meta / code, message, errors);<br>• an exception handler that maps domain exceptions to SRS §9 codes;<br>• request-ID middleware;<br>• `/api/v1` route groups split by module;<br>• Scramble configured and disabled in production;<br>• CORS and Sanctum configuration read from environment variables. | Code |
| B6 | **`InvoiceCalculator`** and **`AllocationCalculator`**: pure functions implementing SRS §7.6.3–7.6.4, with immutable input and breakdown objects. | Code |
| B7 | `php artisan app:preflight`: PHP ≥ 8.3 and extensions (pdo_mysql, mbstring, openssl, fileinfo, gd, zip, intl); database engine and version against the minimums; an actual `CHECK` constraint test and a generated-column unique-index test in a temporary table; writable `storage/` and `bootstrap/cache`; `APP_KEY`; `APP_DEBUG` off in production; consistency between the session domain, Sanctum stateful domains, CORS origin and `FRONTEND_URL`; `APP_TIMEZONE=UTC`; how recently the scheduler last ran (heartbeat); queue tables present. Output: a ✅/❌ table and a non-zero exit code on failure. | Command + tests |
| B8 | Scheduler heartbeat task, writing a cache key that `app:preflight` reads. | Code |

### 3.3 Nigerian demo-data foundation

| # | Task | Output |
|---|---|---|
| D1 | `database/seeders/Demo/Data/`: version-controlled definitions for the fictional salon profile, ~10 staff with their roles and skills, the catalogue (categories, services, variants, products, Lagos-plausible naira prices, allocation defaults), Nigerian first names and surnames (Yoruba, Igbo, Hausa, Edo, Efik and others), Lagos areas, bank and POS reference styles. | Data files |
| D2 | Generators: fictional +234 mobile numbers (valid format, non-assigned patterns, deterministic seed), guest names, payment reference strings. | Code + tests |
| D3 | `php artisan app:demo --fresh`: refuses to run in production; seeds what the current schema supports. In P0 that is the users table only; each later phase extends it. `docs/demo-data.md` lists the logins and dataset contents. | Command, doc |
| D4 | `php artisan app:demo:calc`: runs the calculators on demo catalogue scenarios (solo, 3-person braid, discount, VAT inclusive and exclusive, product line) and prints them. | Command |

### 3.4 Frontend foundations

| # | Task | Output |
|---|---|---|
| F1 | Install the approved packages: `@tanstack/react-query`, `react-hook-form`, `zod`, `@hookform/resolvers`; development only: `openapi-typescript`, `vitest`, `@testing-library/react`, `@playwright/test`. | `package.json`/lock |
| F2 | `next.config.ts`: `output: 'export'`, `trailingSlash`, unoptimised images; the existing Turbopack CSS rule kept. **Day-1 spike** to confirm the export builds cleanly on Next 16.4; the fallback is a webpack build. | Configuration |
| F3 | `lib/api`:<br>• `apiFetch` (credentials, XSRF bootstrap and header, JSON);<br>• an `Idempotency-Key` helper;<br>• error normalisation to `{code, message, fields}`;<br>• generated `schema.d.ts`;<br>• the `pnpm api:types` script. | Code + tests |
| F4 | Providers: QueryClient, an `AuthProvider` shell (the `/me` call is wired in P1), toasts, an offline banner. | Code |
| F5 | Area layouts and placeholders for `/staff`, `/desk`, `/admin` and `/login`. Mobile-first staff layout. `lib/money.ts` formatter (kobo → "₦35,000.00"). | Pages |
| F6 | PWA: `manifest.webmanifest` and icons (a placeholder brand, replaceable), `offline.html`, `public/sw.js` with a **post-build script** that writes the precache list from `out/_next/static/**`, network-only for `/api/*`, and an update prompt. | PWA |
| F7 | `public/.htaccess`: deep-link rewrite to `index.html`, cache headers (immutable for hashed assets, `no-cache` for HTML and `sw.js`), security headers and CSP (self + API origin from the build environment). | File |
| F8 | **Host-neutral frontend build.** The same `out/` artefact is served by Apache (`.htaccess`), Nginx (Stage 3 configuration) or Vercel (`vercel.json`). No feature depends on a particular host. The API origin is injected only through `NEXT_PUBLIC_API_URL` at build time. | Configuration |

### 3.5 Release, deployment and verification

| # | Task | Output |
|---|---|---|
| R1 | `infrastructure/build-release.sh`: builds both artefacts with `composer install --no-dev -o` and `pnpm build`, writes a version stamp, and excludes tests and `.env`. | Script |
| R2 | `infrastructure/cpanel/deploy.sh`: SSH, a release directory, shared `.env` and `storage` symlinks, `migrate --force`, configuration and route caches, an atomic `current` switch, `queue:restart`, keeping 5 releases, and a `--rollback` option. `infrastructure/cpanel/cron.txt` and frontend `.htaccess`. | Scripts |
| R3 | `infrastructure/vps/`: `deploy.sh` (same layout), Nginx server blocks (app and API), a systemd queue-worker unit, a cron line and a README. | Files |
| R4 | **Verification harness** (`infrastructure/verify/`). Container definitions and a smoke-test script, used **in CI only**:<br>• **cPanel-like**: `php:8.3-apache` with `mod_rewrite`, `AllowOverride All`, `app.bizzmag.test`/`api.bizzmag.test` virtual hosts, MariaDB 10.6, a cron runner and an SSH target;<br>• **VPS-like**: Nginx, PHP-FPM 8.3 and MySQL 8.4.<br>The real `deploy.sh` is run against each one. | Harness |
| R5 | Smoke tests:<br>• `GET api/up` returns 200;<br>• the app root and a deep link (`/desk/`) return 200 with `index.html`;<br>• a CORS preflight from the app origin is allowed, and one from another origin is denied;<br>• `app:preflight` exits 0;<br>• `migrate --force` runs twice without error;<br>• rollback, then forward again;<br>• `/sanctum/csrf-cookie` sets the cookie on the correct domain. | Script |
| R6 | GitHub workflows: `ci.yml` (backend, frontend, architecture test, OpenAPI types check); `verify-deploy.yml` (builds the artefacts, runs R4/R5 on both platforms, on pull requests touching infrastructure or backend, and on tags); `release.yml` (on a `v*` tag, attaches the artefacts to a GitHub Release). | Workflows |
| R7 | Runbooks:<br>• `docs/runbooks/deploy-cpanel.md`, with the SSH and the no-SSH (File Manager + Terminal) paths, subdomain document roots, PHP version selection, cron, AutoSSL, and SPF/DKIM notes;<br>• `deploy-vps.md`;<br>• `preflight.md`. | Docs |
| R8 | `infrastructure/docker/compose.yml`: an optional local development stack (MySQL 8.4 + Mailpit) for people who have Docker. Not needed on this machine, because native MySQL 8.4 is used. | File |
| R9 | **Vercel target**:<br>• `frontend/vercel.json`: framework *Next.js*, `pnpm build`, output `out/`; security headers and CSP equivalent to `.htaccess`; `no-cache` for `sw.js` and HTML; immutable caching for `/_next/static/*`; rewrite fallback for deep links;<br>• `docs/runbooks/deploy-vercel.md`: project root `frontend/`, build environment variables (`NEXT_PUBLIC_API_URL`), **custom domain required** (see §7);<br>• a CI check that validates `vercel.json` and confirms every exported route resolves to a file in `out/`. | Files, runbook |

## 4. Database changes

New tables: `audit_events`, `idempotency_keys`, `number_sequences`. The existing Laravel tables (users, cache, jobs, personal_access_tokens) are kept. Business tables start in P1.

## 5. Tests and acceptance criteria

### Tests that must pass in CI

- `Money` and `Percentage` arithmetic and rounding edge cases (0, 1 kobo, very large values, half-up boundaries).
- **Calculators:** worked examples E1–E5 from SRS §7.5, exact to the kobo. E6 (bonus) arrives in P7.
- **Calculators, property tests:** invariants I-1 to I-5, checked over at least 1,000 generated orders covering random lines, quantities, line and invoice discounts, tax on or off and inclusive or exclusive, exempt lines, 1–5 contributors and all three allocation methods.
- `Sequence`: contiguous numbers; a rolled-back transaction leaves no gap; two parallel processes never get the same number (concurrency test).
- `Idempotency`: a replay returns the same response; a mismatched payload returns 422; parallel replays create exactly one result.
- `AuditRecorder`: events are written inside the caller's transaction and disappear if it rolls back.
- Architecture test (module dependency rules).
- `app:preflight`: unit tests for each check, including a simulated failure.
- Demo generators: phone format valid, names unique where required, all prices are positive integer kobo.
- Frontend: `apiFetch` error mapping, idempotency-key reuse on retry, money formatting; `next build` produces `out/` with `sw.js` precache entries.
- **Deployment verification passes on both platform stacks** (R4/R5).

### Acceptance criteria

1. All the CI jobs above are green on the P0 pull request.
2. `app:demo:calc` output reconciles. You can review it; I will include it in the phase report.
3. `app:preflight` runs on this machine and passes. It also flags deliberately wrong settings, for example a mismatched session domain.
4. The release artefacts build successfully. The cPanel, VPS and Vercel runbooks are complete. `vercel.json` validates.
5. The PWA shell installs on a phone from a local build, and the offline page works.

## 6. Security considerations

- No secrets in the repository. `.env.example` uses placeholders. CI secrets are not needed in P0.
- CORS is limited to `FRONTEND_URL`. Debug is off outside local. Security headers are on the frontend.
- The service worker never caches `/api/*`. A test checks this.
- `app:demo` is refused when `APP_ENV=production`.

## 7. Risks and mitigation

| Risk | Likelihood | Mitigation |
|---|---|---|
| **Vercel: cookie login needs a shared parent domain.** The cookie-session design (S2-D02) requires the frontend and API to share a parent domain, for example `app.<domain>` on Vercel and `api.<domain>` on cPanel. A `*.vercel.app` address, including preview deployments, is a different site. Browsers will not send the login cookie from it, so **login will not work from `vercel.app` URLs**. | Certain, if a custom domain is not used | Production on Vercel must use a custom subdomain of the same domain as the API. Preview deployments are for checking the interface only; a demo mode with no login could be added later if you want it. Cross-site cookies (`SameSite=None`) are **not** recommended: Safari blocks them, and they weaken CSRF protection. |
| Vercel deployment cannot be tested live in CI without your Vercel account | Certain | CI validates the configuration and the routes. Your first Vercel deployment, followed by `app:preflight` on the API, is the final check. |
| A package lacks Laravel 13 support | Medium | Check on day 1. If it fails, stop and propose an alternative for your approval. |
| Next 16 static export or Turbopack incompatibility | Low–Medium | Day-1 spike. Fallback: a webpack production build, which is a configuration change only. |
| The cPanel-like container differs from your real host | Medium | Use the minimum supported versions (MariaDB 10.6, PHP 8.3) and Apache `.htaccess` behaviour, and run `app:preflight` on your real host as the final check. |
| CI time on MySQL | Low | Use a service container, transaction-wrapped tests, and a separate fast unit suite. |

## 8. Questions before I start

| # | Question | Default if you say "approve" |
|---|---|---|
| Q1 | ~~Commit the docs?~~ **Resolved:** you commit and push; I provide the messages. Should I work directly in the working tree on `main`, or will you create a branch first? | Work in the current working tree. You decide branching at commit time. |
| Q2 | Is the fictional salon name *Adunni Hair & Beauty Studio, Lekki* acceptable for demo data, or would you like your salon's real name and services in the demo? | Use the fictional name. |
| ~~Q3~~ | **Resolved:** you push. The deployment verification (container-based) runs on GitHub after your push. I run every other check locally and report the results. The repository has the GitHub remote `bankork/bizzmag`. CI, the deployment verification and release artefacts only run once the branch is **pushed**. May I push `feature/p0-foundations`, open a pull request, and push the `v0.1.0` pre-release tag? | Yes to pushing the branch and opening the pull request. The tag is pushed only after you review the pull request. Until the push, I run every check locally except the container-based deployment verification. |

## 9. Definition of Done

All of the following must be true:

- Tasks G1–R8 are complete.
- The §5 tests are green, including deployment verification on both platforms.
- The runbooks are written.
- The release artefacts are built locally by `build-release.sh`. You publish `v0.1.0` from GitHub after pushing.
- The phase report is delivered: test results (actual output), the `app:demo:calc` sample, the preflight sample, known limitations, any deviations from this plan, and **the commit plan with messages**.
- After your push, the GitHub Actions results (including deployment verification) confirm the phase. I will help fix anything that fails there.

**I will then ask for approval before starting P1.**
