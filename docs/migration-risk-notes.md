# Migration Risk Notes

Generated: 2026-07-02

This file records production-risk decisions for database migrations added after the legacy `database/schema/install.sql` baseline. It is a deployment handoff, not a substitute for production metadata checks.

## Current Slice: Page Feed Query Indexes

Migration: `2026_07_02_210000_add_page_feed_query_indexes.php`

| Risk area | Assessment | Mitigation |
| --- | --- | --- |
| Data loss | None expected. The migration adds non-unique indexes only. | No rows, columns, constraints, or table data are changed. |
| Existing dirty data | Not relevant to this migration because no foreign keys, unique constraints, type changes, or not-null constraints are added. | No data cleanup is required before this index-only migration. |
| Table locks | Index creation can lock or slow writes on large MySQL tables. `posts` and `media_files` are likely high-volume tables. | Deploy during a low-traffic window or use online DDL tooling if production table size requires it. |
| Duplicate indexes | `media_files_page_id_id_idx` overlaps the `page_id` prefix of existing media indexes but serves a different mixed-media order path. `posts_publisher_privacy_created_post_idx` is distinct from existing status-based publisher indexes. | Keep both indexes until production EXPLAIN confirms one can be retired in a separate rollout. |
| Rollback | Low risk. `down()` drops only indexes created by this migration and is guarded by table/column/index checks. | Prefer leaving additive indexes in place during app rollback unless the index itself caused deployment issues. |

## Safe Deployment Order

1. Capture production `php artisan migrate:status`.
2. Export existing indexes for `posts` and `media_files`.
3. Estimate row counts and table size for `posts` and `media_files`.
4. Run this index migration separately from constraints or type changes.
5. Review slow query and lock metrics after deploy.
6. Compare expected indexes with production metadata.

## Rollback Command

Use only if this migration is the latest batch and the index operation caused a real issue:

```bash
php artisan migrate:rollback --step=1
```

If the application release fails but indexes are not the cause, roll back code and leave indexes in place.

## Existing Broader Risks

- The legacy schema is still bootstrapped from `database/schema/install.sql`; there is no full create-table Laravel baseline for every legacy table.
- Several legacy columns still have type mismatches with referenced IDs. User-owned foreign keys remain deferred until column type cleanup is planned.
- Some API endpoints and Blade views still perform N+1 or unbounded reads. Indexes reduce query cost but do not replace query refactors.
- SQLite test execution validates migration reversibility, not MySQL lock time or optimizer choice.
