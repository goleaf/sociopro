# Global Scopes Audit

Last reviewed: 2026-07-02

## Current State

No Eloquent global scopes are registered in the application models.

The audit found:

- No custom `Scope` classes under `app/`.
- No model calls to `addGlobalScope()`.
- No models using Laravel `SoftDeletes`.
- No app or route code calling `withoutGlobalScope()`, `withoutGlobalScopes()`, `withTrashed()`, `onlyTrashed()`, or `withoutTrashed()`.
- Tenant, active, published, visibility, soft-delete-like, and ownership filtering is currently expressed with explicit local scopes, policies, form requests, controller queries, or query/service classes.

## Intentional Policy

Do not add tenant, active, published, visibility, ownership, or soft-delete behavior as a global scope unless the behavior is required for every query of that model.

Prefer local scopes for legacy data filters because this codebase still has mixed public/admin/API contexts where unreviewed global filtering could hide records, break moderation screens, or mask authorization gaps.

Current examples of intentional local scopes:

- `Posts::active()` for `posts.status = active`.
- `Posts::notPrivate()` for excluding private posts.
- `Posts::notReported()` for excluding reported posts.
- `Posts::publiclyVisible()` for public-only post queries.
- `Posts::forUser()` for ownership filtering.
- `Posts::forPublisher()` for publisher type and identifier filtering.
- `GroupMember::accepted()` for accepted membership rows.
- `MediaFile::ofType()` for legacy media type filtering.
- `Sponsor::forUser()` for ownership filtering.

## Bypass Rules

Because no global scopes exist today, app code should not call `withoutGlobalScopes()` or related bypass helpers.

If a future global scope is added, the same change must include:

- A clear entry in this document explaining why the scope is global instead of local.
- Tests proving the scoped behavior.
- Tests proving the unscoped or bypassed behavior where bypass is allowed.
- A named method, service, policy, or query object for each approved bypass path.
- Authorization tests for ownership, tenant, visibility, or admin-only bypass flows.

## Soft-Delete-Like Behavior

No model currently uses `SoftDeletes` and the current schema has no canonical `deleted_at` columns. See `docs/soft-delete-audit.md` for the full restore, cascade, index, unique-constraint, and query-expectation checklist.

If soft deletes are introduced later, add tests for:

- Default scoped queries excluding trashed records.
- `withTrashed()` or `onlyTrashed()` only in reviewed admin/moderation/restore flows.
- Public/API routes not leaking trashed records.
- Restore and force-delete authorization.

## Test Coverage

`tests/Feature/GlobalScopeAuditTest.php` guards this policy by checking:

- The documentation exists and names `withoutGlobalScopes` and `SoftDeletes`.
- Models have no registered global scopes.
- Models do not use `SoftDeletes`.
- App and route code do not bypass global scopes.
- Current post active, visibility, report, and ownership filters are local scopes; base queries and `withoutGlobalScopes()` still see the unfiltered row set.
