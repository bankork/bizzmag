# Salon Operations & Accountability Platform

A web app for a salon that records what customers asked for, what staff actually did, what was billed and what was paid. Records are kept apart so a bill cannot be quietly reduced, and staff are credited fairly for shared work.

| Part | Technology |
|---|---|
| `backend/` | Laravel 13 API, PHP 8.3, MySQL 8.0.16+ or MariaDB 10.6+ |
| `frontend/` | Next.js 16 static PWA (React 19, TypeScript, Tailwind, shadcn) |
| `infrastructure/` | Release build, deploy scripts (cPanel, VPS), Vercel config, CI verification |
| `docs/` | Requirements (PRD, SRS), stage reviews, phase plans, runbooks, API contract |

The current requirements baseline is **PRD v1.4** and **SRS v1.2**. Delivery follows [docs/reviews/stage-4-development-roadmap.md](docs/reviews/stage-4-development-roadmap.md).

## Run it locally

**Requirements:** PHP 8.3, Composer, MySQL 8.0.16+ or MariaDB 10.6+, Node 22, pnpm. Docker is optional; see `infrastructure/docker/compose.yml`.

### Backend

```bash
cd backend
cp .env.example .env              # set DB_* for your MySQL
composer install
php artisan key:generate
php artisan migrate
php artisan app:demo              # Nigerian demo salon with staff logins (docs/demo-data.md)
php artisan app:preflight         # checks this machine
php artisan serve                 # API on http://localhost:8000
```

Create the `bizzmag_testing` database for the test suite. The tests never use SQLite.

For a real salon instead of the demo, run `php artisan app:install` to create the business and the owner account.

### Frontend

```bash
cd frontend
pnpm install
NEXT_PUBLIC_API_URL=http://localhost:8000 pnpm dev   # web app on http://localhost:3000
```

**Signing in locally.** Use the API on `localhost:8000` and the web app on `localhost:3000`. Browsers share cookies between ports of the same host, so the cookie sign-in works with no extra setup. In `backend/.env` keep `APP_URL=http://localhost:8000`, `FRONTEND_URL=http://localhost:3000`, `SANCTUM_STATEFUL_DOMAINS=localhost:3000`, `SESSION_DOMAIN=null` and `SESSION_LIFETIME=10080`. Sign in as any demo account, for example `adunni@adunni.test` / `Adunni@2026`.

If you prefer Herd, serve the API as `api.bizzmag.test`, proxy the web app as `app.bizzmag.test` (`herd proxy app.bizzmag http://localhost:3000`) and set `SESSION_DOMAIN=.bizzmag.test`. A plain `.test` cookie domain does not work; both hosts must share a parent such as `bizzmag.test`.

## Checks

| Area | Command |
|---|---|
| Backend: everything (unit, feature, architecture, concurrency) | `cd backend && php artisan test` |
| Backend: fast unit suite only | `vendor/bin/phpunit -c phpunit.unit.xml` |
| Backend: style and static analysis | `vendor/bin/pint --test` and `vendor/bin/phpstan analyse` |
| Frontend | `cd frontend && pnpm lint && pnpm typecheck && pnpm test && pnpm build && pnpm check:export` |
| Browser tests (real API, cookie sign-in) | `cd frontend && pnpm e2e`. Needs MySQL with a `bizzmag_e2e` database; uses ports 3100 and 8100 and never touches your own `.env` or database. |
| No em-dashes in the app design | `pnpm check:no-em-dash` |

GitHub Actions runs all of these on every push and pull request (`ci.yml`). It also deploys each release into cPanel-like and VPS-like stacks and smoke-tests them (`verify-deploy.yml`).

## Deploy

1. Build the release packages:

   ```bash
   NEXT_PUBLIC_API_URL=https://api.your-domain infrastructure/build-release.sh 1.0.0
   ```

   Or push a `v*` tag and download them from the GitHub release.
2. Follow the runbook for your host:
   - [cPanel](docs/runbooks/deploy-cpanel.md)
   - [VPS](docs/runbooks/deploy-vps.md)
   - [Web app on Vercel](docs/runbooks/deploy-vercel.md)
   - [Preflight checks](docs/runbooks/preflight.md)
3. On the first install only, run `php artisan app:install` on the server to create the salon and the owner account.

Roles and what each one may do: [docs/permissions.md](docs/permissions.md).

## Ground rules for contributors

- **Money is integer kobo** everywhere. The frontend only formats it.
- **Business rules live in the backend**, in `app/Domain/<Module>` Actions. The frontend never decides permissions or totals.
- **State changes are audited, idempotent where money moves, and lock the order** they change.
- **Migrations are backward compatible** with the previous release, so a rollback is always safe.
- **No em-dashes** in user-facing text.
