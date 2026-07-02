# Development Workflow

Generated: 2026-07-02

## Branch Rules

- Work on `main` unless the repository owner explicitly asks for another branch.
- Do not create extra branches for routine agent work in this repo.
- Pull/rebase only after inspecting the current dirty tree.
- Never overwrite user changes. If a file has unrelated edits, keep your patch scoped to the requested concern.

## Commit Rules

- Use Conventional Commits: `fix:`, `feat:`, `refactor:`, `test:`, `docs:`, `ci:`, `build:`, or `chore:`.
- Keep commits scoped and reviewable.
- Stage only files that belong to the task.
- Before commit, inspect `git diff --cached --stat`, `git diff --cached --name-only`, and staged diffs for secrets.

## Local Setup

```bash
composer install
npm ci
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --force
php artisan storage:link
```

## Running Tests

Use focused tests while changing one area:

```bash
php artisan test tests/Feature/ContactFormTest.php
php artisan test tests/Feature/ChatUploadSecurityTest.php
```

Run the full suite before commit:

```bash
php artisan test
```

Tests must use factories and fakes, avoid production IDs, avoid real external services, avoid sleeps, assert authorization/validation failures where relevant, and assert database/storage changes for write flows.

## Quality Checks

```bash
composer validate --strict --no-interaction
composer audit --no-interaction
composer quality
composer quality:cache
npm run quality
```

Run optional tools only when installed or intentionally added. In this checkout, PHPStan/Larastan, Rector, ESLint, Stylelint, and Prettier are installed.

## Adding Migrations

- Create new migrations; do not edit production migrations.
- Keep one concern per migration.
- Add reversible `down()` methods.
- Add indexes for new foreign keys, filters, sorts, unique lookups, and cursor pagination columns.
- Use transactions or documented rollout plans for multi-step data changes.
- Test fresh migrations on a throwaway database:

```bash
tmp_db="$(mktemp)"
APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE="$tmp_db" php artisan migrate:fresh --force --no-interaction
rm -f "$tmp_db"
```

## Adding Features

- Add characterization/regression tests first for risky legacy behavior.
- Put validation in Form Requests.
- Put authorization in policies/gates or middleware, not Blade-only conditionals.
- Put workflows in `app/Actions` or `app/Services`.
- Put reusable queries in model scopes or query classes.
- Use transactions for multi-record writes that must succeed or fail together.
- Update README/docs and deployment notes when behavior affects operations.

## Adding API Endpoints

- Preserve the legacy unversioned API unless a versioned migration is explicitly planned.
- Add named routes under the `api.` prefix.
- Use Sanctum/token middleware and throttling appropriate to the endpoint.
- Validate payloads with Form Requests or dedicated validators.
- Use API Resources for non-trivial JSON output.
- Add tests for unauthenticated, unauthorized, validation failure, success, and sensitive-field hiding.

## Adding Frontend Components

- Blade remains server-side rendered. Do not introduce React, Vue, Inertia, or Vite without a dedicated migration.
- Keep Blade presentation-only; preload data in controllers/view models.
- Use escaped output by default.
- Prefer components for repeated UI.
- Verify forms have CSRF tokens, validation error display, labels, semantic buttons/links, and keyboard-accessible controls.

## Release Gate

Before pushing `main`, run the command set relevant to the change. For application changes, the minimum gate is:

```bash
composer validate --strict --no-interaction
composer quality
npm run quality
php artisan route:list --except-vendor
```
