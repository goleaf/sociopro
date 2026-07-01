# Route Audit

Date: 2026-07-01

## Scope

Audited all loaded route files: `routes/web.php`, `routes/api.php`, `routes/auth.php`, `routes/custom_routes.php`, `routes/user.php`, `routes/payment.php`, and `routes/console.php`.

## Safe Fixes Applied

- Moved HTTP route closures from `routes/web.php` into controller actions.
- Removed the dead duplicate default `/api/user` closure. The effective `/api/user` route remains `ApiController@user`.
- Added an explicit name to `/users/{user_id}` as `users.welcome`.
- Protected `/clear-cache` with `auth`, `verified`, and `admin` middleware.
- Removed the duplicated `user` middleware from the main timeline route group.
- Removed the duplicated `/stories/{offset?}/{limit?}` route declaration.
- Removed the duplicated `save/video/short/{id}` route declaration.
- Added `admin` protection to updater/addon routes under `admin/addon/*` and `admin/product/update`.
- Added missing admin middleware to public admin-prefixed routes for Amazon S3 settings, Jitsi settings update, server-side users data, system about/license settings, and paid-content author/payout administration.

## Verification

- `php artisan route:cache` was checked before and after changes.
- `composer ci` passed after the route fixes.
- Added route audit regression tests for HTTP closure routes, `/clear-cache` protection, admin-prefixed middleware, and duplicate route declarations.

## Risky Findings To Refactor Next

| Priority | Risk | Area | Finding | Safe First Step |
|---|---|---|---|---|
| P0 | High | Web/admin routes | Many destructive or state-changing actions still use `GET`, including delete/status/toggle/save/block routes. | Add POST/DELETE companion routes and update one Blade form at a time with CSRF before removing GET compatibility. |
| P0 | High | API routes | API routes are largely ungrouped, unnamed, and rely on controller-level bearer-token checks instead of route middleware. | Group authenticated endpoints behind `auth:sanctum` after adding mobile/API regression tests. |
| P1 | High | Admin routes | Admin actions are still scattered across route files with repeated middleware chains. | Create an `admin` route group with `prefix('admin')`, `name('admin.')`, and shared middleware, then migrate routes module by module. |
| P1 | Medium | Route verbs | `Route::any()` is used for modal loading, stories, profile album/info, server-side users data, and purchase-code handling. | Split each route into explicit GET/POST verbs based on current controller behavior and tests. |
| P1 | Medium | Model binding | Most `{id}` routes pass scalar IDs and call `find()` in controllers. | Convert one resource at a time to route model binding with 404 regression tests. |
| P1 | Medium | Prefix clarity | Several URIs contain typos or unclear names such as `account-enble-req`, `peopel`, `zitsi`, `JobApply`, and mixed snake/camel casing. | Add clean aliases while keeping old routes temporarily, then migrate views/API clients. |
| P2 | Medium | Install routes | Installation routes remain publicly accessible. | Add an installed-state middleware or environment guard once install lifecycle behavior is documented. |
| P2 | Medium | Payment routes | Public payment callback routes are necessary, but route-level signature/webhook validation is not visible. | Document each provider callback contract and add tests for invalid callback payloads. |
| P2 | Low | Console routes | `routes/console.php` uses a closure for the `inspire` command. | Leave as-is unless route/command serialization policy changes; it does not affect HTTP route caching. |

## Suggested Implementation Order

1. Add tests around existing mutating GET endpoints that are still linked from Blade.
2. Convert destructive admin routes to POST/DELETE forms with CSRF, keeping temporary legacy GET routes only where needed.
3. Create grouped admin routes and migrate admin modules in small batches.
4. Group API routes by public/authenticated modules and add explicit route names.
5. Introduce route model binding for high-traffic resources.
6. Retire legacy aliases after logs/tests prove clients no longer use them.
