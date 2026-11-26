# Deploying to cPanel

This runbook installs the API (Laravel) and the web app on one cPanel account. The same steps were tested on a cPanel-like stack (Apache, PHP 8.3, MariaDB 10.6) by the **Verify deployment** workflow on GitHub.

If the web app is hosted on Vercel instead, do Part A here and then follow [deploy-vercel.md](deploy-vercel.md).

## What you need

- A cPanel account with **PHP 8.3** (MultiPHP Manager) and **MySQL 8.0.16+ or MariaDB 10.6+**.
- One domain with two subdomains:
  - `api.your-domain` for the API;
  - `app.your-domain` for the web app.

  Both must be subdomains of the **same** domain, so the login cookie works.
- The two release packages: `bizzmag-api-<version>.tar.gz` and `bizzmag-app-<version>.tar.gz`. Download them from the GitHub release, or build them on your computer:

  ```bash
  NEXT_PUBLIC_API_URL=https://api.your-domain infrastructure/build-release.sh 1.0.0
  ```

  The web app package has the API address built in. Build it with your real API address.

## Part A: one-time setup

1. **Create the database.** Open cPanel > MySQL Databases.
   1. Create a database, for example `youruser_salon`.
   2. Create a user with a strong password.
   3. Add the user to the database with **All Privileges**.
2. **Create the subdomains.** Open cPanel > Domains and create both subdomains, with these document roots:
   - `api.your-domain`: `/home/youruser/apps/bizzmag-api/current/public`
   - `app.your-domain`: `/home/youruser/app.your-domain`

   If cPanel will not accept a document root that does not exist yet, create the folders first in File Manager, or set the document root after the first install.
3. **Choose PHP 8.3.** Open cPanel > MultiPHP Manager and set PHP 8.3 for `api.your-domain`. Then open Select PHP Version > Extensions and make sure these are enabled: `pdo_mysql`, `mbstring`, `gd`, `zip`, `intl`, `fileinfo`, `curl`, `xml`, `opcache`.
4. **Turn on SSL.** Open cPanel > SSL/TLS Status and run AutoSSL for both subdomains. Then open Domains and turn on **Force HTTPS Redirect** for both.
5. **Email (for receipts).** Create a mailbox such as `receipts@your-domain` under cPanel > Email Accounts. Its SMTP settings go into `.env` (step B4).

## Part B: install or update the API

Choose **B1 (SSH)** if your plan has SSH access, or **B2 (no SSH)** otherwise.

### B1. With SSH, from your computer

```bash
SSH_TARGET=youruser@your-server \
API_DIR='~/apps/bizzmag-api' APP_DOCROOT='~/app.your-domain' \
PHP_BIN=/opt/cpanel/ea-php83/root/usr/bin/php \
infrastructure/deploy-remote.sh 1.0.0
```

This uploads both packages and runs the server scripts.

### B2. Without SSH: File Manager and Terminal

1. In File Manager, create `/home/youruser/bizzmag-deploy/`.
2. Upload `deploy-api.sh`, `deploy-app.sh` (from `infrastructure/server/`) and both `.tar.gz` packages into it.
3. Open cPanel > Terminal and run:

   ```bash
   cd ~/bizzmag-deploy
   PHP_BIN=/opt/cpanel/ea-php83/root/usr/bin/php bash deploy-api.sh install bizzmag-api-1.0.0.tar.gz
   bash deploy-app.sh bizzmag-app-1.0.0.tar.gz ~/app.your-domain
   ```

If your plan has no Terminal either, ask your host to run these two commands once, or move to a plan with Terminal or SSH. The deployment needs to run `php artisan migrate`.

### B3. The first install stops on purpose

On the very first run, `deploy-api.sh` creates `~/apps/bizzmag-api/shared/.env` and stops. Nothing goes live yet.

### B4. Fill in `shared/.env`

Edit `~/apps/bizzmag-api/shared/.env` in File Manager:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.your-domain
FRONTEND_URL=https://app.your-domain
SANCTUM_STATEFUL_DOMAINS=app.your-domain
SESSION_DOMAIN=.your-domain
SESSION_SECURE_COOKIE=true

DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=youruser_salon
DB_USERNAME=youruser_salonuser
DB_PASSWORD=the-password-you-created

LOG_LEVEL=warning

MAIL_MAILER=smtp
MAIL_HOST=mail.your-domain
MAIL_PORT=465
MAIL_SCHEME=smtps
MAIL_USERNAME=receipts@your-domain
MAIL_PASSWORD=the-mailbox-password
MAIL_FROM_ADDRESS=receipts@your-domain
```

Leave `APP_KEY` empty; the script generates it. Then run the same install command again. This time it will:

1. migrate the database;
2. add any permissions that are new in this release (`app:permissions:sync`);
3. cache the configuration;
4. run **`app:preflight`**;
5. switch the site live only if every required check passes.

### B5. Add the cron job

Open cPanel > Cron Jobs and add the line from [`infrastructure/cpanel/cron.txt`](../../infrastructure/cpanel/cron.txt), set to run once per minute. Within a minute, preflight's "Scheduler (cron)" check turns to PASS.

### B6. Create the salon and the owner account (first install only)

In cPanel > Terminal (or over SSH):

```bash
cd ~/apps/bizzmag-api/current && /opt/cpanel/ea-php83/root/usr/bin/php artisan app:install
```

It asks for the salon name, the owner's name, the owner's email or phone number, and a password (at least 8 characters, typed twice). It sets up the four default roles and leaves tax switched off. It refuses to run a second time.

For a scripted install, put the password in the environment so it never appears in your shell history:

```bash
read -rs BIZZMAG_OWNER_PASSWORD && export BIZZMAG_OWNER_PASSWORD
php artisan app:install --business="Your Salon" --owner-name="Your Name" --owner-phone="0803 000 0000" --no-interaction
unset BIZZMAG_OWNER_PASSWORD
```

Then sign in at `https://app.your-domain` and add your staff in **Admin > Staff**.

## Part C: check the deployment

```bash
cd ~/apps/bizzmag-api/current && /opt/cpanel/ea-php83/root/usr/bin/php artisan app:preflight
```

Then:

| Check | Expected result |
|---|---|
| Open `https://api.your-domain/up` | A green "Application up" page |
| Open `https://api.your-domain/api/v1/meta` | JSON with your release number |
| Open `https://app.your-domain` | The start page shows **"Connected to ... API"** at the bottom |
| Sign in at `https://app.your-domain/login/` as the owner | You land on **Admin > Overview** |

See [preflight.md](preflight.md) for what each preflight check means.

## Updating to a new release

Run Part B again with the new packages. Your `.env` and uploaded files are kept. The live site switches only after migrations and preflight succeed. The last 5 releases are kept.

## Rolling back

```bash
APP_DIR=~/apps/bizzmag-api PHP_BIN=/opt/cpanel/ea-php83/root/usr/bin/php bash ~/bizzmag-deploy/deploy-api.sh rollback
```

This points the site back to the previous release. Database changes are not undone; releases are designed so the previous version still works with the newer database.

For the web app, deploy the previous `bizzmag-app` package again.

## If the owner cannot sign in

- **Too many wrong passwords:** sign-in from that device is locked for 15 minutes, then works again.
- **Forgotten password:** on the server, give the account a temporary password. The owner must choose a new one at the next sign-in.

  ```bash
  cd ~/apps/bizzmag-api/current && /opt/cpanel/ea-php83/root/usr/bin/php artisan app:user:reset-password owner@your-domain
  ```

  Use the owner's email or phone number. The command prints the temporary password.

## Troubleshooting

| Symptom | Fix |
|---|---|
| Preflight: PHP version or extensions FAIL | Steps A3. Check that `PHP_BIN` points to PHP 8.3 (`$PHP_BIN -v`). |
| Preflight: Database version FAIL | Your host's MySQL or MariaDB is too old. Ask for an upgrade, or use a VPS. |
| Preflight: Session cookie domain FAIL | `SESSION_DOMAIN` must be `.your-domain`, and both subdomains must end with it. |
| Web app says "Cannot reach the API" | Check `https://api.your-domain/up`. Check that the web app was built with the right `NEXT_PUBLIC_API_URL` and that `FRONTEND_URL` matches the web app address exactly. |
| 500 error on the API | Read `~/apps/bizzmag-api/shared/storage/logs/laravel-*.log`. The error page shows a reference that matches a log line. |
