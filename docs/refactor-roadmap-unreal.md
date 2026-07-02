# Unreal Refactor Roadmap

Generated: 2026-07-01

This roadmap converts the audit, checklist, coding standards, and enterprise rulebook into an ordered implementation plan for this Laravel project. It is intentionally phased so each slice can be tested, reviewed, committed, deployed, and rolled back independently.

## Current Baseline

- Laravel: `13.18.0`
- PHP requirement: `^8.3`
- PHPUnit: `12.5.30`
- Formatter: Laravel Pint `1.29.3`
- Frontend build: Laravel Mix / Webpack
- Current JavaScript/CSS stack: Alpine, Axios, Tailwind 3, PostCSS, Laravel Mix, Webpack
- Installed quality tools: Larastan/PHPStan, Rector, ESLint, Stylelint, Prettier
- Not currently installed: Vite

Do not treat optional tools as available until repository files prove they exist. Keep future Vite adoption in a dedicated build-tool phase and do not combine it with behavior refactors.

## Roadmap Rules

- Preserve behavior unless a bug or security issue is proven by tests.
- Add characterization tests before risky refactors.
- Keep each implementation slice small enough for one focused commit.
- Run the verification gate listed for each phase before merging.
- Do not change database contracts, public routes, UI behavior, or API response shapes without tests and rollout notes.
- Keep documentation-only changes separate from application logic changes.

## Phase 0: Safety Baseline and Worktree Hygiene

**Goal:** Keep `main` trustworthy before deeper work begins.

**Tasks**

- [ ] Confirm `git status --short --branch` is clean before each slice.
- [ ] Record current stack versions from `composer.lock`, `package-lock.json`, and Artisan output.
- [ ] Treat `docs/refactor-audit.md` as historical because it was written before the Laravel 13 upgrade.
- [ ] Update audit/checklist docs when a phase materially changes the baseline.
- [ ] Add a short release note for each high-risk behavior or deployment change.

**Verification**

- `git status --short --branch`
- `php artisan --version`
- `composer validate --strict`

**Exit Criteria**

- Clean branch state.
- Current baseline documented.
- No mixed staged/unrelated files.

## Phase 1: Safety Tests and Characterization Coverage

**Goal:** Lock down legacy behavior before refactoring dangerous code.

**Tasks**

- [ ] Add characterization tests for `ApiController`, `AdminCrudController`, `MainController`, `Profile`, `StoryController`, installer/update flows, and payment callbacks.
- [ ] Add route smoke tests for web, API, auth, admin, payment, and install routes.
- [ ] Add 404/500 regression tests for known null-model paths.
- [ ] Add feature tests for destructive routes before changing HTTP verbs.
- [ ] Add external integration tests using `Http::fake()` for payment/provider flows.
- [ ] Add view rendering tests for high-traffic Blade pages before removing queries from views.

**Verification**

- `php artisan test`
- Focused test commands for the changed domain.

**Exit Criteria**

- Critical legacy flows have regression coverage.
- New tests fail before the target bug/security fix and pass after it.

## Phase 2: Formatting and PHP Style

**Goal:** Keep PHP diffs predictable and PSR-12/Pint compliant without hiding behavior changes.

**Tasks**

- [ ] Run Pint only on files touched by a behavior slice, unless doing a dedicated formatting-only commit.
- [ ] Avoid broad formatting churn in legacy controllers until characterization tests exist.
- [ ] Remove commented-out code only in files already being safely refactored.
- [ ] Add return types where safe and covered by tests.
- [ ] Keep formatting-only commits separate from refactor/bugfix commits.

**Verification**

- `vendor/bin/pint --test`
- `git diff --check`

**Exit Criteria**

- New and touched PHP code passes Pint.
- Formatting commits are isolated from behavior changes.

## Phase 3: Static Analysis and Automated Modernization

**Goal:** Introduce analysis tools incrementally without blocking urgent security work.

**Tasks**

- [ ] Plan Larastan/PHPStan installation with a low initial level and explicit baseline file.
- [ ] Add PHPStan/Larastan to Composer in a dedicated tooling commit.
- [ ] Run analysis against a small namespace first, such as `app/Actions`, `app/Queries`, or selected payment services.
- [ ] Plan Rector with dry-run-only review for safe PHP modernization rules.
- [ ] Add Rector only after Larastan/PHPStan noise is understood.
- [ ] Document which paths are excluded and why.

**Verification**

- `composer validate --strict`
- `vendor/bin/phpstan analyse` or configured Composer script after installation.
- `vendor/bin/rector --dry-run` only after Rector is installed.

**Exit Criteria**

- Static analysis exists with a realistic baseline.
- Rector rules are reviewed and dry-run clean before any mass changes.

## Phase 4: Controllers and Action/Service Extraction

**Goal:** Decompose oversized controllers while preserving routes and response contracts.

**Tasks**

- [ ] Split `ApiController` by domain behind existing route contracts.
- [ ] Extract `AdminCrudController` workflows into resource-specific actions/services.
- [ ] Move timeline/feed/profile workflows from `MainController` and `Profile` into actions and query classes.
- [ ] Move payment verification into gateway-specific services.
- [ ] Move upload handling into validated actions/services.
- [ ] Keep controllers limited to authorize, validate, delegate, and return response.

**Verification**

- `php artisan test`
- Focused feature tests per controller slice.
- `vendor/bin/pint --test`

**Exit Criteria**

- Each extracted workflow has tests.
- No new business logic is added to controllers.

## Phase 5: Routes and HTTP Method Safety

**Goal:** Make routing explicit, named, grouped, method-correct, and policy-aware.

**Tasks**

- [ ] Inventory all routes and mark public/auth/user/admin/API/payment/install scope.
- [ ] Remove duplicate route definitions where tests prove no behavior loss.
- [ ] Convert state-changing GET routes to POST/PATCH/PUT/DELETE with CSRF protection.
- [ ] Group routes by middleware, prefix, and name prefix.
- [ ] Add route model binding where safe.
- [ ] Add signed routes only for intentional temporary public links.

**Verification**

- `php artisan route:list`
- `php artisan route:cache`
- Feature tests for old and new methods where behavior changes.

**Exit Criteria**

- Route cache passes.
- Destructive actions require authenticated, authorized, CSRF-protected write methods.

## Phase 6: Validation with Form Requests

**Goal:** Move write validation and command-specific authorization to request classes.

**Tasks**

- [ ] Create Form Requests for auth, profile, account, story, post, upload, payment, admin settings, and installer writes.
- [ ] Preserve legacy validation messages where UI depends on them.
- [ ] Reject unexpected enum-like statuses and malformed IDs.
- [ ] Validate file uploads with MIME, size, extension, and ownership/context rules.
- [ ] Normalize API validation response shapes.

**Verification**

- Feature validation tests for each request.
- `php artisan test`

**Exit Criteria**

- New write endpoints do not validate directly in controllers.
- Request tests cover valid, invalid, and unauthorized inputs.

## Phase 7: Authorization, Policies, and Access Boundaries

**Goal:** Replace scattered role checks and UI-only hiding with backend authorization.

**Tasks**

- [ ] Add policies for users, posts, comments, stories, media, groups, pages, notifications, payments, settings, and admin actions.
- [ ] Replace inline owner/role checks in controllers and Blade.
- [ ] Apply `can` middleware where route-level policy checks are clear.
- [ ] Harden disabled, unverified, guest, owner, non-owner, and admin cases.
- [ ] Remove manual bearer-token parsing from API methods after guard/middleware tests exist.

**Verification**

- Policy test matrix per model/domain.
- Feature tests for guest, disabled, unverified, owner, non-owner, admin.

**Exit Criteria**

- Sensitive actions are protected by policies/gates/middleware.
- UI hiding is only presentation, not security.

## Phase 8: Models, Eloquent Relationships, and Query Layer

**Goal:** Make Eloquent the primary query layer and reduce duplicated query logic.

**Tasks**

- [ ] Add `$fillable`, `$casts`, `$hidden`, and primary-key/table declarations where legacy schema requires them.
- [ ] Define relationships for users, posts, comments, stories, media, pages, groups, payments, notifications, and settings.
- [ ] Extract repeated `where()` and join logic into scopes or query classes.
- [ ] Replace manual joins with relationships where payload/behavior is unchanged.
- [ ] Use API Resources for JSON output boundaries.
- [ ] Add factories for core models before expanding feature tests.

**Verification**

- Unit/focused feature tests for scopes and query classes.
- Serialization tests where sensitive fields are involved.

**Exit Criteria**

- Hot workflows use relationships, scopes, or query classes.
- Models have explicit mass-assignment and cast rules.

## Phase 9: Database, Indexes, and Query Performance

**Goal:** Improve performance through measured query changes, not guesses.

**Tasks**

- [ ] Audit query patterns for timeline, stories, comments, notifications, payments, search, dashboard, albums, blogs, and admin filters.
- [ ] Add `with()`, `loadMissing()`, `withCount()`, `withExists()`, and aggregate eager loading.
- [ ] Add query-count tests for high-traffic pages.
- [ ] Plan indexes for frequent `where`, `orderBy`, join, foreign-key, and cursor pagination columns.
- [ ] Use transactions for multi-step writes.
- [ ] Avoid unbounded `all()`/`get()` on large datasets.

**Verification**

- Query-count tests where practical.
- Database explain checks in the target environment before large index changes.
- `php artisan test`

**Exit Criteria**

- N+1 and aggregate-in-loop patterns are removed from prioritized pages.
- Index migrations are justified by observed query patterns.

## Phase 10: Migrations and Schema Change Discipline

**Goal:** Replace install-dump and mutation shortcuts with reversible, deploy-safe migrations.

**Tasks**

- [ ] Do not edit production migrations; add new migrations.
- [ ] Split schema migrations from data migrations where possible.
- [ ] Add `up()` and `down()` for every migration.
- [ ] Use expand-contract for risky changes.
- [ ] Remove production reliance on SQL dump imports and `DB::unprepared()`.
- [ ] Add migration tests or smoke checks for critical schema changes.

**Verification**

- `php artisan migrate:fresh --seed` in a safe local/test environment when applicable.
- `php artisan test`

**Exit Criteria**

- Schema changes are reversible or explicitly forward-only with documented rollback.
- Destructive changes have backups and rollout notes.

## Phase 11: Frontend and Blade Refactor

**Goal:** Remove business/query logic from Blade and improve accessibility without changing visible behavior unintentionally.

**Tasks**

- [ ] Remove queries from Blade templates and pass preloaded data from controllers/ViewModels.
- [ ] Replace repeated fragments with Blade components.
- [ ] Ensure all internal links and forms use named routes.
- [ ] Add semantic HTML landmarks, labels, buttons, links, table headers, alt text, and focus states.
- [ ] Escape output by default; isolate and document any trusted HTML.
- [ ] Add empty states with `@forelse`.
- [ ] Remove provider secrets from JavaScript-rendered views.

**Verification**

- View rendering tests for changed pages.
- Manual accessibility spot checks for keyboard navigation and focus visibility.
- `npm run production` when assets change.

**Exit Criteria**

- Changed Blade views do not query or perform business logic.
- Accessibility is improved or unchanged with evidence.

## Phase 12: Vite Migration

**Goal:** Move from Laravel Mix/Webpack to Vite safely and separately from UI refactors.

**Tasks**

- [ ] Inventory current Mix entrypoints, generated assets, public files, Blade asset references, and `mix()` usage.
- [ ] Add Vite in a dedicated branch/commit with compatibility notes.
- [ ] Migrate one entrypoint at a time.
- [ ] Treat frontend environment variables as public/build-time values.
- [ ] Update deployment build scripts and cache-busting docs.
- [ ] Keep rollback to Mix possible until production verification succeeds.

**Verification**

- `npm run build` or configured Vite production build after migration.
- Browser smoke tests for homepage, auth, timeline, profile, payment pages, admin pages.

**Exit Criteria**

- All referenced assets resolve in production mode.
- Rollback instructions exist before removing Mix.

## Phase 13: SCSS and CSS Architecture

**Goal:** Make styles maintainable and accessible while avoiding a visual rewrite.

**Tasks**

- [ ] Inventory source SCSS/CSS versus vendored/generated assets.
- [ ] Exclude vendor/public generated assets from lint-first tooling.
- [ ] Plan Sass `@use`/`@forward` migration for source SCSS.
- [ ] Extract tokens for color, spacing, typography, shadows, and breakpoints.
- [ ] Reduce deep nesting and global leaks in touched files.
- [ ] Add focus-visible, reduced-motion, and contrast checks to critical UI.

**Verification**

- `npm run production`
- Stylelint after it is installed and configured.
- Visual smoke checks for changed screens.

**Exit Criteria**

- Source styles have a documented architecture.
- Critical controls remain accessible at mobile and desktop widths.

## Phase 14: JavaScript Quality

**Goal:** Reduce inline/global JavaScript risk and prepare for modern tooling.

**Tasks**

- [ ] Inventory inline scripts, global functions, AJAX helpers, and DOM dependencies.
- [ ] Remove secrets and privileged provider calls from client code.
- [ ] Move repeated DOM behavior into source JS modules where practical.
- [ ] Add ESLint and Prettier in a dedicated tooling phase.
- [ ] Avoid unsafe HTML injection and unescaped user content.
- [ ] Preserve legacy browser assumptions until explicitly changed.

**Verification**

- `npm run production`
- ESLint/Prettier only after installation.
- Feature tests or browser smoke checks for changed interactions.

**Exit Criteria**

- New JS uses source-managed entrypoints.
- No new global pollution or browser-exposed secrets.

## Phase 15: Security Hardening

**Goal:** Remove the highest-risk exploit paths before broad polish work.

**Tasks**

- [ ] Remove or disable executable updater paths, uploaded PHP execution, and unprepared SQL execution.
- [ ] Remove demo database restore/table mutation from auth/session flows.
- [ ] Harden file uploads with validation, storage isolation, and cleanup.
- [ ] Add rate limits for login, registration, password reset, uploads, comments, posts, stories, and API endpoints.
- [ ] Verify payment/webhook signatures and idempotency.
- [ ] Remove sensitive logging and rendered secrets.
- [ ] Add dependency audit gates and document temporary exceptions.

**Verification**

- Security-focused feature tests.
- `composer audit`
- `npm audit` during dependency/security work.
- Manual review of high-risk diff.

**Exit Criteria**

- Critical remote-code/database-mutation paths are removed or locked down.
- Sensitive actions have backend authorization and tests.

## Phase 16: Performance and Payload Control

**Goal:** Improve high-traffic pages with measurable changes.

**Tasks**

- [ ] Establish query and render baselines for timeline, profile, story, notification, search, marketplace, blog, and admin dashboard pages.
- [ ] Paginate or cursor-paginate large lists.
- [ ] Use explicit selected columns where safe.
- [ ] Cache expensive settings/statistics with invalidation.
- [ ] Move expensive aggregates to cached model/query methods or scheduled refreshes.
- [ ] Avoid loading large relationships into sessions/jobs/views.

**Verification**

- Query-count tests.
- Before/after timing or query logs.
- `php artisan test`

**Exit Criteria**

- Performance changes include evidence and no response-contract drift.

## Phase 17: Queues and Jobs

**Goal:** Move slow or retryable work out of HTTP requests.

**Tasks**

- [ ] Select queue driver per environment and document worker requirements.
- [ ] Queue email, media processing, notification fanout, webhooks, provider calls, exports, and cache refreshes.
- [ ] Add idempotency to payment/webhook jobs.
- [ ] Set retries, timeout, backoff, and failure handling.
- [ ] Pass IDs or small DTOs into jobs, not large hydrated models.
- [ ] Add failed-job review and alerting docs.

**Verification**

- `Queue::fake()` feature tests.
- Job unit tests for missing records, retries, and duplicate handling.
- Deployment smoke check for workers.

**Exit Criteria**

- Slow side effects are resilient, retryable, and observable.

## Phase 18: CI and Quality Gates

**Goal:** Stop regressions from reaching `main`.

**Tasks**

- [ ] Add CI for Composer install, npm install/build, Pint, tests, Composer validation, Composer audit, config cache, route cache, and view cache.
- [ ] Add npm audit as a security job with explicit exception handling.
- [ ] Add PHPStan/Larastan after local baseline is accepted.
- [ ] Add ESLint/Stylelint/Prettier only after those tools are configured.
- [ ] Cache dependencies safely.
- [ ] Keep required secrets documented and minimal.

**Verification**

- CI passes on a pull request or branch.
- Local commands match CI commands.

**Exit Criteria**

- `main` is protected by repeatable checks.
- Failing gates have documented ownership and remediation path.

## Phase 19: Deployment Safety

**Goal:** Make releases boring, observable, and reversible.

**Tasks**

- [ ] Document production environment variables and safe defaults.
- [ ] Define backup, migration, cache, queue restart, scheduler, asset build, and smoke-test order.
- [ ] Remove web-accessible install/update/restore paths from production.
- [ ] Document writable paths and permissions.
- [ ] Add logging/monitoring checks for failed jobs, payment failures, auth errors, slow queries, and 500s.
- [ ] Add post-deploy smoke checks for homepage, login, registration, story/post creation, uploads, payment callback, admin dashboard, queue worker, and scheduler.

**Verification**

- `php artisan config:cache`
- `php artisan route:cache`
- `php artisan view:cache`
- `npm run production`
- Manual smoke checklist in staging.

**Exit Criteria**

- Deployments have a written runbook and rollback path.

## Phase 20: Documentation and Knowledge Capture

**Goal:** Keep future refactors aligned with current decisions.

**Tasks**

- [ ] Update `docs/refactor-audit.md` after major risk reductions.
- [ ] Update `docs/refactor-checklist.md` when tasks are completed or superseded.
- [ ] Keep `docs/coding-standards.md` aligned with installed tools.
- [ ] Add ADRs for Vite migration, queue driver choice, CI gates, auth strategy, schema strategy, and static-analysis adoption.
- [ ] Document known legacy exceptions and planned removal phase.

**Verification**

- `git diff --check`
- Links and command examples reviewed.

**Exit Criteria**

- Docs describe the live project state, not stale plans.

## Phase 21: Rollback Strategy

**Goal:** Make every risky phase reversible.

**Tasks**

- [ ] Require rollback notes for migrations, route changes, auth changes, queue changes, build-tool changes, and dependency upgrades.
- [ ] Use expand-contract migrations for schema changes.
- [ ] Keep feature flags for incomplete or high-risk behavior changes.
- [ ] Back up before destructive migrations or data backfills.
- [ ] Define code rollback, database forward-fix, cache clear, worker restart, asset rollback, and smoke-test steps per release.
- [ ] Track which changes are not safely reversible and require forward-only repair.

**Verification**

- Rollback notes included in PR/release description.
- Staging rollback drill for high-risk releases.

**Exit Criteria**

- A failed release can be recovered without guessing.

## Recommended Implementation Order

1. Update stale docs to reflect the Laravel 13 baseline.
2. Add safety tests around the most dangerous workflows.
3. Remove updater/demo restore execution paths.
4. Harden route methods, middleware, and policies.
5. Move validation into Form Requests.
6. Extract controller workflows into actions/services/query classes.
7. Define model relationships, scopes, casts, and factories.
8. Remove Blade queries and browser-exposed secrets.
9. Add database indexes and transactions with measured evidence.
10. Introduce static analysis and frontend linting tools.
11. Plan and execute Vite migration.
12. Move slow external side effects to queues/jobs.
13. Add CI gates and deployment runbooks.
14. Repeat audit/checklist updates after each completed phase.

## Per-Phase Completion Template

Use this template before closing a phase:

```text
Phase:
Scope completed:
Files changed:
Behavior preserved:
Behavior intentionally changed:
Tests added/updated:
Verification commands:
Rollback notes:
Risks deferred:
Next recommended slice:
```
