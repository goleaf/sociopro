# Production Exposure Audit

Generated: 2026-07-02

## Scope

This audit covers production exposure risks for debug mode, Ignition, Telescope, Horizon, Pulse, debug/test routes, `phpinfo`, storage links, public `.env` files, public backups, logs, SQL dumps, test endpoints, and admin tools.

## Current Findings

| Area | Status | Risk | Action |
| --- | --- | --- | --- |
| `APP_DEBUG` | Hardened | Stack traces and environment details can leak when enabled in production. | `.env.example` keeps `APP_DEBUG=false`; production must also set `APP_ENV=production` and `APP_DEBUG=false`. |
| Ignition | Not installed | Debug exception UI must never be exposed in production. | Composer lock contains no Ignition package and no Ignition routes are registered. |
| Telescope | Not installed | Request, query, exception, and payload inspection can expose sensitive data. | Composer lock contains no Telescope package and no Telescope routes are registered. |
| Horizon | Not installed | Queue dashboards expose job names, payload metadata, and operational controls. | Composer lock contains no Horizon package and no Horizon routes are registered. |
| Pulse | Not installed | Application telemetry endpoints expose operational internals. | Composer lock contains no Pulse package and no Pulse routes are registered. |
| Debug routes / `phpinfo` / test endpoints | Hardened | Public diagnostics disclose runtime, paths, loaded extensions, or implementation details. | Regression tests reject debug/test/phpinfo route registration; Apache blocks common probe filenames. |
| Admin tools | Partly hardened | Admin screens can expose settings, users, payments, and operational controls. | Existing route audit enforces `admin` middleware on `admin/*` routes; keep admin behind auth, verified users, and role middleware. |
| Public `.env`, logs, backups, and dumps | Hardened in repo | Static web servers can serve files before Laravel middleware runs. | Removed the tracked public SQL dump and archive; `.gitignore` now blocks new public dumps/backups/logs; Apache denies common sensitive extensions. |
| Legacy install dump | Hardened | The SQL bootstrap dump was previously under `public/assets`. | Moved to `database/schema/install.sql` and configured through `config/install.php`. |
| Storage link / uploads | Hardened for Apache | Uploaded executable or backup files under public storage could be served directly. | Added `storage/app/public/.htaccess` to deny PHP-like executables, dumps, logs, archives, and dotfiles when Apache honors `.htaccess`. |

## Safe Fixes Applied

- Moved the legacy install SQL dump from the public web root to `database/schema/install.sql`.
- Added `config/install.php` with `INSTALL_SCHEMA_DUMP_PATH` override support while defaulting outside `public`.
- Removed the tracked public Sass archive at `public/assets/backend/sass/_base.zip`.
- Removed local ignored `.DS_Store` files from `public`.
- Added public-root Apache deny rules for dotfiles, `.env`, dumps, logs, backups, archives, `phpinfo`, debug/test endpoint filenames, and non-`index.php` PHP files.
- Added public-storage Apache deny rules for PHP-like executables and sensitive backup/dump/log/archive files.
- Added regression coverage in `tests/Feature/ProductionExposureAuditTest.php`.

## Deployment Requirements

- Set production environment values:
  - `APP_ENV=production`
  - `APP_DEBUG=false`
  - `APP_URL=https://...`
  - `LOG_LEVEL=warning` or stricter operational value
  - `SESSION_SECURE_COOKIE=true`
- Serve only the `public` directory as the web root. Never point a virtual host at the repository root.
- For Apache, keep `AllowOverride` enabled for the application `public` directory if relying on committed `.htaccess` rules.
- The committed dotfile deny rule blocks `/.well-known` by default. If ACME HTTP-01 challenges are used, terminate them at the reverse proxy/CDN or add a narrow, reviewed exception for the exact challenge path.
- For Nginx, Caddy, CDN, or object-storage frontends, add equivalent deny rules for:
  - dotfiles and `.env*`
  - `*.sql`, `*.sqlite`, `*.db`, `*.log`
  - `*.bak`, `*.backup`, `*.old`, `*.orig`
  - `*.zip`, `*.tar`, `*.tgz`, `*.gz`, `*.7z`, `*.rar`
  - all PHP-like uploads under storage: `*.php`, `*.phtml`, `*.phar`
  - `phpinfo`, `debug`, `test`, `telescope`, `horizon`, `pulse`, and `ignition` probe paths
- Do not install or enable Ignition, Telescope, Horizon, Pulse, Debugbar, or similar tools on production hosts without explicit authentication, IP allowlists, and a separate reviewed deployment plan.

## Verification Commands

```bash
php artisan test tests/Feature/ProductionExposureAuditTest.php
php artisan route:list --except-vendor
git ls-files public
```

## Remaining Risks

- Apache `.htaccess` rules do not protect Nginx/CDN deployments by themselves; production web-server config must mirror the deny list.
- The Apache dotfile block intentionally protects against public `.env` leaks and also blocks `/.well-known`; certificate challenge handling must be configured at the web-server layer.
- `public/storage` remains intentionally web-accessible for public media. Continue validating upload types, extensions, sizes, and authorization at the application boundary.
- The legacy schema still depends on a SQL dump. It is no longer public, but the long-term fix remains replacing the dump with a verified migration baseline.
