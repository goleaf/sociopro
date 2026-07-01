# Migration Audit

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
