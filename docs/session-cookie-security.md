# Session and Cookie Security

Date: 2026-07-02

## Current Audit Result

The web guard uses Laravel's session driver through the `web` middleware group. `EncryptCookies`, `AddQueuedCookiesToResponse`, `StartSession`, `ShareErrorsFromSession`, and `VerifyCsrfToken` are registered for browser routes.

Authentication lifecycle checks are in place:

- Login calls `session()->regenerate()` after successful authentication.
- Logout calls `Auth::guard('web')->logout()`, `session()->invalidate()`, and `regenerateToken()`.
- The login form includes remember me, and `LoginRequest` passes `$this->boolean('remember')` to `Auth::attempt(...)`.
- `User` models hide `remember_token` from serialization.

Cookie configuration is controlled through `config/session.php` and must be set through environment variables, not direct `env()` calls outside config files.

## Production Session Settings

Use HTTPS in every production environment and ensure proxy/load balancer headers are trusted correctly through `TrustProxies`.

Recommended production values:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_EXPIRE_ON_CLOSE=false
SESSION_ENCRYPT=true
SESSION_DOMAIN=
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
SESSION_SECURE_COOKIE=true
```

Use `SESSION_DRIVER=database` only after adding and migrating a `sessions` table. This repository does not currently include a sessions-table migration, so the checked-in default remains `file` for compatibility. Redis is also acceptable when production Redis is provisioned, monitored, and backed by a clear eviction policy.

## Control Notes

- `SESSION_SECURE_COOKIE=true` prevents browsers from sending session cookies over plain HTTP. `config/session.php` now defaults this to true when `APP_ENV=production` unless the environment explicitly overrides it.
- `SESSION_HTTP_ONLY=true` prevents JavaScript from reading the session cookie.
- `SESSION_SAME_SITE=lax` mitigates CSRF for normal browser flows. Use `strict` only after testing cross-site login/payment/provider redirects. Use `none` only with HTTPS and a proven cross-site cookie requirement.
- `SESSION_ENCRYPT=true` encrypts session payloads at rest in the configured session store. Laravel's `EncryptCookies` middleware also encrypts cookies by default unless a cookie is explicitly excluded.
- Leave `SESSION_DOMAIN` empty unless cookies must intentionally span subdomains. Use a parent domain only after reviewing tenant and subdomain isolation.
- Keep `SESSION_LIFETIME` short enough for the data sensitivity of the app. Lower the value for admin-heavy deployments.
- Remember-me cookies are long-lived by design. Only allow them over HTTPS, keep `remember_token` hidden, and rely on logout to clear the recaller cookie.

## Deployment Checklist

Before enabling production traffic:

```bash
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan test tests/Feature/Auth/AuthenticationTest.php tests/Feature/SecurityHardeningTest.php
```

Then verify response headers in a browser or HTTP client:

- The session cookie has `Secure`, `HttpOnly`, and `SameSite=Lax` or stricter.
- The app is reached through HTTPS and proxy headers preserve the original scheme.
- Login changes the session ID.
- Logout clears authentication state, invalidates previous session data, and rotates the CSRF token.
- Remember-me login sets only the expected Laravel recaller cookie.

## Rollback Notes

If a production cookie rollout blocks login, first confirm HTTPS and `TrustProxies` behavior. If an emergency rollback is required, set `SESSION_SECURE_COOKIE=false` temporarily only behind a private maintenance window, clear/config-cache again, and restore the secure value before reopening public traffic.

If enabling `SESSION_ENCRYPT=true` invalidates existing stored sessions, force logout active sessions during a planned maintenance window and communicate the expected re-login.
