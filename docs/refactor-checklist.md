# Refactor Checklist

Generated: 2026-07-01

This checklist turns the audit in `docs/refactor-audit.md` into implementation tasks. It is intentionally report-only: do not refactor code while using this document unless a task is selected, tested, and committed as its own slice.

Risk labels describe implementation risk:

- **Small risk**: localized change, low behavior impact, usually 1-2 files.
- **Medium risk**: one workflow or domain, needs focused regression tests.
- **High risk**: security, authentication, routing, data mutation, dependencies, or broad behavior changes.

## Controllers

- [ ] **High risk**: Split `app/Http/Controllers/ApiController.php` by domain instead of keeping auth, validation, queries, serialization, payments, media, posts, and profile workflows in one controller.
- [ ] **High risk**: Remove repeated manual bearer-token parsing from API controller methods and rely on route middleware plus Laravel guards.
- [ ] **High risk**: Disable or replace executable update flows in `app/Http/Controllers/Updater.php`, including uploaded PHP execution and unprepared SQL import.
- [ ] **High risk**: Remove demo database restore/table mutation logic from `app/Http/Controllers/Auth/AuthenticatedSessionController.php`.
- [ ] **High risk**: Break `AdminCrudController.php` into resource-specific controllers/actions so authorization, validation, and side effects are reviewable.
- [ ] **Medium risk**: Refactor `MainController.php` timeline/feed/profile actions into smaller controller methods backed by actions or query classes.
- [ ] **Medium risk**: Refactor `StoryController.php` to Eloquent/query objects and remove `DB::table` usage; existing failing tests already describe part of the target.
- [ ] **Medium risk**: Move upload handling out of controllers and into validated actions/services that enforce file type, size, storage disk, and ownership.
- [ ] **Medium risk**: Move payment verification code out of controllers and into gateway-specific services using `Http::fake()`-friendly clients.
- [ ] **Small risk**: Standardize controller redirects to named routes and remove hard-coded URL fragments where safe.
- [ ] **Small risk**: Add controller-level docblocks only where method intent is not obvious after extraction; avoid comments that repeat code.

## Routes

- [ ] **High risk**: Convert state-changing GET routes to `POST`, `PUT`, `PATCH`, or `DELETE` with CSRF protection and method spoofing in forms.
- [ ] **High risk**: Group API routes by public/authenticated/admin scopes and apply `auth:sanctum` to protected endpoints.
- [ ] **High risk**: Add policy checks to routes/actions that mutate users, posts, comments, stories, payments, settings, pages, groups, and notifications.
- [ ] **Medium risk**: Split `routes/custom_routes.php` into domain route files or well-named route groups loaded from `RouteServiceProvider`.
- [ ] **Medium risk**: Add consistent middleware, prefix, and name prefixes to all web/admin/customer route groups.
- [ ] **Medium risk**: Replace manual `find()` style controller loading with route model binding where IDs are passed in URLs.
- [ ] **Medium risk**: Add signed routes only where temporary public links are needed, then verify framework advisory patch level first.
- [ ] **Small risk**: Make all redirects and Blade links use named routes instead of string paths.
- [ ] **Small risk**: Add a route inventory document before changing large route groups so behavior changes are deliberate.

## Requests

- [ ] **High risk**: Create Form Requests for all authentication, account, payment, upload, profile, admin setting, and content mutation endpoints.
- [ ] **High risk**: Add strict upload validation rules for stories, posts, videos, images, and profile media before moving storage logic.
- [ ] **Medium risk**: Replace controller-level `$request->validate()` and `Validator::make()` blocks with dedicated request classes.
- [ ] **Medium risk**: Add authorization methods to Form Requests where the permission decision belongs with the incoming command.
- [ ] **Medium risk**: Normalize API validation errors through JSON responses/resources instead of ad hoc response shapes.
- [ ] **Small risk**: Give request classes explicit rule names and messages where current UI copy depends on specific validation feedback.
- [ ] **Small risk**: Add tests that request classes reject missing IDs, invalid enum-like statuses, oversized files, and unexpected values.

## Models

- [ ] **High risk**: Add `$fillable` to every Eloquent model that accepts mass assignment; avoid `$guarded = []`.
- [ ] **High risk**: Add `$casts` for booleans, dates, JSON, money-like numeric values, and status fields.
- [ ] **High risk**: Move repeated `where()` conditions from controllers/helpers into focused local scopes.
- [ ] **Medium risk**: Define relationships for posts, stories, comments, media, pages, groups, payments, notifications, friends, and settings so controllers can eager load instead of joining manually.
- [ ] **Medium risk**: Add `withCount()`, `withExists()`, and relationship aggregate scopes for dashboard and profile counters.
- [ ] **Medium risk**: Add model observers for cache invalidation, audit logging, and cross-cutting notifications after core write paths are isolated.
- [ ] **Medium risk**: Replace string status fields with PHP enums where database values are stable and covered by tests.
- [ ] **Small risk**: Add `$hidden`, `$visible`, or API Resources to prevent accidental sensitive model serialization.
- [ ] **Small risk**: Add missing factories for core models before expanding feature tests.

## Services

- [ ] **High risk**: Extract update/install/demo-restore behavior into safe deployment-only services or remove it entirely.
- [ ] **High risk**: Move payment gateway verification into dedicated services with no secrets rendered to Blade or JavaScript.
- [ ] **High risk**: Replace global helper business logic with scoped services/actions for translation, settings, notifications, friendship, media, and profile workflows.
- [ ] **Medium risk**: Add action classes for creating posts, creating stories, deleting media, accepting friend requests, joining groups, and changing admin statuses.
- [ ] **Medium risk**: Build query classes for timeline, stories, profile feeds, admin tables, search, and dashboards.
- [ ] **Medium risk**: Introduce a settings service that reads through config/model cache and invalidates consistently on admin updates.
- [ ] **Small risk**: Keep service constructors dependency-injected and framework-native; avoid static service locators except existing Laravel facades where appropriate.
- [ ] **Small risk**: Document action input/output contracts before replacing controller code so tests can target behavior.

## Database

- [ ] **High risk**: Remove production reliance on SQL dump imports and `DB::unprepared()`; represent schema changes with migrations and seeders.
- [ ] **High risk**: Audit all raw query usage and replace with Eloquent scopes, relationships, or model-owned query methods.
- [ ] **High risk**: Add or verify indexes for columns used in authentication, timeline, story visibility, admin filters, notifications, payments, and foreign-key lookups.
- [ ] **Medium risk**: Add foreign-key constraints where current schema relationships are implicit and cleanup behavior is understood.
- [ ] **Medium risk**: Normalize timestamp handling where integer timestamps and Laravel datetime casts are mixed.
- [ ] **Medium risk**: Add migration coverage for any schema changes instead of editing existing production migrations.
- [ ] **Medium risk**: Add seeders/factories that can rebuild a testable local database without relying on install dump side effects.
- [ ] **Small risk**: Add schema documentation for high-traffic tables and relationship assumptions.
- [ ] **Small risk**: Add query-count regression checks for pages currently doing repeated counts in Blade.

## Security

- [ ] **High risk**: Remove `eval()` and unprepared SQL execution paths before any broad refactor.
- [ ] **High risk**: Stop exposing payment or other provider secrets in Blade, JavaScript, logs, or versioned config files.
- [ ] **High risk**: Add policies for user, post, comment, story, media, group, page, notification, payment, and admin setting actions.
- [ ] **High risk**: Enforce authenticated API access through middleware rather than manually checking bearer tokens in each method.
- [ ] **High risk**: Convert destructive GET endpoints to CSRF-protected write methods.
- [ ] **High risk**: Patch Laravel framework advisories and replace abandoned Composer packages.
- [ ] **Medium risk**: Add rate limiting for login, registration, password reset, upload, comment, post, story, and API endpoints.
- [ ] **Medium risk**: Harden file uploads with MIME validation, extension checks, storage isolation, virus-scanning hook points, and non-public temporary paths.
- [ ] **Medium risk**: Review all admin/settings screens for sensitive value display and update authorization.
- [ ] **Small risk**: Add security-focused tests for guest, disabled, unverified, normal user, owner, and admin access boundaries.
- [ ] **Small risk**: Add a dependency-audit step to CI and document temporary exceptions.

## Testing

- [ ] **High risk**: Decide whether existing untracked/failing refactor tests are accepted work, then make the baseline explicit.
- [ ] **High risk**: Add characterization tests before changing `ApiController`, `AdminCrudController`, `MainController`, `StoryController`, update/install flows, or route methods.
- [ ] **High risk**: Add feature tests for every converted destructive route to prove guests are rejected, unauthorized users are forbidden, and authorized users can complete the action.
- [ ] **Medium risk**: Add policy tests for the core content, payment, settings, and admin domains.
- [ ] **Medium risk**: Add request validation tests for uploads, payment callbacks, profile updates, story creation, post creation, and admin settings.
- [ ] **Medium risk**: Add query-count tests for dashboard, timeline, profile, blog, album, comments, and notification views.
- [ ] **Medium risk**: Add HTTP client fakes for external payment and remaining provider calls.
- [ ] **Small risk**: Expand factories so tests do not rely on manual inserts or SQL dump fixtures.
- [ ] **Small risk**: Add smoke tests for route registration, route names, config cache, and view rendering.
- [ ] **Small risk**: Keep documentation-only commits separate from failing behavioral changes until the baseline is repaired.

## Queues

- [ ] **High risk**: Move long-running email, media processing, webhook, notification fanout, and external API calls out of HTTP requests.
- [ ] **Medium risk**: Select and document the queue driver per environment; use database queue if low-ops deployment is required.
- [ ] **Medium risk**: Add retry, timeout, backoff, and failure behavior for queued work.
- [ ] **Medium risk**: Add idempotency keys or duplicate protection for queued payment/webhook side effects.
- [ ] **Medium risk**: Add queue worker deployment instructions and monitoring expectations.
- [ ] **Small risk**: Add tests with `Queue::fake()` for each flow moved to the queue.
- [ ] **Small risk**: Add failed-job review and cleanup procedures to deployment docs.

## Jobs

- [ ] **High risk**: Create jobs for remaining provider-backed long-running work instead of calling external APIs from browser JavaScript.
- [ ] **High risk**: Create payment confirmation/webhook jobs that are idempotent and authorization-aware.
- [ ] **Medium risk**: Create media-processing jobs for image/video resizing, thumbnailing, and cleanup after deletes.
- [ ] **Medium risk**: Create notification fanout jobs for comments, friend requests, group activity, and admin alerts.
- [ ] **Medium risk**: Create cache warmup/refresh jobs for dashboard statistics and expensive aggregates.
- [ ] **Small risk**: Add job-specific tests for retries, failed states, and no-op behavior when records are missing.
- [ ] **Small risk**: Use unique jobs where duplicate work would cause double notifications, double charges, or duplicate media records.

## Frontend

- [ ] **High risk**: Remove database queries and business logic from Blade templates.
- [ ] **High risk**: Remove secrets from JavaScript and replace provider calls with backend endpoints/jobs.
- [ ] **High risk**: Update forms for destructive actions to use CSRF-protected methods matching route changes.
- [ ] **Medium risk**: Replace repeated Blade fragments with anonymous or class-based components for buttons, cards, modals, media rows, alerts, and empty states.
- [ ] **Medium risk**: Ensure controllers pass preloaded view data, DTOs, or ViewModels instead of letting views pull models directly.
- [ ] **Medium risk**: Audit frontend dependency vulnerabilities and legacy public assets now that first-party assets use Vite.
- [ ] **Medium risk**: Add explicit empty states with `@forelse` where lists can be empty.
- [ ] **Small risk**: Use named routes for all internal links and form actions.
- [ ] **Small risk**: Add view tests for high-risk pages after moving query logic out of Blade.
- [ ] **Small risk**: Deduplicate scripts/styles with Blade stacks or components instead of repeated includes.

## Configuration

- [ ] **High risk**: Move all runtime secrets to `.env` and `config/*`; remove any hard-coded or rendered provider credentials.
- [ ] **High risk**: Update `composer.json` constraints to a supported Laravel/PHP combination after dependency risk is understood.
- [ ] **Medium risk**: Replace abandoned packages such as old CORS and PayPal SDK dependencies.
- [ ] **Medium risk**: Add `.env.example` entries for every required payment, mail, queue, cache, filesystem, and provider setting.
- [ ] **Medium risk**: Verify `config:cache`, `route:cache`, and `view:cache` work after route/config cleanup.
- [ ] **Medium risk**: Standardize filesystem disks for public media, private uploads, temporary files, and generated assets.
- [ ] **Small risk**: Document local development PHP, Node, Composer, npm, database, queue, and cache versions.
- [ ] **Small risk**: Add config tests for required keys and invalid production defaults such as debug mode.

## Deployment

- [ ] **High risk**: Add CI gates for tests, Pint, Composer audit, npm audit, route cache, config cache, and build output.
- [ ] **High risk**: Define a safe deployment process for migrations, backups, rollbacks, queue restarts, and cache clears.
- [ ] **High risk**: Remove web-accessible update/install/restore paths from production deployment.
- [ ] **Medium risk**: Add release checklist steps for dependency updates, security advisories, database backups, and queue worker health.
- [ ] **Medium risk**: Add scheduler and queue worker process definitions for the target host.
- [ ] **Medium risk**: Add logging/monitoring expectations for failed jobs, payment failures, authentication errors, slow queries, and 500 responses.
- [ ] **Medium risk**: Add asset build verification and cache-busting checks after frontend tooling changes.
- [ ] **Small risk**: Document required writable paths and permissions.
- [ ] **Small risk**: Document smoke checks after deploy: homepage, login, registration, post/story creation, payment callback, admin dashboard, queue worker, and scheduled tasks.

## Suggested Implementation Order

1. Stabilize current git/test baseline and decide which existing staged or untracked changes belong to active work.
2. Remove the highest-risk executable update/demo restore paths.
3. Patch dependency and secret exposure issues.
4. Secure route boundaries: API middleware, CSRF-correct methods, named groups, and policies.
5. Add characterization tests around the largest controllers and most dangerous routes.
6. Move raw queries and Blade queries into Eloquent scopes, query classes, actions, and ViewModels.
7. Split controllers by domain, one workflow at a time.
8. Add Form Requests, model casts/fillable definitions, factories, and policy tests.
9. Move long-running work into jobs/queues and add deployment support.
10. Modernize frontend build/config/deployment once behavior and security risks are contained.

## Per-Slice Completion Gate

- [ ] The selected task is small enough for one focused commit.
- [ ] Tests or characterization coverage exist before changing behavior.
- [ ] No unrelated files are staged.
- [ ] No raw SQL, Blade queries, or new business logic in views/resources are introduced.
- [ ] `php artisan test` result is recorded.
- [ ] Pint/static/audit commands relevant to the slice are recorded.
- [ ] Rollback risk and deployment notes are documented for medium/high-risk tasks.
