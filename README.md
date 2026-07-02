# Sociopro

Sociopro is a legacy Laravel social application with a server-rendered Blade frontend, a legacy unversioned Sanctum-protected API, admin screens built with controllers/views, public media upload flows, marketplace/job/payment modules, and a Laravel Mix/Webpack asset pipeline.

The current checkout is being hardened incrementally. Preserve public routes and response contracts unless a bug or security issue is proven with tests.

## Requirements

- PHP `^8.3` with `mbstring`, `dom`, `fileinfo`, `gd`, `bcmath`, `sqlite3`, and PDO extensions.
- Composer 2.
- Node 22 and npm 10 for the current frontend toolchain.
- A database supported by the deployed environment. Local and CI use SQLite; the legacy production-oriented baseline still comes from `database/schema/install.sql` plus additive Laravel migrations.
- A queue worker for non-sync production queues.
- A cron entry for Laravel's scheduler if scheduled tasks are added.

No Filament package is installed in this checkout. Do not scaffold Filament resources unless the dependency and panel setup are added intentionally in a separate reviewed change.

## Installation

```bash
composer install
npm ci
cp .env.example .env
php artisan key:generate
```

Set `.env` for your local database, mailer, cache, session, queue, filesystem, and CORS needs. Keep production secrets in the host secret manager and never commit them.

## Environment

Important production values:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://your-domain.example`
- `LOG_CHANNEL=stack` and an appropriate `LOG_LEVEL`
- `CACHE_DRIVER`, `SESSION_DRIVER`, and `QUEUE_CONNECTION` set to production-capable drivers
- `SESSION_SECURE_COOKIE=true` behind HTTPS
- `SESSION_SAME_SITE=lax` or stricter when compatible
- `CORS_ALLOWED_ORIGINS` set to a trusted allowlist for browser API clients
- `MAIL_*` configured for the production mail provider
- `FILESYSTEM_DRIVER` and `AWS_*` configured only when cloud storage is enabled

Use `config()` in application code. Do not call `env()` outside config files.

## Database Setup

For a local empty SQLite database:

```bash
touch database/database.sqlite
php artisan migrate --force
```

The default `DatabaseSeeder` imports the legacy install SQL dump only when the database has not already been initialized. It must not create demo users, real personal data, provider credentials, API keys, mail passwords, or other secrets.

Optional local/demo records live in `Database\Seeders\LocalDemoSeeder` and are guarded to run only in `local` or `testing`:

```bash
php artisan db:seed --class=Database\\Seeders\\LocalDemoSeeder
```

Run fresh migration smoke checks only against throwaway/local databases:

```bash
tmp_db="$(mktemp)"
APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE="$tmp_db" php artisan migrate:fresh --force --no-interaction
rm -f "$tmp_db"
```

## Storage

Create the public storage link on each deployed host:

```bash
php artisan storage:link
```

Legacy public media uploads are served from `public/storage/...`. Recent upload tests preserve this public URL contract while routing writes through Laravel storage-aware helpers.

## Queues And Scheduler

Local development can use `QUEUE_CONNECTION=sync`. Production should use a real queue driver when jobs are enabled:

```bash
php artisan queue:work --tries=3 --backoff=3
php artisan queue:restart
php artisan queue:failed
```

Scheduler cron entry:

```cron
* * * * * cd /path/to/sociopro && php artisan schedule:run >> /dev/null 2>&1
```

This checkout currently has queued import jobs and no committed Horizon setup. Document any new scheduled command before enabling it in production.

## Frontend Build

The project uses Laravel Mix / Webpack, not Vite.

```bash
npm run development
npm run production
```

Frontend checks:

```bash
npm run lint
npm run stylelint
npm run format:check
npm run quality
```

## Tests And Quality

PHP checks:

```bash
composer validate --strict --no-interaction
composer audit --no-interaction
composer pint:test
composer analyse
php artisan test
composer quality
composer quality:cache
composer ci
```

`composer quality` runs Pint in check mode, PHPStan/Larastan, and the full Laravel test suite. `composer ci` adds Composer validation, dependency audit, and Laravel cache smoke checks.

Use focused tests while developing, then the full suite before commit:

```bash
php artisan test tests/Feature/ContactFormTest.php
php artisan test tests/Feature/ChatUploadSecurityTest.php
php artisan test
```

## Common Troubleshooting

- If routes or config behave unexpectedly, run `php artisan optimize:clear`.
- If public uploads do not resolve, run `php artisan storage:link` and verify the web server can read `public/storage`.
- If mail is not sent locally, use `MAIL_MAILER=log` or `MAIL_MAILER=array` for tests.
- If queue jobs do not run in production, confirm `QUEUE_CONNECTION`, worker supervisor config, `php artisan queue:failed`, and `php artisan queue:restart`.
- If frontend assets are missing, run `npm ci && npm run production` and verify the generated Mix manifest is deployed.
- If `composer audit` or `npm audit` fails, record the package, exploitability, runtime/dev scope, and safe upgrade path in `docs/known-technical-debt.md`.

## Production Runbooks

- Architecture: `docs/architecture.md`
- HTTP security headers: `docs/security-headers.md`
- Development workflow: `docs/development-workflow.md`
- Deployment checklist: `docs/deployment-checklist.md`
- Rollback plan: `docs/rollback-plan.md`
- Backup and restore: `docs/backup-and-restore.md`
- Known technical debt: `docs/known-technical-debt.md`
- Senior upgrade summary: `docs/senior-upgrade-summary.md`
