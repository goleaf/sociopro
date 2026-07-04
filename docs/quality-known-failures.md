# Quality Known Failures

Generated: 2026-07-02

There are no accepted failing quality gates at the time this file was created. Do not merge new work with failing checks unless the failure is pre-existing, reproduced, and recorded below with an owner and next step.

## Current Status

| Command | Status | Notes |
| --- | --- | --- |
| `php artisan test` | Failing on 2026-07-04 | Local full-suite run during the agent-hooks slice ended with 19 failed and 1066 passed tests. Failures are outside the hook/doc changes and are recorded below for focused follow-up. |
| `composer ci` | Passing on 2026-07-02 | Runs Composer validation/audit, Pint, Larastan/PHPStan, 496 PHPUnit tests, npm quality, Mix production build, and Laravel cache smoke checks. |
| `php artisan test tests/Feature/ProductionExposureAuditTest.php` | Passing on 2026-07-02 | Confirms debug defaults, absent debug tools, public artifact cleanup, install dump location, and Apache deny rules. |
| `npm audit --omit=dev --audit-level=moderate` | Passing on 2026-07-02 | Production Node dependency audit found no vulnerabilities. |

## Failure Log

### 2026-07-04 - Full PHPUnit Suite

- Exact command: `php artisan test`
- Result: 19 failed, 1066 passed, 22438 assertions, duration 32.02s.
- Short output summary:
  - `Tests\Feature\AddonPackageImportTest`: 8 failures. Several addon import cases throw `RuntimeException` with "You have to update your main application's version." or "It looks like you are skipping a version."; the unsafe nested zip case expected "Addon package contains an unsafe path." but saw the version exception first.
  - `Tests\Feature\AuthBadgeBlogControllerTest::badge payment configuration builds checkout details`: expected badge price `23`, got `null`.
  - `Tests\Feature\BackfillLegacyJsonColumnsCommandTest`: 3 failures around expected non-zero exit codes and expected `{}` backfill values.
  - `Tests\Feature\BuildBadgePageDataActionTest::confirmation builds badge checkout data`: expected badge price `19`, got `null`.
  - `Tests\Feature\DateTimeValidationTest::user ad date range is inclusive`: expected content from `get_settings('ad_charge_per_day')` but response content was `0`.
  - `Tests\Feature\EloquentCastAuditTest`: `App\Models\Currency` was not found.
  - `Tests\Feature\JitsiStreamingViewTest`: settings view tried to read `zitsi_configuration` array keys from `null`.
  - `Tests\Feature\MarketplaceAuthorizationTest::api guest keeps legacy unauthorized update payload`: expected HTTP 200 legacy payload, got 401.
  - `Tests\Feature\ZoomMeetingWorkflowTest`: 2 failures from `DomainException` saying the provided JWT key is too short.
- Risk: The full suite is not currently a reliable green release gate; unrelated red tests can hide regressions in focused work.
- Reason not fixed now: the current slice changes agent hook scripts, subagent docs, and hook documentation only; fixing badge/addon/settings/API/Zoom app behavior would be a separate application task.
- Next step: assign focused repair slices for addon version fixtures, settings seed/defaults, legacy JSON backfill command expectations, marketplace auth contract drift, and Zoom test credentials/fakes. Re-run `php artisan test` after those slices.

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
