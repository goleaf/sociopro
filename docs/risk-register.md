# Risk Register

Generated: 2026-07-01

This register captures current project risks for the legacy Laravel social application. It is report-only and does not change application behavior.

Use this register with:

- `AGENTS.md`
- `docs/project-standards-bible.md`
- `docs/refactor-audit.md`
- `docs/refactor-checklist.md`
- `docs/migration-audit.md`
- `docs/refactor-roadmap-unreal.md`

## Evidence Snapshot

- Current branch before writing: `main` tracking `origin/main`.
- Current app baseline from dependency files: PHP `^8.3`, Laravel `13.18.0`, PHPUnit `12.5.30`, Pint `1.29.3`, Laravel Mix / Webpack frontend build.
- `composer validate --strict`: valid.
- `composer audit --locked`: no security vulnerability advisories found.
- `npm audit --audit-level=moderate`: failed with 11 vulnerabilities, 5 low and 6 moderate, through the Laravel Mix / Webpack dependency chain.
- `php artisan migrate:status --no-interaction`: only `2026_07_01_150000_add_safe_legacy_lookup_indexes` is shown as run in the current migration table.
- Current structure check: no `.github/workflows`, no `app/Jobs`, no `app/Policies`, no `app/Services`; install-specific actions and requests exist.
- Static search still finds `eval()`, `DB::unprepared()`, `DB::select()`, many `DB::table()` calls in controllers/helpers/views, Blade queries, debug `console.log()` statements, state-changing `GET` routes, and partial model `$fillable` / `$casts` coverage.

## Severity and Likelihood

- **Critical**: Could expose execution, secrets, data loss, account takeover, broken deploys, or large production outages.
- **High**: Likely to cause security, data, performance, or maintenance problems if changed or used under load.
- **Medium**: Important debt that increases cost/risk but can be handled after critical surfaces.
- **Low**: Cleanup or documentation risk with limited direct production impact.

Likelihood values are **High**, **Medium**, or **Low** based on current code evidence and expected production exposure.

## Technical Debt Risks

| ID | Risk | Severity | Likelihood | Impact | Owner area | Recommended fix | Safe first step |
| --- | --- | --- | --- | --- | --- | --- | --- |
| TD-001 | Oversized controllers still concentrate validation, authorization, queries, serialization, payments, media, and profile workflows. Evidence includes `ApiController`, `AdminCrudController`, `MainController`, and `Profile`. | High | High | Small changes can cause hidden behavior drift, missing auth, regressions, and slow review. | Backend architecture | Split by domain into Form Requests, policies, actions/services, query objects, and API Resources. | Add characterization tests around one high-risk workflow in `ApiController` before extraction. |
| TD-002 | Global helpers contain database access and business logic, including settings, translations, images, and user lookups. | High | High | Logic is hard to authorize, cache, test, or eager load; views/controllers can hide data access. | Backend platform | Move helper behavior into models, services, actions, ViewModels, or cached settings services. | Inventory helper functions by caller and choose one read-only helper to wrap with tests. |
| TD-003 | Domain service/action structure is incomplete. `app/Actions` currently covers installer flows, while `app/Services` is absent. | Medium | High | New work will keep landing in controllers/models unless clear domain seams exist. | Backend architecture | Add actions/services per domain only when refactoring a tested workflow. | Create a short map of candidate domains: posts, stories, payments, media, notifications, settings. |
| TD-004 | Policies are absent despite many user/content/admin/payment operations. | High | High | Authorization stays scattered in routes/controllers/views and is easy to bypass. | Security/backend | Add model policies and tests for sensitive domains. | Start with one destructive route and add guest/non-owner/owner/admin policy coverage. |
| TD-005 | Models have partial `$fillable` and `$casts` coverage across a large model set. | High | High | Mass assignment, type confusion, and serialization bugs remain likely. | Models/data | Add explicit `$fillable`, `$casts`, `$hidden`, and relationships model by model. | Run a model inventory and add tests before changing the first write-heavy model. |

## Security Risks

| ID | Risk | Severity | Likelihood | Impact | Owner area | Recommended fix | Safe first step |
| --- | --- | --- | --- | --- | --- | --- | --- |
| SEC-001 | `app/Http/Controllers/Updater.php` still contains executable update behavior, including `eval()` and `DB::unprepared()`. | Critical | High | Remote code execution or destructive database mutation if reachable or misconfigured. | Security/platform | Remove or hard-disable web-exposed executable updater paths; replace with deployment-only signed release process. | Add a route/controller test proving updater access is unavailable in normal environments, then disable the route. |
| SEC-002 | Login/session flow still contains demo restore/table mutation behavior in `AuthenticatedSessionController` using `DB::unprepared()` / `DB::select()`. | Critical | Medium | Login could mutate or restore broad database state, damaging production data. | Security/auth | Remove demo restore behavior from runtime auth code and move any demo reset into explicit local-only seeders/commands. | Add a regression test proving login does not execute restore SQL in production-like config. |
| SEC-003 | Many state-changing routes use `GET`, including delete, status, follow/unfollow, mark-read, and environment-toggle routes. | High | High | CSRF, accidental crawler-triggered mutations, and unsafe browser prefetch behavior. | Routing/security | Convert mutations to POST/PATCH/PUT/DELETE with CSRF, method spoofing, named routes, and policies. | Pick one low-blast-radius delete route, add behavior tests, then convert method and forms. |
| SEC-004 | API routes are mostly registered without route-level `auth:sanctum`; only the default `/user` closure is protected. | High | High | Protected-looking API operations can depend on repeated manual token logic and may be inconsistently guarded. | API/security | Group public vs authenticated APIs and use middleware, policies, and resources. | Create an API route inventory and add one guest-denial test for a sensitive endpoint. |
| SEC-005 | Secrets and provider credentials can be rendered to Blade/JavaScript, including Zoom and payment-related values. | Critical | High | Browser exposure of API/provider secrets, payment keys, or meeting credentials. | Security/frontend/payments | Move provider calls server-side, use config-backed secrets, redact admin displays, and avoid browser-visible secret values. | Add view tests proving provider secrets are not rendered. |
| SEC-006 | Debug code remains in Blade/JavaScript and comments, including `console.log()` and old debug snippets. | Medium | High | Sensitive data may leak to browser consoles; production behavior becomes noisy and harder to audit. | Frontend/security | Remove debug code in dedicated frontend cleanup slices. | Inventory debug output by page and remove one page's logs with a focused view/smoke test. |
| SEC-007 | Payment gateway secrets and keys are stored/read through database-backed settings and rendered in admin forms. | High | Medium | Secrets may be stored unencrypted, exposed to over-broad admins, or leaked in logs/views. | Payments/security | Move server secrets to encrypted config or encrypted columns with strict policies and audit logs. | Document each gateway key type and add authorization tests around gateway edit screens. |
| SEC-008 | File upload and media flows are spread across controllers/helpers/models. | High | High | Inconsistent MIME, size, ownership, storage, and deletion rules can allow unsafe uploads or data leakage. | Media/security | Centralize upload validation/storage in Form Requests and media actions/services. | Add tests for invalid file type, oversized upload, and non-owner delete on one media flow. |

## Migration Risks

| ID | Risk | Severity | Likelihood | Impact | Owner area | Recommended fix | Safe first step |
| --- | --- | --- | --- | --- | --- | --- | --- |
| MIG-001 | The legacy schema still lacks a complete reversible Laravel migration chain. Current migration status shows only the safe lookup-index migration. | Critical | High | New environments, rollbacks, and production deploys depend on SQL dumps and manual assumptions. | Database/deployment | Create a verified baseline migration only after comparing install dump, local DB, and production schema. | Produce a schema comparison report before generating any baseline migration. |
| MIG-002 | `public/assets/install.sql` remains the schema bootstrap source. | High | High | Installer and migrations can drift; schema changes are hard to review or roll back. | Database/installer | Keep dump until a tested migration baseline exists, then migrate installer to migrations/seeders. | Add an installer test that documents the current bootstrap contract before changing it. |
| MIG-003 | Destructive migration behavior is not covered by a production migration policy. | High | Medium | Data loss during future type/nullability/index/foreign-key changes. | Database/platform | Require expand-contract migrations, backups, rollback notes, and tests for destructive schema work. | Add a migration checklist entry to future schema PR templates or docs. |
| MIG-004 | Legacy timestamp and foreign-key-like columns have mixed types and nullability. | High | High | Type conversions can silently corrupt data or break relationships. | Database/models | Add data quality reports before type/nullability changes. | Write read-only orphan/type reports for the highest-traffic tables. |

## Database Risks

| ID | Risk | Severity | Likelihood | Impact | Owner area | Recommended fix | Safe first step |
| --- | --- | --- | --- | --- | --- | --- | --- |
| DB-001 | No application foreign keys or cascade rules are documented as active in the legacy schema. | High | High | Orphaned rows, incorrect counts, broken feeds, and unsafe deletes. | Database/data integrity | Add foreign keys in small migrations after orphan cleanup and delete-behavior decisions. | Run orphan reports for posts, comments, media, friendships, notifications, and payments. |
| DB-002 | Many controllers/helpers/views still use `DB::table()` directly. | High | High | Data access bypasses model casts, scopes, relationships, policies, and eager loading. | Backend/data | Move queries into Eloquent relationships, scopes, query objects, and actions. | Start with one view or controller path already covered by tests, then replace with Eloquent. |
| DB-003 | Blade templates still perform database queries and aggregates. | High | High | N+1 queries, slow page renders, hidden dependencies, and null dereferences. | Frontend/backend | Preload data in controllers/ViewModels with eager loads and aggregate helpers. | Pick `backend/user/dashboard.blade.php`, add query/view tests, and pass counts from controller. |
| DB-004 | Some list endpoints/views use unbounded `get()` or broad table reads. | Medium | High | Memory spikes and slow responses as data grows. | Backend/performance | Use pagination, cursor pagination, scopes, and explicit selects. | Inventory top routes with `get()` on large tables and replace one with pagination. |
| DB-005 | Translation helper can write missing phrases during rendering. | Medium | Medium | Render-time writes can create contention, nondeterministic data, and hidden mutations. | Localization/data | Move missing-key management to explicit commands/admin workflow with caching. | Add a test proving a normal page render does not write language rows before changing behavior. |

## Frontend and Accessibility Risks

| ID | Risk | Severity | Likelihood | Impact | Owner area | Recommended fix | Safe first step |
| --- | --- | --- | --- | --- | --- | --- | --- |
| FE-001 | Blade views mix database access, business logic, and rendering. | High | High | Accessibility and UI cleanup becomes risky because markup changes can alter data behavior. | Frontend/backend | Move all data preparation into controllers/ViewModels and keep Blade presentation-only. | Select one high-traffic Blade file and add a render test before moving queries out. |
| FE-002 | Frontend forms and links depend on state-changing GET routes. | High | High | CSRF-correct route migration will break UI unless forms are updated together. | Frontend/routing | Convert links to forms/buttons with CSRF and method spoofing. | Convert one destructive action template with a feature test and view assertion. |
| FE-003 | Accessibility coverage is not visible in tests or docs. | Medium | High | Forms, modals, controls, and dynamic actions may be difficult for keyboard/screen-reader users. | Frontend/accessibility | Add semantic HTML, labels, focus states, keyboard paths, and accessibility smoke tests. | Audit one auth page and one feed/post modal for labels, buttons, focus, and headings. |
| FE-004 | Browser JavaScript includes debug logging and server tokens/config values in some views. | High | Medium | Sensitive data can leak and frontend behavior is harder to secure. | Frontend/security | Remove logs and move provider calls/secrets behind backend endpoints/jobs. | Start with payment and live-streaming views that still render provider configuration. |
| FE-005 | Laravel Mix/Webpack is still the build tool and has npm audit exposure. | Medium | High | Frontend updates and security fixes are constrained by an older build chain. | Frontend/build | Plan a dedicated Mix-to-Vite migration with asset tests and deployment updates. | Add a build inventory documenting current entrypoints before installing Vite. |

## Deployment Risks

| ID | Risk | Severity | Likelihood | Impact | Owner area | Recommended fix | Safe first step |
| --- | --- | --- | --- | --- | --- | --- | --- |
| DEP-001 | CI must remain aligned with installed PHP/Node tooling. | Medium | Medium | Stale workflow versions or missing checks can let regressions through despite automation. | DevOps/QA | Keep `.github/workflows/ci.yml` in sync with Composer, Pint, PHPStan/Larastan, PHPUnit, frontend lint/style/format checks, and Mix build. | Run the same command set locally before changing CI. |
| DEP-002 | Queue infrastructure is not represented by `app/Jobs` or deployment docs. | Medium | High | Slow email/media/payment/provider work may run in HTTP requests and fail unpredictably. | DevOps/backend | Introduce queued jobs with worker deployment instructions and failure handling. | Inventory external/slow flows and choose one idempotent job candidate. |
| DEP-003 | Config, route, and view cache compatibility is not continuously verified. | Medium | Medium | Production deployments may fail after route/config cleanup. | DevOps/platform | Add cache verification to deployment checks once routes/config are stable. | Run cache commands in a clean local pass and document any blockers before enabling CI. |
| DEP-004 | Installer/update/restore surfaces can exist in production-facing code. | Critical | Medium | Production data or code can be mutated by paths that should be deployment-only. | DevOps/security | Remove web access to install/update/restore operations in production and gate installer by environment. | Add route tests proving production cannot reach update/restore handlers. |
| DEP-005 | Rollback strategy for schema, assets, queues, and config is documented but still needs production owner details. | Medium | Medium | Bad deploys may be hard to reverse cleanly if host-specific backup and supervisor commands are unknown. | DevOps/database | Keep `docs/rollback-plan.md` and `docs/backup-and-restore.md` current per release type. | Fill in production backup, process manager, and smoke-check owners before deployment. |

## Package Risks

| ID | Risk | Severity | Likelihood | Impact | Owner area | Recommended fix | Safe first step |
| --- | --- | --- | --- | --- | --- | --- | --- |
| PKG-001 | `npm audit --audit-level=moderate` currently fails with 11 vulnerabilities through Laravel Mix/Webpack-related packages. | Medium | High | Security noise and frontend maintenance drag; some issues have no direct patch in current chain. | Frontend/build | Plan a dedicated frontend tooling migration, likely to Vite, and keep npm audit in CI once actionable. | Document current Mix entrypoints and run `npm run production` before migration planning. |
| PKG-002 | Direct payment packages are numerous: Paytm, Flutterwave, Razorpay, Stripe, PayPal model code, and database-stored gateway keys. | High | Medium | Payment flows are high-risk and package/API drift can break revenue or security. | Payments/platform | Wrap providers behind tested services with faked HTTP clients and clear config ownership. | Build a gateway inventory: package, keys, callbacks, webhooks, tests, and owner routes. |
| PKG-003 | Image stack uses `intervention/image` 2.x. | Medium | Medium | Older image-processing APIs and upload handling can slow modernization and security hardening. | Media/platform | Review upgrade path and isolate image operations behind a media service. | Inventory all image manipulation/file upload call sites before changing packages. |
| PKG-004 | Quality tools are installed but must stay calibrated to legacy code. | Medium | Medium | Overly broad rules or stale baselines can block safe maintenance or hide real regressions. | Tooling/QA | Raise Larastan/PHPStan and frontend lint coverage gradually with small, tested slices. | Fix one module's findings at a time before increasing strictness. |
| PKG-005 | Composer audit is currently clean, but package health and compatibility are still operational risks. | Medium | Medium | Future Laravel/security updates can be blocked by third-party packages. | Platform/dependencies | Maintain a recurring dependency review and upgrade plan. | Add `composer audit --locked` to CI and document temporary exceptions if any appear. |

## Testing Gaps

| ID | Risk | Severity | Likelihood | Impact | Owner area | Recommended fix | Safe first step |
| --- | --- | --- | --- | --- | --- | --- | --- |
| TEST-001 | Current suite is small relative to the application surface: tests focus on auth, install, stories, payments, and selected queries. | High | High | High-risk routes/controllers can regress without signal. | QA/backend | Add characterization tests by domain before refactors. | Prioritize updater/auth restore/API route tests before broad cleanup. |
| TEST-002 | No policy test suite exists because policies are absent. | High | High | Authorization gaps can persist even after UI changes. | QA/security | Add policies and guest/owner/non-owner/admin tests per model. | Add one policy and test set for a destructive content action. |
| TEST-003 | No query-count/performance budget tests exist for dashboard/feed/profile/search pages. | Medium | High | N+1 and aggregate-in-loop regressions can return. | QA/performance | Add query-count smoke tests around high-traffic views after preloading data. | Start with the user dashboard because queries are visible in Blade. |
| TEST-004 | Frontend accessibility is not covered by automated or manual test artifacts. | Medium | High | Forms and dynamic controls can remain inaccessible. | QA/frontend | Add a11y checklist, semantic view tests, and later browser checks for key flows. | Add a checklist-based audit for login, registration, create post, and admin forms. |
| TEST-005 | External provider tests are uneven; PayPal/Paystack have coverage, but broader payment/upload flows need fakes. | High | Medium | Real services may be called or broken behavior may ship untested. | QA/integrations | Use `Http::fake()`, storage fakes, queue fakes, and provider contract tests. | Add fake-backed tests for remaining payment and upload integrations. |

## Unknowns

| ID | Risk | Severity | Likelihood | Impact | Owner area | Recommended fix | Safe first step |
| --- | --- | --- | --- | --- | --- | --- | --- |
| UNK-001 | Production schema may differ from local SQLite/imported install SQL. | Critical | Medium | Migrations/indexes/foreign keys could fail or miss production-only drift. | Database/DevOps | Compare production schema, install dump, and local schema before destructive changes. | Create a read-only schema export checklist and compare table/column/index names. |
| UNK-002 | Production hosting, queue, cache, scheduler, filesystem, and process manager settings are unknown. | High | Medium | Deployment instructions may miss worker restarts, writable paths, storage links, or cache behavior. | DevOps | Document environment topology and required runtime processes. | Fill out an environment inventory: PHP, web server, DB, queue, cache, cron, storage, mail. |
| UNK-003 | Real production data quality is unknown: orphans, duplicate pivots, invalid statuses, mixed timestamp formats, and null owners may exist. | High | High | Schema hardening can fail or silently drop assumptions. | Data/database | Run read-only data quality reports before constraints or type changes. | Create SQL/Eloquent reports for top relationship tables without changing data. |
| UNK-004 | Real traffic and slow-query profile are unknown. | Medium | Medium | Optimization order may target noisy but low-impact code first. | Performance | Measure route/query timings on production-like data before broad performance refactors. | Add local query logging around dashboard/feed/profile smoke tests. |
| UNK-005 | Admin roles and permission boundaries are not fully documented. | High | Medium | Policy work can either over-block legitimate admins or keep privilege escalation paths. | Security/product | Define role capabilities and map every admin action to a policy. | Document current admin route groups and expected actors before policy extraction. |

## Recommended First Implementation Order

1. Add safety tests for updater access, login restore behavior, and one sensitive API route.
2. Disable or remove executable update and demo restore paths.
3. Stop browser exposure of remaining provider secrets in payment and live-streaming views.
4. Convert one destructive `GET` route to a CSRF-protected write route with tests.
5. Move one Blade query-heavy page to controller-preloaded data.
6. Add CI with `php artisan test`, `git diff --check`, Composer validation/audit, and later frontend checks.
7. Produce read-only schema/data quality reports before any foreign key, type, nullability, or baseline migration work.

## Review Cadence

- Revisit this register after each critical or high-risk item is completed.
- Update likelihood after tests, CI, and production topology become clearer.
- Do not remove a risk until the recommended fix is implemented, verified, and linked to a commit or release note.
