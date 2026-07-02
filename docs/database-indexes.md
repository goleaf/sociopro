# Database Indexes

Generated: 2026-07-02

This document records database indexes added by Laravel migrations, why they exist, and the query shape each index is expected to support. It is intentionally query-driven: add indexes only when a real controller, service, model scope, job, API endpoint, or Blade data contract needs the lookup.

## Index Standards

- Put equality filters first, then range or sort columns, then deterministic tie-breakers such as `id` or `post_id`.
- Avoid duplicate indexes. A shorter left-prefix index can be acceptable only when a measured query needs the shorter shape or when an older migration already deployed it and dropping it would be a separate rollout.
- Do not index `TEXT` or `LONGTEXT` columns without a prefix/generated-column/full-text plan.
- Guard legacy index migrations with table, column, and existing-index checks.
- Document every new broad index with table, columns, migration, read path, and rollback risk.

## New Indexes In This Slice

| Table | Index | Columns | Migration | Read path | Reason | Rollback risk |
| --- | --- | --- | --- | --- | --- | --- |
| `media_files` | `media_files_page_id_id_idx` | `page_id`, `id` | `2026_07_02_210000_add_page_feed_query_indexes.php` | `PageController::single_page()` mixed page media sidebar | Existing `media_files_page_type_id_idx` helps type-filtered media tabs, but the mixed sidebar filters only by `page_id` and orders by newest `id`. | Low; rollback drops only this index. |
| `posts` | `posts_publisher_privacy_created_post_idx` | `publisher`, `publisher_id`, `privacy`, `created_at`, `post_id` | `2026_07_02_210000_add_page_feed_query_indexes.php` | API page/group/event timeline reads in `ApiController` | Supports public publisher timelines filtered by publisher and privacy, ordered by `created_at`; `post_id` is included as a deterministic tie-breaker for future pagination/refactors. | Low; rollback drops only this index. |

## Existing High-Value Index Coverage

These existing migrations already cover many legacy hot paths:

- `2026_07_01_150000_add_safe_legacy_lookup_indexes.php`: broad relationship and lookup indexes for posts, comments, media, friendships, groups, pages, saves, notifications, settings, and marketplace tables.
- `2026_07_02_120000_add_marketplace_search_filter_indexes.php`: marketplace listing/search/filter indexes.
- `2026_07_02_130000_add_safe_legacy_relationship_indexes.php`: additional relationship indexes for chats, comments, media files, notifications, saved content, and user status lookups.
- `2026_07_02_140000_add_query_pattern_coverage_indexes.php`: query-specific composite indexes for comments, friendships, group members, groups, invites, notifications, and stories.
- `2026_07_02_190000_add_safe_legacy_datetime_column_constraints.php`: date-window indexes for sponsor and token expiry workflows.

## Deferred Index Work

| Area | Why deferred | Next step |
| --- | --- | --- |
| `posts` web page timeline filtering by `publisher`, `publisher_id`, `status`, and newest `post_id` | Existing `posts_publisher_entity_status_idx` already covers the equality filters. Adding a longer left-prefix index may duplicate that index without production EXPLAIN evidence. | Capture production-like EXPLAIN and slow-query evidence before adding a `post_id`-ordered variant. |
| Legacy Blade queries in album/event/search views | Several views still query in templates. Indexing alone would hide, not fix, the architecture issue. | Move queries into controllers/view models with eager loading, then add query-count tests before index changes. |
| API post media loops | API endpoints fetch media per post. The existing `media_files_post_id_idx` helps, but the N+1 remains. | Add API contract/query-count tests, eager load `media_files`, and paginate unbounded collections. |

## Verification

- `tests/Feature/MigrationSafetyAuditTest.php` asserts the new page-feed indexes exist, roll back, and re-apply cleanly.
- Run `php artisan test tests/Feature/MigrationSafetyAuditTest.php` after index migrations.
- For production, run database-native metadata checks and EXPLAIN plans because PHPUnit uses SQLite `:memory:` and cannot prove MySQL execution plans.
