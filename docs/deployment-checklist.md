# Deployment Checklist

Generated: 2026-07-02

Use this checklist for production or production-like releases of the current Laravel 13 / Laravel Mix application.

## Current Stack

- PHP requirement: `^8.3`; local detected runtime during this pass: PHP 8.5.7.
- Laravel: `13.18.0`.
- Composer: 2.9.5.
- Node / npm: Node 22.22.3 / npm 10.9.8.
- Frontend build: Laravel Mix / Webpack, not Vite.
- Database: SQLite for local/CI; production-like baseline still comes from `database/schema/install.sql` plus additive migrations. The dump must remain outside the public web root.
- CI: `.github/workflows/ci.yml` runs Composer validation/audit, Pint, PHPStan/Larastan, tests, cache smoke checks, migration smoke checks, npm lint/style/format, and production asset build.

## Pre-Deploy

- Confirm the release commit is on `main`, pushed, and green in CI.
- Review `docs/rollback-plan.md` and `docs/backup-and-restore.md` for the release risk.
- Confirm a recent database backup or point-in-time restore point exists.
- Confirm file storage backup/snapshot coverage for `public/storage` and `storage/app`.
- Confirm no real secrets exist in the commit, `.env.example`, logs, docs, or fixtures.
- Confirm production `.env` values:
  - `APP_ENV=production`
  - `APP_DEBUG=false`
  - `APP_URL=https://...`
  - `INSTALL_SCHEMA_DUMP_PATH` empty or set to a path outside `public`
  - `LOG_LEVEL` appropriate for production
  - `CACHE_DRIVER`, `SESSION_DRIVER`, and `QUEUE_CONNECTION` production-ready
  - `SESSION_SECURE_COOKIE=true`
  - `CORS_ALLOWED_ORIGINS` restricted to trusted origins
  - `MAIL_*`, payment providers, filesystem/S3, and API credentials present only in the host secret store
- Confirm generated-image provider keys are not present; that browser-facing feature has been removed.
- Confirm Ignition, Telescope, Horizon, Pulse, Debugbar, `phpinfo`, debug routes, and test endpoints are not installed or exposed.
- Confirm web-server deny rules block `.env*`, dumps, logs, backups, archives, non-front-controller PHP files, and executable uploads under public storage.

## Build And Install

Install PHP dependencies from the lock file:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progress
```

Build frontend assets in CI/release packaging or on the host:

```bash
npm ci
npm run production
```

## Quality Gate

Run before release packaging, or rely on an equivalent green CI run:

```bash
composer validate --strict --no-interaction
composer audit --no-interaction
composer quality
composer quality:cache
npm run quality
php artisan route:list --except-vendor
```

Optional runtime audit:

```bash
npm audit --omit=dev --audit-level=moderate
```

If any command fails, stop the deployment unless `docs/known-technical-debt.md` records the exact accepted risk and next step.

## Database And Storage

- Review every pending migration before running it.
- For normal deploys:

```bash
php artisan migrate --force
```

- For fresh environment smoke checks only, use a throwaway database:

```bash
tmp_db="$(mktemp)"
APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE="$tmp_db" php artisan migrate:fresh --force --no-interaction
rm -f "$tmp_db"
```

- Create the storage link if missing:

```bash
php artisan storage:link
```

- Verify web server permissions for `storage`, `bootstrap/cache`, and `public/storage`.

## Cache And Optimization

After dependencies, assets, and migrations are in place:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

If route caching fails, treat it as a release blocker unless documented in `docs/known-technical-debt.md`.

## Queues And Scheduler

- Restart workers after code deploy:

```bash
php artisan queue:restart
php artisan queue:failed
```

- Confirm the process supervisor is running the expected queue command.
- Confirm scheduler cron exists if scheduled commands are enabled:

```cron
* * * * * cd /path/to/sociopro && php artisan schedule:run >> /dev/null 2>&1
```

- Horizon is not installed in this checkout.

## Health And Smoke Checks

- Homepage, login, logout, registration, password reset page.
- Authenticated timeline and profile page.
- Contact form validation and mail handoff.
- Post create/edit with image and video upload.
- Web and API chat video upload.
- Marketplace list/create/update with image upload.
- Private media download denial for non-owner and success for owner.
- Job application PDF upload and authorized admin download.
- Payment configuration screens and callback routes using provider test modes only.
- API login and a protected API endpoint with a test token.
- Logs show no fresh fatal errors, raw stack traces to users, secrets, cookies, provider tokens, or excessive uploaded filenames.
- Failed jobs queue is empty or understood.

## Rollback Hook

If smoke checks fail, use `docs/rollback-plan.md`. For data-loss, schema, or storage incidents, use `docs/backup-and-restore.md` before attempting destructive repairs.
