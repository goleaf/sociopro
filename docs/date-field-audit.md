# Date Field Audit

Date: 2026-07-02

## Scope

Audited schema, models, validation, controllers, query objects, Blade view models, existing migrations, and tests for `created_at`, `updated_at`, `deleted_at`, `published_at`, `verified_at`, `paid_at`, `expires_at`, and custom date columns.

The application is still bootstrapped from `public/assets/install.sql`, so this pass only applies changes that are safe, reversible, and covered by tests. Broad timestamp normalization remains deferred because multiple tables still use legacy string or epoch timestamps.

## Safe Changes Applied

| Area | Field or query path | Previous risk | Change | Risk |
| --- | --- | --- | --- | --- |
| Database | `personal_access_tokens.expires_at` | Stored as `varchar(255)` even though Sanctum expiry logic treats it as a nullable datetime. | Added a guarded reversible migration that converts clean values to nullable `dateTime` and skips invalid dirty values. | Medium |
| Database | token expiry pruning / lookup | No date-friendly index on token expiry. | Added `personal_access_tokens_expires_id_idx` on `expires_at, id`. | Small |
| Database | active sponsor slots | Existing sponsor date index starts with `user_id`, so global active sponsor queries by `status`, `start_date`, and `end_date` are not covered. | Added `sponsors_status_start_end_id_idx`. | Small |
| Database | verified user lookups | API code filters users by `email_verified_at` without an index. | Added `users_email_verified_id_idx`. | Small |
| Models | `Users.email_verified_at`, `Users.lastActive` | Legacy `Users` model returned raw strings while the auth `User` model cast the same columns as datetimes. | Added formatted datetime casts to keep array/JSON output in legacy `Y-m-d H:i:s` shape. | Small |
| Models | `Posts.posted_on` | Custom post lifecycle date was a timestamp column but returned as an untyped string. | Added a formatted datetime cast that preserves legacy serialization. | Small |

## Current Field Findings

| Field family | Current state | Decision |
| --- | --- | --- |
| `created_at`, `updated_at` | Mixed between real timestamp columns, `varchar(100)` columns, `CURRENT_TIMESTAMP` defaults, and code paths that write integer epochs with `time()`. | Deferred. Converting all timestamp fields now would change ordering, serialization, and existing integer comparisons. |
| `deleted_at` | No canonical `deleted_at` columns or `SoftDeletes` usage are currently present. | No schema change. Add soft deletes only with explicit recovery/audit requirements, policies, and scoped tests. |
| `published_at` | No canonical `published_at` column was found in the current dump-backed core schema. Some features use status flags instead. | No schema change. Future publish workflows should use nullable `dateTime('published_at')` plus status transition tests. |
| `verified_at` | `users.email_verified_at` exists as a nullable timestamp and is cast on the auth model. No generic `verified_at` column was found. | Added a supporting index and kept the existing Laravel-compatible name. |
| `paid_at` | No canonical `paid_at` field was found in core tables. Payments currently use `payment_histories.created_at` plus status/provider identifiers. | Deferred. Add `paid_at` only during a payment lifecycle refactor with provider callback tests. |
| `expires_at` | `personal_access_tokens.expires_at` exists as text in the legacy dump. | Converted safely when clean and indexed for expiry workflows. |
| Custom date fields | `users.lastActive`, `users.date_of_birth`, `posts.posted_on`, `events.event_date`, `events.event_time`, `sponsors.start_date`, `sponsors.end_date`, `batchs.start_date`, and `batchs.end_date` are active custom date paths. | Cast safe datetime columns. Keep browser date-only and time-only strings as strings until public output and validation are refactored together. |

## Timezone Contract

`config/app.php` currently sets the application timezone to `Asia/Dhaka`. Existing persisted values must not be reinterpreted as UTC in this slice.

Current behavior:

- Legacy writes using `now()`, `date()`, and `time()` follow the current application/server behavior.
- `users.timezone` is used for presentation-time personalization in selected view-model paths.
- Birth dates are stored as legacy integer timestamps based on `config('app.timezone')`.
- Browser date-only inputs such as `events.event_date` are stored as `Y-m-d` strings and should not be cast to full datetimes without UI/API compatibility tests.

Future target:

- New schema should use UTC-backed datetime columns and convert to user timezone only at the presentation/API boundary.
- Any switch from `Asia/Dhaka` to UTC requires a migration plan, backfill rules, API serialization tests, and production data sampling.

## Migration Safety

Migration: `database/migrations/2026_07_02_190000_add_safe_legacy_datetime_column_constraints.php`

The migration:

- checks table, column, and index existence;
- converts only `personal_access_tokens.expires_at`;
- accepts only strict datetime strings before conversion;
- skips the type change if invalid dirty data exists;
- adds only non-unique indexes;
- implements a reversible `down(): void`.

## Deferred Cleanup

| Area | Why deferred | Safe first step |
| --- | --- | --- |
| String-backed timestamps | Many controllers still write Unix epochs or formatted strings to `created_at` / `updated_at`. | Add per-table regression tests for ordering, serialization, and date filtering before converting one domain at a time. |
| `events.event_date` / `event_time` | Stored as browser date and time strings and exposed through existing web/API flows. | Introduce a combined event starts-at value object or column only after preserving existing request and response contracts. |
| `users.date_of_birth` | Stored as a legacy integer timestamp and validated by existing profile/admin flows. | Keep integer cast until a date-only column migration with display/backfill tests is planned. |
| `created_at` indexes on text or epoch columns | Some legacy fields are strings but filtered numerically or by date functions. | Normalize the write path first, then add or adjust indexes based on the final type. |
| Payment lifecycle dates | No `paid_at` exists; payment state is inferred from history rows and provider status. | Add explicit payment state transitions before introducing `paid_at`. |

## Tests Added or Updated

- `MigrationSafetyAuditTest` covers datetime migration type conversion, indexes, rollback, and dirty-data skipping.
- `EloquentCastAuditTest` covers custom lifecycle date casts and legacy serialization.
- `DateFieldAuditTest` guards this audit document and the timezone/deferred-risk notes.
