# Phase P0 — Foundations: Phase Report

**Date:** 2026-11-25
**Plan:** [p0-foundations-plan.md](p0-foundations-plan.md) v1.1 (approved 2026-11-25)
**Branch:** `development`. Nothing has been committed or pushed; the commit plan is in §8.
**Status:** Complete locally. Waiting for your review, your push, and the GitHub Actions results.

---

## 1. Summary

Every P0 task is built, tested and documented. All checks pass on this machine:

| Area | Result |
|---|---|
| Backend tests (unit, feature, architecture, concurrency) | **164 passed**, 94,654 assertions, on MySQL 8.4 |
| Backend style (Pint) and static analysis (Larastan level 6) | Pass, 0 errors |
| Frontend lint, typecheck, unit tests | Pass, 18 tests |
| Static export build and export check | Pass. 6 pages, `sw.js` precaches 43 files |
| No em-dashes in the design | Pass (frontend, PDF views, demo data, backend app code) |
| API contract | `docs/api/openapi.json` matches the code. TypeScript types generated from it. |
| Real deployment rehearsal on this machine | Pass. See §4. |
| Container deployment verification (cPanel-like and VPS-like) | **Not run yet.** It needs Docker, which runs on GitHub after your push (§6). |

**Bugs caught by the tests during this phase, now fixed:**

1. **Sequence deadlock.** Four parallel workers deadlocked on the invoice-number sequence, because `INSERT IGNORE` followed by `SELECT ... FOR UPDATE` takes conflicting locks. Only 66 of 80 numbers committed. After the fix, 80 of 80 are contiguous, across three repeated runs.
2. **Broken service-worker precache list.** The generator replaced only the first placeholder, and that one was inside a comment. The export check caught it.
3. **`.env.example` missing from the API package.** The rsync filter order excluded it, which would have broken a first install.
4. **Wrong cron hint.** It pointed at a timestamped release folder that is later deleted, instead of `current/`.
5. **Two type errors in the demo code** (found by Larastan) and **one test that asserted nothing** (it scanned the wrong folder).

## 2. What you can try now

```bash
cd backend
php artisan app:demo:calc      # worked salon invoices, reconciled to the kobo
php artisan app:preflight      # host readiness check
php artisan app:demo           # loads the 12 demo staff accounts
```

**Sample from `app:demo:calc`.** Knotless braids (Medium) by two braiders, plus 2 packs of X-pression hair, with a 10% invoice discount and VAT 7.5% exclusive:

| Item | Value | Invoice discount | Net | VAT | Line total |
|---|---|---|---|---|---|
| Knotless braids (Medium) | ₦40,000.00 | ₦4,000.00 | ₦36,000.00 | ₦2,700.00 | ₦38,700.00 |
| X-pression hair (pack) x 2 | ₦7,000.00 | ₦700.00 | ₦6,300.00 | ₦472.50 | ₦6,772.50 |
| **Customer pays** | | | | | **₦45,472.50** |

**Staff credit.**

| Staff | Role | Share | Credit |
|---|---|---|---|
| Chiamaka Eze | Primary | 60% | ₦21,600.00 |
| Funmilayo Ogunleye | Assistant | 40% | ₦14,400.00 |

The two credits add up exactly to the ₦36,000 line net. The product line earns no staff credit.

Three more scenarios are included:

- a three-person braid with an equal split (₦21,666.66 / ₦21,666.67 / ₦21,666.67);
- a nails visit with inclusive VAT and a line discount;
- bridal makeup with a fixed invoice discount.

All four report "reconciles".

**The web app.** Run `cd frontend && NEXT_PUBLIC_API_URL=http://localhost:8000 pnpm dev`. You get the start page, the Staff, Front desk and Admin placeholder areas, the sign-in placeholder, and a live "Connected to API" indicator. Installing it as an app on your phone needs a production build served over HTTPS, so try that on your first deployment.

## 3. Delivered against the plan

| Task | Status | Notes |
|---|---|---|
| G1–G3 Git, Dependabot, PR template, README | Done | No commits made (your rule). Dependabot targets `development`. |
| B1 Packages | Done | All six installed on Laravel 13.43 with no compatibility issues. |
| B2 MySQL test configuration, `.env.example` | Done | `phpunit.xml` uses `bizzmag_testing`. `phpunit.unit.xml` is the fast suite. |
| B3 `app/Domain` modules and architecture test | Done | 17 modules. Enforces the dependency graph and that it has no cycles. |
| B4 Shared kernel | Done | Money, Percentage, Rounding, Apportion, BusinessDay, Sequence, IdempotencyGuard, RowLock, OrderLock contract, AuditRecorder |
| B5 API foundations | Done | Envelope, error codes, request IDs, `/api/v1` per-module routes, CORS, Scramble (local only) |
| B6 Calculators | Done | `InvoiceCalculator`, `AllocationCalculator`, plus `CollectedContribution`. Worked examples E1–E5 exact; 3,500 generated property cases. |
| B7 `app:preflight` | Done | 7 check groups, 20 checks, `--json` |
| B8 Scheduler heartbeat | Done | Also: queue worker through the scheduler on cPanel, daily pruning of idempotency keys |
| D1–D4 Nigerian demo data | Done | Salon, 12 staff, 6 categories / 37 services / 8 products, names, payment styles, generators, `app:demo`, `app:demo:calc` |
| F1–F8 Frontend foundations | Done | Static export, API client, query client, auth shell, area layouts, PWA, `.htaccess`, host-neutral build |
| R1–R9 Release, deploy, verify, Vercel, runbooks | Done | See §5 for one structural change |
| Em-dash guard (your rule) | Done | `pnpm check:no-em-dash` in CI, plus a backend test for demo data |

## 4. Deployment rehearsal on this machine

I installed the real `bizzmag-api-0.1.0.tar.gz` with `infrastructure/server/deploy-api.sh` into a scratch folder, configured as production:

- HTTPS URLs;
- shared cookie domain `.adunni.test`;
- a throwaway database, dropped afterwards.

The steps and their results:

1. **First install** stopped on purpose and created `shared/.env`. Nothing went live.
2. **Second install** ran the migrations, warmed the caches, and ran a strict production preflight: 17 PASS and 1 expected WARN ("no cron yet"). It then switched the release atomically.
3. **The live release responded correctly:**
   - `/up` returned 200;
   - `/api/v1/meta` returned release 0.1.0;
   - CORS allowed `https://app.adunni.test` with credentials;
   - CORS never named a foreign origin;
   - an unknown route returned the JSON 404.
4. **The scheduler** wrote its heartbeat and ran the queue worker.
5. **A redeploy** found "Nothing to migrate", and preflight passed with **no warnings**.
6. **Rollback** returned to the previous release.
7. **The web-app package was published to a document root:**
   - the AutoSSL `.well-known` folder was kept;
   - a stale file was removed;
   - every page and PWA file was present;
   - the CSP points at the production API.

## 5. Deviations from the plan

| Plan | What I did | Why |
|---|---|---|
| Separate `cpanel/deploy.sh` and `vps/deploy.sh` | **One pair of server scripts** (`infrastructure/server/deploy-api.sh`, `deploy-app.sh`) for both hosts, plus `deploy-remote.sh` for SSH from your computer | The release logic is identical on both hosts. One script means one thing to keep correct. Host differences live in the web server configuration and runbooks. |
| CSP in `.htaccess` | CSP in a `<meta>` tag, built from `NEXT_PUBLIC_API_URL` | Vercel cannot template headers from variables. This way the policy is identical on Apache, Nginx and Vercel. `X-Frame-Options` still comes from the host. |
| HTTPS redirect in `.htaccess` | Use cPanel's **Force HTTPS Redirect** switch | This is safer on hosts behind proxies, and avoids redirect loops. |
| Approved frontend test packages | Also added `@testing-library/dom`, `jsdom`, `@vitejs/plugin-react` | These are the standard companions that Vitest and Testing Library need to run React tests. Development only. |
| Placeholder icon | A simple plum icon generated by script | Replace it with the salon's logo whenever you like (`frontend/public/icons/`). |

## 6. Known limitations and open items

1. **Container deployment verification has not run yet.** `verify-deploy.yml` (Apache + PHP 8.3 + MariaDB 10.6, and Nginx + PHP-FPM + MySQL 8.4) needs Docker, which this machine does not have. It runs on GitHub when you push. If anything fails there, send me the log and I will fix it before P1.
2. **Local cookie login will need a shared local domain in P1.** Locally, the web app (`localhost:3000`) and API (`bizzmag-api.test`) are on different hosts. Preflight warns about this. In P1, I will set up local development so both share one domain, probably with a Herd proxy such as `app.bizzmag.test`.
3. **The web-app package has the API address built in.** For GitHub releases, set the repository variable `NEXT_PUBLIC_API_URL` (see `release.yml`), or build it yourself with `build-release.sh`. Vercel builds it itself.
4. **Demo phone numbers** use real network prefixes with a `000` block. They are fictional but could, in principle, belong to someone. Never use demo data to send real messages. Phase 5 will remove the phone number from WhatsApp links in demo mode.
5. **Your local MySQL was not running** when the phase started. I started your existing MySQL 8.4 on port 3307 with its own config file, and created `bizzmag_testing` next to `bizzmag_dev`. Your other project's databases were not touched. MySQL is still running; stop it with `~/.local/mysql-8.4/bin/mysqladmin -P 3307 -uroot -p shutdown` if you want.

## 7. What is NOT in P0 (by design)

There are no business screens, no sign-in, and no salon tables yet: no customers, orders or invoices. Those start in P1 (access and settings) and P2 (catalogue and customers).

## 8. Commit plan

Commit in this order, and push when you are happy. The CI and Verify deployment workflows run on push.

1. **backend: shared kernel, calculators and API foundations**
   ```bash
   git add backend/app/Domain backend/app/Http backend/app/Providers backend/bootstrap/app.php \
           backend/config backend/routes backend/database/migrations backend/composer.json backend/composer.lock
   git commit -m "feat(backend): add domain kernel, money calculators and API foundations

   - Money, percentage and rounding in integer kobo, with a shared apportion
     rule so discounts and staff credit never create or lose value
   - Invoice and staff allocation calculators implementing SRS 7.6
   - Gap-free number sequences, idempotency guard, row locking and an
     append-only audit recorder
   - Versioned /api/v1 routes, standard response envelope, error codes,
     request ids, CORS for the web app and the scheduler heartbeat
   - Install spatie/laravel-permission, laravel-dompdf, openspout,
     laravel-backup and scramble (approved in Stage 2 and Stage 3)"
   ```

2. **backend: preflight, demo data and test suite**
   ```bash
   git add backend/app/Console backend/database/seeders backend/tests backend/phpunit.xml \
           backend/phpunit.unit.xml backend/phpstan.neon backend/.env.example
   git commit -m "feat(backend): add app:preflight, Nigerian demo salon and test suite

   - app:preflight checks PHP, database features, migrations, folders,
     environment, cookie domains, Vercel addresses and cron
   - app:demo seeds the fictional Adunni Hair & Beauty Studio staff;
     app:demo:calc prints reconciled worked invoices
   - Tests run on MySQL: unit, feature, architecture and parallel-process
     concurrency tests (164 tests)
   - Larastan level 6"
   ```

3. **frontend: static PWA shell and API client**
   ```bash
   git add frontend
   git commit -m "feat(frontend): add static PWA shell, API client and area layouts

   - Static export that runs on cPanel, Nginx or Vercel without Node.js
   - API client with cookie auth, CSRF retry, idempotency keys and typed
     errors; TanStack Query; generated OpenAPI types
   - Staff, front desk and admin placeholder areas, offline banner and
     update prompt
   - Service worker that precaches static assets only, never API data
   - vercel.json, .htaccess, export check and no-em-dash check"
   ```

4. **infrastructure and CI**
   ```bash
   git add infrastructure .github .gitignore
   git commit -m "ci: add release build, deploy scripts and deployment verification

   - build-release.sh produces the API and web app packages
   - Server deploy scripts with atomic releases, preflight gate and rollback,
     shared by cPanel and VPS; deploy-remote.sh for SSH deployments
   - Nginx and systemd configs for VPS, cron line for cPanel
   - CI for backend and frontend, container verification on cPanel-like and
     VPS-like stacks, and tagged releases"
   ```

5. **docs**
   ```bash
   git add README.md docs
   git commit -m "docs: add runbooks, demo data guide, API contract and P0 report

   - Deployment runbooks for cPanel, VPS and Vercel, and preflight reference
   - Demo data guide with sign-in accounts
   - OpenAPI contract and the Phase 0 plan and report"
   ```

**After pushing,** check GitHub > Actions. Both **CI** and **Verify deployment** (cpanel and vps) should be green.
