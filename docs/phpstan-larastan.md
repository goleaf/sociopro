# Larastan / PHPStan Operating Guide

Created: 2026-07-01

## Current Configuration

- Larastan is installed through `larastan/larastan` and loaded from `phpstan.neon`.
- PHPStan runs at `level: 1`.
- Analysis now covers `app`, `config`, `database`, `routes`, `tests`, `phpstan-bootstrap.php`, and `rector.php`.
- `phpstan-bootstrap.php` remains loaded so legacy facades and helper aliases can be resolved consistently.
- There is no active PHPStan baseline. New static-analysis issues should be fixed instead of ignored.

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

## Level 1 Cleanup Completed

- Added explicit legacy Eloquent models for referenced add-on tables: `Job`, `JobApply`, `JobCategory`, `JobWishlist`, `PaidContentPackages`, `PaidContentPayout`, `Fundraiser_category`, and `Fundraiser_payout`.
- Replaced legacy collection `get()->count()` and `get()->first()` patterns that Larastan identified with query-level `exists()`, `count()`, and `first()` calls.
- Replaced the `jorenvanhocht/laravel-share` facade alias in `BlogController` with the bound share service so PHPStan uses the package's real method signature.
- Initialized nullable upload filenames before optional upload branches and only assigned image/file fields when an upload produced a filename.
- Added safe defaults for response arrays that were previously initialized only inside authenticated branches.
- Added the missing AI-image persistence helper for `MainController::generateImage()`.

## Level 2 Blockers

Level 2 was probed on 2026-07-01 and is not safe to enable yet. It reports more than 1000 findings, mostly dynamic Eloquent property access on legacy models and `Authenticatable` values. Before raising to level 2, add model PHPDoc/property annotations or typed accessors in focused batches and fix incorrect `find()`/collection assumptions that level 2 exposes.

Do not reintroduce `phpstan-baseline.neon` casually. If a baseline is ever needed again, document why, keep it temporary, and shrink it before raising strictness.

## Strictness Roadmap

1. Keep `level: 1` passing without a baseline.
2. Add useful model PHPDoc for high-traffic models first: `User`, `Posts`, `Page`, `Group`, `Notification`, `Comments`, `Media_files`, and payment/add-on models.
3. Replace `find()` calls that may return `null` with `findOrFail()`, explicit null handling, or guarded responses.
4. Convert mixed `auth()->user()` / `auth('sanctum')->user()` usage to typed user retrieval helpers where practical.
5. Re-run `vendor/bin/phpstan analyse --level=2 --memory-limit=1G` after each focused batch.
6. Raise `phpstan.neon` to level 2 only after the temporary level-2 command passes cleanly.
7. Continue one level at a time. Do not jump levels on this legacy project without a dedicated branch and full regression pass.
