# Test Performance Notes

Last profiled: 2026-07-02

## Summary

- Baseline before this optimization pass: `php artisan test --profile --compact` passed 497 tests in 40.84s. The top 10 slowest tests accounted for 4.19s.
- After this optimization pass: `php artisan test --profile --compact` passed 497 tests in 38.63s. The top 10 slowest tests accounted for 3.07s.
- Coverage intent was preserved: repeated identical HTTP assertions were replaced with deterministic setup around the same framework throttling buckets, while each optimized rate-limit path still verifies an allowed request and the throttled response.

## Changes Applied

- `tests/Feature/ApiRateLimitAuditTest.php`
  - Replaced repeated full HTTP loops with exact Laravel rate-limiter priming.
  - Kept one successful request and one `429` assertion for each throttled path.
  - Avoided repeated token generation, password broker work, search calls, and contact-mail flow traversal.

- `tests/Feature/Auth/PasswordResetTest.php`
  - Reused Laravel's password broker to create valid reset tokens directly in token-consumer tests.
  - Kept the dedicated reset-link notification test unchanged so mail notification coverage remains.

- `tests/Feature/RolePermissionAuditTest.php`
  - Cached source file discovery and comment-stripped file contents inside the PHPUnit process.
  - Preserved all scanned directories, regex rules, and assertions.

## Remaining Slow Tests

| Test | Last Profile | Reason | Safe Next Step |
| --- | ---: | --- | --- |
| `Tests\Feature\MigrationSafetyAuditTest > project migrations run from imported schema cleanly` | 0.74s | Runs migration safety checks against the imported legacy schema. | Keep as-is unless migration audit coverage is split into narrower targeted fixtures. |
| `Tests\Feature\MigrationSafetyAuditTest > safe legacy foreign key constraints are present and reversible` | 0.31s | Exercises constraint add/drop behavior for production-risk migrations. | Keep as-is; this is valuable high-risk database coverage. |
| `Tests\Feature\ApiRateLimitAuditTest > api password reset is rate limited by email and client` | 0.29s | Still makes one real API password reset request to preserve endpoint coverage. | Consider direct limiter-only coverage only if a separate API forgot-password success test exists. |
| `Tests\Feature\Auth\PasswordResetTest > reset password link can be requested` | 0.28s | Sends the actual reset notification through Laravel's notification fake. | Keep as-is; it is the canonical reset-link mail coverage. |
| `Tests\Feature\Auth\PasswordConfirmationTest > password is not confirmed with invalid password` | 0.28s | Exercises real password validation failure through the web guard. | Keep as-is unless a lower-cost hash configuration is introduced globally for testing. |
| `Tests\Feature\InstallWizardTest > install post steps reject unexpected http methods` | 0.28s | Boots route handling and checks two unsupported method responses. | Low priority; already concise and behavior-focused. |
| `Tests\Feature\Auth\AuthenticationTest > users can not authenticate with invalid password` | 0.28s | Exercises real invalid password handling. | Keep as-is unless testing hash cost is safely lowered. |
| `Tests\Feature\MigrationSafetyAuditTest > foreign key rollback preserves pre existing helper named indexes` | 0.22s | Verifies rollback behavior around existing indexes. | Keep as-is; protects migration rollback safety. |
| `Tests\Unit\FileUploaderTest > local upload writes resized public files through storage disk` | 0.21s | Performs real image resizing through faked storage. | Keep as-is unless image processing can use a smaller fixture without reducing assertions. |
| `Tests\Feature\ListEndpointValidationTest > admin users datatable caps per page and rejects unsafe sort fields` | 0.19s | Exercises HTTP validation and database-backed listing behavior. | Keep as-is; cost is modest and coverage is useful. |

## Guardrails

- Do not replace high-risk migration tests with shallow source scans.
- Do not remove real notification coverage from the reset-link request test.
- Do not remove the one real allowed request from each rate-limit audit path unless another test explicitly covers that endpoint's non-throttled behavior.
- Re-run `php artisan test --profile --compact` before and after future performance work so slow-test notes stay evidence-based.
