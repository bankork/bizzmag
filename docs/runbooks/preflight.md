# `php artisan app:preflight`

Run this on the server, in the API's `current` folder, after every deployment. The deploy script runs it automatically and **refuses to switch the site live** if any check fails.

```bash
cd ~/apps/bizzmag-api/current && php artisan app:preflight
php artisan app:preflight --json   # for scripts
```

There are three possible results:

- **PASS:** everything is fine.
- **WARN:** the site works, but this should be fixed.
- **FAIL:** the site will not work correctly. The exit code is 1.

Each problem is printed with its fix.

| Check | What it confirms | Typical fix |
|---|---|---|
| PHP version | PHP 8.3 or newer | cPanel MultiPHP Manager |
| PHP extensions | pdo_mysql, mbstring, gd, zip and the other Laravel requirements | cPanel Select PHP Version > Extensions |
| Recommended extensions | intl, opcache (WARN only) | Same place |
| Database connection | The `.env` database settings work | Check the `DB_*` values |
| Database version | MySQL 8.0.16+ or MariaDB 10.6+ | Ask the host to upgrade, or use a VPS |
| CHECK constraints | The database really rejects negative money | Same as above |
| Generated-column unique index | The database really enforces one active service record | Same as above |
| Migrations | No pending migrations | `php artisan migrate --force` |
| Queue, cache and session tables | Present | Same as above |
| Writable folders | storage/ and bootstrap/cache are writable | `chmod -R 775` those folders |
| Application key | `APP_KEY` is set | `php artisan key:generate --force` |
| Environment | Production with debug off | `APP_ENV=production`, `APP_DEBUG=false` |
| Application timezone | UTC storage | Do not change `config/app.php`. The salon timezone is a business setting. |
| HTTPS | `APP_URL` is https | AutoSSL or certbot |
| CORS origin | The web app may call the API | `FRONTEND_URL` = the exact web app address |
| Sanctum stateful domain | Login cookies are accepted from the web app | `SANCTUM_STATEFUL_DOMAINS=app.your-domain` |
| Session cookie domain | Both subdomains share the login cookie | `SESSION_DOMAIN=.your-domain` |
| Vercel domain | The web app is not on a `vercel.app` address | Use a custom subdomain ([deploy-vercel.md](deploy-vercel.md)) |
| Secure cookies | Secure, SameSite=lax in production | `SESSION_SECURE_COOKIE=true` |
| Session store | Sessions are kept in the database, so disabling someone signs them out everywhere | `SESSION_DRIVER=database` |
| Business installed | `app:install` has been run (WARN until then) | `php artisan app:install` |
| Permissions | Every permission of this release exists | `php artisan app:permissions:sync` (the deploy script runs it) |
| Session lifetime | `SESSION_LIFETIME` is at least the employee idle limit set in Settings | Raise `SESSION_LIFETIME` (minutes) or lower the limit in Settings |
| Scheduler (cron) | Cron ran in the last 5 minutes. WARN right after a first deployment. | Add the cron job ([cron.txt](../../infrastructure/cpanel/cron.txt)) |

After editing `.env`, run `php artisan config:cache`, then run preflight again.
