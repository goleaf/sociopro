# API Authentication Audit

Date: 2026-07-02

## Scope

Audited API authentication guards, bearer tokens, Sanctum configuration, Passport usage, cookies, CSRF expectations, token expiration, token revocation, token abilities, and logout behavior.

## Current Contract

- The API uses Sanctum personal access tokens through the custom `api.token` middleware.
- Passport is not installed and no Passport guards, routes, or middleware are used.
- Bearer-only API: protected `api.*` routes require a bearer token; web session cookies do not authenticate API requests.
- The API middleware group intentionally does not run session or CSRF middleware. CSRF protection remains a browser/web-route concern.
- Public auth routes are limited separately with `api-token`, `api-registration`, and `api-password-reset` throttles.

## Fixes Applied

| Area | Risk | Finding | Fix |
| --- | --- | --- | --- |
| Token expiration | High | Login-created tokens were issued without `expires_at`, and Sanctum's global expiration was `null`. | Set the default Sanctum expiration to 30 days via `SANCTUM_EXPIRATION=43200` and stamp login tokens with an explicit `expires_at`. |
| Token abilities | Medium | Login-created tokens relied on Sanctum's implicit wildcard ability. | Login now creates tokens with the explicit API ability list from `ApiTokenAbility`. |
| Token revocation | High | The bearer API had no logout endpoint to revoke the current token. | Added `POST /api/logout` (`api.auth.logout`) to delete only the current personal access token. |
| Cookie auth boundary | High | The API contract needed regression coverage proving cookies and CSRF headers do not bypass bearer authentication. | Added tests for web-session plus CSRF-header requests without bearer tokens. |

## CSRF And Cookie Expectations

This project currently exposes a bearer-only API. First-party SPA cookie authentication is not enabled because `EnsureFrontendRequestsAreStateful` is not in the API middleware group. Requests with web session cookies or CSRF headers must still provide a valid personal access token for protected API routes.

If a future browser SPA enables Sanctum stateful authentication, it must be implemented as a separate documented change with CSRF-cookie setup, stateful domain review, CORS review, and regression tests proving bearer clients are not broken.

## Logout And Revocation

`POST /api/logout` performs current-token revocation and revokes only the token used for the current request. Other tokens belonging to the same user remain valid. Revoked or deleted tokens must continue to return the legacy API authentication payload:

```json
{
  "success": false,
  "message": "Unauthorized access"
}
```

## Safe Rollout Notes

- Existing tokens older than the configured Sanctum expiration may become invalid once this is deployed with `SANCTUM_EXPIRATION=43200`.
- Keep `SANCTUM_EXPIRATION` in minutes and tune it per environment only through `.env` and `config/sanctum.php`.
- Run `php artisan sanctum:prune-expired --hours=24` from the scheduler when operationally safe to remove expired token records.
- Do not add Passport unless a separate migration plan covers guard changes, token tables, scopes, and client compatibility.
