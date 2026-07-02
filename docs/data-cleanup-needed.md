# Data Cleanup Needed

Generated: 2026-07-02

No new data cleanup is required for `2026_07_02_210000_add_page_feed_query_indexes.php` because the migration adds only non-unique indexes and does not enforce foreign keys, uniqueness, nullability, enum values, money types, JSON validity, or datetime conversion.

## Deferred Cleanup Before Future Constraints

| Area | Risk | Cleanup strategy |
| --- | --- | --- |
| User-owned foreign keys | Many legacy child columns are signed `int`, while `users.id` is `bigint unsigned`. Adding FKs without a type strategy can fail or lock tables. | Plan expand-contract type alignment, then run orphan checks before adding constraints. |
| Legacy Blade query paths | Templates still query `posts` and `media_files` directly in some album/event/search views. | Move queries to controllers/view models, add eager loading, and add query-count tests. |
| API timeline N+1 reads | Page/group/event timeline APIs fetch users and media per post. | Add API contract tests, eager load relationships, paginate collections, then measure query count. |
| Production-only duplicate data | Unique constraints added by prior guarded migrations may skip if production has duplicates. | Compare expected unique indexes with production metadata and merge/delete duplicates with owner approval. |
| Invalid typed values | Money, datetime, and JSON migrations skip dirty values rather than coercing them. | Use the related audit docs to identify rows, clean them in batches, then rerun guarded migrations. |

## Required Evidence Before Cleanup Migrations

- Production backup or point-in-time recovery is verified.
- Row counts and table sizes are captured.
- Read-only duplicate/orphan/invalid-value reports are reviewed.
- Cleanup rules are approved by the domain owner.
- Rollback and restore steps are documented.
