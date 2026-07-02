# Quality Known Failures

Generated: 2026-07-02

There are no accepted failing quality gates at the time this file was created. Do not merge new work with failing checks unless the failure is pre-existing, reproduced, and recorded below with an owner and next step.

## Current Status

| Command | Status | Notes |
| --- | --- | --- |
| `composer ci` | Passing on 2026-07-02 | Runs Composer validation/audit, Pint, Larastan/PHPStan, 496 PHPUnit tests, npm quality, Mix production build, and Laravel cache smoke checks. |
| `php artisan test tests/Feature/ProductionExposureAuditTest.php` | Passing on 2026-07-02 | Confirms debug defaults, absent debug tools, public artifact cleanup, install dump location, and Apache deny rules. |
| `npm audit --omit=dev --audit-level=moderate` | Passing on 2026-07-02 | Production Node dependency audit found no vulnerabilities. |

## Local Cache Note

If a production-default test fails locally immediately after config changes, run `php artisan optimize:clear` and retry. Do not commit stale `bootstrap/cache/*` files.

## Failure Log Template

When a quality gate fails and cannot be fixed safely in the current task, add a new entry with:

- Date.
- Exact command.
- Short output summary.
- Risk.
- Reason it was not fixed now.
- Next step.
