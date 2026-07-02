# Zero-Downtime Migrations

Generated: 2026-07-02

Use this as the quick operational checklist for production database changes. The longer migration inventory lives in `docs/zero-downtime-migration-plan.md`.

## Rules

- Prefer additive, reversible migrations.
- Do not drop or rename production columns without expand-contract compatibility.
- Do not add constraints until production data quality reports are clean.
- Do not backfill large tables inside schema migrations.
- Do not combine indexes, constraints, type changes, and destructive cleanup in one deploy.
- Treat index creation on large tables as a lock-risk operation even when no data changes.
- Keep application code compatible with both old and new schema during expand phases.

## Expand-Contract Checklist

1. Expand: add nullable columns, tables, or indexes without changing existing writes.
2. Backfill: process in chunks or queued jobs with idempotent retry behavior.
3. Dual-write: write old and new locations while verification runs.
4. Dual-read: read new data with fallback to old data.
5. Contract: remove old usage in code.
6. Drop: delete old schema only in a later release with backup approval.

## Current Page Feed Index Migration

`2026_07_02_210000_add_page_feed_query_indexes.php` is an additive index migration. It can be deployed independently, but production should still check:

- existing `posts` and `media_files` indexes;
- row counts and table size;
- MySQL online DDL support or migration tooling requirements;
- lock and slow-query metrics during deploy.

Rollback drops only `media_files_page_id_id_idx` and `posts_publisher_privacy_created_post_idx`. If app code needs to roll back and the indexes are healthy, leave the indexes in place.
