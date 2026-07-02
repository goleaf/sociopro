# Zero-Downtime Migration Plan

Date: 2026-07-02

## Current Migration State

The live checkout has eleven project migrations. Local `php artisan migrate:status` must be checked before each deploy because this developer database may not mirror production. Production status must be checked separately before deploy.

| Migration | Local status | Production-safety review | Deploy risk |
| --- | --- | --- | --- |
| `2026_07_01_150000_add_safe_legacy_lookup_indexes.php` | Ran | Additive indexes with reversible `down()`, but `up()` does not check existing index names/columns. May fail if production already has manual duplicate indexes. | Medium |
| `2026_07_02_120000_add_marketplace_search_filter_indexes.php` | Ran | Additive and guarded. Includes wide text-like sort/search columns; verify target MySQL key length and table size before deploy. | Medium |
| `2026_07_02_130000_add_safe_legacy_relationship_indexes.php` | Ran | Additive, guarded by table/column/index checks, reversible. | Low |
| `2026_07_02_140000_add_query_pattern_coverage_indexes.php` | Ran | Additive, guarded, and documented by query path. | Low |
| `2026_07_02_150000_add_safe_legacy_foreign_key_constraints.php` | Ran | Guarded by table/column checks, orphan checks, nullability checks, and existing FK detection. Constraint creation can still lock tables. | Medium |
| `2026_07_02_160000_add_safe_legacy_unique_constraints.php` | Ran | Guarded by duplicate checks and existing unique index checks. Unique index creation can lock or fail on production-only duplicates. | Medium |
| `2026_07_02_170000_add_safe_legacy_nullable_column_constraints.php` | Ran | Skips dirty null/blank data. Column changes may rebuild tables depending on database engine/version. | Medium |
| `2026_07_02_180000_add_safe_legacy_money_precision_constraints.php` | Ran | Skips unsafe money values and changes only clean columns. Type conversion may rewrite tables. | Medium |
| `2026_07_02_190000_add_safe_legacy_datetime_column_constraints.php` | Ran | Skips unsafe datetime values, adds guarded indexes. Datetime type changes may rewrite tables. | Medium |
| `2026_07_02_200000_add_safe_legacy_json_column_constraints.php` | Check locally | Skips invalid JSON values and changes only already-cast hidden payment payloads. Native JSON conversion may rewrite tables and differs by engine. | Medium |
| `2026_07_02_210000_add_page_feed_query_indexes.php` | Check locally | Adds guarded non-unique indexes for page media sidebars and public publisher timeline reads. No data or constraints are changed. | Low |

## Deployment Rule

Do not run these migrations blindly on production. Treat the current migration set as a staged hardening backlog, not a single fire-and-forget release. The safest production path is:

1. Take a schema and data backup.
2. Capture production `php artisan migrate:status`, database engine/version, table sizes, row counts, index list, and foreign key list.
3. Run read-only preflight checks for duplicates, orphans, invalid JSON, invalid money values, invalid datetimes, nulls, and blank required fields.
4. Deploy additive indexes first.
5. Deploy constraints only after dirty-data reports are clean.
6. Deploy type/nullability changes only after app code has dual-read/dual-write compatibility where needed.
7. Keep each migration batch small enough to roll back independently.

## Expand-Contract Strategy

### Renames

Never rename a production column or table in one step.

Expand:

- Add the new column/table nullable and without constraints.
- Backfill from the old column/table in small batches.
- Add application dual-write so old and new stay synchronized.
- Add dual-read with preference for the new field and fallback to the old field.
- Add indexes/constraints on the new field only after backfill is complete.

Contract:

- Switch reads to the new field only.
- Stop writing the old field.
- Observe production for at least one release.
- Drop the old field in a later migration with a backup and rollback plan.

Rollback:

- During expand, rollback is disabling new reads and continuing old writes.
- During contract, rollback requires old data still present or a reverse backfill.

### Drops

Do not drop columns, tables, indexes, or constraints in the same release that removes their code usage.

Safe order:

1. Prove zero reads/writes with code search, logs, and metrics.
2. Stop application usage in one release.
3. Keep the schema object for one full deploy window.
4. Take a backup.
5. Drop in a separate migration.
6. Keep rollback SQL or restore instructions ready.

Rollback:

- Dropped data requires restore from backup.
- Dropped indexes/constraints can be recreated with a forward migration.

### Type Changes

Type changes are not zero-downtime by default. In this project, money, datetime, and JSON migrations are guarded, but they can still lock or rewrite production tables.

Safe order:

1. Add a new typed column when the table is large or the engine rewrites the table.
2. Backfill in chunks using primary key windows.
3. Dual-write old and new values.
4. Validate row counts and sampled values.
5. Switch reads to the new typed column.
6. Drop or ignore the old column later.

Accept direct `change()` only for small tables or after staging proves lock time is acceptable.

Rollback:

- Keep original column until the contract phase.
- If direct conversion was used, rollback through the migration `down()` and restore from backup if conversion was lossy.

### Backfills

Never backfill a large table inside the same migration that changes schema shape.

Rules:

- Use queued jobs or Artisan commands for large tables.
- Process by primary key range with `chunkById()` / cursor windows.
- Make backfills idempotent.
- Record progress and failures.
- Keep writes compatible while backfill runs.
- Validate with counts and checksums before contract.

Rollback:

- Backfills should be repeatable and reversible where possible.
- If data is derived, keep source data until validation and one safe release pass.

### Indexes

Indexes are additive, but not always non-blocking.

Rules:

- Check existing indexes by name and column list before adding.
- Avoid full indexes on `TEXT`/very-wide columns unless an explicit prefix or generated-column strategy is chosen.
- Prefer composite indexes that match real `where`, `orderBy`, and pagination patterns.
- For MySQL production, use online DDL options or a migration tool such as `pt-online-schema-change` / `gh-ost` when table size requires it.
- Do not add several heavy indexes to a large table in one deploy.

Rollback:

- Dropping an index is usually safe but can still take locks. Drop in a separate deploy if traffic-sensitive.

### Constraints

Foreign keys, unique indexes, and not-null constraints require clean data first.

Rules:

- Run duplicate/orphan/null reports before migration.
- Add constraints one domain at a time.
- For foreign keys, choose `cascade`, `restrict`, or `set null` from business ownership, not convenience.
- For unique constraints, verify production-only duplicates and decide merge/delete rules before applying.
- For not-null constraints, make app validation and defaults enforce the invariant before the database does.

Rollback:

- Constraints can be dropped, but data written while constraints were active may no longer match the previous loose model.
- Keep application code compatible with both constrained and unconstrained states during deploy.

### Locks

Plan for locks explicitly.

Before deploy:

- Estimate row count and table size for each touched table.
- Test migration runtime against a production-size copy.
- Set a deployment window for type changes and constraints.
- Prefer one risky table per deploy.
- Monitor blocked queries, deadlocks, replication lag, and queue depth.

During deploy:

- Apply migrations with maintenance communication ready.
- Stop if lock time exceeds the tested threshold.
- Do not retry a failed DDL loop repeatedly during peak traffic.

## Migration-Specific Notes

### Additive Index Migrations

`2026_07_01_150000`, `2026_07_02_120000`, `2026_07_02_130000`, `2026_07_02_140000`, and `2026_07_02_210000` should run before constraints and type changes. The first lookup-index migration is the most fragile because it lacks `Schema::hasIndex()` guards. On production, compare existing indexes first and skip or replace with a new guarded migration if equivalent indexes already exist.

### Foreign Keys

`2026_07_02_150000` has good data guards, but adding foreign keys can lock child tables and reject production-only orphans. Run orphan reports immediately before deployment. If any candidate is skipped because dirty data exists, record the skipped constraint and create a cleanup migration/job before retrying.

### Unique Constraints

`2026_07_02_160000` skips duplicates instead of failing. This protects deployment, but it can also leave expected constraints unapplied. After deploy, compare the expected unique indexes from `MigrationSafetyAuditTest` against production metadata and file cleanup tasks for skipped constraints.

### Nullability Changes

`2026_07_02_170000` skips nulls and blanks for selected fields. Treat skipped columns as data-quality tickets. Do not force not-null constraints until all write paths validate and default the field.

### Money, Datetime, and JSON Type Changes

`2026_07_02_180000`, `2026_07_02_190000`, and `2026_07_02_200000` are guarded but still use direct type changes. For large production tables, prefer an expand-contract typed-column path instead of direct `change()` unless staging proves lock time is acceptable.

## Rollback Plans

### Fast Rollback

Use when app behavior fails but migrations are additive:

- Revert the app release.
- Leave additive indexes in place.
- Disable new code paths or feature flags.
- Do not drop constraints or columns during an incident unless they are the direct cause.

### Schema Rollback

Use when the migration itself causes production issues:

- Stop traffic-sensitive workers if they intensify lock contention.
- Run `php artisan migrate:rollback --step=1` only for the latest known-safe batch and only after confirming the `down()` path is non-destructive.
- For index-only migrations, rollback is dropping the new indexes.
- For constraints, rollback is dropping the new constraint while preserving data.
- For type changes, rollback may be lossy; use backup restore if values cannot be faithfully converted back.

### Data Rollback

Use when a backfill or cleanup changes data incorrectly:

- Restore affected rows from backup or point-in-time recovery.
- Re-run the app in old-read mode while investigating.
- Keep backfill jobs idempotent so they can be resumed after correction.

## Required Preflight Checklist

- [ ] Production backup or point-in-time recovery is verified.
- [ ] Production `migrate:status` is captured.
- [ ] Target database engine/version is captured.
- [ ] Existing indexes and constraints are exported.
- [ ] Dirty-data reports are clean or expected skips are documented.
- [ ] Large table row counts and estimated lock times are reviewed.
- [ ] Migration order is approved: indexes, cleanup/backfill, constraints, type/nullability changes, contract drops.
- [ ] Rollback owner and command plan are assigned.
- [ ] Post-deploy metadata comparison is scheduled.

## Required Post-Deploy Checklist

- [ ] `php artisan migrate:status` shows the intended batch state.
- [ ] Expected indexes/constraints are present in production metadata.
- [ ] Skipped guarded changes are listed as follow-up cleanup tasks.
- [ ] Slow query and lock metrics are reviewed.
- [ ] Application smoke tests pass for auth, marketplace, payments, notifications, stories, and admin settings.
- [ ] Rollback window remains open until metrics are stable.
