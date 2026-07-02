# API HTTP Contract Audit

## Scope

Audited the legacy unversioned API registered in `routes/api.php` on 2026-07-02.
The current route surface contains 134 named API routes: 53 `GET` routes and 81
`POST` routes. Four routes are public (`api.data.index`, `api.auth.login`,
`api.auth.signup`, `api.password.forgot`); the rest are protected by the
`api.token` bearer token middleware.

The API is mobile-client compatible and intentionally preserves legacy response
shapes. This audit does not rename URLs, change route names, replace `POST`
tunneled actions with `PUT`, `PATCH`, or `DELETE`, or change legacy HTTP 200
error transports where clients already depend on them.

## Findings

| Area | Current state | Risk | Decision |
| --- | --- | --- | --- |
| HTTP verbs | Read/list endpoints use `GET`. Creates, updates, deletes, accepts, declines, likes, saves, views, and toggles use `POST`. | Medium | Preserve existing routes. Add RESTful verbs only in a future versioned API and keep legacy aliases during migration. |
| Idempotency | Some `POST` routes are naturally non-idempotent (`create_post`, `chat_save`). Some represent idempotent state changes (`unsave_for_later`, `mark_as_read`) but do not declare idempotency guarantees. | Medium | Do not retry mutation requests blindly. Add idempotency keys later for payments, uploads, chat, imports, and background side effects. |
| Status codes | Several legacy errors return HTTP 200 with `success: false`; Laravel validation exceptions still return 422 JSON. | Medium | Preserve compatibility. Treat `error.http_status` as canonical where present. Normalize only in a versioned API. |
| Redirects | API routes can hit Laravel validation behavior that redirects when the request does not negotiate JSON. | High | The API middleware now forces JSON negotiation before controllers run. API clients should never receive validation redirects. |
| JSON-only behavior | Most controllers already return arrays or `response()->json()`, but some routes rely on Laravel defaults. | Medium | The API group now sets `Accept: application/json` internally and returns `Vary: Accept`. |
| Content negotiation | Legacy clients may omit `Accept` or send broad browser headers. | Medium | Preserve client compatibility by making API routes JSON-first regardless of the inbound `Accept` header. |
| Validation responses | The project supports both legacy `validationError` payloads and Laravel's default `errors` payload depending on endpoint implementation. | Medium | Preserve both shapes. New Form Requests should use the documented `ApiErrorResponse` envelope. |
| Caching headers | API responses include private profile, token, notification, chat, marketplace, and payment-adjacent data but lacked a consistent cache policy. | High | The API group now sends `Cache-Control: no-store, no-cache, must-revalidate, private`, `Pragma: no-cache`, and `Expires: 0`. |

## Compatibility Contract

- Existing public URLs, route names, and `GET`/`POST` methods remain supported.
- Existing success payloads remain unchanged.
- Legacy error payloads with `success`, `message`, and `validationError` remain supported.
- Legacy HTTP 200 error transports remain supported on endpoints that already use them.
- API responses are JSON-only and must not redirect to web forms, login screens, or back URLs.
- API responses are private and non-cacheable unless a future endpoint receives a tested, explicit public cache policy.

## Deprecations

| Legacy pattern | Replacement target | Migration order |
| --- | --- | --- |
| Verbful mutation URLs such as `/api/delete_post/{id}` | Versioned resource route such as `DELETE /api/v2/posts/{post}` | Add v2 route, add contract tests, publish client docs, keep v1 alias, deprecate with headers later. |
| `POST` for updates and deletes | `PATCH`, `PUT`, or `DELETE` in a versioned API | Introduce alongside current routes; do not replace in-place. |
| HTTP 200 for authentication, authorization, not-found, and validation errors | Canonical 401, 403, 404, and 422 transport statuses | Keep legacy v1 behavior; switch only for v2 or explicitly opted-in clients. |
| Mixed validation response shapes | Standard `ApiErrorResponse` envelope with `validationError` compatibility key | Convert endpoint by endpoint with regression tests. |
| Unpaginated list endpoints | Paginated or cursor-paginated resources | Add response contract tests before changing payload shapes. |
| Mutation routes without idempotency keys | `Idempotency-Key` support for side-effecting writes | Start with payments, uploads, chat, and notification actions. |

## Suggested Implementation Order

1. Keep this middleware-level JSON/cache contract in place for all API routes.
2. Add feature tests around the highest-traffic mobile endpoints before changing route internals.
3. Convert inline validation to API Form Requests while preserving current response shapes.
4. Add API Resources for new versioned endpoints; keep legacy array payloads stable.
5. Introduce `/api/v2` resource routes with canonical verbs and status codes.
6. Add deprecation headers to v1 only after client owners confirm migration windows.
7. Remove legacy aliases only in a major API version with rollback notes.

## Verification

- `php artisan route:list --json --path=api` confirmed 134 named API routes.
- Feature coverage verifies JSON/no-redirect behavior for public validation,
  protected authentication failure, and Laravel validation exceptions.
- Cache header assertions cover `no-store`, `private`, `Pragma: no-cache`,
  `Expires: 0`, and `Vary: Accept`.
