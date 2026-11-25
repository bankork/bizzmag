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
php artisan app:demo              # Nigerian demo salon (docs/demo-data.md)
php artisan app:preflight         # checks this machine
```

Create the `bizzmag_testing` database for the test suite. The tests never use SQLite.

### Frontend

```bash
cd frontend
pnpm install
NEXT_PUBLIC_API_URL=http://localhost:8000 pnpm dev
```

## Checks

| Area | Command |
|---|---|
| Backend: everything (unit, feature, architecture, concurrency) | `cd backend && php artisan test` |
| Backend: fast unit suite only | `vendor/bin/phpunit -c phpunit.unit.xml` |
| Backend: style and static analysis | `vendor/bin/pint --test` and `vendor/bin/phpstan analyse` |
| Frontend | `cd frontend && pnpm lint && pnpm typecheck && pnpm test && pnpm build && pnpm check:export` |
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

## Ground rules for contributors

- **Money is integer kobo** everywhere. The frontend only formats it.
- **Business rules live in the backend**, in `app/Domain/<Module>` Actions. The frontend never decides permissions or totals.
- **State changes are audited, idempotent where money moves, and lock the order** they change.
- **Migrations are backward compatible** with the previous release, so a rollback is always safe.
- **No em-dashes** in user-facing text.
