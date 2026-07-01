# Database Standards

Generated: 2026-07-02

These standards apply to all new database design, schema changes, migrations, seeders, factories, models, and refactor slices in this Laravel project. Existing legacy tables may violate these rules because the application is still partly bootstrapped from `public/assets/install.sql`; do not normalize legacy schema in-place without a tested migration plan.

Use this document together with:

- `AGENTS.md`
- `docs/coding-standards.md`
- `docs/project-standards-bible.md`
- `docs/migration-audit.md`
- `docs/risk-register.md`

## Core Rules

- New schema changes MUST be represented by Laravel migrations.
- Existing production migrations MUST NOT be edited. Add a new migration.
- Migrations MUST be reversible with `down(): void`, unless irreversibility is explicitly documented with a rollback plan.
- Destructive changes require an expand-contract plan, backup notes, data quality checks, and explicit approval.
- Do not use raw SQL when Laravel schema builder can express the change.
- Do not add foreign keys, unique constraints, type changes, or nullability changes to legacy tables until data quality checks prove they are safe.
- All new schema names MUST be clear, consistent, lowercase snake_case, and domain-focused.
- Eloquent models, validation rules, factories, and tests MUST be updated with schema changes.

## Table Names

- Use plural snake_case table names: `users`, `posts`, `payment_histories`.
- Use clear domain names instead of abbreviations: `notifications`, not `notifs`.
- Avoid misspellings in new tables. Legacy names such as `message_thrades`, `reciver_id`, and `batchs` remain only for backward compatibility.
- For module tables, group by domain prefix only when it improves discovery: `marketplace_orders`, `marketplace_order_items`.
- Do not use reserved SQL words such as `order`, `group`, `user`, `type`, or `status` as table names.
- Lookup/reference tables SHOULD use plural nouns: `categories`, `currencies`, `payment_gateways`.
- Audit/history tables SHOULD use clear suffixes: `*_histories`, `*_events`, or `*_logs`.

## Primary Keys

- New tables SHOULD use Laravel's default `id` primary key with `bigIncrements()` or `$table->id()`.
- Use UUID/ULID primary keys only when the public/API contract or distributed-write model requires them.
- Do not expose auto-increment IDs as security boundaries. Authorization must not depend on hard-to-guess identifiers.
- Legacy tables with non-standard keys, such as `posts.post_id`, `comments.comment_id`, `stories.story_id`, and `settings.setting_id`, require explicit model `$primaryKey` settings.
- Composite primary keys SHOULD be avoided in Eloquent-managed tables. Use a surrogate primary key plus unique constraints for domain uniqueness.

## Foreign Keys

- New relationship columns MUST use `{singular_model}_id`, for example `user_id`, `post_id`, or `payment_gateway_id`.
- Foreign key column types MUST match the referenced primary key type.
- Add indexes for every foreign key column.
- Add actual database foreign key constraints when lifecycle and delete behavior are understood.
- Choose delete behavior intentionally:
  - `cascadeOnDelete()` only when child rows have no useful meaning without the parent.
  - `nullOnDelete()` only when the child can remain valid without the parent.
  - `restrictOnDelete()` when deletion must be blocked until dependencies are handled.
  - Application-managed cleanup when legacy behavior or cross-domain workflows require custom rules.
- Never add foreign keys to legacy tables before running orphan reports and documenting cleanup behavior.
- Do not create nullable foreign keys unless the relationship is genuinely optional.
- Validation rules SHOULD mirror database relationships with safe `exists` rules where appropriate.

## Polymorphic Columns

- Prefer explicit foreign keys over polymorphic relations when the set of target models is small and stable.
- Use polymorphic relations only when they remove real duplication and the ownership/lifecycle rules are documented.
- Standard column names are `{name}_type` and `{name}_id`, for example `subject_type` and `subject_id`.
- Polymorphic type values SHOULD use Laravel morph maps instead of raw class names in the database.
- Add a composite index on polymorphic pairs: `[$name.'_type', $name.'_id']`.
- Include a domain-specific discriminator or status column only when it is used by queries and validation.
- Document allowed target models and delete behavior before introducing a polymorphic relation.

## Pivot Tables

- Pivot tables SHOULD be named with singular model names in alphabetical order: `role_user`, `group_user`, `post_tag`.
- If the pivot has domain meaning, timestamps, statuses, permissions, metadata, or its own lifecycle, model it as a first-class table instead of a simple pivot.
- Simple pivots SHOULD contain only the two foreign keys plus optional timestamps.
- Add a unique constraint on the pair of foreign keys to prevent duplicate membership rows.
- Add indexes in both lookup directions when both sides query the relation.
- Pivot foreign keys SHOULD cascade or restrict based on the relationship lifecycle.
- Use custom pivot models only when pivot behavior, casts, events, or methods are needed.

## Timestamps

- New tables SHOULD use `$table->timestamps()` unless the data is immutable reference data.
- Use nullable timestamps only when a row may legitimately exist before timestamps are known.
- Use `$table->timestamp('published_at')->nullable()` or `$table->dateTime('published_at')->nullable()` for lifecycle moments.
- Store application timestamps in UTC. Convert for display at the application boundary.
- Do not store timestamps as strings in new schema.
- Do not mix integer epoch, text date, and timestamp columns for the same domain in new schema.
- Avoid `ON UPDATE CURRENT_TIMESTAMP` unless it is an explicit database-level requirement and covered by tests.

## Soft Deletes

- Use `$table->softDeletes()` only when business rules require recovery, audit visibility, or delayed cleanup.
- Do not add soft deletes as a default habit.
- When a table uses soft deletes, update model traits, queries, policies, exports, and unique constraints.
- Unique constraints with soft deletes need explicit design, such as partial indexes where supported or application-level conflict handling.
- Foreign key behavior must account for soft-deleted parents and children.
- Soft-deleted records must not leak into user-visible lists unless intentionally included.

## Indexes

- Add indexes for:
  - foreign keys;
  - common `where` filters;
  - common `orderBy` columns;
  - unique lookups;
  - pagination and cursor pagination columns;
  - polymorphic `{type, id}` pairs;
  - high-volume aggregate conditions.
- Composite indexes MUST match the query order: equality filters first, then range/sort columns, then deterministic tie-breakers like `id`.
- Prefer `created_at, id` or domain timestamp plus `id` for deterministic cursor-style lists.
- Avoid duplicate indexes and indexes fully covered by a left-prefix of another index unless a measured query needs both.
- Avoid indexing large `TEXT`/`LONGTEXT` columns. For MySQL, use a type cleanup, generated column, prefix strategy, or full-text/search engine plan instead.
- Every new index must have a descriptive name when Laravel's generated name would be unclear or too long.
- Index migrations should guard table, column, and index existence when they target legacy schema.
- Document expected query benefit for broad index migrations, especially on write-heavy tables.

## Unique Constraints

- Use database unique constraints for true invariants, not only validation rules.
- Unique constraints MUST be backed by user-friendly validation messages for write endpoints.
- Scope uniqueness to the real business boundary: global, tenant, owner, parent, or soft-delete-aware.
- Do not add unique constraints to legacy tables until duplicate reports prove the data is clean.
- For pivots, use a unique pair or tuple such as `['user_id', 'page_id']`.
- For slugs, use the parent scope when slugs are not globally unique.
- Never build unique rule SQL from untrusted input.

## Enum and Status Columns

- New enum/status columns SHOULD use clear names: `status`, `payment_status`, `visibility`, `role`, `type`, or `state`.
- Store stable string values unless there is a strong reason to store integers.
- Back status columns with PHP enums when the set is closed and compatible with existing persisted values.
- Define casts on the Eloquent model for enum-backed fields.
- Validate enum/status input with PHP enums, `Rule::enum()`, or `Rule::in()`.
- Avoid multiple overlapping status columns that describe the same lifecycle.
- Document allowed values, transitions, and terminal states for business-critical statuses.
- Add indexes for status columns used by lists, filters, moderation queues, jobs, or scheduled commands.

## Money Columns

- Never use `float`, `double`, or `real` for money in new schema.
- Use `decimal(12, 2)` for ordinary currency amounts unless the domain needs more precision.
- Use integer minor units, such as cents, when arithmetic precision and provider compatibility benefit from it.
- Always store the currency code in a separate `currency_code` or `currency_id` column when amounts may vary by currency.
- Use ISO 4217 currency codes for string currency fields.
- Add casts for money columns, for example `decimal:2`, or map to a value object in a focused refactor.
- Legacy money-like fields such as `payment_histories.amount`, `sponsors.paid_amount`, and `marketplaces.price` require data cleanup before type conversion.

## JSON Columns

- Use JSON columns for structured metadata that is queried rarely or whose shape can vary safely.
- Do not use JSON to hide core relational data that needs joins, foreign keys, filters, or uniqueness.
- JSON columns SHOULD have clear names: `metadata`, `settings`, `payload`, `provider_response`, or `extra`.
- Define Eloquent casts for JSON columns: `array`, `collection`, encrypted casts, or DTO/value-object casts where useful.
- Validate JSON payload shape at the application boundary before persistence.
- Do not store secrets, tokens, passwords, or payment credentials in plain JSON.
- Do not query deeply nested JSON on hot paths without an index, generated column, or schema redesign.
- Keep provider raw responses redacted and bounded in size.

## Migration Naming Rules

- Migration names MUST describe one concern in snake_case.
- Use Laravel timestamped migration filenames.
- Prefer names like:
  - `create_marketplace_orders_table`
  - `add_status_index_to_posts_table`
  - `add_deleted_at_to_comments_table`
  - `rename_legacy_receiver_columns_on_chats_table`
  - `backfill_marketplace_price_minor_units`
- Avoid vague names such as `update_database`, `change_users`, `fix_indexes`, or `new_fields`.
- Use `create_*_table` for new tables.
- Use `add_*_to_*_table` for additive columns or indexes.
- Use `rename_*_on_*_table` for renames and include backward compatibility notes.
- Use `drop_*_from_*_table` only with explicit destructive-change approval and rollback notes.
- Use separate migrations for schema changes and data backfills unless they must be deployed atomically.
- For expand-contract changes, use multiple migrations:
  1. expand: add nullable/new columns and indexes;
  2. backfill: populate data safely in batches;
  3. switch application reads/writes;
  4. contract: remove old columns only after deployment validation.

## Legacy Schema Policy

- `public/assets/install.sql` remains a legacy bootstrap source until a verified migration baseline replaces it.
- Do not edit the dump casually. Changes can alter fresh installs and installer behavior.
- For legacy tables, prefer additive indexes and read-only audits before structural cleanup.
- Before adding constraints to legacy tables, run reports for:
  - orphaned foreign keys;
  - duplicate candidate unique keys;
  - invalid enum/status values;
  - nulls in required relationships;
  - mixed date/time formats;
  - non-numeric values in money-like columns;
  - production-only schema drift.
- Document every deferred unsafe cleanup in `docs/migration-audit.md` or a dedicated migration plan.

## Verification Checklist

Before committing any schema-related work:

- [ ] Existing production migrations were not edited.
- [ ] The new migration has one clear concern.
- [ ] `up()` and `down()` are implemented.
- [ ] Destructive operations have approval, backup notes, and rollback notes.
- [ ] Table, column, index, and constraint names follow these standards.
- [ ] Foreign keys and unique constraints are backed by data quality checks.
- [ ] Indexes match actual query patterns.
- [ ] Money columns do not use float/double/real.
- [ ] JSON columns have casts and validation.
- [ ] Models, factories, seeders, validation, and tests were updated where relevant.
- [ ] `php artisan migrate`, rollback, and test commands were run when safe.
