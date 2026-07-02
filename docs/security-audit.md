# OWASP-Style Security Audit

Date: 2026-07-02

## Scope

This audit reviewed the current Laravel application for authentication, authorization, IDOR, validation, mass assignment, CSRF, XSS, SQL injection, file uploads, SSRF, secrets, sessions, cookies, CORS, rate limits, logging, debug mode, and dependency vulnerabilities.

The audit focused on safe, incremental fixes that preserve legacy client behavior.

## Commands Run

```bash
composer audit --no-interaction
npm audit --audit-level=moderate
npm audit --omit=dev --audit-level=moderate
composer validate --strict --no-interaction
vendor/bin/pint --test
php artisan test
php artisan route:cache
php artisan route:clear
rg security patterns across app, routes, config, resources, database, and tests
```

## Safe Fixes Applied

| Area | Risk | Fix |
| --- | --- | --- |
| Security headers | Missing baseline browser hardening headers | Added global `SecurityHeaders` middleware for `X-Content-Type-Options`, `X-Frame-Options`, `X-XSS-Protection`, `Referrer-Policy`, `Permissions-Policy`, and HTTPS-only HSTS. |
| SSRF | Link previews fetched arbitrary user-influenced URLs server-side | Added `ServerSideUrl` guard for HTTP(S)-only public targets, blocking localhost/private/reserved IPs, disabling redirects, adding timeout, and limiting the response read. |
| Raw SQL | Unused auth-controller helper contained `DB::unprepared()` and `DB::select('SHOW TABLES')` | Removed the unreferenced helper and unused imports. |
| API IDOR / route safety | Registered marketplace unsave API route had no active method | Restored `unsave_for_later` with authenticated-user scoping and regression coverage. |
| Session cookies | `same_site` was `null` despite secure default guidance | Changed default to `SESSION_SAME_SITE=lax`. |
| Password hashing | Bcrypt default was `10` rounds | Changed default to `12`; tests keep `BCRYPT_ROUNDS=4` in `phpunit.xml`. |
| Production defaults | `.env.example` encouraged debug logging | Changed template defaults to `APP_DEBUG=false`, `LOG_LEVEL=warning`, `SESSION_SAME_SITE=lax`, and `BCRYPT_ROUNDS=12`. |

## Dependency Results

| Ecosystem | Result | Notes |
| --- | --- | --- |
| Composer | No security vulnerability advisories found | `composer audit --no-interaction` passed. |
| npm runtime dependencies | No vulnerabilities found | `npm audit --omit=dev --audit-level=moderate` passed. |
| npm full tree | 11 low/moderate findings | Findings are through legacy Laravel Mix development tooling (`elliptic`, `uuid` via `laravel-mix` dependency chain). npm reports no fix available. Treat as a build-tool modernization task, not a runtime blocker. |

## Findings Deferred

| Area | Severity | Reason Deferred | Safe First Step |
| --- | --- | --- | --- |
| CORS wildcard origins | Medium | `supports_credentials=false` lowers browser credential risk, and changing origins can break unknown API/browser clients. | Add `CORS_ALLOWED_ORIGINS` config and deploy in report-only/client-observation mode before restricting production. |
| Broad raw HTML in Blade | High | Many legacy views intentionally render trusted/sanitized HTML through helpers such as `script_checker`; changing globally risks breaking content rendering. | Audit one feature at a time, replace unsafe raw output with escaped output or a sanitizer contract, and add XSS regression tests. |
| Queries inside Blade | Medium | Existing audit/test coverage flags this as architectural debt, but broad removal is refactor-sized. | Move one high-traffic partial's queries into controller/view-model data with query-count tests. |
| Payment callback verification | High | Payment routes are rate-limited but provider signature verification needs gateway-specific behavior and tests. | Add signed callback verification per gateway starting with the active production provider. |
| Legacy API error status compatibility | Medium | Some validation/auth failures intentionally return HTTP 200 for mobile compatibility. | Preserve legacy responses until a versioned API migration exists. |
| Link-preview DNS rebinding residual risk | Medium | The SSRF guard blocks obvious internal targets but still uses PHP stream resolution after validation. | Move previews to a queued fetcher with a filtering HTTP client or network egress controls. |
| npm dev-tool vulnerabilities | Medium | npm reports no fix available in the Laravel Mix chain. | Plan frontend build migration away from Laravel Mix/Webpack notifier dependencies or isolate builds in CI. |

## Category Notes

- Authentication: login/password reset routes are rate-limited; reset tokens expire after 60 minutes; Sanctum API tokens have expiration coverage.
- Authorization and IDOR: marketplace write paths use token abilities/policies; this pass restored scoped API unsave behavior.
- Validation and mass assignment: several Form Requests and payload protection tests exist; broad inline validation remains legacy debt.
- CSRF: web group uses `VerifyCsrfToken`; API uses bearer-token middleware and does not rely on session cookies.
- SQL injection: remaining raw expression found in marketplace search is bound and column-allowlisted; removed one unused raw SQL helper.
- File uploads: marketplace upload validation restricts image type, extension, size, and dimensions; additional upload surfaces still need feature-by-feature review.
- Logging/debug: existing tests block dump/debug statements and raw exception-message logging; no fork-safety debug routes are present in the final route diff.
- Secrets: staged changes were scanned for secret-like values; examples use placeholders only.
