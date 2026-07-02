# Test Suite Audit

Generated: 2026-07-02

## Scope

This audit reviews the current Laravel test suite for missing feature tests, unit tests, API contract tests, authorization tests, validation tests, database tests, frontend tests, flaky tests, slow tests, factory gaps, external API calls, and CI gaps. It is documentation-only and does not refactor application or test code.

## Current Test Inventory

| Area | Current state |
| --- | --- |
| Test framework | PHPUnit 12 through `php artisan test` |
| Test files | 89 total: 78 feature, 11 unit |
| Test count | 496 tests, 14,480 assertions |
| Full suite runtime | 40.30s with `php artisan test --profile --compact` |
| Slowest test | `ApiRateLimitAuditTest::api password reset is rate limited` at 1.13s |
| Slowest 10 tests | 4.17s total, 10.35% of suite runtime |
| App routes | 503 application routes: 135 API, 124 admin, 11 install, 14 auth-like, 219 other web |
| Route methods | 329 GET, 184 POST, 8 DELETE, 8 PATCH, 8 PUT, 8 OPTIONS |
| Factories | 11 factories for 54 models; 43 models have no dedicated factory |
| Frontend surface | 291 Blade files, 2 JS entry files, 1 CSS entry file |
| Frontend tests | No Dusk, Playwright, Cypress, Vitest, Jest, or browser accessibility test runner configured |
| CI gates | Composer validate/audit, Pint, Larastan/PHPStan, PHPUnit, cache smoke checks, migration smoke check, npm audit/lint/style/format/build |

## Verification

| Command | Result |
| --- | --- |
| `php artisan test --profile --compact` | Passed: 496 tests, 14,480 assertions, 40.30s |
| `php artisan route:list --json --except-vendor` | Passed: 503 application routes inspected |
| Repository scans for tests, factories, app classes, routes, frontend assets, package scripts, and CI | Completed |

## Executive Summary

The suite is much healthier than a typical legacy Laravel project: it has broad security, route, auth, validation, migration, model, and performance regression coverage, and CI runs real PHP and frontend quality gates. The primary weakness is not raw test count; it is uneven coverage against the large legacy surface. Many tests are audit-style guardrails, while large modules such as groups, events, video, profile albums/photos, reports/search, admin CRUD, badges, sponsors, paid content, and several payment paths still lack direct behavior and contract tests.

The highest-risk gaps are API contract coverage for the 135 API routes, authorization coverage for state-changing web/admin routes, missing factories for most legacy models, absent browser/accessibility tests for Blade-heavy workflows, and incomplete external provider isolation for SDK-based payment flows.

## Prioritized Findings

### P0 - API Contract Coverage Is Too Thin For The Route Surface

| Field | Detail |
| --- | --- |
| Risk | High |
| Evidence | The app exposes 135 API routes, mostly through `ApiController`; current API tests focus on marketplace, notifications, tokens, error format, rate limits, sensitive leaks, and selected auth behavior. |
| Impact | Legacy mobile/API clients can break response shape, status code, auth behavior, pagination, or validation semantics without a failing test. |
| Missing tests | Contract tests for albums, blogs, events, groups, jobs, paid content, profile media, videos, fundraisers, comments/reactions, search/filtering, and notification accept/decline endpoints. |
| Safe first step | Create `tests/Feature/ApiContractSmokeTest.php` with a route data provider that covers representative GET/POST endpoints for each API module, asserting status code, JSON envelope shape, auth requirement, and no sensitive fields. |

### P0 - Authorization Coverage Is Uneven Across State-Changing Routes

| Field | Detail |
| --- | --- |
| Risk | High |
| Evidence | A heuristic route scan found 50 GET route candidates with state-changing names such as delete, status, join, block, like, save, and unsave. Existing tests cover admin middleware, marketplace, pages, media downloads, user ads, and tokens, but not every candidate route. |
| Impact | IDOR, CSRF-like state changes over GET, and role bypasses can survive because route-level access is not consistently asserted. |
| Missing tests | Guest, wrong-user, wrong-role, owner, admin, and disabled-user cases for groups, events, videos, comments, post saves, page likes, profile album/photo actions, account status toggles, admin CRUD deletes/status updates, and payment/admin settings routes. |
| Safe first step | Add a route-risk data provider that lists state-changing routes and marks the expected guard/middleware/status, then fill it one module at a time with real authorization assertions. |

### P0 - External Payment Provider Isolation Is Incomplete

| Field | Detail |
| --- | --- |
| Risk | High |
| Evidence | PayPal, Paystack, and Zoom tests use `Http::fake()`. Stripe uses static SDK calls, Razorpay creates an SDK client, Paytm uses `PaytmWallet`, and Flutterwave/Razorpay/Stripe/Paytm paths do not have equivalent no-network contract tests. No global `Http::preventStrayRequests()` guard was found. |
| Impact | Future tests can accidentally hit payment providers or miss provider-specific failures and credential handling. |
| Missing tests | Stripe create/status with mocked SDK boundary, Razorpay order creation/status with mocked client, Paytm callback success/failure/open states behind a facade fake, Flutterwave status/create behavior, missing credentials for every provider, and no-real-network guard coverage. |
| Safe first step | Introduce provider adapter seams or container-bound wrappers for SDK/facade calls, then add tests that prove each payment provider can be exercised without network access or real credentials. |

### P1 - Factory Coverage Is Far Behind Model Count

| Field | Detail |
| --- | --- |
| Risk | High |
| Evidence | 54 models exist, but only 11 factories exist. Missing factories include `Event`, `Group`, `Job`, `JobApply`, `MediaFile`, `PaymentGateway`, `PaymentHistoryEntry`, `Stories`, `Video`, `Sponsor`, `Report`, `Notification`, and many pivot-like/domain models. |
| Impact | Tests rely on manual inserts, legacy dump state, and duplicated setup. This increases brittleness and makes authorization/API tests expensive to add. |
| Missing tests | Factory validity tests for every business model and useful states for owner/admin/disabled/user media/payment/job/event/group scenarios. |
| Safe first step | Add factories in priority order for models used by high-risk routes: `Event`, `Group`, `Video`, `Job`, `JobApply`, `MediaFile`, `PaymentGateway`, `PaymentHistoryEntry`, `Stories`, `Sponsor`, and `Report`. |

### P1 - Missing Feature Tests For Large Web Modules

| Field | Detail |
| --- | --- |
| Risk | High |
| Evidence | Strong coverage exists for auth, marketplace, pages, blogs, installer, media download, security audits, and several payment checks. Large controllers such as `GroupController`, `EventController`, `VideoController`, `Profile`, `CustomUserController`, `AdminCrudController`, `BadgeController`, `SponsorController`, and `Report\SearchController` have only partial or indirect tests. |
| Impact | Core social workflows can regress in validation, redirects, flash/session state, ownership, file handling, and Blade data contracts. |
| Missing tests | Create/update/delete/list/show flows for groups, events, videos, profile albums/photos, friendships beyond accept/send, comments/reactions, search, sponsors, badges, admin lookup CRUD, admin settings, and user payment history. |
| Safe first step | Start with one module per PR: add happy path, validation failure, guest denial, wrong-owner denial, allowed-owner/admin path, and database/file side effects. |

### P1 - Validation Tests Are Concentrated In Refactored Areas

| Field | Detail |
| --- | --- |
| Risk | Medium-High |
| Evidence | Marketplace, pages, blogs, dates, unique rules, list endpoints, and selected uploads are tested well. Many legacy controllers still use broad request handling and lack endpoint-specific validation tests. |
| Impact | Invalid files, unsafe IDs, enum/status values, dates, pagination, sorting, and nested arrays can reach business logic in older modules. |
| Missing tests | Group/event/video/job/profile/admin CRUD validation, upload size/MIME/dimensions, route ID validation, enum/status values, pagination/sorting bounds, and update uniqueness. |
| Safe first step | For each controller moved to Form Requests, add validation tests before replacing inline validation or raw request access. |

### P1 - Database Tests Are Strong For Migrations But Weak For Domain Integrity

| Field | Detail |
| --- | --- |
| Risk | Medium-High |
| Evidence | Migration safety, constraints, indexes, nullable/money/date/json/pivot/soft-delete audits are strong. Domain workflow tests do not consistently assert relationship integrity, cascade behavior, or uniqueness around business actions. |
| Impact | Database constraints can pass while workflows still create orphaned rows, duplicate pivots, or inconsistent counters/statuses. |
| Missing tests | Event/group membership uniqueness, notification ownership, comments/replies lifecycle, media attachment ownership, job application uniqueness, payment history idempotency, paid content package relations, and admin deletes with expected restrict/cascade behavior. |
| Safe first step | Add workflow-level database assertions around the highest-risk delete/status/update routes before changing schema or controller logic. |

### P1 - Frontend And Accessibility Tests Are Absent

| Field | Detail |
| --- | --- |
| Risk | Medium-High |
| Evidence | The app has 291 Blade files and many inline AJAX/form behaviors, but no browser runner or JS unit test framework is configured. CI runs ESLint, Stylelint, Prettier, and Mix production build only. |
| Impact | Broken modals, AJAX flows, inaccessible controls, missing focus states, invalid form labels, and JavaScript regressions can ship while PHP tests pass. |
| Missing tests | Browser smoke tests for login/register, timeline post create/edit, marketplace create/update, chat upload, profile media, admin CRUD, payment flow pages, and mobile-width rendering. Accessibility checks for labels, headings, buttons/links, keyboard navigation, and color contrast. |
| Safe first step | Add a small Playwright or Laravel Dusk smoke suite for the top five user journeys, then add axe accessibility checks for key Blade pages. |

### P2 - Unit Test Layer Is Too Small For The Amount Of Domain Logic

| Field | Detail |
| --- | --- |
| Risk | Medium |
| Evidence | Only 11 unit test files exist. Useful unit tests exist for money, URL safety, file upload, parser, enums, API errors, and marketplace query logic. Many Actions, services, rules, DTO-like mappers, and payment boundaries are still mostly feature-tested or untested. |
| Impact | Feature tests carry too much weight, making failures harder to diagnose and refactors slower. |
| Missing tests | Unit tests for install actions, friend actions, page profile view-data action, payment service adapters, upload validation rule variants, query objects/scopes, middleware helpers, and complex model accessors/scopes. |
| Safe first step | For every new action/service extraction, add direct unit tests for success, failure, boundary input, and exception path before wiring it into controllers. |

### P2 - Flakiness Risk Exists Around Global DB Configuration And Time

| Field | Detail |
| --- | --- |
| Risk | Medium |
| Evidence | `tests/TestCase.php` imports the legacy install schema into in-memory SQLite for every app test. Some installer/import tests temporarily switch SQLite database paths and DB connection extensions. Tests also use `now()` and `time()` in many places without freezing time. |
| Impact | Order-dependent failures can appear when config/DB state leaks between tests or when boundary-date assertions run near midnight/timezone changes. |
| Missing tests/process | No random-order CI job, repeat-run job, or explicit leak detector for DB config changes. |
| Safe first step | Add a focused test helper for temporary DB connection mutation that always restores config, then add a nightly or manual CI job that runs the suite twice or with randomized order if PHPUnit support is enabled. |

### P2 - Placeholder And Misplaced Tests Add Noise

| Field | Detail |
| --- | --- |
| Risk | Low-Medium |
| Evidence | `tests/Unit/ExampleTest.php` and `tests/Feature/Unit/ExampleTest.php` assert `true`; `tests/Feature/Unit/ExampleTest.php` is nested under the feature suite while using a unit namespace. |
| Impact | Inflates suite confidence and creates confusing structure for future agents. |
| Safe first step | Replace placeholders with real smoke tests or delete them in a dedicated test-hygiene commit after confirming no CI tooling expects them. |

### P2 - Large Test Files Are Becoming Maintenance Hotspots

| Field | Detail |
| --- | --- |
| Risk | Low-Medium |
| Evidence | `MigrationSafetyAuditTest.php` is 1,147 lines, `MarketplaceAuthorizationTest.php` 788 lines, `ApiMarketplaceValidationTest.php` 717 lines, and several other tests exceed 300 lines. |
| Impact | Large files slow review, hide duplicated setup, and make it harder to add module-specific coverage cleanly. |
| Safe first step | Split only when touching the domain: move shared builders into helper methods or factories, then split by feature boundary such as marketplace API validation, marketplace ownership, and marketplace saved-products behavior. |

### P3 - CI Has Quality Gates But No Coverage, Mutation, Flake, Or Browser Signals

| Field | Detail |
| --- | --- |
| Risk | Medium |
| Evidence | CI runs strong PHP and frontend quality gates but does not collect PHPUnit coverage, publish JUnit artifacts, track slow tests over time, run browser/accessibility tests, run tests in random order, or run tests against a production-like non-SQLite database. |
| Impact | Coverage gaps and flaky/order-dependent behavior are invisible until a human notices. SQLite-only CI can miss MySQL/PostgreSQL behavior if production uses a different engine. |
| Safe first step | Add JUnit and coverage artifact collection first, then add optional scheduled jobs for random/repeated suite runs and browser smoke tests. Add a production-engine DB job only after the target production database is confirmed. |

## Recommended Implementation Order

1. Add factory coverage for the top workflow models: events, groups, videos, jobs/applications, media files, payment gateways/history, stories, sponsors, reports, and notifications.
2. Add route-risk authorization tests for state-changing GET/admin/web routes, starting with delete/status/payment/settings routes.
3. Add API contract smoke tests by module for the 135 API routes, preserving current response shapes before refactors.
4. Add provider isolation tests and SDK seams for Stripe, Razorpay, Paytm, and Flutterwave.
5. Add feature tests for groups, events, videos, profile albums/photos, comments/reactions, search, badges, sponsors, and admin CRUD.
6. Add missing validation tests while converting legacy endpoints to Form Requests.
7. Add browser smoke tests and accessibility checks for the highest-value Blade journeys.
8. Split oversized tests only as the relevant module receives new coverage.
9. Add CI artifacts for JUnit/coverage, then scheduled flake/random-order checks.
10. Remove or replace placeholder example tests once real smoke coverage exists.

## CI Gap Checklist

- [ ] Publish PHPUnit JUnit output as a CI artifact.
- [ ] Publish coverage summary or HTML/XML coverage artifact when Xdebug/PCOV is available.
- [ ] Add a scheduled repeated test run to detect state leaks.
- [ ] Add a random-order test run if the PHPUnit configuration supports it safely.
- [ ] Add a browser/accessibility job after choosing Playwright or Dusk.
- [ ] Add a no-real-network test guard for Laravel HTTP client and payment SDK seams.
- [ ] Add a production-like database job once the production engine is confirmed.
- [ ] Track top slow tests over time.

## Do Not Do Yet

- Do not delete placeholder tests in the same commit as this audit.
- Do not introduce a browser runner without a separate setup PR and CI budget review.
- Do not convert every route to tests at once; the route surface is too large for one safe review.
- Do not add factories with fake relationships that violate current legacy schema expectations.
- Do not claim API contracts are stable until representative mobile/client response fixtures exist.
