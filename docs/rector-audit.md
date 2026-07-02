# Rector Audit

Date: 2026-07-01

## Detected Versions

| Tool | Version / Constraint | Notes |
| --- | --- | --- |
| PHP runtime | 8.5.7 locally | Rector is configured for PHP 8.3 because `composer.json` allows `^8.3`. |
| Laravel | 13.18.0 | Detected from `composer show laravel/framework`. |
| Rector | 2.5.2 | Installed as a dev dependency. |

## Enabled Conservative Rules

| Rule | Why It Is Allowed |
| --- | --- |
| `ExplicitPublicClassMethodRector` | Adds explicit `public` only where PHP already defaults class methods to public. |
| `RemoveUnusedVariableInCatchRector` | Removes unused catch variable names without changing caught exception types or control flow. |
| `StrContainsRector` | Converts strict `strpos(...) !== false` checks to `str_contains(...)` for PHP 8.0+ syntax. |

## Applied Changes

Rector was first run with `composer rector:dry`. The approved dry-run changed six files:

- `app/Actions/Install/PrepareDatabaseConnection.php`
- `app/Helpers/ApiHelper.php`
- `app/Helpers/CommonHelper.php`
- `app/Http/Controllers/MainController.php`
- `app/Providers/RouteServiceProvider.php`
- `app/Providers/ViewServiceProvider.php`

## Skipped Risky Rules

| Rule / Set | Risk | Safe First Step |
| --- | --- | --- |
| `RectorLaravel` package and Laravel upgrade sets | Laravel version upgrade sets can rewrite framework conventions broadly and may change route, validation, container, or helper behavior in a legacy app. | Add focused regression tests for each module, then introduce Laravel-specific sets one version/scope at a time. |
| Rector prepared `deadCode` set | Can remove methods, properties, annotations, or branches that are reached dynamically through Blade, routes, string callbacks, or package hooks. | Use route/method inventory and feature coverage before enabling individual dead-code rules. |
| Rector prepared `codeQuality` set | Contains many semantics-preserving rules in isolation, but the combined set can rewrite conditionals and legacy loose comparisons too broadly. | Trial individual rules with dry-runs and inspect each diff. |
| Type declaration and typed property rules | Can break legacy null/string/int coercion, dynamic attributes, Eloquent hydration, and tests relying on loose input. | Add types manually in small slices with tests. |
| Constructor promotion and readonly rules | Can change inheritance, serialization, framework injection expectations, or mock setup behavior. | Apply only to isolated DTO/action classes with tests. |
| `AddOverrideAttributeToOverriddenMethodsRector` | Requires PHP 8.3 and is usually safe, but can turn future vendor signature drift into fatal errors. | Enable only after reviewing framework override methods and CI PHP version coverage. |
| `ChangeSwitchToMatchRector` and conditional simplification rules | May alter loose comparison behavior and fallthrough semantics in legacy controllers. | Add branch coverage before use. |

## Explicit Dry-Run Rejection

`StrContainsRector` was skipped for `app/Http/Controllers/LanguageController.php`. Rector proposed changing `strpos($row->phrase, '____') == false` to `! str_contains(...)`. That is not a behavior-preserving rewrite because the legacy loose comparison treats a match at position `0` as `false`. This should be fixed only as a tested bug fix, not as an automated modernization.

## Static Analysis Scope

Installing Rector also installs PHPStan as a dependency. The existing `composer analyse` script is now configured to run PHPStan with a `1G` memory limit and a focused level-0 scope covering the Rector-touched paths that do not currently contain known pre-existing PHPStan blockers:

- `app/Actions/Install`
- `app/Helpers`
- `app/Providers/RouteServiceProvider.php`
- `app/Providers/ViewServiceProvider.php`
- `phpstan-bootstrap.php`
- `rector.php`

The full application was also tested with PHPStan during this pass. After adding a PHPStan bootstrap for configured Laravel facade aliases, full-project analysis still reports 45 legacy findings. They were not suppressed or baselined in this task.

Known full-project PHPStan blockers:

- Missing legacy model classes referenced by controllers and view models: `Job`, `JobApply`, `JobCategory`, `JobWishlist`, `PaidContentPackages`, `PaidContentPayout`, and `FundraiserPayout`.
- Pre-existing controller issues: `PaymentController` references `Paytm` in the controller namespace and has a missing return path in `paytm_paymentCallback()`.
- The obsolete controller helper has been removed.
- `PageController` accesses an undefined `$user` property.
- `Authenticate::redirectTo()` has a missing explicit `null` return.
- `StripePay` catches `Exception` without importing the global exception class.
- `routes/console.php` uses `$this` inside the console command closure; PHPStan cannot infer the bound command context without framework-aware extensions.

Safe first step: add module-level tests around the referenced flows, then fix these findings in small commits before expanding PHPStan paths or adding Larastan.
