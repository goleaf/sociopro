# Refactor Audit

Generated: 2026-07-01

This is a report-only audit of the current checkout. No application code was changed for this document. The worktree already contained unrelated modified and untracked files before this report was added, so implementation work should start by stabilizing that state.

## Verification

| Check | Result | Notes |
| --- | --- | --- |
| `php artisan --version` | Laravel 9.52.21 | Project is behind current Laravel conventions and security baseline. |
| `php artisan test` | Failed | 58 passed, 2 failed. Failures are in `tests/Feature/StoryControllerRefactorTest.php`: current code still uses `DB::table` in `StoryController`, and `App\Queries\StoriesQuery` is missing. PHP 8.5 deprecation warnings also appear in older dependencies. |
| `vendor/bin/pint --test` | Failed | 193 files checked; style issues remain in `app/Http/Controllers/ApiController.php` and `app/Http/Controllers/MainController.php`. |
| `composer validate --strict` | Passed | `composer.json` is valid. |
| `composer audit` | Failed | 4 advisories affect `laravel/framework`; abandoned packages include `fruitcake/laravel-cors` and `paypal/rest-api-sdk-php`. |
| `npm audit --audit-level=moderate` | Failed | 11 vulnerabilities through the Laravel Mix/Webpack dependency chain. |

## Prioritized Tasks

| Order | Risk | Task | Main targets | Suggested first slice |
| --- | --- | --- | --- | --- |
| 1 | Critical | Establish a clean safety baseline before refactors. | Current dirty worktree, untracked tests, CI/test tooling | Decide the fate of existing staged/untracked files, make the test suite expectation explicit, and add a small CI gate for `php artisan test`, Pint, Composer audit, and npm audit. |
| 2 | Critical | Patch framework and dependency security exposure. | `composer.json`, `composer.lock`, `package.json`, `webpack.mix.js` | Plan Laravel 9 -> supported Laravel upgrade, replace abandoned PayPal/CORS packages, and migrate the frontend build from Mix/Webpack to a supported path such as Vite. |
| 3 | Critical | Remove executable updater and database-restore paths. | `app/Http/Controllers/Updater.php`, `app/Http/Controllers/Auth/AuthenticatedSessionController.php` | Disable `eval()` and `DB::unprepared()` flows, remove demo restore behavior from login/session code, and replace with controlled migrations/seeders or signed deployment jobs. |
| 4 | Critical | Move API authentication and authorization into middleware, policies, and resources. | `routes/api.php`, `app/Http/Controllers/ApiController.php` | Group protected API routes under `auth:sanctum`, remove repeated manual bearer-token checks, add policies, and cover one high-risk endpoint with a feature test before expanding. |
| 5 | High | Convert state-changing GET routes to CSRF-protected methods. | `routes/web.php`, `routes/custom_routes.php`, delete/status/follow/notification routes | Start with admin/user/content delete and status routes. Use `POST`, `PUT`, `PATCH`, or `DELETE`, route model binding, named route groups, and policy checks. |
| 6 | High | Remove raw query and DB facade usage from controllers, helpers, and views. | `ApiController`, `StoryController`, `MainController`, `PaymentHistory`, `CommonHelper`, `ApiHelper`, Blade views | Extract Eloquent scopes/actions/query objects. Start with `StoryController` because failing tests already describe the intended direction. |
| 7 | High | Eliminate queries and business logic from Blade. | `resources/views/backend/user/dashboard.blade.php`, `frontend/album_details`, `frontend/blogs/single_blog.blade.php`, `frontend/main_content/create_post_modal.blade.php` | Move counts and relationship data into controllers/ViewModels using `with()`, `withCount()`, and paginated data; add view tests for empty states. |
| 8 | High | Stop exposing secrets and privileged gateway values to the browser. | `resources/views/frontend/main_content/create_post_modal.blade.php`, payment settings views, `config/paypal.php` | Replace client-side Hugging Face calls with a backend action/job and move all runtime secrets to `.env` + `config()`. Verify admin-only visibility for payment credentials. |
| 9 | High | Decompose oversized controllers into requests, actions, services, and resources. | `app/Http/Controllers/ApiController.php`, `AdminCrudController.php`, `MainController.php`, `Profile.php` | Split one domain at a time: validation into Form Requests, writes into Actions, JSON output into API Resources, and shared rules into model scopes. |
| 10 | Medium | Add authorization layer and model hardening. | Missing `app/Policies`, models without `$fillable`/`$casts`, route actions | Create policies for user/content/payment/admin domains, add `$fillable` and `$casts` to models, and replace inline role checks with policy assertions. |
| 11 | Medium | Improve query performance and payload control. | Hot controllers, dashboard views, comments/blog/page/album flows | Add eager loads, `withCount()`, pagination, explicit selects, and indexes for frequent filters. Add query-count tests for the highest-traffic pages. |
| 12 | Medium | Replace render-time translation writes with explicit cached translation management. | `app/Helpers/CommonHelper.php::get_phrase()` | Stop inserting translation rows during view rendering; preload/cache phrases and make missing-key handling deterministic. |
| 13 | Medium | Rebuild schema/change management around migrations and factories. | `database/migrations`, install SQL dumps, `database/factories` | Add factories for core models, create migration-backed schema changes, and retire production mutation paths based on SQL dumps. |
| 14 | Low | Modernize static analysis and style enforcement. | Pint config, Larastan/PHPStan or Psalm, Rector, GitHub Actions | Add incremental Larastan/PHPStan at a low baseline, then tighten after the critical security and controller refactors land. |

## Files To Refactor First

- `app/Http/Controllers/Updater.php`: executes uploaded PHP and unprepared SQL; this is the most dangerous code path found.
- `app/Http/Controllers/Auth/AuthenticatedSessionController.php`: login/session code contains demo database restore behavior and broad table mutation.
- `routes/api.php`: many protected-looking endpoints are public route definitions with repeated manual token checks in controller methods.
- `app/Http/Controllers/ApiController.php`: about 7,900 lines and 130+ methods, mixing auth, validation, query building, serialization, and business rules.
- `routes/web.php` and `routes/custom_routes.php`: many GET routes mutate state and should be method-correct, grouped, named, and policy-protected.
- `resources/views/frontend/main_content/create_post_modal.blade.php`: queries settings in Blade and exposes an API token to browser JavaScript.
- `resources/views/backend/user/dashboard.blade.php`: performs repeated aggregate/database work directly in Blade.
- `app/Helpers/CommonHelper.php` and `app/Helpers/ApiHelper.php`: contain shared global query/business logic that should move to scoped services, actions, or models.
- `app/Http/Middleware/UserMiddleware.php`: dereferences `auth()->user()` without a null-safe guard and has ambiguous authorization logic.
- Core Eloquent models: many models lack `$fillable`, `$casts`, factories, and focused scopes, making controller cleanup harder and mass-assignment behavior unclear.

## Implementation Order

1. Stabilize the repository state and decide whether the existing failing/untracked tests are intended acceptance tests.
2. Remove or disable the critical updater/demo restore paths before broad refactors.
3. Patch Composer and npm security exposure, or at minimum document temporary constraints while the Laravel upgrade is planned.
4. Secure API and web route boundaries with middleware, CSRF-correct HTTP verbs, route model binding, and policies.
5. Move database access out of Blade/helpers/controllers into Eloquent scopes, query objects, actions, and ViewModels.
6. Break up the largest controllers domain by domain, adding characterization tests before each slice.
7. Add factories, model casts/fillable definitions, static analysis, and query-count regression tests.

## Caveats

- The test suite currently fails because of existing/untracked refactor tests, not because this report changed application behavior.
- PHP 8.5 surfaces deprecation warnings in older tooling and framework dependencies; verify the supported PHP target before upgrading.
- The audit used static inspection and local command output. Query-count and index verification should be repeated with real production-like data after the first refactor slice.
