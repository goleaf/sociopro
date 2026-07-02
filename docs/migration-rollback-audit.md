# Migration Rollback Audit

Last reviewed: 2026-07-02

## Summary

No current migration is intentionally irreversible. Every project migration defines a `down(): void` method and is covered by `MigrationSafetyAuditTest`.

The rollback policy is conservative for this legacy SQL-dump schema:

- Missing indexes, missing columns, and missing tables must be treated as no-op rollback cases.
- Foreign-key helper indexes are intentionally retained during rollback because the migration cannot prove whether a helper-named index was created by the migration or already existed for production query performance.
- Schema rollbacks that convert stricter data types back to legacy types are supported for test and emergency rollback, but money, datetime, and JSON column changes are backup-required rollback operations in production after new writes.
- Rollbacks must not delete application data. Destructive data cleanup belongs in a separately reviewed migration or maintenance command with backup and restore notes.

## Reviewed Migrations

| Migration | Rollback status | Notes |
| --- | --- | --- |
| `2026_07_01_150000_add_safe_legacy_lookup_indexes.php` | Reversible | Guarded so repeated `up()` / `down()` calls skip existing or missing indexes safely. |
| `2026_07_02_120000_add_marketplace_search_filter_indexes.php` | Reversible | Adds only compatible marketplace search/filter index coverage and safely skips equivalent existing indexes. |
| `2026_07_02_130000_add_safe_legacy_relationship_indexes.php` | Reversible | Additive relationship indexes are dropped only when the expected names exist. |
| `2026_07_02_140000_add_query_pattern_coverage_indexes.php` | Reversible | Additive query-pattern indexes are guarded by table, column, and index checks. |
| `2026_07_02_150000_add_safe_legacy_foreign_key_constraints.php` | Partially contract-reversible | Foreign keys are dropped on rollback. Helper indexes are intentionally retained to avoid dropping pre-existing production indexes. |
| `2026_07_02_160000_add_safe_legacy_unique_constraints.php` | Reversible | Unique indexes are skipped when duplicate dirty data exists and dropped by explicit names on rollback. |
| `2026_07_02_170000_add_safe_legacy_nullable_column_constraints.php` | Reversible | Required-column changes are skipped when dirty null data exists; rollback relaxes columns back to nullable. |
| `2026_07_02_180000_add_safe_legacy_money_precision_constraints.php` | Backup-required rollback | Decimal upgrades are safe when values are numeric. Rolling back to legacy float/text types can lose precision or loosen validation after new production writes. |
| `2026_07_02_190000_add_safe_legacy_datetime_column_constraints.php` | Backup-required rollback | Datetime upgrades are skipped for invalid dates. Rolling back to strings is operationally reversible but can lose stricter database guarantees. |
| `2026_07_02_200000_add_safe_legacy_json_column_constraints.php` | Backup-required rollback | JSON upgrades are skipped for invalid JSON. Rolling back to text is operationally reversible but removes database-level JSON guarantees. |

## Safe Rollback Order

1. Confirm the target database has a current backup.
2. Run migrations down in reverse timestamp order.
3. Confirm foreign keys are removed before dropping or changing dependent columns.
4. Keep additive helper indexes unless a separate index cleanup migration proves ownership and production usage.
5. Re-run migrations from the imported legacy schema in tests before deployment.

## Verification

Use these commands before committing migration rollback changes:

```bash
php artisan test tests/Feature/MigrationSafetyAuditTest.php
vendor/bin/pint --test
php artisan test
```
