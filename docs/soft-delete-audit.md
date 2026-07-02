# Soft Delete Audit

Date: 2026-07-02

## Current Decision

No active soft-delete usage is present in this project.

The current dump-backed schema, Laravel migrations, and model layer do not define canonical deleted_at columns or use Laravel's `SoftDeletes` trait. Existing delete behavior should therefore be treated as hard deletes until a feature-specific recovery or moderation requirement is explicitly designed and covered by tests.

## Findings

| Area | Current state | Decision | Risk |
| --- | --- | --- | --- |
| `deleted_at` columns | No `deleted_at` columns were found in `database/schema/install.sql` or current project migrations. | Do not add broad soft-delete columns without a domain-specific restore requirement. | Low |
| Model traits | No model currently uses `Illuminate\Database\Eloquent\SoftDeletes`. | Do not add `SoftDeletes` independently of schema, policies, indexes, and query tests. | Low |
| Indexes | No soft-delete indexes are required because no soft-delete columns exist. | Future soft-delete migrations must index `deleted_at`, plus common lookup combinations such as owner/status/deleted state when used by queries. | Medium |
| Restore logic | No restore flows currently exist. Representative model deletes are hard deletes and `restore()` is unavailable. | Add restore authorization, UI/API behavior, and tests in the same slice that introduces soft deletes. | Medium |
| Cascade behavior | Existing cascade decisions are documented in `docs/foreign-key-audit.md`; they are based on hard-delete lifecycle rules. | Re-audit cascades before introducing soft deletes on parent records, especially posts, users, groups, pages, media, and marketplace items. | High |
| Unique constraints | Current unique constraints do not include `deleted_at` because rows are removed instead of retained as trashed. | If a unique field becomes soft-deletable, decide whether uniqueness should include trashed rows or use a generated/partial-index strategy supported by the target database. | High |
| Query expectations | Public, API, admin, and moderation queries currently expect all persisted rows unless local scopes filter status, visibility, ownership, or report state. | Adding `SoftDeletes` changes default query results through a global scope and requires scoped/unscoped regression tests. | High |

## Required Future Checklist

Before any model adopts soft deletes, the same change must include:

- a reversible migration that adds `deleted_at` with an index;
- the `SoftDeletes` trait on the matching model only after the column exists;
- delete, restore, and force-delete tests;
- route/API tests proving public queries do not leak trashed records;
- admin/moderation tests proving allowed `withTrashed()` or `onlyTrashed()` behavior;
- policy methods for `restore` and `forceDelete` when users can trigger those actions;
- cascade behavior decisions for children and pivot-like tables;
- unique constraints reviewed for whether trashed rows reserve unique values;
- documentation updates in this file and any affected module audit.

## Test Coverage

`tests/Feature/SoftDeleteAuditTest.php` guards this decision by checking:

- the audit document names `deleted_at`, `SoftDeletes`, indexes, restore, cascade behavior, unique constraints, query expectations, and hard deletes;
- models and schema do not have partial soft-delete adoption;
- the install dump and project migrations do not define unowned `deleted_at` columns;
- a representative `Category` delete is a hard delete and has no restore path until soft deletes are intentionally introduced.
