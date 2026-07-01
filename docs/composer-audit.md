# Composer Dependency Audit

Generated: 2026-07-01

This audit covers `composer.json` and `composer.lock` for outdated, abandoned, vulnerable, duplicated, unnecessary, and Laravel-incompatible Composer packages. It intentionally avoids major dependency upgrades and applies only low-risk cleanup.

## Audit Sources

- Local PHP runtime: PHP `8.5.7`
- Composer: `2.9.5`
- Laravel framework: `13.18.0`
- Composer advisory source: Packagist security advisories via `composer audit`
- Composer package metadata from the current lockfile and Packagist metadata

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

## Follow-Up Checks to Add to CI

```bash
composer validate --strict --no-interaction
composer audit --no-interaction
composer outdated --direct --strict --no-interaction
```

For release gates, keep `composer audit` mandatory. Treat `composer outdated --direct --strict` as an informational dependency-health check unless the team wants outdated packages to fail CI.

## References

- Composer CLI command reference: <https://getcomposer.org/doc/03-cli.md>
- Packagist security advisories: <https://packagist.org/security-advisories/>
- Packagist `composer audit` overview: <https://blog.packagist.com/discover-security-advisories-with-composers-audit-command/>
