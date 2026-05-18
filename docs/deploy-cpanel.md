# Deploying Bano to cPanel shared hosting

This project was originally built with Docker + PostgreSQL + Redis. To run on a typical cPanel shared host (no Docker, no Redis, often no PostgreSQL), the app is configured to use **MySQL** for the database and Laravel's **database** driver for cache, sessions, and queue.

## Prerequisites on the host

Verify in cPanel before you start:

- **PHP 8.3** is available under *Software → MultiPHP Manager* (or *Select PHP Version*).
- The following PHP extensions are enabled (under *Select PHP Version → Extensions*): `pdo_mysql`, `mbstring`, `bcmath`, `intl`, `zip`, `gd`, `openssl`, `tokenizer`, `xml`, `ctype`, `fileinfo`, `json`, `curl`.
- **MySQL** is available under *Databases → MySQL Databases*.
- **SSH access** is enabled (under *Security → SSH Access*). Without SSH, you cannot run `php artisan` commands — strongly recommended.
- **Cron Jobs** are enabled (under *Advanced → Cron Jobs*).

If any of these are missing, contact your host before continuing.

## Step 1 — Create the MySQL database

In cPanel → *MySQL Databases*:

1. Create a database, e.g. `youruser_banu`.
2. Create a database user, e.g. `youruser_banu` with a strong password.
3. Add the user to the database with **ALL PRIVILEGES**.

Save the full database name and username — cPanel prefixes them with your account name.

## Step 2 — Upload the code

Use Git (via SSH) or SFTP to place the project somewhere **outside** `public_html`, for example:

```
/home/youruser/banu/
```

If you do not have Composer on the host, run `composer install --no-dev --optimize-autoloader` locally first and upload the `vendor/` directory along with the code.

## Step 3 — Point the domain to `public/`

In cPanel → *Domains*, set the document root of your domain (or subdomain) to:

```
/home/youruser/banu/public
```

This keeps the rest of the Laravel app outside the web root.

## Step 4 — Configure `.env`

Copy `.env.example` to `.env` on the server and edit it:

```
APP_ENV=production
APP_DEBUG=false
APP_KEY=                       # set in step 5
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=localhost              # cPanel always uses localhost for MySQL
DB_PORT=3306
DB_DATABASE=youruser_banu
DB_USERNAME=youruser_banu
DB_PASSWORD=your-strong-password

CACHE_STORE=database
QUEUE_CONNECTION=database
SESSION_DRIVER=database
```

Remove the `DB_ROOT_PASSWORD` line — it's only for local Docker.

## Step 5 — Initialise the app (via SSH)

```
cd /home/youruser/banu
php artisan key:generate --force
php artisan storage:link
php artisan migrate --force
php artisan db:seed --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
chmod -R 775 storage bootstrap/cache
```

## Step 6 — Set up cron for queue + scheduler

In cPanel → *Cron Jobs*, add two entries (replace `youruser` with your account):

**Queue worker** — every minute, processes any pending jobs and exits:

```
* * * * * cd /home/youruser/banu && /usr/local/bin/php artisan queue:work --stop-when-empty --max-time=55 >> /dev/null 2>&1
```

**Laravel scheduler** — required by Laravel even if you have no scheduled tasks yet:

```
* * * * * cd /home/youruser/banu && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1
```

Check the exact PHP binary path with `which php` over SSH — it may be `/usr/bin/php` or a path under `/opt/cpanel/`.

## Step 7 — Verify

Open your domain in a browser. Check:

1. The home page loads with no 500 error. If you see a generic error, temporarily set `APP_DEBUG=true` to see the trace, then turn it back off.
2. Login / OTP flows work (sessions are stored in the `sessions` table — check with `SELECT COUNT(*) FROM sessions;`).
3. Trigger something that dispatches a job, then watch `SELECT * FROM jobs;` and `SELECT * FROM failed_jobs;` to confirm queue processing.

## Updating the app later

```
cd /home/youruser/banu
git pull              # or upload the new files
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Things that won't work on shared cPanel

- **Real-time broadcasting** (WebSockets, Pusher, Reverb) — typically blocked. Use polling instead.
- **Long-running queue workers** — replaced by the cron above; jobs will run with up to ~1 minute of latency.
- **Redis-only cache features** (`Cache::tags`, `Cache::lock` with atomic semantics) — the database driver supports basic `lock`, but tags are not available. None are currently used in this project.

## When you outgrow shared cPanel

If traffic grows or you need real-time features, move to a VPS ($5–10/month) and run the original Docker stack — `docker compose up -d` will give you the full PostgreSQL + Redis setup back. The only change is restoring those service blocks in `docker-compose.yml` and reverting the `.env` values.
