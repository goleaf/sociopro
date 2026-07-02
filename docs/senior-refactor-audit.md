# Senior Refactor Audit

Generated: 2026-07-02
Scope: full repository audit for `/Users/andrejprus/Herd/sociopro`
Mode: documentation-only; no production code was refactored in this pass.

This audit reflects the live checkout, not older project memories or historical audit files. The application is not a greenfield rewrite candidate. It already has meaningful guardrails, tests, and modernization work, but the legacy social-network core is still concentrated in large controllers, helpers, Blade templates, and an install SQL dump. The realistic path is a phased, behavior-preserving refactor with tests first.

## 1. Project Overview

| Area | Current finding |
| --- | --- |
| Laravel | `laravel/framework` `13.18.0` from `composer.lock` |
| PHP | `composer.json` requires `^8.3`; local runtime is PHP `8.5.7` |
| Composer | Composer `2.9.5` |
| Node / npm | Node `v22.22.3`, npm `10.9.8` |
| Frontend build | Laravel Mix `6.x` / Webpack `5.x` via `webpack.mix.js`; no `vite.config.*` detected |
| Frontend assets | Tailwind CSS, Alpine.js, Axios, SweetAlert2, Moment Timezone; resource entrypoints are `resources/js/app.js` and `resources/css/app.css` |
| Admin UI | Filament is not installed in this checkout; admin screens are legacy controllers and Blade views |
| Database | `.env.example` and PHPUnit use SQLite; legacy schema is imported from `database/schema/install.sql`; production assumptions still look MySQL-compatible |
| Schema history | 11 additive migrations plus a 2,318-line legacy install dump |
| Test framework | PHPUnit `12.x`; no Pest detected |
| Quality tools | Laravel Pint, PHPStan/Larastan, Rector, ESLint, Stylelint, Prettier, npm audit |
| CI | GitHub Actions at `.github/workflows/ci.yml` for Composer validation/audit, Pint, PHPStan, tests, cache smoke checks, route list, migration fresh, npm quality, and Mix production build |
| Route surface | 503 app routes, 0 unnamed routes, 0 route closures, 77 GET routes with state-changing-looking names/URIs |
| Code size signals | 43 controllers; largest are `ApiController` 7,534 lines, `AdminCrudController` 1,515, `MainController` 1,235 |
| Tests | 82 top-level test files were detected, with additional nested tests under Feature/Unit subdirectories |

### Main Business Modules

- Authentication, account activation, email verification, password reset, session login/logout.
- Social feed: posts, stories, comments, reactions, sharing, memories, media, albums.
- Social graph: friends, followers, blocks, notifications, chat/message threads.
- Communities: groups, pages, events, invitations, memberships.
- Marketplace: products, categories, brands, saves, payments and private downloads.
- Content: blogs, videos, jobs, badges, sponsors, paid content, fundraisers.
- Payments and integrations: Stripe, Razorpay, Paytm, Flutterwave, Paystack, PayPal-style status handling, Zoom/live streaming.
- Install/update/addon flows: installer, SQL import, addon package import/update.
- Admin and user dashboards.

### Detected Architecture Style

The project is a hybrid legacy Laravel application:

- Strong recent guardrails exist: GitHub Actions, Pint, Larastan, PHPUnit, route/security/model audit tests, `.env.example` placeholders, code-quality docs, API idempotency, payment/webhook tests, and some Actions/Queries/Resources/Policies.
- The legacy core remains controller/helper/view driven: `ApiController`, `AdminCrudController`, `MainController`, global helpers, and Blade templates still own too much validation, authorization, query construction, rendering logic, and workflow logic.
- The database layer is still anchored to a legacy SQL dump, with additive safety migrations layered on top. This is workable for now, but it is not a senior production schema workflow until a baseline migration and production data cleanup plan exist.

### High-Risk Areas

- State-changing GET routes in web/admin/user flows.
- Limited policy coverage for sensitive social, media, group, event, admin, and payment actions.
- Queries and aggregates in Blade templates.
- Raw `DB::table()` usage across helpers, controllers, models, and Blade.
- Legacy schema bootstrap via `database/schema/install.sql`.
- Large god controllers that make security and regression review difficult.
- File upload/download logic split between newer support classes and legacy controller/helper paths.
- Public asset tree is large and legacy; frontend build only covers a small modern entrypoint.

### Missing or Stale Documentation

- A single current architecture map exists, but several older audit docs still describe earlier Laravel versions or past state.
- Deployment docs exist, but production ownership details are still not fully verifiable from the repository: process supervisor, queue worker topology, scheduler owner, backup tooling, RTO/RPO, domain/CORS origin list.
- There is no production database diff report comparing real data against the new constraint migrations.

### Missing Tests

- Behavior tests for the majority of `ApiController` endpoints.
- Authorization matrix tests for posts, comments, stories, media, albums, groups, events, pages, jobs, badges, sponsors, admin CRUD, paid content, fundraisers, and chat.
- Regression tests around all state-changing GET routes before converting them to POST/DELETE.
- Query-count tests for feed, search, profile, groups, events, chat, notifications, and admin lists.
- Browser/accessibility tests for Blade forms, modals, dropdowns, and dynamic components.
- Production-like migration tests against MySQL-compatible behavior, locks, and data cleanup.

## 2. Code Quality Problems

| ID | Issue | Severity | Risk | Exact files involved | Why it matters | Safest first fix | Tests needed before refactor? | Change now? |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| CQ-01 | God API controller | High | Maintainability, security | `app/Http/Controllers/ApiController.php` | 7,534 lines mix validation, auth, queries, serialization, uploads, payments, and social workflows. Small changes are hard to prove safe. | Add endpoint contract tests, then split one domain at a time into controllers, Form Requests, Actions, Queries, and API Resources. | Yes | Document only |
| CQ-02 | God admin controller | High | Maintainability, authorization | `app/Http/Controllers/AdminCrudController.php` | 1,515 lines centralize unrelated admin CRUD, deletes, payment history, category, brand, group, and job operations. | Extract one admin resource at a time; keep route names; add policy-backed authorization and feature tests. | Yes | Document only |
| CQ-03 | Feed/profile controller business logic | High | Maintainability, performance | `app/Http/Controllers/MainController.php`, `app/Http/Controllers/Profile.php`, `app/Http/Controllers/GroupController.php` | Controllers build queries, manipulate media, and coordinate workflows directly. | Move workflows to `app/Actions`, repeated filters to scopes/query objects, and upload/delete logic to services. | Yes | Document only |
| CQ-04 | Queries and logic in Blade | High | Performance, maintainability | `resources/views/frontend/album_details/album_details.blade.php`, `resources/views/frontend/profile/album_details.blade.php`, `resources/views/frontend/search/searchview.blade.php`, `resources/views/frontend/search/group.blade.php`, `resources/views/frontend/events/single_event.blade.php`, `resources/views/frontend/events/event_invite_modal.blade.php`, `resources/views/frontend/main_content/*`, `resources/views/backend/admin/blog/*.blade.php`, `resources/views/backend/admin/page/*.blade.php` | Views issue database queries, counts, path calculations, and conditional domain logic, creating N+1 risks and making rendering hard to test. | Preload data in controllers/ViewModels; pass typed arrays/DTOs; convert repeated fragments to components. | Yes for each page | Document only |
| CQ-05 | Helper/static abuse | Medium | Maintainability, security | `app/Helpers/CommonHelper.php`, `app/Helpers/ApiHelper.php`, composer autoload `files` | Global helpers contain DB reads/writes, language insertion, upload pathing, URL fetching, settings lookup, and image helpers. | Move side-effect helpers to services/actions and keep globals pure. | Partial | Document only |
| CQ-06 | Domain writes inside models | Medium | Maintainability, data integrity | `app/Models/Badge.php`, `app/Models/Sponsor.php` | Models write payment histories and mutate related tables via `DB::table()`, hiding workflows from controllers/tests. | Extract badge/sponsor payment actions with transactions and tests. | Yes | Document only |
| CQ-07 | Unbounded `::all()` calls | Medium | Performance | `app/Http/Controllers/AdminCrudController.php` lines around category/brand/group/job category loads | `Pagecategory::all()`, `Category::all()`, `Brand::all()`, `Group::all()`, and `JobCategory::all()` can grow without bounds. | Use `query()->select([...])->orderBy(...)->paginate()` or bounded lists where UI requires all options. | Yes for view output | Can change in focused phase |
| CQ-08 | Inconsistent legacy naming | Medium | Maintainability | `app/Models/Users.php`, `app/Models/User.php`, legacy tables like `message_thrades`, `batchs`, `blogcategories` | Parallel user models and legacy table names raise confusion and drift risk. | Make `User` canonical; deprecate `Users`; document unavoidable table-name compatibility. | Yes | Document only |
| CQ-09 | Untyped legacy methods | Medium | Maintainability | Large controllers, helpers, some models | Modern PHP types are partial; static analysis is intentionally low level. | Add parameter/return types only while touching covered files; raise Larastan level gradually. | Yes for changed behavior | Document only |
| CQ-10 | Dead/commented code candidates | Low | Maintainability | `app/Helpers/ApiHelper.php`, `app/Http/Controllers/ApiController.php`, public legacy assets | Some commented legacy queries and redundant imports remain. | Remove only inside tested slices; keep docs listing candidates. | No for pure dead comments, yes near logic | Document only |
| CQ-11 | Formatting baseline mostly good | Low | Maintainability | `pint.json`, `composer.json`, CI | Pint is installed and scripted; future drift is covered. | Keep Pint in `composer ci` and GitHub Actions. | No | Already in place |

## 3. Laravel Best-Practice Gaps

| ID | Issue | Severity | Risk | Exact files involved | Why it matters | Safest first fix | Tests needed before refactor? | Change now? |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| LB-01 | Missing Form Requests across legacy domains | High | Security, maintainability | `app/Http/Controllers/ApiController.php`, `MainController.php`, `AdminCrudController.php`, `GroupController.php`, `Profile.php` | Some modules have requests, but many writes still validate inline or rely on controller conditionals. | Add Form Requests per endpoint with `authorize()`, `prepareForValidation()`, rules, messages, and validation tests. | Yes | Document only |
| LB-02 | Thin-controller pattern is only partial | High | Maintainability | Same large controllers plus route files | Modern Actions/Queries exist but are not yet the dominant pattern. | Start with one mature pattern, such as marketplace, then repeat per domain. | Yes | Document only |
| LB-03 | Policy coverage is too narrow | High | Security | `app/Policies/MarketplacePolicy.php`, `app/Policies/PagePolicy.php`; missing policies for posts/comments/media/groups/events/jobs/admin/payment/chat | Sensitive actions still rely on inline checks or UI hiding. | Add model policies and matrix tests per resource; enforce in controllers and requests. | Yes | Document only |
| LB-04 | API Resources only cover part of API | Medium | Security, maintainability | `app/Http/Resources/Api/*`, `app/Http/Controllers/ApiController.php` | Hand-built arrays can leak fields or break response consistency. | Add contract tests, then Resources per endpoint; preserve public response shapes. | Yes | Document only |
| LB-05 | Route model binding underused | Medium | Security, maintainability | `routes/*.php`, large controllers taking `{id}` | Raw IDs force manual lookup and make ownership scoping inconsistent. | Convert by domain to scoped route model binding plus policy checks. | Yes | Document only |
| LB-06 | Route verbs are unsafe in many places | High | Security, deployment | `routes/web.php`, `routes/user.php`, `routes/custom_routes.php`, `routes/payment.php` | 77 GET routes look state-changing by URI/name, so CSRF and crawler/prefetch side effects are possible. | Add regression tests, then change mutators to POST/PATCH/DELETE with CSRF while preserving route names where possible. | Yes | Document only |
| LB-07 | Queue usage is minimal | Medium | Performance, reliability | `app/Jobs/ImportAddonPackageJob.php`, `app/Jobs/ImportInstallSqlDumpJob.php`, payment/mail/upload workflows | Slow work appears to run synchronously in many controllers/helpers. | Move email, image processing, exports/imports, external API calls, and webhook side effects into idempotent jobs. | Yes | Document only |
| LB-08 | Cache invalidation standards are incomplete | Medium | Performance, correctness | `app/Models/User.php`, helpers, docs | Cache use exists but no universal invalidation model is visible. | Add domain-specific cache keys with context and TTLs; use observers/events only where justified. | Yes for permission-sensitive cache | Document only |
| LB-09 | Logging standards are partial | Medium | Security, operations | `app/Support/Logging/*`, `docs/logging-sensitive-data-audit.md`, payment/webhook code | Sensitive log redaction has tests, but operational logging conventions are not fully enforced per module. | Standardize structured logs and redaction rules around payments, auth, webhooks, uploads. | Yes for redaction | Document only |
| LB-10 | `env()` usage is mostly clean | Low | Deployment | `config/*`, `.env.example`, scans of `app/` | Runtime `env()` abuse was not found as a major issue; keep it guarded. | Continue requiring config entries and `.env.example` placeholders for new keys. | No | Already acceptable |

## 4. Security Risks

| ID | Issue | Severity | Risk | Exact files involved | Why it matters | Safest first fix | Tests needed before refactor? | Change now? |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| SEC-01 | State-changing GET routes | Critical | Security | `routes/web.php`, `routes/user.php`, `routes/custom_routes.php`, `routes/payment.php`; callers in Blade/JS | GET mutators bypass Laravel CSRF protection and can be triggered by crawlers, previews, prefetch, or embedded images. | Characterize current behavior, then migrate to POST/PATCH/DELETE with `@csrf`; keep redirects/flash messages. | Yes | Document only |
| SEC-02 | Incomplete authorization / IDOR risk | Critical | Security | Controllers for posts, comments, media, albums, groups, events, chat, admin, jobs, pages, payment history | Raw IDs and limited policy coverage can allow cross-user access if any inline check is missed. | Add policies and owner/non-owner/admin tests per resource before route/controller refactors. | Yes | Document only |
| SEC-03 | Raw database access outside model scopes | High | Security, maintainability | `app/Helpers/ApiHelper.php`, `app/Helpers/CommonHelper.php`, `app/Http/Controllers/*`, `app/Models/Badge.php`, `app/Models/Sponsor.php`, multiple Blade views | The project rule forbids raw SQL/DB access outside model internals; current code still has many `DB::table()` sites. | Replace with Eloquent scopes/query objects/actions per domain. | Yes | Document only |
| SEC-04 | Raw HTML rendering | High | Security, frontend | `resources/views/frontend/settings/*.blade.php`, `resources/views/frontend/marketplace/single_product.blade.php`, `resources/views/frontend/blogs/single_blog.blade.php`, `resources/views/frontend/groups/bio.blade.php`, `resources/views/frontend/events/single_event.blade.php`, `resources/views/frontend/profile/profile_info.blade.php` | `{!! script_checker(..., false) !!}` renders trusted rich text but is unsafe if writers are compromised or permissions broaden. | Choose an HTML sanitizer policy, sanitize on write or render, and add XSS payload tests. | Yes | Can change in focused security phase |
| SEC-05 | Chat rendering needs continued XSS coverage | Medium | Security | `resources/views/frontend/chat/single-message.blade.php`, chat controllers/tests | Current chat message output uses `nl2br(e(...))`, which is safer than raw output, but this is a high-value regression target. | Keep/expand chat XSS tests and forbid future raw rendering. | Yes | Already partly mitigated |
| SEC-06 | File uploads split across paths | High | Security | `app/Support/Files/FileUploader.php`, `app/Helpers/CommonHelper.php`, `ApiController.php`, `Profile.php`, `ChatController.php`, media/download actions | Central validation exists, but legacy helpers/controllers still handle files and paths directly. | Route every upload/download through the centralized service/action with Storage fakes and authorization tests. | Yes | Document only |
| SEC-07 | Directory permissions in upload helper | High | Security | `app/Helpers/CommonHelper.php` | Legacy helper creates directories with broad permissions in some paths. | Tighten permissions and centralize upload directory creation. | Yes for upload flows | Focused phase |
| SEC-08 | Unsafe server-side URL fetching risk | High | Security | `app/Helpers/CommonHelper.php`, `app/Support/Http/ServerSideUrl.php`, `.env.example` URL allowlist settings | Link-preview style fetches can become SSRF if any call path bypasses `ServerSideUrl`. | Ensure all fetches go through `ServerSideUrl`, restrict schemes/hosts in production, and test private IP blocking. | Yes | Focused phase |
| SEC-09 | CORS defaults are permissive | High | Security | `.env.example`, `config/cors.php` | `CORS_ALLOWED_ORIGINS=*` is acceptable for public token APIs only; risky for authenticated browser flows. | Document production origins and require explicit allowlist outside local/demo. | Config tests recommended | Focused deployment/security phase |
| SEC-10 | Sessions/cookies need production assertion | Medium | Security | `.env.example`, `config/session.php`, docs/session-cookie-security.md | `SESSION_SECURE_COOKIE=false` in example is fine for local, but production must force HTTPS cookies and safe same-site behavior. | Add deployment checklist assertion and config tests for prod env. | Yes for config assertions | Document/phase |
| SEC-11 | Webhook coverage is partial | Medium | Security | `app/Http/Controllers/PaymentController.php`, payment services, `tests/Feature/PaymentWebhookSecurityTest.php`, docs/webhook-audit.md | Paystack replay/signature settings exist, but every provider callback needs signature/idempotency verification. | Add provider-by-provider contract tests with `Http::fake()`/SDK fakes. | Yes | Document only |
| SEC-12 | Unsafe redirect/modal path risks need review | Medium | Security | `app/Http/Controllers/ModalController.php`, routes containing dynamic `{view_path}`, redirect helpers | Dynamic view/redirect parameters can become open redirect or local view disclosure bugs if not allowlisted. | Add allowlists and tests before changing view paths. | Yes | Document only |
| SEC-13 | Sensitive admin/payment fields | Medium | Security | payment gateway controllers/views/models, `.env.example`, `config/*` | Payment credentials are represented in database/config paths; leakage through logs or views must be prevented. | Verify hidden fields/resources/log redaction; add tests for responses and logs. | Yes | Document only |
| SEC-14 | API token and rate-limit coverage is partial | Medium | Security | `app/Providers/RouteServiceProvider.php`, API middleware, Sanctum config, `tests/Feature/ApiTokenAbilityTest.php`, `ApiRateLimitAuditTest.php` | Guardrails exist, but every sensitive endpoint must declare required token ability and throttling expectations. | Add endpoint-by-endpoint token ability matrix. | Yes | Document only |
| SEC-15 | APP_DEBUG/debug exposure guarded but must stay enforced | Low | Security, deployment | `.env.example`, `tests/Feature/ProductionDebugInstrumentationTest.php`, `SecretLeakAuditTest.php` | No obvious debug instrumentation in app code, but this is a release blocker if it regresses. | Keep tests in CI and production checklist. | No | Already guarded |

## 5. Database and Migration Risks

| ID | Issue | Severity | Risk | Exact files involved | Why it matters | Safest first fix | Tests needed before refactor? | Change now? |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| DB-01 | Schema source is a legacy SQL dump | Critical | Deployment, data-loss | `database/schema/install.sql`, `app/Actions/Install/ImportInstallSqlDump.php`, installer tests | Install/migrate behavior is not fully represented by Laravel migrations; production diffs are hard to review. | Generate a reviewed baseline migration from the current schema and freeze the dump as installer legacy input. | Yes | Document only |
| DB-02 | Foreign keys are incomplete | High | Data integrity | `database/migrations/2026_07_02_150000_add_safe_legacy_foreign_key_constraints.php`, models, schema dump | Additive FK migration exists, but constraints must be enabled only when production data is clean. | Run data audit, document violations in `docs/data-cleanup-needed.md`, add one constraint group at a time. | Yes | Document only |
| DB-03 | Unique constraints are partial | High | Data integrity | `database/migrations/2026_07_02_160000_add_safe_legacy_unique_constraints.php`, validation rules | Validation-only uniqueness can race under concurrency. | Add database constraints after duplicate cleanup and validation tests. | Yes | Document only |
| DB-04 | Nullable/default cleanup is partial | Medium | Data integrity | `2026_07_02_170000_add_safe_legacy_nullable_column_constraints.php`, schema dump | Tightening nullability can break dirty production data if not audited. | Keep expand-and-contract; document cleanup SQL/Laravel commands. | Yes | Document only |
| DB-05 | Money precision needs production audit | High | Data integrity | `2026_07_02_180000_add_safe_legacy_money_precision_constraints.php`, `app/Support/Money/Money.php`, payment models | Payment/marketplace/fundraiser amounts must not use unsafe floats or inconsistent precision. | Standardize decimal/minor-unit handling per table and add rounding tests. | Yes | Document only |
| DB-06 | JSON/date constraints need cleanup | Medium | Data integrity | `2026_07_02_190000_add_safe_legacy_datetime_column_constraints.php`, `2026_07_02_200000_add_safe_legacy_json_column_constraints.php` | Legacy string dates/JSON need shape guarantees before constraints. | Backfill invalid rows, add casts and request validation. | Yes | Document only |
| DB-07 | Missing/partial indexes | High | Performance | index migrations, `docs/database-indexes.md`, query-heavy controllers/views | Recent index migrations help, but Blade/controller queries still reveal hot filters that need production EXPLAIN. | Profile routes, add justified indexes only after checking duplicates. | Query-count tests recommended | Document only |
| DB-08 | Unbounded list queries | Medium | Performance | `AdminCrudController.php`, Blade DB queries, helpers | `all()`/unbounded `get()` can become memory and latency issues. | Paginate lists and chunk background work. | Yes for output | Focused phase |
| DB-09 | N+1 from Blade and helpers | High | Performance | `resources/views/frontend/*`, `CommonHelper.php`, `ApiHelper.php`, feed/search/profile/event views | Relationship/count lookups inside loops multiply query count. | Eager load and precompute counts via `withCount()`/`withExists()`. | Query-count tests | Document only |
| DB-10 | Soft delete consistency unknown | Medium | Data integrity | models, `docs/soft-delete-audit.md`, migrations | If `deleted_at` columns and `SoftDeletes` traits diverge, restore/delete behavior is inconsistent. | Audit model-by-model and add delete/restore tests where soft deletes are intended. | Yes | Document only |
| DB-11 | Transaction boundaries are incomplete | High | Data integrity | payment, marketplace, media, friend/group/event workflows | Multi-table writes without transactions can leave partial state. | Extract actions and wrap writes in `DB::transaction`; dispatch after commit. | Yes | Document only |
| DB-12 | Legacy naming inconsistency | Low | Maintainability | tables `batchs`, `message_thrades`, category pluralization, model table mappings | Renames are risky and not worth immediate data-loss risk. | Keep compatibility mappings; document future expand-and-contract renames only if business value exists. | Yes for any rename | Document only |

## 6. Frontend Quality Risks

| ID | Issue | Severity | Risk | Exact files involved | Why it matters | Safest first fix | Tests needed before refactor? | Change now? |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| FE-01 | Laravel Mix/Webpack is legacy stack | Medium | Deployment, maintainability | `webpack.mix.js`, `package.json`, `resources/js/app.js`, `resources/css/app.css` | Mix works but the Laravel ecosystem has moved to Vite; docs already reference Vite concepts. | Plan a separate Mix-to-Vite migration with asset manifest tests and visual smoke checks. | Yes | Document only |
| FE-02 | Blade semantics/accessibility are inconsistent | High | Frontend, accessibility | `resources/views/frontend/**`, `resources/views/backend/**`, forms/modals/nav partials | Legacy markup has many anchor-as-button patterns, inline handlers, missing form semantics, and likely heading/order issues. | Page-by-page accessibility refactor after behavior tests; preserve CSS hooks. | Yes for critical forms | Document only |
| FE-03 | Queries in views | High | Performance, maintainability | Same Blade files listed in CQ-04 | Data access in views blocks frontend cleanup and creates N+1s. | Move all query data to controllers/ViewModels first. | Yes | Document only |
| FE-04 | Raw HTML/rich text rendering | High | Security | Files listed in SEC-04 | Stored XSS risk for rich content. | Define sanitizer and test malicious payloads. | Yes | Focused security phase |
| FE-05 | Duplicated Blade markup | Medium | Maintainability | post modals, search views, admin CRUD forms, event/group/page views | Duplicated forms and UI fragments make accessibility fixes inconsistent. | Extract anonymous components with clear props and slots. | Rendering tests recommended | Document only |
| FE-06 | SCSS/CSS architecture is not modular | Medium | Frontend | `resources/css/app.css`, `public/assets/**` | Modern entrypoint is tiny while legacy public assets are large and global; design tokens are not consistently centralized. | Introduce tokens/utilities in resources CSS/SCSS, then retire proven-unused legacy assets. | Visual/build tests | Document only |
| FE-07 | JavaScript global/inline behavior | Medium | Security, accessibility | Blade `onclick`/`javascript:void(0)` usage, `public/assets/**`, `resources/js/app.js` | Inline handlers and legacy global scripts make keyboard/accessibility/security fixes harder. | Move page behavior to ES modules and delegated listeners; add CSRF-aware fetch helper. | Yes | Document only |
| FE-08 | Unsafe DOM injection needs continued audit | Medium | Security | legacy public JS assets, dynamic Blade snippets | Vendor/legacy scripts contain `innerHTML`/DOM writes; user-data paths must be distinguished from static template writes. | Audit only first-party dynamic paths; avoid mass-editing minified/vendor files. | Yes | Document only |
| FE-09 | Oversized/legacy frontend bundle risk | Medium | Performance | `public/assets/**`, `package-lock.json`, Mix output | Large committed assets and old dependencies can slow load/build and increase audit noise. | Measure bundle, remove duplicates only with route/page evidence. | Build and visual checks | Document only |

## 7. Testing Gaps

| ID | Issue | Severity | Risk | Exact files involved | Why it matters | Safest first fix | Tests needed before refactor? | Change now? |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| TST-01 | Coverage is guardrail-heavy, behavior-light | High | Testing, maintainability | `tests/Feature/*AuditTest.php`, `ApiControllerResponseTest.php`, legacy controllers | Many tests assert standards and absence of bad patterns, but not full user behavior for legacy modules. | Add characterization tests for one domain before each refactor. | Yes | Document only |
| TST-02 | Authorization matrix gaps | Critical | Security, testing | Missing tests for posts/comments/media/groups/events/chat/admin/payment domains | IDOR prevention cannot be trusted without guest/non-owner/owner/admin tests. | Add matrix tests per policy/resource. | Yes | Document only |
| TST-03 | Validation test gaps | High | Security, testing | write endpoints without Form Requests | Legacy inline validation can regress silently. | Create Form Request tests and feature validation assertions. | Yes | Document only |
| TST-04 | API contract gaps | High | Deployment, testing | `ApiController.php`, mobile/API clients | Response shape changes can break clients. | Snapshot/current-shape tests before Resource refactors. | Yes | Document only |
| TST-05 | Database behavior gaps | Medium | Data integrity | migrations, model relationships, factories | Safe migrations exist, but production-like data constraints are not fully exercised. | Add relationship, uniqueness, soft-delete, transaction rollback tests. | Yes | Document only |
| TST-06 | File upload/download gaps outside covered paths | High | Security, testing | chat/media/profile/album/story/marketplace/job uploads | Some upload/download security tests exist; coverage needs every file surface. | Use `Storage::fake()` per upload surface and assert unauthorized failures. | Yes | Document only |
| TST-07 | External integration gaps | Medium | Security, reliability | payment/Zoom/webhook services | Some payment tests exist; every provider callback needs fake-based signature/idempotency tests. | Add provider-by-provider fakes; never call real services. | Yes | Document only |
| TST-08 | Frontend/a11y tests missing | Medium | Frontend, testing | Blade views, modals, forms | Accessibility regressions are currently manual. | Add feature tests for labels/errors/escaped output; consider browser tests only after stack is stable. | Yes where practical | Document only |
| TST-09 | Performance regression tests are sparse | Medium | Performance, testing | feed/search/profile/group/event/list endpoints | N+1 and unbounded query regressions need query-count coverage. | Add route-level query-count tests around hot pages. | Yes | Document only |

## 8. CI and Deployment Gaps

| ID | Issue | Severity | Risk | Exact files involved | Why it matters | Safest first fix | Tests needed before refactor? | Change now? |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| CI-01 | CI is strong but must stay representative | Medium | Deployment | `.github/workflows/ci.yml`, `composer.json`, `package.json` | CI runs SQLite and local build checks; production may use MySQL and real queues. | Add optional MySQL service job or scheduled production-like migration smoke test. | No | Document only |
| CI-02 | Static analysis level is intentionally low | Medium | Maintainability | `phpstan.neon`, `composer analyse` | Level 1 is safe for legacy code but leaves type issues undiscovered. | Raise level gradually after adding types/baseline. | Yes for fixes | Document only |
| CI-03 | Frontend build checks cover resources only | Medium | Frontend, deployment | npm scripts, `public/assets/**`, Mix config | ESLint/Stylelint/Prettier cover `resources`, not the full legacy public asset tree. | Keep public vendor assets out of mass linting; migrate first-party JS/CSS into resources. | Build/visual checks | Document only |
| CI-04 | Migration safety is SQLite-first | Medium | Deployment, data-loss | CI migration step, migrations, schema dump | SQLite does not reveal all MySQL lock/index/FK behavior. | Add MySQL-compatible migration rehearsal before production schema changes. | Yes | Document only |
| CI-05 | Queue restart/scheduler ownership not verifiable | Medium | Deployment | `docs/deployment-checklist.md`, production process manager not in repo | The app will need workers once slow work is queued. | Document supervisor/systemd/Laravel Cloud/Horizon equivalent when target infra is known. | No | Document only |
| CI-06 | Rollback and backup docs need rehearsal evidence | Medium | Deployment, data-loss | `docs/rollback-plan.md`, `docs/backup-and-restore.md` | Docs exist, but no evidence of restore drills or migration rollback drills is committed. | Add restore-drill checklist and record non-secret evidence per release. | No | Document only |
| CI-07 | Secrets policy mostly documented | Low | Security | `.env.example`, `SecretLeakAuditTest`, CI env | Placeholders are good; keep scans/tests active. | Add new env keys only with config entries and placeholder examples. | No | Already guarded |

## 9. Command Results From This Audit Pass

These checks were selected because they are safe for a documentation-only audit and already supported by the repository tooling.

| Command | Result | Notes |
| --- | --- | --- |
| `git status --short --branch` | Passed | Only the two requested docs are modified. |
| `php artisan route:list --except-vendor --json` | Passed | Detected 503 routes, 0 unnamed routes, 0 closures, and 77 state-changing-looking GET routes. |
| `composer validate --strict --no-interaction` | Passed | Covered by `composer ci`; `composer.json` is valid. |
| `composer audit --no-interaction` | Passed | Covered by `composer ci`; no Composer security advisories reported. |
| `vendor/bin/pint --test` | Passed | Covered by `composer ci`; Pint reported `{"tool":"pint","result":"passed"}`. |
| `composer analyse` | Passed | Covered by `composer ci`; PHPStan/Larastan scanned 361 files with no errors. |
| `php artisan test` | Passed | Covered by `composer ci`; 496 tests passed with 14,480 assertions in 40.43s. |
| `npm run quality` | Passed | Covered by `composer ci`; ESLint, Stylelint, Prettier check, production npm audit, and Mix production build passed. |
| `composer quality:cache` | Passed | Covered by `composer ci`; config, route, and view cache smoke checks passed and caches were cleared. |
| `git diff --check` | Passed | No whitespace errors were reported after the docs update. |

## 10. Safest Immediate Work Order

1. Keep this audit and `docs/senior-refactor-roadmap.md` as the current planning baseline.
2. Do not begin broad refactors until Phase 1 characterization tests exist for the target domain.
3. Prioritize security-critical but testable slices: state-changing GET route migration, policy/IDOR coverage, file upload/download consolidation, and rich-text sanitization.
4. Use the mature marketplace pattern as the template: Form Requests, Query object, API Resource, Policy, feature tests.
5. Treat database changes as production projects: audit real data first, then add additive migrations and rollback notes.
