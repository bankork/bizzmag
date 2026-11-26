# Deploying to a VPS

This uses the same release packages and the same server scripts as cPanel. It was tested on an Nginx, PHP-FPM 8.3 and MySQL 8.4 stack by the **Verify deployment** workflow.

## One-time server setup (Ubuntu 24.04 LTS)

```bash
sudo apt update && sudo apt install -y nginx mysql-server rsync unzip certbot python3-certbot-nginx \
  php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-gd php8.3-intl php8.3-opcache
sudo ufw allow OpenSSH && sudo ufw allow 'Nginx Full' && sudo ufw enable
```

If PHP 8.3 is not in your distribution's repositories, add `ppa:ondrej/php` first.

### Database

```sql
CREATE DATABASE salon_live CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'salon'@'localhost' IDENTIFIED BY 'a-strong-password';
GRANT ALL PRIVILEGES ON salon_live.* TO 'salon'@'localhost';
```

### Folders

```bash
sudo mkdir -p /srv/bizzmag/api /srv/bizzmag/app /srv/bizzmag/deploy
sudo chown -R www-data:www-data /srv/bizzmag
```

### Nginx

1. Copy [`infrastructure/vps/nginx/bizzmag.conf`](../../infrastructure/vps/nginx/bizzmag.conf) to `/etc/nginx/sites-available/bizzmag`.
2. Replace `your-domain` with your domain.
3. Enable the site and reload Nginx:

   ```bash
   sudo ln -s /etc/nginx/sites-available/bizzmag /etc/nginx/sites-enabled/
   sudo nginx -t && sudo systemctl reload nginx
   ```

4. Add HTTPS:

   ```bash
   sudo certbot --nginx -d api.your-domain -d app.your-domain
   ```

## Deploy

From your computer:

```bash
SSH_TARGET=deploy@your-server API_DIR=/srv/bizzmag/api APP_DOCROOT=/srv/bizzmag/app \
infrastructure/deploy-remote.sh 1.0.0
```

Or on the server, as `www-data`:

```bash
sudo -u www-data APP_DIR=/srv/bizzmag/api bash deploy-api.sh install bizzmag-api-1.0.0.tar.gz
sudo -u www-data bash deploy-app.sh bizzmag-app-1.0.0.tar.gz /srv/bizzmag/app
```

The first install stops and creates `/srv/bizzmag/api/shared/.env`. Fill it in as in [deploy-cpanel.md, step B4](deploy-cpanel.md#b4-fill-in-sharedenv), with these differences:

```dotenv
DB_HOST=127.0.0.1
QUEUE_WORK_VIA_SCHEDULER=false
```

Then run the install again, and create the salon and owner account once:

```bash
cd /srv/bizzmag/api/current && sudo -u www-data php artisan app:install
```

See [deploy-cpanel.md, step B6](deploy-cpanel.md#b6-create-the-salon-and-the-owner-account-first-install-only) for the scripted form.

## Scheduler and queue worker

**Scheduler.** Run `sudo crontab -u www-data -e` and add:

```
* * * * * php /srv/bizzmag/api/current/artisan schedule:run >> /dev/null 2>&1
```

**Queue worker.** Install the systemd unit:

```bash
sudo cp infrastructure/vps/systemd/bizzmag-queue.service /etc/systemd/system/
sudo systemctl daemon-reload && sudo systemctl enable --now bizzmag-queue
```

## Check, update, roll back

These work the same as on cPanel. See the "Part C", "Updating", "Rolling back" and "If the owner cannot sign in" sections of [deploy-cpanel.md](deploy-cpanel.md).

Nginx uses `$realpath_root`, so a new release is served as soon as the `current` link switches. No reload is needed.
