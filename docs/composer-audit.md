# Composer and npm Dependency Audit

Generated: 2026-07-02

This audit covers `composer.json`, `composer.lock`, `package.json`, and `package-lock.json` for outdated, abandoned/deprecated, vulnerable, duplicated, unnecessary, and Laravel/frontend-build-incompatible packages. It intentionally avoids major dependency upgrades and applies only low-risk cleanup.

## 2026-07-02 Audit Summary

| Area | Result | Notes |
| --- | --- | --- |
| Composer advisories | Pass | `composer audit --format=json --no-interaction` reported `advisories: []`. |
| Composer abandoned packages | Pass | `composer audit --format=json --no-interaction` reported `abandoned: []`. |
| Composer compatibility | Pass with watch items | Locked packages install against PHP `8.5.7` and Laravel `13.18.0`; old-but-compatible payment/media/share packages remain upgrade risks. |
| npm production audit | Pass | `npm audit --omit=dev --json` reported 0 vulnerabilities. |
| npm full audit | Fails informationally | `npm audit --json` reported 11 dev-tool vulnerabilities: 5 low, 6 moderate, 0 high, 0 critical. npm reports no automatic fix. |
| Safe update applied | None | `npm audit fix --dry-run` had no changes. A trial `webpack` lockfile update to `5.108.3` broke Laravel Mix production builds and was reverted. |
| Direct npm deprecations | Pass | Direct packages checked with `npm view <package> deprecated`; no deprecation messages were reported. |

## 2026-07-02 Commands Used

```bash
composer validate --strict --no-interaction
composer audit --format=json --no-interaction
composer outdated --direct --format=json --no-interaction
npm audit --json
npm audit --omit=dev --json
npm audit fix --dry-run --json
npm outdated --json
npm ls --depth=0 --json
npm explain webpack-dev-server webpack-notifier node-notifier sockjs uuid elliptic crypto-browserify node-libs-browser --json
npm update webpack
npm install --save-dev webpack@5.104.1
```

## npm Findings

The full npm audit findings are development/build-tool scoped through `laravel-mix@6.0.49`:

| Chain | Severity | Scope | Fix status | Safe decision |
| --- | --- | --- | --- | --- |
| `laravel-mix -> webpack-dev-server -> sockjs -> uuid` | Moderate | Dev server only | No npm fix available in current Mix chain | Do not force an override; migrate build tooling in a dedicated Mix-to-Vite/Webpack modernization. |
| `laravel-mix -> webpack-notifier -> node-notifier -> uuid` | Moderate | Dev notification tooling | No npm fix available in current Mix chain | Avoid local notification/HMR exposure on untrusted networks; defer until build-tool migration. |
| `laravel-mix -> node-libs-browser -> crypto-browserify -> browserify-sign/create-ecdh -> elliptic` | Low | Build polyfills | No npm fix available in current Mix chain | Treat as dev/build-chain debt, not runtime application exposure. |

`npm audit fix --dry-run --json` reported `added: 0`, `removed: 0`, and `changed: 0`, so no automatic safe security update was available. Production/runtime dependencies remain clean under `npm audit --omit=dev --json`.

## Safe Cleanup Decision on 2026-07-02

| Package | Action | Reason | Runtime impact |
| --- | --- | --- | --- |
| `webpack` | Kept at `5.104.1` | Updating to `5.108.3` is inside the semver constraint but Laravel Mix 6 fails with `Cannot find module 'webpack/lib/SizeFormatHelpers'`. | Keeping the current lock preserves `npm run production`. Treat newer Webpack as part of the Mix-to-Vite or Mix replacement project. |

## Audit Sources

- Local PHP runtime: PHP `8.5.7`
- Composer: `2.9.5`
- Laravel framework: `13.18.0`
- Node: `v22.22.3`
- npm: `10.9.8`
- Frontend build: Laravel Mix `6.0.49` / Webpack `5.104.1`
- Composer advisory source: Packagist security advisories via `composer audit`
- Composer package metadata from the current lockfile and Packagist metadata
- npm advisory source: npm audit report from the current lockfile

Commands used:

```bash
composer validate --strict --no-interaction
composer audit --format=json --no-interaction
composer outdated --direct --format=json --no-interaction
composer outdated --direct --minor-only --format=json --no-interaction
composer outdated --direct --patch-only --format=json --no-interaction
composer update --dry-run --no-interaction
composer why guzzlehttp/guzzle --no-interaction
```

## Safe Cleanup Applied

| Package | Action | Reason | Runtime impact |
| --- | --- | --- | --- |
| `guzzlehttp/guzzle` | Removed from root `require` | The app does not use `GuzzleHttp` classes directly, and Guzzle remains installed through transitive requirements. | No package version changed; Guzzle remains available through Laravel/S3/Flutterwave dependencies. |

After cleanup, `guzzlehttp/guzzle` is still required by:

- `laravel/framework`
- `flutterwavedev/flutterwave-v3`
- `aws/aws-sdk-php`
- `php-http/guzzle7-adapter`
- Flysystem/S3 related packages

No production or dev package versions were upgraded or downgraded.

## Security and Abandoned Package Findings

| Check | Result | Notes |
| --- | --- | --- |
| `composer audit` advisories | Pass | `advisories: []` |
| `composer audit` abandoned packages | Pass | `abandoned: []` |
| `composer validate --strict` | Pass | `composer.json is valid` |
| `composer update --dry-run` | Pass | Nothing to modify in the lockfile under current constraints. |

No known Composer security advisories or abandoned package flags were reported at audit time.

## Outdated Direct Packages

`composer outdated --direct` reports only major-version update opportunities for stable direct packages.

| Package | Current | Latest reported | Risk | Recommendation |
| --- | ---: | ---: | --- | --- |
| `intervention/image` | `2.7.2` | `4.1.5` | High | Do not upgrade in a blind cleanup. The app uses `Image::make()` in controllers/models and registers the legacy service provider/facade. Add image upload/resize characterization tests before planning a v4 migration. |
| `stripe/stripe-php` | `10.21.0` | `20.3.0` | High | Payment-critical major upgrade. Add Stripe checkout/status regression tests and review API breaking changes before upgrading. |
| `phpunit/phpunit` | `12.5.30` | `13.2.2` | Medium | Dev-only major upgrade. Current Laravel 13 supports PHPUnit 12 and 13, but upgrading pulls a full PHPUnit/Sebastian major tree. Defer until test tooling migration is planned. |

No direct patch-only or minor-only updates were available under current constraints.

Composer also reports several major dev-branch signals when forced with `--major-only`, including `fakerphp/faker` `2.0.x-dev`, `mockery/mockery` `2.0.x-dev`, `league/flysystem-aws-s3-v3` `4.x-dev`, and Guzzle `8.0.x-dev`. These are not safe cleanup updates and should not be applied without explicit major-upgrade planning.

## Laravel Compatibility Findings

| Package | Current | Compatibility status | Notes |
| --- | ---: | --- | --- |
| `laravel/framework` | `13.18.0` | Compatible/current | Locked to Laravel 13 and current as of this audit. |
| `laravel/sanctum` | `4.3.2` | Compatible | Installed direct package for API/auth token support. |
| `yajra/laravel-datatables-oracle` | `13.1.3` | Compatible | Requires Illuminate `^13` and PHP `^8.3`. |
| `php-flasher/flasher-laravel` | `2.6.1` | Compatible | Requires Illuminate `^11.0|^12.0|^13.0`. |
| `anandsiddharth/laravel-paytm-wallet` | `2.0.0` | Composer-compatible but old | Released in 2020 and only constrains Illuminate `>=8.0`. Keep until Paytm payment tests exist. |
| `jorenvanhocht/laravel-share` | `4.2.0` | Composer-compatible but old | Used in blog/share UI. No safe replacement without UI regression coverage. |
| `intervention/image` | `2.7.2` | Works in current tests, but legacy | Latest major is v4. Upgrade requires planned migration. |

No hard Laravel 13 Composer conflicts were found.

## Potentially Unnecessary Packages

These were not removed because they touch payment flows, local developer workflow, or user-facing behavior.

| Package | Evidence | Risk | Safe first step |
| --- | --- | --- | --- |
| `flutterwavedev/flutterwave-v3` | No `Flutterwave\` namespace usage found in app code. Current Flutterwave flow appears to use hosted checkout JavaScript and `App\Models\payment_gateway\Flutterwave`. | High | Add Flutterwave payment-create/status tests. If still unused, remove in a separate payment-focused commit. |
| `laravel/sail` | No `docker-compose*` file or `vendor/bin/sail` usage found outside Composer files. | Low/Medium | Confirm team/local deployment workflow. If unused, remove in a dev-dependency cleanup commit. |
| `laravel/tinker` | Runtime dependency in `require`, but generally a developer console tool. | Low/Medium | Decide whether production installs need Tinker. If not, consider moving to `require-dev` in a separate deployment-reviewed commit. |

## Duplicated or Redundant Requirements

| Package | Status | Notes |
| --- | --- | --- |
| `guzzlehttp/guzzle` | Cleaned up | Removed as a direct root requirement. It is still installed transitively, so Laravel HTTP client behavior is preserved. |
| `fakerphp/faker` | Kept | Direct dev dependency used by Laravel test/factory conventions. |
| `mockery/mockery` | Kept | Direct dev dependency expected by Laravel/PHPUnit tests. |
| `league/flysystem-aws-s3-v3` | Kept | Required for configured S3 filesystem support. |

## High-Risk Upgrade Queue

Recommended order:

1. Add focused payment regression tests for Stripe, Paytm, Razorpay, Paystack, PayPal, and Flutterwave.
2. Decide whether the unused-looking Flutterwave PHP SDK is truly needed.
3. Add image upload, orientation, resize, and storage regression tests.
4. Plan `intervention/image` v4 migration separately.
5. Plan `stripe/stripe-php` major migration separately.
6. Evaluate dev-tool cleanup for `laravel/sail` and `laravel/tinker`.
7. Consider PHPUnit 13 only after current test suite and CI expectations are stable.
8. Plan a Laravel Mix to Vite or supported Webpack pipeline migration to remove the dev-only npm audit chain.

## Follow-Up Checks to Add to CI

```bash
composer validate --strict --no-interaction
composer audit --no-interaction
composer outdated --direct --strict --no-interaction
npm audit --omit=dev --audit-level=moderate
npm run production
```

For release gates, keep `composer audit` and `npm audit --omit=dev` mandatory. Treat full `npm audit` and `composer outdated --direct --strict` as dependency-health checks until the Laravel Mix dev-tool chain is replaced.

## References

- Composer CLI command reference: <https://getcomposer.org/doc/03-cli.md>
- Packagist security advisories: <https://packagist.org/security-advisories/>
- Packagist `composer audit` overview: <https://blog.packagist.com/discover-security-advisories-with-composers-audit-command/>
