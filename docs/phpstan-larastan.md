# Larastan / PHPStan Operating Guide

Created: 2026-07-01

## Current Configuration

- Larastan is installed through `larastan/larastan` and loaded from `phpstan.neon`.
- PHPStan starts at `level: 0` because this legacy Laravel codebase still contains unresolved add-on models, dynamic framework patterns, and query refactors that need regression coverage.
- Analysis now covers `app`, `config`, `database`, `routes`, `tests`, `phpstan-bootstrap.php`, and `rector.php`.
- `phpstan-bootstrap.php` remains loaded so legacy facades and helper aliases can be resolved consistently.
- `phpstan-baseline.neon` exists only to make Larastan usable immediately while the remaining debt is reduced in safe slices.

## Commands

Run the static-analysis gate:

```bash
composer analyse
```

Regenerate the baseline only after intentionally fixing or moving legacy code:

```bash
vendor/bin/phpstan analyse --memory-limit=1G --generate-baseline
```

Run the full local quality gate:

```bash
composer ci
```

## Clear Issues Fixed During Setup

- Added the missing `Http` facade import in `MainController`.
- Added the missing Paytm payment model import in `PaymentController`.
- Added explicit null returns where nullable methods previously fell through.
- Fixed the Stripe payment model to catch the global `Exception`.
- Added explicit Eloquent `BelongsTo` return types for existing relationships used by eager loading.
- Initialized the authenticated user in `PageController` before its video-loading action reads `$this->user`.

## Baseline Debt

The baseline currently covers 45 legacy findings:

- Missing or retired add-on model classes: `Job`, `JobApply`, `JobCategory`, `JobWishlist`, `PaidContentPackages`, `PaidContentPayout`, `Fundraiser_category`, and `Fundraiser_payout`.
- Legacy collection calls that Larastan recommends pushing back into database queries.
- A `jorenvanhocht/laravel-share` facade/static typing mismatch that needs package-level behavior verification before changing UI output.

Do not add new baseline entries casually. New code should pass `composer analyse` without increasing `phpstan-baseline.neon`.

## Strictness Roadmap

1. Keep `level: 0` until all baseline entries are either fixed or confirmed as removed legacy features.
2. Fix missing model classes or remove dead controller/API paths with route and feature-test coverage.
3. Refactor Larastan collection-call findings into query-level `count()`, `first()`, or aggregate calls, backed by regression tests for the affected pages.
4. Verify and fix the social-share facade usage with package documentation and page-rendering tests.
5. Regenerate the baseline after each focused cleanup and confirm the ignored error count only decreases.
6. When the baseline reaches zero at level 0, raise PHPStan to level 1 and repeat the same shrink-only process.
7. Continue one level at a time. Do not jump levels on this legacy project without a dedicated branch and full regression pass.
