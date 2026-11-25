## What this changes

<!-- One or two sentences, in plain language. -->

## Phase and requirements

- Phase:
- Requirements (PRD / SRS IDs):

## Checks

- [ ] Tests added or updated for every changed rule, calculation or permission
- [ ] `php artisan test`, `vendor/bin/pint --test`, `vendor/bin/phpstan analyse` pass
- [ ] `pnpm lint`, `pnpm typecheck`, `pnpm test`, `pnpm build`, `pnpm check:export` pass
- [ ] Money stays integer kobo end to end; the frontend only formats it
- [ ] New state changes write an audit event
- [ ] No em-dashes in user-facing text (`pnpm check:no-em-dash`)
- [ ] Migrations are backward compatible with the previous release
- [ ] Docs, runbooks and demo data updated where needed

## Screenshots or demo notes

<!-- For user-visible changes. -->
