# Migration Audit

## 2026-07-02 Update: Query Pattern Index Coverage

### Scope

Audited current query patterns across controllers, query objects, view models, providers, Blade helpers, routes, Eloquent relationships, local scopes, filters, searches, ordered lists, grouped selectors, and pagination paths. The live SQLite schema and `public/assets/install.sql` were cross-checked before adding indexes because the project remains dump-backed and some legacy MySQL columns are too wide for safe automatic indexing.

No existing migration or SQL dump was edited.

### Safe Fix Applied

Added `database/migrations/2026_07_02_140000_add_query_pattern_coverage_indexes.php`.

The migration adds guarded, non-unique, reversible indexes for query paths that were not fully covered by earlier migrations:

| Index | Columns | Why it exists |
|---|---|---|
| `comments_type_content_parent_comment_idx` | `comments.is_type`, `id_of_type`, `parent_id`, `comment_id` | Supports comment counts and latest root/child comment previews filtered by content type, content id, and parent, then ordered by newest comment. |
| `currencies_code_idx` | `currencies.code` | Supports admin/system currency selectors that sort by currency code. |
| `friendships_accepter_status_importance_id_idx` | `friendships.accepter`, `is_accepted`, `importance`, `id` | Supports accepted-friend lookups from the recipient side with sidebar/profile ordering by importance and id. |
| `friendships_requester_status_importance_id_idx` | `friendships.requester`, `is_accepted`, `importance`, `id` | Supports accepted-friend lookups from the requester side with the same importance/id ordering. |
| `group_members_group_status_id_idx` | `group_members.group_id`, `is_accepted`, `id` | Supports accepted group-member counts and recent member lists ordered by newest membership id. |
| `groups_privacy_status_id_idx` | `groups.privacy`, `status`, `id` | Supports public active group discovery lists filtered by visibility/status and ordered by newest group id. |
| `invites_sender_receiver_event_idx` | `invites.invite_sender_id`, `invite_reciver_id`, `event_id` | Supports event invitation accept/decline updates and deletes by sender, receiver, and event. |
| `invites_sender_receiver_group_idx` | `invites.invite_sender_id`, `invite_reciver_id`, `group_id` | Supports group invitation accept/decline updates and deletes by sender, receiver, and group. |
| `notifications_receiver_created_id_idx` | `notifications.reciver_user_id`, `created_at`, `id` | Supports older-notification pagination filtered by receiver and date, ordered by newest notification id. |
| `stories_status_created_story_idx` | `stories.status`, `created_at`, `story_id` | Supports active story feed windows filtered by status and age, ordered by newest story id. |

### Duplicate Avoidance

The migration checks both index names and exact column lists with `Schema::hasIndex()` before creating anything. Several new indexes intentionally extend older prefixes, for example accepted friendship and group-member indexes, because those list paths also order by `importance` or `id`. Exact duplicate column lists were not added.

### Deferred Indexes

These candidates were intentionally not added in this pass:

- `languages.name + phrase`: `get_phrase()` uses this lookup heavily, but `phrase` is `varchar(300)` in the dump. A full composite index may exceed older MySQL/InnoDB key-length limits, so it needs a deliberate prefix/full-text strategy instead of an automatic safe migration.
- Leading-wildcard searches such as `LIKE '%term%'` on titles, names, descriptions, and locations: normal B-tree indexes are not useful for these patterns. They should be addressed with validated search inputs, prefix-only search where acceptable, or a dedicated search engine/full-text plan.
- Addon tables that are not present in the local dump-backed schema, including fundraiser, job, badge, and paid-content tables: migrations for those tables need addon-aware schema verification first.
- Foreign keys and unique constraints: these remain deferred until orphan/duplicate data checks pass.

### Verification Added

Updated `tests/Feature/MigrationSafetyAuditTest.php` to verify the query-pattern index migration can run `up()`, `down()`, and `up()` again, and that every expected index appears with the intended column order.

## 2026-07-02 Update: Full Migration Safety Pass

### Scope

Audited all Laravel migration files currently present in `database/migrations`:

- `2026_07_01_150000_add_safe_legacy_lookup_indexes.php`
- `2026_07_02_120000_add_marketplace_search_filter_indexes.php`
- `2026_07_02_130000_add_safe_legacy_relationship_indexes.php`

Also cross-checked the legacy schema source `public/assets/install.sql` and the local SQLite schema because this project is still dump-backed. No existing production migration was edited.

### Safe Fix Applied

Added `database/migrations/2026_07_02_130000_add_safe_legacy_relationship_indexes.php`.

This migration adds guarded, non-unique indexes for existing high-traffic lookup columns:

- `addons.unique_identifier`
- unread chat checks by `chats.reciver_id`, `read_status`, and `id`
- comment target counts by `comments.id_of_type`
- public/group event lists by `events.privacy`, `group_id`, and `id`
- feeling/activity picker lookups by `feeling_and_activities.type`
- follower target lookups by `followers.page_id` and `followers.group_id`
- invite target checks by receiver plus `page_id` or `post_id`
- active language filtering by `languages.name`
- Zoom/live-stream lookup by `live_streamings.publisher_id` and `user_id`
- media lookups by product and album-image references
- notification target/status lookups by event, page, and group
- payment cleanup/reporting by `payment_histories.item_id`
- save-for-later group lookups by `saveforlaters.user_id` and `group_id`
- active/user-list filtering by `users.status` and `id`

The migration intentionally avoids uniqueness, foreign keys, nullability changes, type changes, and indexes on MySQL `TEXT` columns. The `up()` and `down()` methods both guard table, column, and index existence.

### Migration File Findings

| Migration | Risk | Finding | Action |
|---|---:|---|---|
| `2026_07_01_150000_add_safe_legacy_lookup_indexes.php` | Medium | Additive and reversible, but `up()` does not check for existing indexes before creating them. It may fail on production databases where DBAs already added equivalent manual indexes. | Do not edit the existing migration. Compare production indexes before deployment. Future migrations must use `Schema::hasIndex()`. |
| `2026_07_01_150000_add_safe_legacy_lookup_indexes.php` | High | Some indexed columns are `TEXT` in the legacy MySQL dump, including `groups.user_id`, `marketplaces.category`, `posts.album_image_id`, and `videos.category`. MySQL may reject full indexes on `TEXT` columns without prefix lengths. | Documented as deployment risk. Do not add more `TEXT` indexes without a type cleanup or explicit MySQL prefix strategy. |
| `2026_07_02_120000_add_marketplace_search_filter_indexes.php` | Medium | Uses guards and rollback checks. The `status/title` composite can be wide because both columns are varchar-like in the dump; older MySQL/InnoDB settings may reject it. | Keep, but verify on the target MySQL version before production deployment. |
| `2026_07_02_120000_add_marketplace_search_filter_indexes.php` | High | `marketplaces.price` is stored as text, so price sorting/filtering remains semantically fragile even with an index. | Defer a decimal money migration until data quality and UI/API compatibility are tested. |
| `2026_07_02_130000_add_safe_legacy_relationship_indexes.php` | Low | Additive, guarded, reversible indexes only. | Applied as the safe fix for this pass. |

### Schema Risks Still Deferred

These are not safe one-step fixes:

- No complete reversible baseline migration exists for the dump-backed application schema.
- The legacy dump still has no application foreign keys or cascade rules.
- Foreign-key-like columns have inconsistent types, for example `groups.user_id` as `text` and `marketplaces.category` as `text`.
- Money-like fields use floating-point or text storage, including `payment_histories.amount`, `sponsors.paid_amount`, and `marketplaces.price`.
- Timestamp columns mix `timestamp`, `varchar(100)`, text, nullable values, and `CURRENT_TIMESTAMP` defaults.
- Many relationship columns are nullable even though application code assumes related owners or targets.
- Unique constraints for pivot-like tables are still deferred because duplicate data must be checked first.

### Required Safe Order From Here

1. Apply and verify additive indexes only.
2. Export production schema metadata and compare tables, columns, indexes, and MySQL version/settings against local SQLite and `public/assets/install.sql`.
3. Run read-only orphan reports for posts, comments, media, friendships, notifications, payments, pages, groups, and marketplace rows.
4. Clean duplicate/orphan data in reversible batches.
5. Add foreign keys one domain at a time after delete behavior is explicitly chosen.
6. Use expand-contract migrations for type, nullability, money precision, and timestamp normalization.
7. Generate a baseline migration only after installer behavior is covered by tests and the dump/import path has a rollback plan.

### Verification Added

Added `tests/Feature/MigrationSafetyAuditTest.php`.

The test suite now verifies:

- every migration file defines a `down(): void` method;
- the new safe relationship index migration can run `up()`, `down()`, and `up()` again;
- all expected indexes exist after migration and are removed by rollback.

Date: 2026-07-01

## Scope

Audited the schema sources that exist in this checkout:

- `database/migrations`: directory was missing before this audit.
- `public/assets/install.sql`: the legacy install dump that creates the application schema.
- `database/database.sqlite`: local imported development database.
- `php artisan migrate:status`: reports only `2019_12_14_000001_create_personal_access_tokens_table` as run.

Laravel's `db:show` / `db:table` inspection commands could not be used because this Laravel 9 app does not have `doctrine/dbal` installed. No database-schema MCP tool was available in this session, so the live schema was inspected through SQLite metadata and the install dump.

## Executive Summary

The application does not currently have a reversible Laravel migration chain for the legacy schema. The schema is created from `public/assets/install.sql`, while `database/migrations` had no migration files. That is the largest rollback and deployability risk: the app can import the dump, but it cannot safely migrate a blank database forward or roll the legacy schema backward through Laravel.

The dump defines primary keys for application tables plus only these non-primary indexes:

- `users_email_unique`
- `password_resets_email_index`
- `failed_jobs_uuid_unique`
- `personal_access_tokens_token_unique`
- `personal_access_tokens_tokenable_type_tokenable_id_index`

No application foreign keys or cascade rules are defined in the dump or the local SQLite database.

## Safe Fix Applied

Added `database/migrations/2026_07_01_150000_add_safe_legacy_lookup_indexes.php`.

This migration adds non-unique lookup indexes for relationship and filter columns already used throughout the app. It intentionally does not:

- change column types,
- change nullability,
- add unique constraints,
- add foreign keys,
- add cascade rules,
- edit the legacy SQL dump,
- rewrite existing migrations.

The migration includes a `down()` method that drops the added indexes in reverse order.

## Findings

### Missing migration history

Severity: Critical

There is no Laravel migration chain for the app schema. New environments depend on `public/assets/install.sql`, and rollback is only possible for migrations added after this point.

Recommended follow-up:

1. Keep the legacy dump as the bootstrap source until a full baseline migration is intentionally generated.
2. Create a verified baseline migration only after comparing the dump, production schema, and all addon tables.
3. Do not remove the dump until the installer is changed and tested against the baseline migration.

### Missing foreign keys and cascade rules

Severity: High

The live schema has no foreign keys. Eloquent relationships imply many references, including:

- `account_active_requests.user_id -> users.id`
- `albums.user_id -> users.id`
- `album_images.album_id -> albums.id`
- `blogs.user_id -> users.id`
- `blogs.category_id -> blogcategories.id`
- `comments.user_id -> users.id`
- `events.user_id -> users.id`
- `events.group_id -> groups.id`
- `followers.user_id / follow_id -> users.id`
- `friendships.requester / accepter -> users.id`
- `group_members.user_id -> users.id`
- `group_members.group_id -> groups.id`
- `invites.event_id -> events.id`
- `invites.page_id -> pages.id`
- `invites.group_id -> groups.id`
- `marketplaces.user_id -> users.id`
- `media_files.post_id -> posts.post_id`
- `notifications.sender_user_id / reciver_user_id -> users.id`
- `notifications.event_id -> events.id`
- `notifications.group_id -> groups.id`
- `page_likes.user_id -> users.id`
- `page_likes.page_id -> pages.id`
- `pages.user_id -> users.id`
- `pages.category_id -> pagecategories.id`
- `posts.user_id -> users.id`
- `post_shares.post_id -> posts.post_id`
- `reports.post_id -> posts.post_id`
- `saved_products.product_id -> marketplaces.id`
- `saveforlaters.video_id -> videos.id`
- `videos.user_id -> users.id`

Foreign keys and cascades were not added in this pass because existing data may contain orphaned rows, several columns are nullable despite being used like required relations, and some inferred references have inconsistent types.

Recommended follow-up:

1. Add orphan-data reports for each relationship.
2. Decide delete behavior per domain: cascade, restrict, null-on-delete, or application-managed cleanup.
3. Add foreign keys in small migrations after data cleanup has passed.

### Missing indexes

Severity: High

Most relationship and filter columns were unindexed, including heavy query paths in posts, media files, friendships, comments, notifications, group membership, page likes, chats, saves, and settings.

Applied safe index coverage for:

- account activation lookups by user/status,
- albums and album images by owner,
- blogs by user/category/status,
- block checks,
- chat threads and unread messages,
- comment tree lookups,
- events by group/user/publisher,
- followers,
- accepted friendships by requester/accepter,
- group members by group/user/status,
- invites by receiver/group/event,
- marketplace owner/category/currency filters,
- media files by post/user/page/group/album/story/chat,
- message-thread participant pairs,
- notification receiver/status and sender/receiver filters,
- page likes in both lookup directions,
- page owner/category filters,
- payment gateway identifier lookups,
- payment history owner/item filters,
- post owner/publisher/activity/album/date filters,
- report status lookups,
- saved product and save-for-later lookups,
- settings by type,
- share target columns,
- sponsor owner/status/date filters,
- story owner/publisher filters,
- video category/privacy/user filters.

### Inconsistent column types

Severity: High

Several columns are used like foreign keys but are not consistently typed as integers:

- `groups.user_id` is `text`, but `Group::getUser()` treats it as `users.id`.
- `marketplaces.category` is `text`, but `Marketplace::getCategory()` treats it as `categories.id`.
- `marketplaces.brand` is `text`, but `Marketplace::getBrand()` treats it as `brands.id`.
- `shares.share_user_id` is `text`, but the name implies a user reference.
- `posts.album_image_id` is `text`, but code uses it like an album-image reference.
- timestamp columns are mixed across `text`, `timestamp`, integer epoch writes, nullable values, and `CURRENT_TIMESTAMP` defaults.

No type changes were made because these rewrites can fail or silently coerce data.

### Nullable mistakes

Severity: Medium

Many relationship columns are nullable despite app code commonly assuming a related owner or target exists, for example `posts.user_id`, `pages.user_id`, `groups.user_id`, `blogs.user_id`, `events.user_id`, `media_files.post_id`, `group_members.user_id`, and `group_members.group_id`.

Some child tables use `NOT NULL` without a foreign key, such as `album_images.album_id` and `album_images.user_id`, which prevents nulls but still allows orphan IDs.

No nullability changes were made because existing rows and legacy install behavior must be checked first.

### Duplicate indexes

Severity: Low

No duplicate application indexes were found in the local SQLite schema. The only non-primary indexes found before this audit were the dump-defined indexes listed in the executive summary.

### Rollback problems

Severity: High

The legacy dump cannot be rolled back through Laravel. Only new migrations can be rolled back. The new index migration has a reversible `down()` method, but it assumes the indexes were created by its `up()` method.

Recommended follow-up:

1. Before applying this migration to a database that may have manual indexes, compare current index names first.
2. Do not run destructive rollback operations on the imported legacy schema.
3. Add future schema changes as small Laravel migrations with explicit `down()` methods.

## Unsafe Changes Deferred

These changes should not be automatic safe fixes:

- Adding foreign keys or cascades before orphan checks.
- Making nullable owner columns non-null.
- Converting text foreign-key columns to integers.
- Adding unique constraints to pivot-like tables such as `page_likes`, `followers`, `saved_products`, or `saveforlaters` before duplicate checks.
- Replacing `public/assets/install.sql` with generated migrations without updating and testing the installer.

## Verification Notes

Run these after changing schema:

```bash
php artisan migrate
php artisan migrate:rollback --step=1
php artisan migrate
php artisan test
```

For production or large datasets, run an explicit index comparison before migration because Laravel 9 in this repo does not provide `Schema::hasIndex()`.
