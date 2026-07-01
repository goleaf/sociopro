# Project Standards Bible

Generated: 2026-07-01

This document is the strict operating contract for this Laravel project. It applies to every new feature, bug fix, refactor, audit, documentation change, and deployment preparation task.

Use it together with:

- `AGENTS.md`
- `docs/coding-standards.md`
- `docs/refactor-audit.md`
- `docs/refactor-checklist.md`
- `docs/enterprise-refactor-rulebook.md`
- `docs/refactor-roadmap-unreal.md`

## Current Project Baseline

- PHP requirement: `^8.3`
- Laravel framework: `13.18.0`
- Laravel Sanctum: `4.3.2`
- PHPUnit: `12.5.30`
- Laravel Pint: `1.29.3`
- Node: `v22.22.3`
- npm: `10.9.8`
- Frontend build tool: Laravel Mix / Webpack
- JavaScript/CSS stack: Alpine, Axios, Tailwind CSS 3, PostCSS, Laravel Mix, Webpack
- Present tooling: Pint, PHPUnit
- Not currently installed: Larastan/PHPStan, Rector, Vite, ESLint, Stylelint, Prettier

Do not treat a tool as available until the repository files prove it. Vite, ESLint, Stylelint, Prettier, Larastan/PHPStan, and Rector standards in this document are target standards for planned adoption or future migrations, not permission to silently add packages inside unrelated work.

## Language Used in This Document

- **MUST** means required.
- **MUST NOT** means forbidden.
- **SHOULD** means required unless there is a documented legacy constraint.
- **MAY** means allowed when it improves clarity, safety, performance, or maintainability.
- **Legacy exception** means existing code may violate the rule, but new code must not deepen the violation.

## Global Engineering Contract

- Preserve existing behavior unless a bug or security issue is explicitly being fixed.
- Add or update tests before changing risky legacy behavior.
- Keep each commit scoped to one logical concern.
- Do not mix broad formatting, package upgrades, documentation, refactors, and behavior changes in one commit unless the user explicitly asks for it.
- Never commit secrets, credentials, private keys, tokens, production `.env` values, database dumps, or provider credentials.
- Never introduce raw SQL strings when Laravel schema builder, query builder, or Eloquent can express the operation safely.
- Never put database queries in Blade templates, Blade components, loops, or conditional rendering.
- Never rely on frontend hiding, Blade conditionals, or Filament visibility as authorization.
- Never expose server-side secrets to JavaScript, Blade, public config, logs, exceptions, or API responses.
- Always inspect the live code and dependency files before applying a pattern.
- Always document risky leftovers, partial migrations, and known exceptions.

## Required Workflow for Any Change

1. Read `AGENTS.md` and the relevant docs in `docs/`.
2. Inspect current dependencies from `composer.json`, `composer.lock`, `package.json`, lock files, and build config.
3. Inspect routes, controllers, models, views, tests, config, migrations, and jobs related to the task.
4. Identify the smallest safe change.
5. Add characterization tests first for risky legacy behavior.
6. Implement with Laravel conventions and the standards in this file.
7. Run the relevant verification commands.
8. Review the diff for secrets, unrelated files, raw SQL, unsafe queries, and behavior drift.
9. Commit with a Conventional Commit message.
10. Push only after verifying the intended commit is at `HEAD`.

## PHP Standards

- PHP code MUST follow PSR-12 and Laravel Pint.
- Namespaces MUST match PSR-4 autoload paths.
- Use one class, enum, interface, or trait per file unless a local Laravel convention proves otherwise.
- Prefer typed properties, typed parameters, and return types when safe.
- Prefer value objects or DTOs for complex structured data crossing layers.
- Prefer native enums for closed status sets when database and PHP version support are clear.
- Prefer exceptions or explicit result objects over magic boolean failure states.
- Use constructor injection for dependencies.
- Avoid service location through `app()`, facades, or static helpers when constructor injection is practical.
- Avoid global helpers for business logic. Helpers may format simple values or wrap unavoidable legacy behavior.
- Avoid commented-out code. Git already stores old code.
- Comments SHOULD explain non-obvious intent, risk, domain rules, provider quirks, or rollback notes.
- Do not add `declare(strict_types=1);` randomly to isolated legacy files. Adopt it only as a coherent, tested slice.
- Use `vendor/bin/pint --test` before committing PHP changes.

## Laravel Standards

- Use Laravel conventions before custom abstractions.
- Controllers MUST be thin.
- Form Requests MUST handle validation for write endpoints.
- Policies or gates MUST protect sensitive actions.
- Eloquent relationships, scopes, query classes, actions, and services MUST own reusable data access and workflows.
- API Resources MUST shape non-trivial JSON responses.
- Events/listeners MAY decouple side effects after core state changes succeed.
- Jobs MUST handle slow, retryable, or external side effects.
- Config files MUST be the only place that reads `env()` values.
- Do not bypass Laravel middleware, validation, authorization, encryption, hashing, escaping, storage, queue, cache, mail, or notification primitives without a documented reason.
- Prefer route model binding over manual `find()` in controllers.
- Prefer named routes for redirects and generated links.

## Controllers

- A controller method SHOULD only coordinate: request, authorization, action/service/query, and response.
- Do not put payment verification, uploads, media processing, notification fanout, query construction, role logic, or business rules directly in controllers.
- Do not duplicate conditions across controllers. Extract to model scopes, query classes, actions, services, or policies.
- Use Form Request classes for `store`, `update`, import, checkout, profile, payment, upload, and destructive actions.
- Use `$this->authorize()` or middleware for model operations.
- Return redirects, views, API resources, or response objects from controllers; do not return loosely shaped arrays for complex responses.
- Split controller methods when one method contains unrelated HTML and JSON behavior.
- Keep route model binding explicit enough to make authorization and missing-model behavior predictable.
- Use `abort_unless`, `authorize`, policy responses, or dedicated exceptions for access failures.

## Services

- Services SHOULD model integration boundaries or cohesive domain capabilities.
- Use services for payment providers, media storage, settings, search, notifications, external HTTP clients, and complex reusable domain workflows.
- Services MUST NOT depend on HTTP request objects unless the service is explicitly an adapter for HTTP input.
- Services MUST NOT return Blade views or redirects.
- Services SHOULD expose clear methods with typed arguments and return values.
- External services MUST use timeouts, retries where safe, structured error handling, and sanitized logging.
- Provider credentials MUST come from `config()`, never direct `env()` calls.
- Use `Http::fake()` or equivalent fakes in tests for service integrations.

## Actions

- Actions SHOULD be single-purpose command classes.
- Action names MUST be imperative and specific, for example `CreateStoryAction`, `SyncProviderPaymentAction`, or `DeleteUploadedMediaAction`.
- Actions SHOULD expose `handle()` unless the existing codebase has a clearer local convention.
- Actions MUST own transaction boundaries for multi-write workflows.
- Actions MUST be testable without an HTTP request.
- Actions SHOULD return a model, DTO, enum result, or explicit value object.
- Do not hide authorization inside actions unless the action is specifically an application service invoked from multiple entry points. Controller and route boundaries still need explicit authorization.
- Use events or jobs for side effects only after core state changes succeed.

## Form Requests

- Every write endpoint SHOULD have a Form Request.
- Put input rules in `rules()`.
- Put command-specific authorization in `authorize()` when it depends on the request context.
- Normalize or prepare input in `prepareForValidation()` only when it is deterministic and safe.
- Use `Rule` objects, enum validation, exists/unique constraints, file constraints, and conditional rules instead of ad hoc controller checks.
- Validate uploaded files by MIME type, size, dimensions, ownership, purpose, and storage path constraints.
- Do not trust hidden fields, frontend constraints, query strings, or JavaScript validation.
- Tests MUST cover required fields, invalid formats, unauthorized users, invalid owners, oversized files, and provider callback payloads for critical flows.

## Policies and Authorization

- Every sensitive model operation MUST have a policy or gate.
- Policy methods SHOULD follow Laravel conventions: `viewAny`, `view`, `create`, `update`, `delete`, `restore`, and `forceDelete`.
- Do not scatter role checks across controllers, Blade, resources, or JavaScript.
- Do not treat UI hiding as security.
- Route-level middleware MAY be used for coarse access; policy checks still belong near the model operation.
- Authorization tests SHOULD cover guest, owner, non-owner, disabled user, unverified user, normal user, admin, and provider callback cases as applicable.
- Use policy responses when denial reasons matter to the product.
- Prefer explicit tenant/user ownership checks over implicit assumptions.

## Models

- Every mass-assignable model MUST define `$fillable`.
- Do not use `$guarded = []` in new or refactored models.
- Define `$casts` for booleans, dates, arrays, JSON, encrypted values, decimals, enums, and custom value objects.
- Define relationships instead of repeating joins or foreign key lookups in controllers.
- Use local scopes for reusable filters. One scope should express one responsibility.
- Use accessors and mutators only for presentation-safe or model-local transformations.
- Avoid side-effect-heavy model methods. Move workflows to actions or services.
- Use observers for cross-cutting model effects such as audit logging, cache invalidation, or notification dispatch, but keep them predictable.
- Use `$hidden`, `$visible`, API Resources, or DTOs to prevent sensitive field leaks.
- Do not define global `$with` relationships unless the relationship is required almost everywhere.
- Use `with()`, `load()`, `loadMissing()`, `withCount()`, `withExists()`, and aggregate eager-loading helpers to avoid N+1 queries.
- Do not call `Model::all()` unless the dataset is provably tiny and bounded.
- For large datasets, use pagination, `chunkById()`, `lazyById()`, or cursor-based iteration.

## Migrations

- One migration MUST describe one schema concern.
- Migrations MUST have both `up()` and `down()` methods.
- Do not edit existing production migrations. Create a new migration.
- Define foreign keys when lifecycle and delete behavior are understood.
- Add indexes for foreign keys, common filters, common sort columns, unique lookups, and cursor pagination columns.
- Use explicit column types, nullability, defaults, and comments when the schema is not self-explanatory.
- Use decimals for money. Do not use floats for money.
- Store dates and times with clear timezone expectations.
- Avoid destructive migrations without a backup, expand-contract plan, and rollback notes.
- Separate data migrations from schema migrations when possible.
- Raw SQL in migrations requires a documented exception and tests or manual verification notes.
- Verify schema changes with `php artisan migrate:fresh --seed` or the project-approved equivalent when safe.

## Database Schema and Query Standards

- Eloquent is the primary query layer.
- Query builder is allowed when it improves clarity and still uses parameter binding.
- Raw SQL string concatenation is forbidden.
- Do not use `DB::select()`, `DB::statement()`, `DB::raw()`, or `DB::unprepared()` in controllers, views, jobs, helpers, or resources.
- If raw expressions are unavoidable, isolate them in a model scope or query object, bind parameters safely, document the reason, and test the behavior.
- Never query in Blade views.
- Never execute aggregates inside loops. Use eager-loaded aggregate helpers.
- Never use unbounded offset pagination on large tables.
- Prefer `simplePaginate()` when total counts are not displayed.
- Prefer cursor pagination for large append-only feeds with supporting indexes.
- Use transactions for multi-step writes that must succeed or fail together.
- Keep database constraints aligned with application validation.
- Avoid polymorphic relationships unless they remove real complexity and are documented.
- Add database-level uniqueness where duplicate prevention matters.
- Use `EXPLAIN` during performance work on high-volume queries.

## Blade Standards

- Blade MUST be presentation only.
- Controllers, actions, query objects, or ViewModels MUST prepare all data before rendering.
- Do not query relationships, call aggregating methods, or fetch settings from Blade.
- Use escaped `{{ }}` output by default.
- Use `{!! !!}` only for trusted, sanitized HTML with a documented reason.
- Prefer Blade components for reusable UI.
- Use `@props` at the top of anonymous components.
- Use `@forelse` when a collection may be empty.
- Use `@csrf` on every non-GET form.
- Use `@method` for PUT, PATCH, and DELETE form spoofing.
- Use `old()` and validation error output for forms.
- Keep conditionals simple. Move complex display decisions to ViewModels, DTOs, presenters, or component classes.
- Do not embed secrets, provider credentials, internal IDs, or sensitive config values in HTML.

## HTML and Accessibility Standards

- Use semantic HTML first: headings, landmarks, lists, buttons, links, forms, labels, tables, and fieldsets.
- Target WCAG 2.2 AA for user-facing pages.
- Buttons perform actions. Links navigate.
- Interactive elements MUST be keyboard accessible.
- Every input MUST have an accessible label.
- Form errors MUST be programmatically associated or clearly adjacent to their fields.
- Images MUST have meaningful alt text or empty alt text when decorative.
- Do not replace native controls with custom ARIA widgets unless there is a proven need.
- Do not use click-only `div` or `span` elements.
- Maintain logical heading order and source order.
- Ensure focus states are visible.
- Ensure color is not the only signal for status, validation, or destructive actions.
- Tables MUST use headings and scopes when presenting tabular data.
- Modal/dialog behavior MUST manage focus, escape behavior, and background interaction.

## SCSS and CSS Standards

- Keep CSS organized by tokens, base, layout, components, utilities, and page-specific exceptions.
- Prefer small reusable classes and components over deep selector chains.
- Avoid deep nesting. Two levels is the default limit unless a framework pattern requires more.
- Avoid `!important` except for documented utility or third-party override cases.
- Use design tokens for colors, spacing, typography, radii, shadows, and z-index layers.
- Use logical properties where they improve internationalization and layout resilience.
- Use responsive styles with mobile-first breakpoints.
- Do not hide focus outlines without replacing them with an accessible visible focus state.
- Do not create global leaks from component styles.
- Sass target standard: use `@use` and `@forward` for new SCSS architecture.
- Legacy Sass `@import` may remain until a dedicated frontend tooling migration.
- Stylelint target standard: use a project config and standard SCSS rules once Stylelint is added.

## JavaScript Standards

- Use JavaScript to enhance server-rendered Blade, not to replace core server behavior.
- Keep server authorization and validation authoritative.
- Do not expose secrets or private config to browser code.
- Avoid global variables. Use modules or local initialization patterns.
- Keep DOM selectors stable and intentional.
- Use event delegation for repeated elements when practical.
- Debounce or throttle noisy events such as search, scroll, resize, and input listeners.
- Handle loading, success, error, timeout, and empty states.
- Sanitize or escape any HTML inserted into the DOM.
- Avoid inline scripts when a bundled/module entrypoint is practical.
- ESLint target standard: configure rules before enforcing them.
- Prettier target standard: use a checked-in config if Prettier is adopted.
- Do not introduce frontend package upgrades in behavior refactor commits.

## Vite Standards

This project currently uses Laravel Mix / Webpack. These Vite standards apply only when a dedicated Vite migration is approved.

- Add Vite in its own build-tool migration commit.
- Do not mix a Vite migration with unrelated backend refactors.
- Use explicit frontend entrypoints.
- Treat `import.meta.env` values as build-time/client-visible values.
- Only variables intentionally safe for browser exposure may use the `VITE_` prefix.
- Never put API secrets, payment keys, database credentials, private tokens, or server-only values in `VITE_*` variables.
- Keep server-side secrets behind Laravel routes, controllers, jobs, or services.
- Verify production assets with the configured build command after migration.
- Update deployment docs, CI, cache-busting assumptions, and Blade asset directives when migrating from Mix.

## API Standards

- JSON APIs MUST validate input with Form Requests or explicit validated data objects.
- JSON APIs MUST authorize every sensitive action.
- Use API Resources for model serialization and relationship shaping.
- Do not return full Eloquent models directly for sensitive or relationship-heavy resources.
- Use consistent HTTP status codes.
- Use pagination for list endpoints.
- Use request throttling for login, registration, password reset, public write endpoints, search, and provider callbacks where applicable.
- Version public APIs when breaking response contracts.
- Do not leak stack traces, SQL errors, tokens, internal paths, or provider payloads.
- Validate webhook signatures and provider callback authenticity.
- Make idempotency explicit for payment, webhook, import, export, and retryable endpoints.
- Use Sanctum or project-standard auth middleware for authenticated APIs.

## Testing Standards

- `php artisan test` MUST pass before committing application logic changes.
- Feature tests SHOULD cover HTTP endpoints, redirects, views, validation, authorization, policies, jobs, notifications, events, and database writes.
- Unit tests SHOULD cover actions, services, scopes, value objects, and pure logic.
- Use factories instead of manual inserts.
- Use `RefreshDatabase` or the project-standard database reset strategy.
- Use `Http::fake()`, `Queue::fake()`, `Event::fake()`, `Notification::fake()`, `Storage::fake()`, and mail fakes where appropriate.
- Do not hit real payment providers, email providers, social APIs, object storage, or external URLs in automated tests.
- Add regression tests before fixing proven bugs.
- Add characterization tests before risky refactors.
- Test authorization bypass paths, not just hidden UI controls.
- Test missing-model behavior for routes that previously produced null property errors.
- Keep tests deterministic and independent of execution order.
- When a suite is already failing, record the exact pre-existing failures and keep the commit scoped.

## Queues, Jobs, Events, and Notifications

- Use jobs for work that may exceed 200ms, call external services, process media, send emails, fan out notifications, refresh caches, or perform exports/imports.
- Jobs MUST be idempotent or explicitly protected against duplicate side effects.
- Jobs SHOULD set tries, timeout, backoff, retry-until, and failure behavior when non-trivial.
- Pass IDs or small DTOs to jobs, not large hydrated models with unnecessary relationships.
- Re-load models inside jobs and handle deleted/missing records safely.
- Use transactions carefully when dispatching jobs. Dispatch after commit when the job depends on committed data.
- Events should describe domain facts that already happened.
- Listeners should be focused, testable, and queued when expensive.
- Notifications must not include secrets or sensitive data unless the channel and recipient are explicitly allowed.
- Deployment docs MUST describe queue workers when new queued work is added.

## Cache Standards

- Cache only data with a clear key, owner, TTL, and invalidation plan.
- Do not cache permission-sensitive data without including user, tenant, locale, and visibility context in the cache key.
- Do not cache secrets in shared caches.
- Prefer `Cache::remember()` in services, query objects, model methods, or cache-specific classes, not Blade.
- Use cache tags only when the configured cache driver supports them.
- Invalidate caches from actions, observers, events, or explicit services after writes.
- Do not use caching to hide inefficient query patterns until the query itself has been reviewed.
- Document cache keys and invalidation behavior for high-risk domains.

## Config and Environment Standards

- All environment-backed values MUST be accessed through `config()` outside config files.
- `env()` MUST NOT appear in application runtime code, Blade, jobs, services, controllers, middleware, tests, or resources except when testing config behavior.
- Every new environment variable MUST be added to `.env.example`.
- Config keys SHOULD be grouped by provider or domain.
- Production MUST support `php artisan config:cache`.
- Required production config should fail closed or surface a clear deployment error.
- Do not log full config arrays when they may contain secrets.
- Do not expose server config to frontend bundles unless every value is intentionally public.
- After config changes, verify with `php artisan config:cache` when safe and clear generated caches if local artifacts are created.

## Security Standards

- Follow OWASP Laravel and general web application guidance.
- Validate all input at trust boundaries.
- Authorize every sensitive action server-side.
- Protect against IDOR by checking ownership, tenant, visibility, and role context.
- Use parameter binding through Laravel/Eloquent/query builder APIs.
- Do not concatenate user input into SQL, shell commands, paths, URLs, redirects, or HTML.
- Escape Blade output by default.
- Sanitize trusted-rich-text input and document the sanitizer.
- Protect uploads with validation, storage isolation, randomized names, MIME checks, size limits, and authorization checks.
- Store passwords only through Laravel hashing.
- Store tokens and provider secrets encrypted or in environment-backed config as appropriate.
- Verify webhook signatures and replay protections.
- Avoid sensitive logging. Redact tokens, passwords, API keys, card data, and provider secrets.
- Use CSRF protection for browser state-changing requests.
- Use rate limiting for abusive or expensive endpoints.
- Use secure cookies and HTTPS in production.
- Do not keep debug mode enabled in production.
- Keep dependencies patched and audit provider SDKs before upgrades.

## Git Standards

- Use Conventional Commits.
- Use `docs:` for documentation-only commits.
- Use `fix:` for bug fixes.
- Use `refactor:` for behavior-preserving code restructuring.
- Use `test:` for tests-only commits.
- Use `ci:` for CI workflow changes.
- Keep commits small and atomic.
- Inspect `git status --short` before staging.
- Stage only files that belong to the task.
- Review `git diff --cached` before commit.
- Do not commit generated build artifacts unless the repository explicitly tracks them.
- Do not rewrite shared history or force push without explicit approval.
- Do not revert user work unless explicitly requested.
- Push only after verification commands complete and the intended commit is at `HEAD`.

## CI Standards

- CI SHOULD install Composer dependencies with locked versions.
- CI SHOULD install Node dependencies from the lock file when frontend assets are built.
- CI MUST run `php artisan test`.
- CI SHOULD run `vendor/bin/pint --test` for PHP code.
- CI SHOULD run `composer validate --strict`.
- CI SHOULD run frontend production build when frontend files or package files change.
- CI SHOULD add static analysis after Larastan/PHPStan is adopted.
- CI SHOULD add JS/CSS linting after ESLint/Stylelint/Prettier are adopted.
- CI MUST fail on secret scanning, syntax errors, failed tests, and failed production builds.
- CI should cache Composer and npm dependencies without caching secrets or build outputs incorrectly.

## Deployment Standards

- Deploy from a clean, tested commit.
- Production deploys SHOULD run Composer install with optimized autoloading and without dev dependencies.
- Production deploys SHOULD run asset builds from locked Node dependencies.
- Production deploys SHOULD run `php artisan config:cache`, `route:cache`, `view:cache`, and relevant optimize commands when compatible.
- Migrations MUST be reviewed for rollback risk before production.
- Destructive database changes require backup and rollback notes.
- Queue workers MUST be restarted after code changes.
- Scheduler, queue, storage links, cache drivers, mail, logging, and payment configs MUST be verified in deployment docs.
- Do not deploy with `APP_DEBUG=true`.
- Do not deploy without required secrets present in environment-managed configuration.
- Rollbacks MUST account for code, database schema, assets, queued jobs, and cache state.

## Documentation Standards

- Document why decisions were made, not just what changed.
- Keep docs close to the affected domain when possible.
- Update docs in the same commit only when the documentation is part of the requested scope or directly necessary for safe operation.
- Use ADRs in `docs/decisions/` for expensive-to-reverse architecture decisions.
- Document risky exceptions, legacy constraints, rollback steps, and operational requirements.
- Keep README/setup docs accurate when commands, dependencies, or deployment steps change.
- Do not document aspirational tooling as installed tooling.
- For audit reports, include severity, risk, file references, suggested tests, and implementation order.
- For standards documents, include enforcement gates and source references.

## Minimum Verification Gates

Documentation-only changes:

- `git diff --check`
- `php artisan test` when required by repository instructions before commit

PHP code changes:

- `vendor/bin/pint --test`
- `php artisan test`
- Focused tests for the changed domain

Frontend changes:

- Current Mix project: `npm run production`
- Future Vite project: configured Vite build command

Config or deployment changes:

- `php artisan config:cache`
- `php artisan route:cache` when route definitions changed
- `php artisan view:cache` when Blade compilation behavior changed
- Clear generated local caches afterwards if they create artifacts

Database changes:

- Relevant migration command
- Rollback check when safe
- Feature tests covering the changed behavior

## Source References

- PHP-FIG PSR-12: https://www.php-fig.org/psr/psr-12/
- Laravel 13 validation and Form Requests: https://laravel.com/docs/13.x/validation
- Laravel 13 controllers and resource Form Requests: https://laravel.com/docs/13.x/controllers
- Laravel 13 authorization: https://laravel.com/docs/13.x/authorization
- Laravel 13 Eloquent: https://laravel.com/docs/13.x/eloquent
- Laravel 13 migrations: https://laravel.com/docs/13.x/migrations
- Laravel 13 testing: https://laravel.com/docs/13.x/testing
- Laravel 13 configuration: https://laravel.com/docs/13.x/configuration
- Laravel 13 deployment: https://laravel.com/docs/13.x/deployment
- Laravel 13 Pint: https://laravel.com/docs/13.x/pint
- Vite env and modes: https://vite.dev/guide/env-and-mode
- OWASP Laravel Cheat Sheet: https://cheatsheetseries.owasp.org/cheatsheets/Laravel_Cheat_Sheet.html
- OWASP SQL Injection Prevention: https://cheatsheetseries.owasp.org/cheatsheets/SQL_Injection_Prevention_Cheat_Sheet.html
- OWASP Mass Assignment: https://cheatsheetseries.owasp.org/cheatsheets/Mass_Assignment_Cheat_Sheet.html
- MDN accessible HTML: https://developer.mozilla.org/en-US/docs/Learn_web_development/Core/Accessibility/HTML
- W3C WCAG 2.2: https://www.w3.org/TR/WCAG22/
- Sass `@use`: https://sass-lang.com/documentation/at-rules/use/
- Sass `@forward`: https://sass-lang.com/documentation/at-rules/forward/
- ESLint rule configuration: https://eslint.org/docs/latest/use/configure/rules
- Stylelint getting started: https://stylelint.io/user-guide/get-started/
- Prettier configuration: https://prettier.io/docs/configuration
