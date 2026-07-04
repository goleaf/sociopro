# Code Ownership Map

Generated: 2026-07-01

This document groups the repository by responsibility and identifies files that need extra review before changes. It is a documentation-only ownership map for refactor planning, review routing, and future agent work.

Use this with:

- `AGENTS.md`
- `docs/architecture-map.md`
- `docs/module-inventory.md`
- `docs/refactor-definition-of-done.md`
- `docs/risk-register.md`
- `docs/refactor-checklist.md`

## Ownership Model

Ownership means responsibility for review and safety, not exclusive permission. Many legacy files cross boundaries; when a file appears in multiple concerns, use the strictest owner and review requirement.

| Owner area | Primary responsibility | Typical reviewer focus |
| --- | --- | --- |
| Backend | Laravel application behavior, controllers, models, actions, queries, mail, providers, middleware | Behavior preservation, authorization, validation, Eloquent usage, service boundaries |
| Frontend | Blade views, Vite/SCSS entrypoints, public theme assets, accessibility | Escaped output, no Blade queries, accessibility, asset compatibility, UI regressions |
| Database | Schema, seed/import flow, factories, model-table contracts | Migration safety, indexes, dump compatibility, data integrity, rollback |
| DevOps | Runtime config, Composer/npm/build, cache/deploy entrypoints, server bootstrap | Dependency safety, environment config, build/deploy commands, cache behavior |
| Security | Auth, middleware, policies/gates, secrets, payment/provider boundaries, upload boundaries | Access control, secret handling, mass assignment, raw SQL, CSRF, callbacks |
| Testing | PHPUnit config, test suites, factories, test database setup | Regression coverage, fakes, fixture safety, focused and full-suite reliability |
| Shared Architecture | Cross-cutting providers, helpers, view models, docs, route architecture | Coupling, module boundaries, duplication, refactor sequencing |

## Backend Ownership

Backend owns most PHP application behavior.

Primary paths:

- `app/Http/Controllers/**`
- `app/Http/Requests/**`
- `app/Models/**`
- `app/Actions/**`
- `app/Queries/**`
- `app/ViewModels/**`
- `app/Helpers/**`
- `app/Mail/**`
- `app/Traits/**`
- `app/Providers/**`
- `routes/*.php`

High-risk backend files:

- `app/Http/Controllers/ApiController.php`: mobile/API mega-controller, 134 routed endpoints, payment/addon/feed/profile/group/page/event/blog/video/chat coverage.
- `app/Http/Controllers/AdminCrudController.php`: admin mega-controller, user/settings/payment/content/category/group/page/blog management.
- `app/Http/Controllers/MainController.php`: feed, posts, live/Zoom, addon settings, profile/sidebar interactions.
- `app/Http/Controllers/PaymentController.php`: gateway selection, gateway callbacks, payment creation/status.
- `app/Http/Controllers/InstallController.php`: installer state, database setup, admin creation, SQL dump import flow.
- `app/Http/Controllers/Profile.php`: profile, album, media, friend requests, account state.
- `app/Http/Controllers/SettingController.php`: settings, mail/S3/live/Jitsi, static pages, contact mail.
- `app/Http/Controllers/Updater.php`: addon/product update and addon status/delete operations.
- `app/Helpers/CommonHelper.php` and `app/Helpers/ApiHelper.php`: globally autoloaded helper logic with cross-module reach.
- `app/ViewModels/BladeViewData.php`: shared view data source used across layouts and sidebars.
- `app/Traits/ZoomMeetingTrait.php`: external live-video provider integration and token flow.

Backend files that should not be modified casually:

- Any controller with state-changing `GET` routes until tests and authorization are in place.
- `app/Models/payment_gateway/*.php` because provider behavior, credentials, and callbacks are money-sensitive.
- `app/Providers/RouteServiceProvider.php` because it controls route loading and conditional addon route registration.
- `app/Providers/ViewServiceProvider.php` because it injects shared data into broad view surfaces.
- `app/Http/Kernel.php` and `app/Http/Middleware/**` because they define request security and session behavior.
- `routes/api.php`, `routes/web.php`, `routes/custom_routes.php`, `routes/payment.php`, `routes/user.php`.

## Frontend Ownership

Frontend owns server-rendered UI, Blade composition, asset loading, and public theme/vendor assets.

Primary paths:

- `resources/views/**`
- `resources/js/app.js`
- `resources/js/bootstrap.js`
- `resources/scss/app.scss`
- `vite.config.js`
- `postcss.config.cjs`
- `public/assets/frontend/**`
- `public/assets/backend/**`
- `public/assets/payment/**`
- `public/js/share.js`

High-risk frontend files:

- `resources/views/frontend/index.blade.php`: frontend shell, global asset loading, fundraiser/paid-content asset references.
- `resources/views/frontend/common_scripts.blade.php`: shared frontend JS behavior and infinite-scroll style helpers.
- `resources/views/frontend/header.blade.php`, `left_navigation.blade.php`, `right_sidebar.blade.php`: broad layout/navigation surfaces.
- `resources/views/frontend/main_content/*.blade.php`: feed/post/comment rendering and modals.
- `resources/views/frontend/notification/notification.blade.php`: notification actions and addon route references.
- `resources/views/backend/admin/sidebar.blade.php`: admin navigation, addon visibility, route references.
- `resources/views/payment/**`: payment provider screens and inline provider JS.
- `public/assets/frontend/css/fundraiser/**` and `public/assets/frontend/paid-content/**`: addon theme bundles with many copied/vendor assets.
- `public/assets/backend/css/style.scss` and `public/assets/backend/css/_*.scss`: backend theme source-like SCSS committed under public assets.

Frontend files that should not be modified casually:

- Compiled/vendor-like public bundles such as Bootstrap, Summernote, CKEditor, DataTables, Plyr, Leaflet, and bundled addon assets. Treat these as vendor upgrades or build artifacts with explicit review.
- `public/js/share.js` unless the build process and source changes are understood.
- Blade templates that currently contain queries, provider configuration, inline scripts, or route calls to optional addon routes.
- Payment provider views under `resources/views/payment/**`.

Frontend review requirements:

- Preserve Blade view data contracts unless all callers are updated and tested.
- Keep output escaped unless trusted sanitized HTML is explicitly required.
- Do not add queries or aggregates in Blade.
- Check keyboard navigation, labels, focus states, alt text, headings, and button/link semantics for UI changes.

## Database Ownership

Database owns schema state, seed/import behavior, table-model mapping, and migration safety.

Primary paths:

- `database/migrations/**`
- `database/seeders/**`
- `database/factories/**`
- `database/schema/install.sql`
- `app/Models/**` for table mapping, relationships, casts, fillable fields, scopes, and constraints.
- `docs/migration-audit.md`

High-risk database files:

- `database/schema/install.sql`: legacy schema/data bootstrap source. Changes can rewrite baseline installs.
- `database/seeders/DatabaseSeeder.php`: imports the install dump when `settings` is absent.
- `database/migrations/2026_07_01_150000_add_safe_legacy_lookup_indexes.php`: additive lookup indexes across many legacy tables.
- `app/Actions/Install/ImportInstallSqlDump.php`: SQL dump normalization/import behavior.
- `app/Actions/Install/ConfigureDatabase.php` and `PrepareDatabaseConnection.php`: runtime DB configuration flow.
- `app/Models/User.php` and `app/Models/Users.php`: duplicate model surface for `users`.
- Models whose mapped tables are missing locally: `CommonModel`, `FileUploader`, `Fundraiser`, `FundraiserDonation`, `PaidContentCreator`.

Database files that should not be modified casually:

- Existing production migrations.
- `database/schema/install.sql`.
- `database/seeders/DatabaseSeeder.php`.
- `database/database.sqlite` is local runtime state and is not a source file to commit.
- Any model `$table`, `$fillable`, `$casts`, relationship, or accessor that changes persisted behavior.

Database review requirements:

- New migrations must be reversible or explicitly documented as irreversible.
- Destructive changes need backup, deployment, and rollback notes.
- Additive index changes need table/column existence checks in this legacy schema.
- Large data changes need chunking or queued/batched execution.
- Keep SQL dump compatibility in mind until the schema is migrated to first-class migrations.

## DevOps Ownership

DevOps owns dependency metadata, build/runtime configuration, cache behavior, and deployment entrypoints.

Primary paths:

- `composer.json`
- `composer.lock`
- `package.json`
- `package-lock.json`
- `vite.config.js`
- `postcss.config.cjs`
- `phpunit.xml`
- `pint.json`
- `.env.example`
- `.htaccess`
- `public/.htaccess`
- `public/index.php`
- `index.php`
- `artisan`
- `bootstrap/app.php`
- `bootstrap/cache/.gitignore`
- `config/*.php`
- future `.github/workflows/**`

High-risk DevOps files:

- `composer.json` and `composer.lock`: PHP dependency graph, package discovery, autoloaded helpers.
- `package.json` and `package-lock.json`: frontend build/dependency graph.
- `vite.config.js`: current build tool entrypoint.
- `.env.example`: public environment contract.
- `config/app.php`: providers, aliases, helper-loaded package behavior.
- `config/database.php`, `config/filesystems.php`, `config/mail.php`, `config/services.php`, `config/session.php`, `config/queue.php`, `config/sanctum.php`: runtime integration/security behavior.
- `.htaccess`, `public/.htaccess`, `public/index.php`, `index.php`: HTTP entrypoint and server rewrite behavior.

DevOps files that should not be modified casually:

- Lock files unless the task is explicitly a dependency/build update.
- `.env.example` without matching `config()` usage and documentation.
- Runtime config files without cache/deploy verification notes.
- `vite.config.js` or `postcss.config.cjs` without verifying asset build output expectations.
- `phpunit.xml` without understanding test database and environment effects.

DevOps review requirements:

- Dependency changes require `composer validate --strict` or relevant npm build/audit checks when safe.
- Build-tool changes require `npm run build` when feasible.
- Config/cache-sensitive changes require deployment notes for `config:cache`, `route:cache`, `view:cache`, and cache clearing.

## Security Ownership

Security owns request boundaries, authentication, authorization, provider credentials, callbacks, uploads, and secret-handling rules.

Primary paths:

- `app/Http/Middleware/**`
- `app/Http/Requests/**`
- `app/Providers/AuthServiceProvider.php`
- `app/Providers/BroadcastServiceProvider.php`
- `routes/auth.php`
- `routes/channels.php`
- `routes/api.php`
- `routes/payment.php`
- `config/auth.php`
- `config/sanctum.php`
- `config/session.php`
- `config/cors.php`
- `config/services.php`
- `config/filesystems.php`
- `config/mail.php`
- `config/paypal.php`
- `app/Models/payment_gateway/**`
- `app/Http/Controllers/PaymentController.php`
- `app/Http/Controllers/SettingController.php`
- `app/Http/Controllers/Updater.php`
- `app/Http/Controllers/InstallController.php`
- `app/Models/FileUploader.php`
- `.env.example`

High-risk security files:

- `routes/api.php`: many API endpoints rely on controller behavior rather than route-level authenticated groups.
- `routes/custom_routes.php` and `routes/web.php`: include state-changing `GET` routes.
- `app/Http/Middleware/AdminMiddleware.php`, `UserMiddleware.php`, `UserActivity.php`, `VerifyCsrfToken.php`.
- `app/Providers/AuthServiceProvider.php`: currently no policy map.
- `app/Http/Controllers/Updater.php` and installer actions: can modify runtime/addon state.
- `app/Http/Controllers/PaymentController.php` and gateway models: payment/provider verification.
- `app/Models/FileUploader.php` and storage config: upload and S3 behavior.
- `resources/views/**` that expose provider settings, route actions, or unescaped output.

Security files that should not be modified casually:

- Any auth, middleware, session, Sanctum, CORS, mail, filesystem, payment, or provider config.
- Any route or controller that mutates state, deletes data, changes account status, or verifies provider callbacks.
- Any file that touches `.env`, provider keys, storage disks, uploads, or payment history.

Security review requirements:

- No secrets or production values in commits.
- No `env()` outside config files.
- No mass assignment with raw request input.
- No UI-only authorization.
- No raw SQL string concatenation.
- External callbacks need verification, fakes in tests, and failure-path coverage.

## Testing Ownership

Testing owns the project verification contract and fixtures.

Primary paths:

- `tests/**`
- `phpunit.xml`
- `database/factories/**`
- Test-supporting seeders/actions when used by tests.
- `.phpunit.result.cache` is local/generated state and should not be staged.

High-risk testing files:

- `tests/TestCase.php`
- `tests/CreatesApplication.php`
- `phpunit.xml`
- `database/factories/UserFactory.php`
- Installer/payment/story/account tests that protect recent refactors:
  - `tests/Feature/InstallWizardTest.php`
  - `tests/Feature/ImportInstallSqlDumpTest.php`
  - `tests/Feature/ConfigureDatabaseTest.php`
  - `tests/Feature/PaymentControllerGatewayQueryTest.php`
  - `tests/Feature/PaymentPageViewDataTest.php`
  - `tests/Feature/PaypalPaymentStatusTest.php`
  - `tests/Feature/PaystackPaymentStatusTest.php`
  - `tests/Feature/StoryControllerRefactorTest.php`
  - `tests/Feature/AccountDisableRouteTest.php`
  - `tests/Feature/ApiControllerResponseTest.php`

Testing files that should not be modified casually:

- `phpunit.xml`, because it controls test environment and database behavior.
- Shared test base classes.
- Existing regression tests that define current legacy behavior.
- Factories used by many feature tests.

Testing review requirements:

- Refactors must add/update tests for the touched behavior.
- External effects must use fakes.
- Tests should not depend on local `.env`, local database contents, real providers, or manual files.
- If a test is changed because behavior changed, document whether the behavior change was intentional.

## Shared Architecture Ownership

Shared architecture owns module boundaries, cross-cutting providers, global helpers, documentation, and refactor sequencing.

Primary paths:

- `docs/**`
- `AGENTS.md`
- `app/Providers/**`
- `app/Helpers/**`
- `app/ViewModels/**`
- `app/Queries/**`
- `routes/*.php`
- `composer.json` autoload sections

High-risk shared architecture files:

- `AGENTS.md`: agent rules and repository-wide safety contract.
- `docs/project-standards-bible.md`, `docs/refactor-definition-of-done.md`, `docs/module-inventory.md`, `docs/architecture-map.md`, `docs/risk-register.md`.
- `app/Providers/ViewServiceProvider.php`: global data injection and layout query risk.
- `app/Providers/RouteServiceProvider.php`: all route loading and conditional addon loading.
- `app/Helpers/CommonHelper.php` and `app/Helpers/ApiHelper.php`: cross-cutting helper behavior.
- `app/ViewModels/BladeViewData.php`: broad shared view dependency.
- `routes/custom_routes.php`: mixed module routing.

Shared architecture files that should not be modified casually:

- Project rule/docs files that define future agent behavior.
- Route/provider/helper/view-model files used across multiple modules.
- Composer autoload rules.

Shared architecture review requirements:

- Changes must preserve current behavior unless tests prove intentional changes.
- Cross-module extraction should happen one module at a time.
- New shared abstractions need a real repeated pattern, tests, and clear placement.

## Frequently Changed Files

The counts below are from git history filtered to files currently tracked in the repository.

| Changes | File | Primary owner | Notes |
| ---: | --- | --- | --- |
| 18 | `app/Http/Controllers/InstallController.php` | Backend + Database + Security | Installer behavior and setup flow; high-risk despite focused tests. |
| 12 | `tests/Feature/InstallWizardTest.php` | Testing | Core safety net for installer behavior. |
| 8 | `app/Http/Controllers/AdminCrudController.php` | Backend + Security | Large admin surface. |
| 7 | `app/Http/Controllers/PaymentController.php` | Backend + Security | Payment/provider surface. |
| 6 | `app/Http/Controllers/ApiController.php` | Backend + Security | API mega-controller. |
| 6 | `app/ViewModels/BladeViewData.php` | Shared Architecture + Frontend | Shared layout/view data and query risk. |
| 5 | `app/Http/Controllers/MainController.php` | Backend + Frontend + Security | Feed/live/addon user-facing behavior. |
| 5 | `app/Http/Controllers/Profile.php` | Backend + Security | Profile/media/social graph behavior. |
| 5 | `app/Http/Controllers/StoryController.php` | Backend | Story behavior with current tests. |
| 5 | `routes/web.php` | Backend + Security + Shared Architecture | Main web route surface. |
| 4 | `routes/custom_routes.php` | Backend + Security + Shared Architecture | Largest mixed route file. |
| 4 | `app/Providers/ViewServiceProvider.php` | Shared Architecture + Frontend | Global view data injection. |
| 4 | `app/Traits/ZoomMeetingTrait.php` | Backend + Security | Zoom/JWT integration. |
| 4 | `app/Models/payment_gateway/StripePay.php` | Backend + Security | Payment gateway behavior. |
| 4 | `app/Http/Middleware/UserActivity.php` | Backend + Security | Cross-request activity tracking. |
| 4 | domain controllers such as `GroupController`, `PageController`, `MarketplaceController`, `EventController`, `SettingController` | Backend | Shared legacy module surfaces. |
| 3 | `composer.json` / `composer.lock` | DevOps + Shared Architecture | Dependency/autoload changes. |
| 3 | `config/app.php`, `config/session.php`, `config/services.php` | DevOps + Security | Runtime/service/session behavior. |
| 3 | gateway models under `app/Models/payment_gateway/**` | Backend + Security | Payment provider logic. |
| 3 | `routes/payment.php`, `routes/user.php` | Backend + Security | Payment/user dashboard routes. |

## Large Files and Broad Blast Radius

Large files deserve extra review even if they have low recent churn.

| File | Size signal | Owner | Why risky |
| --- | ---: | --- | --- |
| `app/Http/Controllers/ApiController.php` | 7,919 lines | Backend + Security | Many modules and API behaviors in one class. |
| `app/Http/Controllers/AdminCrudController.php` | 1,403 lines | Backend + Security | Admin dashboard and many CRUD workflows. |
| `app/Http/Controllers/MainController.php` | 1,251 lines | Backend + Frontend + Security | Feed, posts, live, addon, AI, and settings behavior. |
| `app/ViewModels/BladeViewData.php` | 1,020 lines | Shared Architecture + Frontend | Global layout/view helper with database access risk. |
| `app/Helpers/CommonHelper.php` | 754 lines | Shared Architecture + Backend | Globally autoloaded helper logic. |
| `routes/custom_routes.php` | 508 lines | Shared Architecture + Security | Mixed web/admin/domain routes and state-changing GET routes. |
| `resources/views/frontend/right_sidebar.blade.php` | 525 lines | Frontend + Shared Architecture | Addon navigation, route dependencies, shared layout state. |
| `resources/views/frontend/notification/notification.blade.php` | 486 lines | Frontend + Security | Notification actions and addon routes. |
| `resources/views/backend/admin/sidebar.blade.php` | 472 lines | Frontend + Security | Admin navigation and authorization visibility. |
| `resources/views/frontend/common_scripts.blade.php` | 430 lines | Frontend | Shared client behavior. |
| `public/assets/**` | 113 MB total | Frontend + DevOps | Vendor/theme/addon bundles committed in repository. |
| `package-lock.json` | 10,704 lines | DevOps | Dependency lockfile; only edit through npm. |
| `database/schema/install.sql` | legacy SQL dump | Database + Security | Baseline install schema/data source. |

## Files That Should Not Be Modified Casually

These files require a clear task, tests or verification, and owner-aware review:

- Route files: `routes/api.php`, `routes/web.php`, `routes/custom_routes.php`, `routes/payment.php`, `routes/user.php`, `routes/auth.php`, `routes/channels.php`.
- Mega-controllers: `ApiController.php`, `AdminCrudController.php`, `MainController.php`, `InstallController.php`, `PaymentController.php`, `Profile.php`, `SettingController.php`, `Updater.php`.
- Security/runtime files: `app/Http/Kernel.php`, `app/Http/Middleware/**`, `app/Providers/AuthServiceProvider.php`, `app/Providers/RouteServiceProvider.php`, `app/Providers/ViewServiceProvider.php`.
- Payment/provider files: `app/Models/payment_gateway/**`, `config/paypal.php`, `resources/views/payment/**`, `routes/payment.php`.
- Database bootstrap files: `database/schema/install.sql`, `database/seeders/DatabaseSeeder.php`, `database/migrations/**`, install actions under `app/Actions/Install/**`.
- Dependency/build files: `composer.json`, `composer.lock`, `package.json`, `package-lock.json`, `vite.config.js`, `postcss.config.cjs`, `phpunit.xml`, `pint.json`.
- Runtime config: `.env.example`, `config/app.php`, `config/database.php`, `config/filesystems.php`, `config/mail.php`, `config/services.php`, `config/session.php`, `config/queue.php`, `config/sanctum.php`.
- Public/vendor-like assets: Bootstrap, Summernote, CKEditor, DataTables, Plyr, Leaflet, paid-content/fundraiser bundles, and standalone `public/js/share.js`.
- Project contracts: `AGENTS.md`, `docs/project-standards-bible.md`, `docs/refactor-definition-of-done.md`, `docs/risk-register.md`, `docs/module-inventory.md`, `docs/architecture-map.md`.

## Multi-Owner Review Rules

Use multi-owner review when a change crosses boundaries:

- Backend + Security: any authorization, account state, admin, API, payment, upload, route method, or callback change.
- Backend + Database: models, scopes, relationships, migrations, SQL dump import, seeders, table mappings.
- Frontend + Security: Blade actions, forms, unescaped output, provider config exposure, admin visibility, payment views.
- Frontend + DevOps: Vite/build changes, generated assets, public vendor/theme bundles.
- DevOps + Security: config, `.env.example`, services, mail, filesystem, session, queue, Sanctum, CORS.
- Testing + any owner: tests changed to match behavior changes, especially where regressions are possible.
- Shared Architecture + any owner: routes, providers, helpers, view models, or docs that affect multiple modules.

## Local-Only and Generated Artifacts

The following may exist locally but should not be treated as source ownership targets:

- `.env`
- `database/database.sqlite`
- `vendor/**`
- `node_modules/**`
- `.phpunit.cache/**`
- `.phpunit.result.cache`
- `.DS_Store` files
- Laravel cache files under `bootstrap/cache/**` other than `.gitignore`

Do not stage these unless the task explicitly changes repository policy for generated/local artifacts.

## Recommended First Ownership Cleanups

1. Add CODEOWNERS-style ownership after the team decides exact people or groups.
2. Split route files by module after characterization tests are in place.
3. Extract payment, installer, media/upload, and notification services before touching broad controllers.
4. Move Blade query/data concerns into controllers, query classes, or ViewModels one page at a time.
5. Decide whether public vendor/addon assets are source of truth, generated output, or third-party vendor copies.
6. Convert the SQL dump baseline into first-class migrations only with a tested rollout and rollback plan.
