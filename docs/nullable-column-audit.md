# Nullable Column Audit

Date: 2026-07-02

## Scope

Audited live SQLite metadata imported from `public/assets/install.sql`, current additive migrations, required / nullable validation rules, factories, seeders, auth flows, payment flows, settings reads, and high-use reference tables.

This project still has a legacy dump-backed schema where many business-required columns were declared `DEFAULT NULL`. This pass only adds reversible database constraints where current data and known write paths support the change.

## Safe Migration Added

Added `database/migrations/2026_07_02_170000_add_safe_legacy_nullable_column_constraints.php`.

The migration checks table existence, column existence, null data, blank string data for required string keys, and current column metadata before changing a column. If production data contains null or blank values for a candidate column, that column is skipped instead of failing deployment.

## Columns Made Required

| Table | Column | Default | Reason |
|---|---|---:|---|
| `account_active_requests` | `status` | `pending` | Account activation requests are state records; code creates them with pending status and admin counts depend on status. |
| `currencies` | `name` | none | Currency display name is required reference data. |
| `currencies` | `code` | none | Currency code is the lookup key and is already uniquely constrained. |
| `currencies` | `symbol` | none | Marketplace/payment display requires a currency symbol. |
| `currencies` | `paypal_supported` | `0` | Support flag should be explicit, not nullable. |
| `currencies` | `stripe_supported` | `0` | Support flag should be explicit, not nullable. |
| `payment_gateways` | `identifier` | none | Payment routing resolves gateways by identifier. |
| `payment_gateways` | `currency` | none | Payment gateway currency is required for payment display/config. |
| `payment_gateways` | `title` | none | Admin/payment UI needs a gateway title. |
| `payment_gateways` | `test_mode` | `1` | New gateways should default to test mode rather than production mode. |
| `payment_gateways` | `status` | `0` | New gateways should default disabled until configured. |
| `payment_gateways` | `is_addon` | `0` | Core gateways are not addon-owned by default. |
| `settings` | `type` | none | Settings are keyed by `type`; null keys cannot be retrieved safely. |
| `users` | `name` | none | Registration/admin validation requires a name. |
| `users` | `email` | none | Auth, password reset, and unique validation require an email. |
| `users` | `password` | none | Password-authenticated users must have a password hash. |
| `users` | `user_role` | `general` | Middleware and policies branch by role; defaulting prevents null-role accounts from bypassing clear states. |
| `users` | `status` | `0` | New accounts default disabled unless an explicit flow activates them. |

## Validation Mismatches Fixed

- Registration and admin user creation already require `users.name`, `users.email`, and `users.password`, but the database allowed nulls.
- User middleware treats `users.user_role` and `users.status` as required state, but the database allowed nulls.
- Marketplace/payment code treats `currencies.code`, currency display fields, and support flags as reference data, but the database allowed nulls.
- Payment gateway resolution treats `payment_gateways.identifier`, currency, title, and state flags as required operational fields, but the database allowed nulls.
- Settings reads use `settings.type` as the key for every settings lookup, but the database allowed null keys.

## Deferred Cleanup-First Items

| Candidate | Why not changed now | Safe first step |
|---|---|---|
| `languages.name`, `languages.phrase`, `languages.translated` | Current data is clean, but `LanguageController` can still write unvalidated language and translated values. | Add Form Request validation for language create/update/phrase update, then add a required-column migration. |
| `payment_gateways.model_name` | Current data is clean, but existing tests and custom gateway creation can create a gateway row before force-filling `model_name`. | Update payment gateway creation to set `model_name` atomically, then require it. |
| `users.username`, `users.friends`, `users.followers`, `users.timezone`, `users.lastActive` | Current dump row is populated, but factories and some legacy insert paths omit these values. Text/longtext defaults are also not safely portable across MySQL versions. | Normalize factories and user creation actions first; prefer app-level defaults for JSON text fields before DB constraints. |
| `settings.description` | Several settings intentionally use blank or optional descriptions. | Audit each setting type and add per-setting validation/defaults rather than one table-wide rule. |
| `comments.parent_id` | Legacy schema uses `0` as a required sentinel for root comments. | Convert root comments to nullable `parent_id`, update queries to use null semantics, then add a foreign key if safe. |
| `chats.thumbsup`, `chats.read_status`, `invites.is_accepted`, `posts.report_status`, `reports.status` | These already use non-null integer flags with legacy `0` defaults; the main risk is enum/type semantics, not nullability. | Convert to typed enums/constants and validation in a separate behavior-preserving pass. |
| `feeling_and_activities.feeling_and_activity_id` | MySQL dump defines it as required, while SQLite import metadata reports it differently because it is a legacy non-standard key. | Fix importer/model primary-key handling separately before changing nullability. |

## Cleanup Steps For Skipped Required Columns

1. Add or tighten Form Request validation for each write path that can create or update the candidate column.
2. Run production duplicate/null/blank reports for the candidate column.
3. Backfill nulls and blanks to canonical values, or delete invalid rows when safe.
4. Add a follow-up nullable-column migration after cleanup passes.
5. Do not rely on rerunning an already-applied migration that skipped dirty columns; create a new migration for cleanup-completed constraints.

## Verification

`tests/Feature/MigrationSafetyAuditTest.php` verifies the nullable-column migration can run `up()`, `down()`, and `up()` again. It also seeds dirty null data and confirms the migration skips the blocked column until the data is cleaned.
