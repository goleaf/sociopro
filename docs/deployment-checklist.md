# Deployment Checklist

Generated: 2026-07-02

This checklist is for production or production-like releases of the current Laravel 13 / Laravel Mix application. Fill in host-specific commands before relying on it for an incident.

## Current Stack

- PHP requirement: `^8.3`; local detected runtime: PHP 8.5.7.
- Laravel: `13.18.0`.
- Composer: 2.9.5.
- Node / npm: Node 22.22.3 / npm 10.9.8.
- Frontend build: Laravel Mix / Webpack, not Vite.
- Database: sqlite is the local/test default; production code still targets MySQL-compatible schema and the install dump.
- CI: `.github/workflows/ci.yml` runs PHP quality/tests and frontend lint/style/format/build checks.

## Pre-Deploy

- Confirm the release commit is on `main` and pushed.
- Review `git diff --check`, `vendor/bin/pint --test`, `composer analyse`, `php artisan test`, and `npm run production` results for the release.
- Run `composer validate --strict --no-interaction` and `composer audit --no-interaction`.
- Review `npm audit --omit=dev --audit-level=moderate`; current runtime dependency audit is expected to be cleaner than full dev-tool audit.
- Confirm `.env` contains no generated-image provider keys and no browser-exposed provider secrets.
- Confirm `APP_ENV=production`, `APP_DEBUG=false`, secure session/cookie values, mail, queue, cache, filesystem, and payment settings.
- Confirm backup or point-in-time recovery has been tested for the target database.

## Deploy

- Put the application in maintenance mode only if the change requires it.
- Install dependencies with locked versions: `composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction`.
- Install/build frontend assets in CI or release packaging: `npm ci && npm run production`.
- Run migrations with `php artisan migrate --force` only after reviewing migration rollback notes.
- Run `php artisan storage:link` if the target host does not already have the public storage link.
- Cache production config/routes/views after deployment verification allows it:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Post-Deploy Smoke Checks

- Homepage, login, registration, timeline, post creation with image upload.
- Marketplace create/update with image upload.
- Private media download denial for non-owner and success for owner.
- Job application PDF upload and admin download.
- Admin settings page without generated-image provider fields.
- Payment configuration screens and callback routes, using provider test modes only.
- Logs contain no secrets, raw stack traces, provider tokens, or full uploaded filenames from production users.

## Rollback Hook

If any smoke check fails, use `docs/rollback-plan.md`. For data-loss, schema, or storage incidents, use `docs/backup-and-restore.md` before attempting destructive repairs.
