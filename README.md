# Sociopro

Legacy Laravel social application.

## Composer Commands

Run Composer scripts with `composer <script>` from the project root.

| Command | Description |
| --- | --- |
| `composer test` | Run the full Laravel test suite. |
| `composer test:unit` | Run PHPUnit's `Unit` testsuite. |
| `composer test:feature` | Run PHPUnit's `Feature` testsuite. |
| `composer pint` | Format PHP files with Laravel Pint. |
| `composer pint:test` | Check PHP formatting without writing changes. |
| `composer analyse` | Run PHPStan if `vendor/bin/phpstan` is installed; otherwise print a skip message. |
| `composer rector:dry` | Run Rector in dry-run mode if `vendor/bin/rector` is installed; otherwise print a skip message. |
| `composer rector` | Run Rector if `vendor/bin/rector` is installed; otherwise print a skip message. |
| `composer quality` | Run formatting checks, optional static analysis, and the full test suite. |
| `composer ci` | Run Composer validation, Composer audit, formatting checks, optional static analysis, and tests. |

Current installed PHP quality tools are PHPUnit, Laravel Pint, Larastan/PHPStan, and Rector. Composer scripts still guard optional tool execution so local setup remains explicit and failure output is clear.

## Production Security

Session and cookie hardening requirements are documented in [docs/session-cookie-security.md](docs/session-cookie-security.md). Review that runbook before changing auth, proxy, HTTPS, session driver, same-site, secure cookie, or remember-me behavior.

## Current Runtime Notes

- The external generated-image feature has been removed from the web UI, routes, provider configuration, installer data, admin settings, and regression fixtures. Do not reintroduce browser-exposed provider-token flows without a fresh server-side design and tests.
- Local public media uploads are written under `public/storage/...` because legacy Blade/API helpers serve files from that public path. Do not move local uploads back to `storage/app/public` without updating the helper/view contract and upload tests.
- Deployment, rollback, backup/restore, performance, and upgrade-slice notes live in `docs/deployment-checklist.md`, `docs/rollback-plan.md`, `docs/backup-and-restore.md`, `docs/performance-audit.md`, and `docs/senior-upgrade-summary.md`.

## Database Seeding

The default `DatabaseSeeder` imports the legacy install SQL dump only when the database has not already been initialized. Treat it as production-safe schema/reference data: it must not create demo users, real personal data, provider credentials, API keys, mail passwords, or other secrets.

Local/demo records are separated into `Database\Seeders\LocalDemoSeeder` and are intentionally opt-in:

```bash
php artisan db:seed --class=Database\\Seeders\\LocalDemoSeeder
```

`LocalDemoSeeder` is guarded to run only in `local` or `testing` environments, uses factories for demo records, and is repeat-safe. Real production credentials must be configured after installation through the application settings or environment-backed config, never committed into seeders or `public/assets/install.sql`.
