# Middleware Audit

Last updated: 2026-07-01

## Scope

Audited `app/Http/Middleware`, `app/Http/Kernel.php`, Sanctum middleware config, route middleware usage, ordering, request/response mutation, and existing route tests.

## Inventory

| Middleware | Registration | Route usage | Security sensitivity | Notes |
| --- | --- | --- | --- | --- |
| `AdminMiddleware` | Alias `admin` | Admin routes and admin route groups | High | Enforces admin role. Must run after `auth` and `verified`. Guest requests redirect to login; non-admin web users redirect to the safe timeline route; JSON callers receive 403. |
| `Authenticate` | Alias `auth` | Protected web routes | High | Redirects unauthenticated web users to `login`; JSON requests keep Laravel default unauthenticated behavior. |
| `EncryptCookies` | Web group and Sanctum config | All web requests | High | Framework cookie encryption wrapper. Empty local override is still registered and should not be removed casually. |
| `PreventBackHistory` | Alias `prevent-back-history` | Authenticated pages that should not be browser-cacheable | Medium | Mutates response cache headers. Now uses Symfony/Laravel-compatible header mutation and standard no-cache directives. |
| `PreventRequestsDuringMaintenance` | Global stack | All requests | Medium | Framework maintenance-mode wrapper. Empty local override is registered globally. |
| `RedirectIfAuthenticated` | Alias `guest` | Guest auth routes | Medium | Redirects authenticated users to `RouteServiceProvider::HOME`. |
| `TrimStrings` | Global stack | All requests | Medium | Mutates request input by trimming strings except password fields. Ordering before `ConvertEmptyStringsToNull` is correct. |
| `TrustProxies` | Global stack | All requests behind proxies/load balancers | High | Controls forwarded header trust. Keep reviewed for deployment topology. |
| `UserActivity` | Alias `activity` | Authenticated user/admin flows | Medium | Mutates authenticated user `lastActive` and cache. Guest requests now pass through safely. |
| `UserMiddleware` | Alias `user` | General-user routes and mixed admin/user pages | High | Enforces active general users and allows admins. Guest requests now redirect to login. |
| `VerifyCsrfToken` | Web group and Sanctum config | Web forms and Sanctum CSRF | High | Excludes `login` and `payment/status`; `payment/status` is likely webhook/callback compatibility, while `login` should be reviewed before removal. |

## Findings

| Area | Status | Details | Action |
| --- | --- | --- | --- |
| Naming | Pass with legacy names | `AdminMiddleware` and `UserMiddleware` are clear enough, though Laravel convention would also accept `EnsureUserIsAdmin` / `EnsureUserIsActive`. | Keep names to avoid alias churn. |
| Registration | Pass | Every file in `app/Http/Middleware` is registered in the Kernel global stack, `web` group, route aliases, or Sanctum config. | Added regression coverage. |
| Ordering | Pass | Routes using `admin`, `user`, or `activity` run `auth` first; `admin` routes also run `verified` first. | Added regression coverage. |
| Route usage | Pass | `admin`, `user`, `activity`, and `prevent-back-history` are actively used across route files. | No unused middleware removed. |
| Authorization leakage | Improved | `AdminMiddleware` and `UserMiddleware` previously assumed an authenticated user and could throw if used without `auth`. | Added guest-safe redirects to `login`. |
| Duplicated logic | Known debt | Role/status checks remain in middleware and some controllers. | Prefer policies/gates in future authorization refactors. |
| Request mutation | Expected | `TrimStrings` mutates request input globally. | Keep password exceptions. |
| Response mutation | Improved | `PreventBackHistory` previously used Laravel-only `header()` chaining while returning `Symfony\Component\HttpFoundation\Response`. | Switched to response header bag mutation. |
| Config access | Pass | No direct `env()` usage found in middleware. | Continue using config files for environment values. |
| Tests | Improved | Middleware registration, alias mapping, ordering, guest-safe handling, activity mutation, and no-cache headers are covered. | Keep `tests/Feature/MiddlewareAuditTest.php` updated with middleware changes. |

## Admin Route Group Audit

| Control | Status | Current behavior | Safe next step |
| --- | --- | --- | --- |
| Authentication | Pass | Registered admin routes include `auth` before admin authorization. Guests are redirected to `login` before controller actions run. | Keep `auth` first on every route that uses `admin`, `activity`, or user-state middleware. |
| Email verification | Pass | Registered admin routes include `verified` before `admin`. Unverified admins are redirected to `verification.notice`. | Do not add admin routes without `verified` unless the route is explicitly documented as pre-verification. |
| Authorization | Pass with legacy role model | Admin access is role-based through `UserRole::Admin`; no permission layer is currently active. Non-admin web users are redirected to `timeline`; JSON requests receive 403. | Move high-risk admin actions toward policies/gates in small slices. |
| Tenant scoping | Not applicable | No tenant middleware, tenant model, or tenant route prefix is currently present in the registered admin stack. | If tenancy is introduced, add tenant-resolution middleware before authorization and test cross-tenant denial. |
| Throttling | Gap documented | Admin routes do not currently define a dedicated `throttle:*` middleware. Existing auth verification routes are throttled separately. | Define an admin rate-limit policy before adding broad throttling, then apply it to high-risk write/export routes first. |
| Secure redirects | Improved | Denied non-admin web requests no longer use `redirect()->back()`, avoiding loops or redirects to user-controlled previous admin URLs. | Keep denial redirects fixed to safe named routes, and return HTTP status codes for JSON/API-style requests. |
| Browser cache | Partial | Most admin routes use `prevent-back-history`; a few download/history routes do not. | Review routes without `prevent-back-history` separately to avoid changing download behavior in this audit. |

## Admin Access Regression Coverage

- `tests/Feature/AdminRouteAccessTest.php` covers guest redirects, unverified admin redirects, non-admin web denial redirects, non-admin JSON denial status, allowed admin middleware access, and registered admin route middleware ordering.
- `tests/Feature/MiddlewareAuditTest.php` continues to cover alias registration, auth-before-role ordering, guest-safe middleware behavior, activity tracking, and no-cache headers.
- `tests/Feature/RouteAuditTest.php` continues to require admin-prefixed routes to include admin middleware.

## Unused Middleware Removal

No middleware was removed. Each application middleware class is currently reachable through Kernel registration or Sanctum configuration:

- Global stack: `TrustProxies`, `PreventRequestsDuringMaintenance`, `TrimStrings`.
- Web group: `EncryptCookies`, `VerifyCsrfToken`.
- Route aliases: `Authenticate`, `RedirectIfAuthenticated`, `AdminMiddleware`, `UserMiddleware`, `UserActivity`, `PreventBackHistory`.
- Sanctum config: `EncryptCookies`, `VerifyCsrfToken`.

## Security-Sensitive Middleware Rules

- Do not add `admin`, `user`, or `activity` to routes without `auth` before them.
- Do not add `admin` to routes without `verified` unless there is a documented reason.
- Do not remove `VerifyCsrfToken` from the web group.
- Do not add new CSRF exceptions without documenting the external client or callback that requires it.
- Do not remove `TrustProxies` or alter trusted proxy behavior without deployment review.
- Do not change `PreventBackHistory` headers without testing browser-cache behavior around authenticated pages.

## Follow-Up Tasks

| Priority | Risk | Task |
| --- | --- | --- |
| 1 | High | Review the `VerifyCsrfToken` `login` exception. If browser login forms include CSRF tokens, remove the exception with a regression test. |
| 2 | High | Replace broad role checks in controllers with policies/gates for admin and user-owned resources. |
| 3 | Medium | Define an admin throttling standard for high-risk write, export, cache, and settings routes before applying broad `throttle:*` middleware. |
| 4 | Medium | Rename `UserMiddleware` to a more explicit class name only if route aliases and docs are updated in the same small refactor. |
| 5 | Medium | Consider moving activity tracking to an event/listener or queued write if `lastActive` updates become a write bottleneck. |
