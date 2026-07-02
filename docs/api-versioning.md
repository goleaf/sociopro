# API Versioning and Response Compatibility

Date: 2026-07-02

## Scope

This audit covers the current API routing surface, response compatibility rules, future versioning strategy, and deprecation process for the Laravel API loaded from `routes/api.php`.

No runtime route changes were made in this audit. The current API remains a legacy unversioned contract under `/api/*`.

## Current API Routing Strategy

`App\Providers\RouteServiceProvider` loads `routes/api.php` with:

- URI prefix: `/api`
- Middleware group: `api`
- Route name prefix inside `routes/api.php`: `api.`

The live route table currently reports 134 API routes via `php artisan route:list --path=api`.

Current route categories:

| Category | Routes | Middleware | Compatibility status |
| --- | ---: | --- | --- |
| Public legacy API | 4 | `api` | Stable legacy client surface. |
| Authenticated legacy API | 130 | `api`, `api.token` | Stable legacy client surface guarded by the project bearer-token middleware. |

Public legacy routes are:

- `GET /api/data`
- `POST /api/login`
- `POST /api/signup`
- `POST /api/forgot_password`

All other named API routes should remain behind `api.token` unless a tested public contract explicitly requires otherwise.

## Versioning Decision

Do not add `/api/v1` during this audit.

Reason:

- Existing clients use unversioned `/api/*` URLs.
- There is no parallel versioned API currently consumed by clients.
- Adding duplicate versioned routes now would increase the public surface without migration tests, client telemetry, or a proven breaking-change need.
- `routes/api.php` already documents the intended safe path: add versioned groups beside the legacy surface only after client migration tests prove the existing public URLs remain supported.

Treat the current unversioned API as the legacy compatibility version. Future versioned APIs must be additive and parallel, not replacements for the existing `/api/*` routes.

## When A New Version Is Required

Create an explicit version group only when a change cannot safely preserve existing client behavior.

| Change type | New version required? | Rule |
| --- | --- | --- |
| Add optional response field | No | Additive fields are allowed if existing keys and types remain stable. |
| Add optional request parameter | No | Validate it and preserve the previous default behavior when omitted. |
| Add new endpoint | Usually no | Prefer adding to the legacy surface only if it follows the existing contract. New client-only APIs may start in `/api/v1`. |
| Rename/remove response key | Yes | Keep old key in legacy API; introduce replacement in versioned API. |
| Change field type or meaning | Yes | Do not change legacy semantics silently. |
| Change success/error envelope | Yes | Preserve legacy envelope and introduce the new envelope in a versioned API or add fields only. |
| Change transport HTTP status for legacy errors | Usually yes | Legacy clients may expect HTTP 200 with `success: false`. |
| Tighten authorization for a security bug | No | Security fixes may land in legacy API with regression tests and release notes. |
| Rename URI or route name | Yes or alias required | Keep old URI as an alias until deprecation completes. |
| Require new authentication scheme | Yes | Keep `api.token` compatibility until clients migrate. |

## Future Version Group Pattern

When versioning is needed, add the versioned API beside the current unversioned group:

```php
Route::name('api.')->group(function () {
    // Existing legacy /api/* routes stay here.

    Route::prefix('v1')
        ->as('v1.')
        ->group(function () {
            Route::controller(ApiController::class)->group(function () {
                // Public /api/v1/* routes.
            });

            Route::middleware('api.token')->group(function () {
                Route::controller(ApiController::class)->group(function () {
                    // Protected /api/v1/* routes.
                });
            });
        });
});
```

This produces names such as `api.v1.auth.login` and URLs such as `/api/v1/login`.

Versioned route rules:

- Keep legacy `/api/*` routes in place while clients migrate.
- Do not move an existing route into a version group without leaving a tested legacy alias.
- Keep public and protected routes separated inside the version group.
- Use route names for internal references.
- Add feature tests proving both legacy and versioned routes return compatible responses during migration.
- Document every versioned endpoint, including any intentional response differences.

## Response Compatibility Rules

The legacy API has mobile/public client compatibility requirements. Existing response keys, JSON nesting, scalar types, status behavior, and error messages are part of the contract unless tests prove otherwise.

Follow `docs/api-error-format.md` for API error changes:

- Preserve existing top-level legacy keys such as `success`, `message`, and `validationError`.
- Add the newer `error` envelope only as an additive field on legacy endpoints.
- Do not remove sensitive-field protections introduced by API resources.
- Keep legacy transport status behavior where clients expect HTTP 200 with `success: false`.
- Use `error.http_status` to express the canonical HTTP meaning when transport status must remain legacy-compatible.

Success response rules:

- Do not remove existing fields.
- Do not rename existing fields.
- Do not change existing field types.
- Do not change list ordering without tests and documentation.
- Do not expose hidden or sensitive model attributes.
- Prefer additive fields and resources that preserve the current shape.

Error response rules:

- Validation errors must preserve legacy `validationError` where already exposed.
- Authentication errors must preserve the current unauthorized payload for protected legacy endpoints.
- Authorization and not-found changes must be tested because legacy clients may distinguish them by message text instead of HTTP status.
- New machine-readable error codes must be additive.

## Deprecation Rules

No API route, request field, response field, enum value, status meaning, or authentication behavior may be removed or broken without a documented deprecation path.

Required deprecation process:

1. Add or identify the replacement endpoint, field, or behavior.
2. Add tests proving the legacy behavior still works.
3. Add tests proving the replacement behavior works.
4. Document the deprecation in this file or a linked API changelog.
5. Add server-side telemetry or log sampling to confirm active usage before removal.
6. Notify known clients before announcing a removal date.
7. Keep legacy behavior for at least 180 days after client notification unless the change is a critical security fix.
8. Add `Deprecation`, `Sunset`, and `Link` headers only after clients are known to tolerate additional headers.
9. Remove only after usage is confirmed near zero and rollback is documented.

For emergency security fixes, compatibility may be tightened immediately, but the change must include:

- A regression test proving the vulnerability is fixed.
- A clear release note.
- A rollback or mitigation plan if legitimate clients are affected.

## Safe Implementation Order For Versioning

1. Freeze the current route list with feature tests for representative public and protected endpoints.
2. Document the exact legacy response contracts for login, signup, profile, timeline, notifications, and one mutating endpoint.
3. Add `/api/v1` groups additively only for endpoints that need changed contracts.
4. Introduce versioned controllers, Form Requests, API Resources, and policies endpoint by endpoint.
5. Keep old route names and URLs available until telemetry proves clients have migrated.
6. Add deprecation headers and docs after the replacement endpoint is live and tested.
7. Remove legacy endpoints only in a dedicated release with rollback notes.

## Rollback Strategy

For any future versioned API rollout:

- Ship versioned routes additively so rollback can disable the new group without touching legacy routes.
- Keep migrations additive and compatible with both legacy and versioned controllers.
- Feature-flag new versioned behavior when the change affects authentication, payments, uploads, notifications, or write workflows.
- Keep legacy route tests in CI until the legacy API is formally retired.
- If a versioned endpoint breaks clients, route traffic back to the unversioned endpoint or disable the versioned group while preserving `/api/*`.

## Audit Evidence

Commands used during this audit:

```bash
php artisan route:list --path=api
```

Observed compatibility facts:

- 134 API routes are currently loaded.
- All API routes are under `/api/*`.
- Route names use the `api.` prefix.
- Public routes are limited to data, login, signup, and forgot-password endpoints.
- Protected routes use `api.token`.
- No `/api/v1` or other version prefix is currently registered.

## Open Follow-Ups

| Priority | Risk | Task | Safe first step |
| --- | --- | --- | --- |
| P0 | High | Capture response contracts for the most-used mobile endpoints. | Add feature tests for legacy success and error shapes before controller refactors. |
| P0 | High | Keep route-level API authentication coverage current. | Extend guard tests whenever routes are added to `routes/api.php`. |
| P1 | Medium | Prepare `/api/v1` only when a breaking response or auth change is proven necessary. | Add one additive versioned route behind tests, leaving the legacy route untouched. |
| P1 | Medium | Add usage telemetry before deprecating legacy endpoints. | Log route name, client version if available, and deprecation candidate usage without logging sensitive payloads. |
| P2 | Medium | Publish an API changelog. | Create a small `docs/api-changelog.md` before the first versioned endpoint ships. |
