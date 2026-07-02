# Local Quality Commands

Generated: 2026-07-02

Use these commands after installing Composer and npm dependencies. The project uses Laravel Mix/Webpack, not Vite.

## Setup

```bash
composer install
npm ci
cp .env.example .env
php artisan key:generate
```

For local tests, use SQLite or another safe test database. Do not point `phpunit.xml` or local test runs at production data.

## PHP Commands

```bash
composer validate --strict --no-interaction
composer audit --no-interaction
composer format:test
composer format
composer analyse
composer test
composer test:unit
composer test:feature
composer quality:php
composer quality:cache
```

- `composer format:test` checks Pint formatting.
- `composer format` applies Pint formatting.
- `composer analyse` runs Larastan/PHPStan with the current safe legacy level.
- `composer quality:cache` verifies `config:cache`, `route:cache`, `view:cache`, and clears generated caches afterwards.

## Frontend Commands

```bash
npm run lint
npm run lint:fix
npm run stylelint
npm run format:check
npm run format
npm run audit:prod
npm run production
npm run quality
```

- `npm run audit:prod` audits production Node dependencies only.
- `npm run production` builds Laravel Mix assets.
- If a production build changes tracked files under `public/css`, `public/js`, or `public/mix-manifest.json`, review the diff before staging.

## Full Local Gates

```bash
composer quality
composer ci
```

- `composer quality` runs the PHP quality gate and then the frontend quality gate.
- `composer ci` runs Composer validation, Composer audit, `composer quality`, and Laravel cache smoke checks.
- If `composer quality` fails because npm dependencies are missing, run `npm ci` and retry.

## Migration Smoke Check

Run this only against a temporary or test database:

```bash
tmp_db="$(mktemp)"
APP_ENV=testing APP_DEBUG=false DB_CONNECTION=sqlite DB_DATABASE="$tmp_db" php artisan migrate:fresh --force --no-interaction
rm -f "$tmp_db"
```

Never run destructive migration checks against production or shared staging databases without explicit approval.

## Troubleshooting

- Formatting failure: run `composer format`, inspect the diff, and commit only intended formatting changes.
- Static-analysis failure: fix the actual type, relation, nullable, or PHPDoc issue. Do not create a baseline unless a dedicated cleanup plan documents why.
- Test failure: reproduce with the focused test, fix behavior or document the legacy failure in `docs/quality-known-failures.md`.
- Frontend build failure: run `npm ci`, then `npm run lint`, `npm run stylelint`, `npm run format:check`, and `npm run production` separately to identify the failing gate.
