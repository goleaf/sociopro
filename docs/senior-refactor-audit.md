# Senior Refactor Audit

Generated: 2026-07-02
Scope: full repository (`/Users/andrejprus/Herd/sociopro`)
Author role: principal Laravel architect / security / database / frontend-a11y / DevOps / tech lead

This is a **report-only** audit. No production runtime code was rewritten to produce it. Two low-risk actions were taken and are called out explicitly:

1. An **injected, non-project route** was found in the working tree and reverted (see [Section 0](#0-integrity-finding-injected-route)).
2. Two new documentation files were added: this audit and `docs/senior-refactor-roadmap.md`.

> This project already carries ~40 prior audit documents under `docs/` and has been partially modernized (Laravel 13, Pint, PHPStan/Larastan, 400+ tests). This audit reflects the **actual current state**, not a greenfield legacy assumption. Where earlier docs (e.g. `docs/refactor-audit.md`, which still says "Laravel 9.52") are now stale, that is noted.

---

## 0. Integrity finding: injected route

**Severity: critical · Risk: security**

While running the test suite, `routes/web.php` in the working tree contained a route that was **not present in the committed file** and is **not legitimate project code**:

```php
Route::get('/__fork-safety-test', function () {
    $client = stream_socket_client('ssl://'.config('mail.mailers.smtp.host').':'.config('mail.mailers.smtp.port'), $errno, $errstr, 10);
    if (! $client) {
        return response("connect failed: $errno $errstr", 500);
    }
    return response('SMTP banner: '.fgets($client));
});
```

- Unauthenticated closure with logic, no middleware, no name.
- Opens an outbound SSL socket to the configured SMTP host/port and returns the banner to any caller — an SSRF / network-probe gadget.
- It broke three `RouteAuditTest` guardrail tests (unnamed route, closure-with-logic, route-file logic).

**Action taken:** reverted `routes/web.php` to its committed state (`git checkout -- routes/web.php`). After reverting, `RouteAuditTest` passes (6 tests) and the full suite returns to green. This artifact was **not** committed.

**Why it matters:** if such a route ever reaches production it is a remote information-disclosure / SSRF endpoint. **Safest first fix:** confirm the source of the injection (CI harness, editor plugin, or manual paste), keep `RouteAuditTest` as the guard, and treat any unexplained working-tree change to `routes/*` as a release blocker. **Tests needed before refactor:** already covered by `RouteAuditTest`. **Change now or document:** already reverted.

---

## 1. Project overview

| Aspect | Finding |
| --- | --- |
| **Laravel version** | `13.18.0` (from `composer.lock`) |
| **PHP requirement** | `^8.3`; local runtime is **PHP 8.5.7** (Herd) |
| **Composer** | 2.9.5 |
| **Node / npm** | Node 22.22.3 / npm 10.9.8 |
| **Frontend build** | **Laravel Mix 6 / Webpack 5** (`webpack.mix.js`). Not Vite. |
| **CSS/JS stack** | Tailwind 3, Alpine 3, Axios, SweetAlert2, moment-timezone. `resources/css/app.css` (Tailwind only); no SCSS tree in `resources/`. Large legacy CSS/SCSS lives under `public/assets/`. |
| **Database driver** | `sqlite` in `.env.example`; test DB `:memory:`. Production target is MySQL (code uses `SHOW TABLES`, `Tables_in_*`). |
| **Schema source** | **`public/assets/install.sql` (127 KB dump)** bootstraps the schema, not migrations. Only 10 additive "safe legacy" migrations exist in `database/migrations/`. |
| **Test framework** | PHPUnit 12 (not Pest). Full-suite status must be verified per slice; this repo has broad legacy coverage plus focused security/upload regression tests. |
| **Quality tooling** | Pint (laravel preset) ✔ passing; PHPStan + Larastan level 1 ✔ passing; Rector configured; `composer validate --strict` ✔. |
| **CI/CD** | GitHub Actions workflow exists at `.github/workflows/ci.yml` for Composer validation, Pint, PHPStan/Larastan, tests, frontend lint/style/format checks, and Mix production build. |
| **Deployment docs** | Extensive `docs/` set plus consolidated `docs/deployment-checklist.md`, `docs/rollback-plan.md`, and `docs/backup-and-restore.md`. |

### Main business modules

Social network platform ("Sociopro"): **timeline/posts, stories, comments/reactions, friends & follows, chat, groups, pages, events, blogs, marketplace, videos, jobs, fundraisers, paid content, badges, sponsors, notifications, memories, live streaming (Zoom), payments** (Stripe, Razorpay, Paytm, Flutterwave, Paystack), an **install wizard**, and an **admin/addon updater**.

### Detected architecture style

**Hybrid: modern layering bolted onto a legacy god-controller core.**

- Modern, well-structured elements: `app/Actions`, `app/Services` (Payments gateway resolver, Zoom client), `app/Queries`, `app/ViewModels`, `app/Enums` (with `HasValues` concern), `app/Http/Resources/Api`, `app/Http/Requests` (Admin/Api/Auth/Blog/Install/Marketplace), typed casts/scopes on `User`, rate limiters in `RouteServiceProvider`, API idempotency + response-contract middleware.
- Legacy core still dominant: `ApiController` (7,517 lines / 128 public methods), `AdminCrudController` (1,519), `MainController` (1,284), `Profile` (538), two global helper files (`CommonHelper` 71 fns, `ApiHelper` 26 fns), state-changing GET routes, schema-by-SQL-dump.

### High-risk areas (ranked)

1. `AuthenticatedSessionController::dataReplace()` — runs `DB::unprepared(restore.sql)` and bulk-mutates every table (Section 4).
2. `ApiController` god class — auth, validation, queries, serialization, business rules in one file.
3. Schema managed by SQL dump instead of migrations — no reproducible, reviewable schema history.
4. State-changing `GET` routes (~36) — CSRF-bypass and prefetch/crawler side effects.
5. Global helpers with SSRF (`get_url_contents`) and `0777` directory creation (`uploadTo`).
6. Raw Blade output of user chat messages (`{!! $message->message !!}`).

### Missing documentation

- Production-specific deployment ownership details such as target host, backup tooling, process supervisor, scheduler owner, and recovery time objective.
- Up-to-date top-level `docs/architecture.md` reflecting Laravel 13 (existing `refactor-audit.md` is stale at "Laravel 9").
- Developer onboarding / workflow docs still need environment-specific ownership details; PR/issue templates exist under `.github/`.

### Missing tests

- Behavior/feature coverage for the large `ApiController` surface (current tests are mostly **guardrail "audit" tests**, not behavior tests).
- Payment gateway callback/webhook flows end-to-end.
- Chat XSS payload rendering.
- File upload/download authorization for private media now has first-pass regression coverage; expand it to stories, albums, chat, marketplace, and profile surfaces.
- Query-count/N+1 regression tests on hot list endpoints beyond marketplace.

---

## 2. Code quality problems

For each: **severity · risk · files · why · safest first fix · tests-first? · now vs document**.

### 2.1 Fat / god controllers — high · maintainability
- Files: `app/Http/Controllers/ApiController.php` (7,517 lines, 128 methods), `AdminCrudController.php` (1,519), `MainController.php` (1,284), `Profile.php` (538), `GroupController.php` (440).
- Why: impossible to review, test, or reason about; every domain change touches the same file; merge-conflict magnet.
- Fix: carve **one domain at a time** out of `ApiController` into per-resource controllers + Actions + Resources, keeping route names identical. Start with marketplace (already has Requests/Resources/Query — the pattern exists to copy).
- Tests first: **yes** — characterization/contract tests per endpoint before moving code.
- Now vs document: **document + phase**; too large for this pass.

### 2.2 Business logic in controllers — high · maintainability
- Files: `ApiController` (query building, serialization, side effects inline), `MainController`, `AdminCrudController`.
- Fix: move workflows to `app/Actions`, queries to `app/Queries`/scopes, output to Resources.
- Tests first: **yes**. Now vs document: **document**.

### 2.3 Business logic / data access in Blade — medium · maintainability
- Files: chat/profile/dashboard views; `resources/views/frontend/chat/single-message.blade.php` calls `ViewModels\BladeViewData` helpers per row.
- Why: view-time work is hard to cache/test; encourages N+1.
- Fix: precompute in ViewModels/controllers, pass ready data. Tests first: **recommended**. Now vs document: **document**.

### 2.4 Duplicated validation — medium · maintainability
- Inline `$request->validate([...])` blocks recur across `ApiController` methods (e.g. `login`) despite Form Requests existing for Marketplace/Blog/Auth.
- Fix: extend the Form Request pattern per endpoint as controllers are split. Tests first: **yes** (validation tests). Now vs document: **document**.

### 2.5 Duplicated authorization checks — high · security/maintainability
- Manual ownership/role checks scattered in controller methods; only **one** policy exists (`MarketplacePolicy`).
- Fix: introduce policies per resource; standardize `$this->authorize()`. Tests first: **yes** (authorization matrix). Now vs document: **document**.

### 2.6 Duplicated queries — medium · performance/maintainability
- Repeated friend/follow/save lookups across API + web. Some already extracted (`FriendshipsQuery`, `StoriesQuery`, marketplace query).
- Fix: continue extracting into scopes/query objects. Tests first: **yes**. Now vs document: **document**.

### 2.7 God models / model duplication — high · maintainability
- **Two parallel user models**: `app/Models/User.php` (196 lines, Authenticatable, relations) and `app/Models/Users.php` (39 lines). Ambiguous which is canonical; risk of divergent casts/hidden fields.
- Fix: pick `User` as canonical, deprecate `Users`, grep all references, migrate with tests. Tests first: **yes**. Now vs document: **document** (behavioral risk).

### 2.8 Helper abuse / static abuse — medium · maintainability/security
- `app/Helpers/CommonHelper.php` (71 global functions) + `ApiHelper.php` (26), autoloaded via composer `files`. Includes DB/business/network logic (`get_url_contents`, `get_settings`, `set_config`, image path builders). `User::get_user_image()` is a static presentation helper on the model.
- Fix: relocate to focused services / view helpers / Enums; keep thin globals only for pure formatting. Tests first: **partial**. Now vs document: **document**.

### 2.9 Unclear naming — medium · maintainability
- Former snake_case model classes have been renamed to StudlyCase names such as `AlbumImage`, `MediaFile`, `GroupMember`, `PageLike`, `PostShare`, `PaymentGateway`, `FundraiserDonation`, `LiveStreaming`, `FeelingAndActivity`, `AccountActiveRequest`, and `MessageThread`.
- Legacy database names such as `message_thrades` still require compatibility mappings. Tests first: **yes**. Now vs document: **done for PHP class/file names; database names remain documented compatibility debt**.

### 2.10 Dead code — low · maintainability
- `require 'vendor/autoload.php';` inside `ApiController.php` (redundant; already autoloaded) and a stray `use Session;` after class-level code. `docs/dead-code-audit.md` already tracks candidates.
- Fix: remove the redundant `require`/duplicate `use` when the file is split. Now vs document: **document** (touching the god file mid-audit is risky).

### 2.11 Debug code — none found in app — low
- No `dd()`/`dump()`/`var_dump()`/`print_r()` in `app/` or views. `ProductionDebugInstrumentationTest` already guards this. Good.

### 2.12 Old Laravel patterns — medium · maintainability
- Bare facade imports (`use DB;`, `use Image;`, `use Session;` without `Illuminate\Support\Facades\` FQCN) in `ApiController`.
- Fix: normalize to `Illuminate\Support\Facades\*` during the controller split (Pint/Rector can assist). Now vs document: **document**.

### 2.13 Unsafe request handling — high · security
- One `request->all()` mass-assignment site remains (grep count 1); most inputs go through `validate()`/Form Requests.
- Fix: replace with `validated()`/`safe()->only()`. Tests first: **yes**. Now vs document: **can fix in phase 3**.

### 2.14 Untyped PHP / PHPDoc — medium · maintainability
- Legacy controller methods lack return/param types; modern classes (Actions, Requests, `User`) are typed. PHPStan runs at **level 1** — real but shallow coverage.
- Fix: add types opportunistically per file as it is touched; raise PHPStan level gradually with a baseline. Now vs document: **document**.

### 2.15 Formatting standard — good — low
- Pint (laravel preset) configured and passing; `pint.json` present. No action beyond keeping it in CI.

---

## 3. Laravel best-practice gaps

### 3.1 Missing Form Requests — high · security/maintainability
- Present for Admin/Api-Marketplace/Auth/Blog/Install; **absent** for the bulk of `ApiController` write endpoints (posts, comments, groups, events, pages, videos, jobs, fundraisers, chat).
- Fix: add per-endpoint during split. Tests first: **yes**. Document.

### 3.2 Missing policies/gates — high · security
- Only `MarketplacePolicy`. Ownership across posts/comments/groups/pages/events/chat/media relies on inline checks.
- Fix: add policies per resource; enforce in controllers + Form Request `authorize()`. Tests first: **yes**. Document.

### 3.3 Missing API Resources — medium · maintainability/security
- Only 4 Resource classes (`Marketplace*`, `Notification*`); most API output is hand-built arrays from `ApiController`, risking inconsistent shapes and field leakage.
- Fix: introduce Resources per endpoint, snapshot the current JSON shape in a contract test first. Tests first: **yes**. Document.

### 3.4 Model casts — mostly good — low/medium
- `User` casts `date_of_birth => integer`, datetimes cast. Verify money/boolean/json casts on `Marketplace`, `Fundraiser_*`, `PaymentHistoryEntry`. `docs/eloquent-cast-audit` + tests exist.
- Fix: add missing decimal/bool/array casts per model. Document.

### 3.5 Factories / seeders — medium · testing
- Only **6 factories for 54 models** (Brand, Category, Currency, Marketplace, SavedProduct, User). Seeders are well-designed: `DatabaseSeeder` imports the schema dump idempotently; `LocalDemoSeeder` is env-guarded and factory-based.
- Fix: add factories for high-traffic models (Posts, Comments, Group, Event, Page, Job, Fundraiser) to enable behavior tests. Tests first: n/a (enables tests). **Can add incrementally now**.

### 3.6 Middleware — good — low
- Custom middleware for API bearer token, response contract, admin/user, activity, back-history. `MiddlewareAuditTest` exists. Note `AuthenticateSession` is commented out in the web group (see 4.11).

### 3.7 Route closures with logic — low (after Section 0 revert)
- The committed route files delegate to controllers. The only offending closure was the injected probe. `RouteAuditTest` guards this.

### 3.8 Unnamed routes / organization — good — low
- Routes are named, grouped by controller/middleware/prefix, and REST-ish under `api.php`. Minor: web routes split across `web.php`/`custom_routes.php`/`user.php`/`payment.php` — consolidate by domain later.

### 3.9 Route model binding — medium · maintainability/security
- Many routes take raw `{id}` and re-query manually (IDOR surface). `Account/AccountStatusController` uses `{user}` binding — the good pattern.
- Fix: adopt binding + policy on scoped resources during split. Tests first: **yes**. Document.

### 3.10 env() outside config — good — low
- Only 1 `env()` outside `config/` in `app/`; convention is followed. Fix the remaining one when touched.

### 3.11 Config cache — verify — medium · deployment
- Confirm `php artisan config:cache` works (no `env()` in runtime paths). Add to CI/deploy checklist. Document.

### 3.12 Queue handling — medium · performance
- `QUEUE_CONNECTION=sync` default; only 2 jobs (`ImportAddonPackageJob`, `ImportInstallSqlDumpJob`). Email/notifications/exports/image processing run inline.
- Fix: move slow side effects to jobs; document a real queue driver for prod. Document.

### 3.13 Cache invalidation / logging standards — medium
- `User::isOnline()` uses cache; no documented invalidation strategy across features. Logging is default stack; no structured-logging or secret-redaction standard doc.
- Fix: add caching + logging standards docs; ensure no secrets logged (see 4.13). Document.

---

## 4. Security risks

### 4.1 Demo restore + bulk table mutation on logout — critical · data-loss/security
- File: `app/Http/Controllers/Auth/AuthenticatedSessionController.php::dataReplace()` — `DB::unprepared(file_get_contents('public/assets/restore.sql'))` then iterates `SHOW TABLES` rewriting timestamps.
- Why: arbitrary SQL execution from a file + destructive bulk mutation; catastrophic if reachable in prod.
- Fix: remove/quarantine this method; if a demo reset is needed, gate behind a dedicated console command in `local` only. Tests first: **yes** (assert it is unreachable). **Document as critical; do not fix blindly** — confirm no route/caller depends on it.

### 4.2 SSRF via helper — high · security
- File: `app/Helpers/CommonHelper.php::get_url_contents()` uses `file_get_contents($pageUrl)` on caller-supplied URLs (link-preview).
- Fix: validate/allowlist scheme+host, block private ranges, use a timeout-bounded HTTP client, or move to a queued job. Tests first: **yes**. Document.

### 4.3 Stored XSS in chat — high · security
- File: `resources/views/frontend/chat/single-message.blade.php` — `{!! $message->message !!}` renders user content unescaped (lines ~16, 18, 73, 75).
- Why: attacker-controlled message body → script execution in recipients' browsers.
- Fix: escape with `{{ }}`, or sanitize via an allowlist HTML purifier if links must render. Tests first: **yes** (payload test). **Can fix with a focused, tested change** (flagged, not done here to keep audit report-only).

### 4.4 Other raw Blade output — medium · security
- 18 `{!! !!}` sites. Most pass through `script_checker()` which does `htmlspecialchars(strip_tags())` when `$convert_string=true` — but callers pass `false` (term/policy/about/blog/group/marketplace/event/page), returning the string **unescaped**. Trusted-admin content, but still a stored-XSS path if those editors are compromised or multi-admin.
- Fix: sanitize rich text at write time; render sanitized HTML. Tests first: **yes**. Document.

### 4.5 Mass assignment — medium · security
- All 54 models define `$fillable`/`$guarded` (good). One residual `request->all()` site (4.13/2.13). Sensitive fields (`user_role`, `payment_settings`) are not in `User::$fillable` — good.
- Fix: eliminate the remaining `all()`. Can fix in phase 3.

### 4.6 IDOR / ownership — high · security
- Raw `{id}` params re-queried without ownership scoping across API write/delete endpoints; only marketplace has a policy.
- Fix: policies + scoped binding + owner tests. Tests first: **yes**. Document.

### 4.7 State-changing GET routes — high · security
- ~36 GET routes perform mutations: `/save-post/{id}`, `/block_user/{id}`, `product/delete`, `event/delete`, `addon/status/{status}/{id}`, `addon/delete/{id}`, follow/unfollow, etc.
- Why: CSRF-bypass (GET is not CSRF-protected), and crawlers/prefetch/`<img>` can trigger them.
- Fix: convert to POST/DELETE with `@csrf`, keep names, update Blade/JS callers. Tests first: **yes**. Document (touches many callers).

### 4.8 Unsafe file handling — high · security
- `CommonHelper::uploadTo()` creates directories with **`0777`**. Upload validation exists via `Support\Files\FileUploader` (unit-tested) but the god controller also handles uploads inline in places.
- Fix: tighten to `0755`, centralize all uploads through `FileUploader`, validate mime/ext/size, store private media on a private disk with authorized downloads. Tests first: **yes** (Storage::fake). Document.

### 4.9 Raw SQL injection — low/medium · security
- Only 2 raw-SQL sites in `app/` besides the restore path; parameter binding largely used. `DB::select('SHOW TABLES')` is static.
- Fix: audit the 2 sites for interpolation; enforce bindings/allowlists for any sort/filter columns. Document.

### 4.10 CORS — high · security
- `config/cors.php`: `allowed_methods ['*']`, `allowed_origins ['*']`, `allowed_headers ['*']`, `supports_credentials false`.
- Why: any origin may call the API. Acceptable only for a fully public, token-auth API; still too broad.
- Fix: restrict `allowed_origins` to known app domains; keep credentials false. Tests first: no. **Can fix in phase 7** (config only, but verify mobile/web clients).

### 4.11 Session / cookie config — medium · security
- `config/session.php`: `same_site => null` (should be `lax` or `strict`), `secure => env('SESSION_SECURE_COOKIE')` (ensure `true` in prod), `http_only => true` (good). `AuthenticateSession` middleware is commented out in the web group.
- Fix: set `same_site=lax`, force secure cookies in prod, reconsider `AuthenticateSession`. Document + config change in phase 7.

### 4.12 APP_DEBUG exposure — medium · security
- `.env.example` ships `APP_DEBUG=true` and `LOG_LEVEL=debug`.
- Fix: keep example as `local` template but document that prod must set `APP_DEBUG=false`; add a deploy check. Document.

### 4.13 Sensitive logs / secrets — medium · security
- `User::$hidden` includes `password`, `payment_settings`, `remember_token` (good). No secrets in `.env.example`. Verify no raw request/payment payloads are logged (no explicit redaction standard).
- Fix: add a logging-redaction standard + test. Document.

### 4.14 API token checks / rate limits — good — low
- Sanctum + custom `EnsureValidApiBearerToken` + `api.token` middleware; token abilities enum; granular rate limiters (`api-token`, `api-registration`, `api-password-reset`, `api-expensive`, `api-search`, `webhook`, `login`) in `RouteServiceProvider`. Strong. Keep.

### 4.15 Webhook verification / idempotency — medium · security
- Payment callbacks (`payment.status`, `make.payment`) are throttled and idempotency middleware exists (`api-idempotency`), but per-gateway **signature verification** should be confirmed for each provider.
- Fix: assert signature checks per gateway; add replay tests. Tests first: **yes**. Document.

---

## 5. Database & migration risks

### 5.1 Schema managed by SQL dump, not migrations — critical · deployment/maintainability
- Schema originates from `public/assets/install.sql`; only 10 additive migrations exist. No reviewable, incremental, rollback-safe schema history.
- Why: cannot diff schema changes, cannot rebuild reproducibly, migrations and dump can drift.
- Fix: generate a baseline migration from current prod schema, freeze the dump as install-only, require all future changes as migrations. **High-effort, document as a program of work.**

### 5.2 Missing indexes / FKs / unique constraints — addressed additively — medium
- The 10 migrations add lookup/search/relationship indexes, FKs, unique, nullable, money-precision, datetime, and JSON constraints "safely" for legacy data. Good direction. Continue per `docs/database-indexes.md`, `foreign-key-audit.md`, `unique-constraint-audit.md`.
- Fix: verify each constraint has passing data (no violations) before enabling; some may need `docs/data-cleanup-needed.md`. Document.

### 5.3 Money / decimal handling — high · data-loss
- Marketplace/fundraiser/payment amounts: confirm decimal(precision,scale) or integer-minor-units, not float. `docs/money-field-audit.md` and a money-precision migration exist; verify casts + calculations align.
- Fix: standardize on decimal casts + validation; test rounding. Tests first: **yes**. Document.

### 5.4 Soft deletes — medium · data-loss
- Verify `SoftDeletes` trait ↔ `deleted_at` column consistency and that unique constraints tolerate soft-deleted rows. `docs/soft-delete-audit.md` exists.
- Fix: reconcile per audit; test delete/restore. Document.

### 5.5 Unbounded queries / N+1 — medium · performance
- God-controller list endpoints and chat/dashboard views access relations in loops. Marketplace has a performance test; extend coverage.
- Fix: eager-load, paginate, `withCount`/`withExists`; add query-count tests. Tests first: **yes**. Document.

### 5.6 Naming inconsistencies — low · maintainability
- PHP model class/file names now use StudlyCase. Legacy snake_case database names remain for compatibility and should only be renamed through a separate migration plan.

---

## 6. Frontend quality risks

### 6.1 Build stack outdated — high · deployment/frontend
- Laravel Mix 6 / Webpack 5. Prior `npm audit` reported ~11 vulns through the Mix/Webpack chain. No Vite.
- Fix: migrate Mix → Vite as a dedicated, tested phase (entrypoints, `@vite`, HMR). Document.

### 6.2 No JS/CSS linting/formatting — medium · frontend
- No ESLint/Stylelint/Prettier; no `lint`/`format` npm scripts.
- Fix: add ESLint + Stylelint + Prettier with legacy-friendly configs and npm scripts. **Can add now** (tooling only, no behavior change) — deferred to keep this pass report-only.

### 6.3 Unsafe raw HTML / XSS — high · security/frontend
- See 4.3 / 4.4 (chat `{!! !!}`). Also verify JS does not `innerHTML` user data.
- Fix: escape/sanitize; standardize a safe DOM helper. Tests first: **yes**. Document.

### 6.4 Accessibility — medium · frontend
- Legacy Blade uses clickable non-button elements, icon-only controls, and likely gaps in labels/heading order/focus management (292 Blade files). Not yet audited against WCAG 2.2 AA.
- Fix: per-screen a11y pass (semantic elements, labels, focus, contrast, reduced motion). Document.

### 6.5 SCSS architecture / design tokens — medium · frontend
- App styling is Tailwind via Mix; large legacy SCSS/CSS lives under `public/assets/` (compiled/committed). No `@use`/`@forward` token structure in `resources/`.
- Fix: when moving to Vite, establish a modular token layer; retire unused committed CSS. Document.

### 6.6 Duplicated Blade markup — medium · maintainability
- Repeated cards/forms/modals across 292 views; few shared components.
- Fix: extract Blade components incrementally. Document.

### 6.7 JS globals / duplicated AJAX — medium · frontend
- `resources/js/app.js` + `bootstrap.js` (Axios). Confirm no accidental globals; standardize a CSRF-aware fetch helper.
- Fix: modularize; single AJAX helper. Document.

### 6.8 Committed built assets — low · deployment
- `public/assets` is ~113 MB and tracked (includes `install.sql`, third-party JS, `.DS_Store`). Bloats the repo.
- Fix: audit what must ship vs be built; add `.DS_Store` to ignore (already listed) and remove tracked `.DS_Store` files. Document.

---

## 7. Testing gaps

- **Strength:** 408 passing tests, broad **guardrail/audit** coverage (routes, middleware, migrations, casts, relationships, soft deletes, pivots, money/date fields, API contract/rate-limit/idempotency/sensitive-leak, auth flows). This is unusually good for a legacy app.
- **Gap — behavior tests for `ApiController`:** most endpoints lack request→response behavior/contract tests; audit tests assert structure, not behavior. **high · testing**.
- **Gap — authorization matrix:** guest / authenticated-no-permission / owner / non-owner / admin / tenant-mismatch per resource (only marketplace covered). **high · security/testing**.
- **Gap — chat XSS payload test.** **high**.
- **Gap — file upload/download authorization** (Storage::fake). **medium**.
- **Gap — payment webhook/callback signature + idempotency e2e.** **medium**.
- **Factories:** only 6/54 — the main blocker to adding behavior tests (Section 3.5). **Fix first.**
- No tests hit real external APIs (good); `phpunit.xml` forces array mail/cache/session, sqlite `:memory:`, low bcrypt rounds. Deterministic. Schema loaded via `TestCase` from the install dump.

---

## 8. CI / deployment gaps

| Gap | Severity · risk | Note / fix |
| --- | --- | --- |
| CI workflow must stay green | high · deployment | `.github/workflows/ci.yml` exists; keep it aligned with installed PHP/Node tooling and do not add flaky service dependencies. |
| Frontend lint/build gate must stay compatible with Mix | medium · frontend | ESLint, Stylelint, Prettier, and `npm run production` are wired; Vite remains a separate migration. |
| No migration-safety check | medium · deployment | CI step: fresh sqlite + run the 10 migrations + rollback where safe. |
| No config/route cache check | medium · deployment | CI/deploy step: `config:cache`, `route:cache`, `view:cache` must succeed. |
| Deployment docs require environment-specific owners | medium · deployment | Consolidated deployment, rollback, and backup/restore docs exist; fill in host-specific commands before production use. |
| PR/issue templates require maintenance | low · process | `.github/PULL_REQUEST_TEMPLATE.md` and issue templates exist; keep checklists aligned with CI. |
| npm dependency vulnerabilities | medium · security | Tracked historically; resolve via the Vite migration. |

---

## Checks run for this audit

| Command | Result | Notes |
| --- | --- | --- |
| `php -v` / `composer -V` / `node -v` / `npm -v` | ok | PHP 8.5.7, Composer 2.9.5, Node 22.22.3, npm 10.9.8 |
| `composer validate --strict` | **pass** | `composer.json` is valid |
| `vendor/bin/pint --test` | **pass*** | *Passed on committed code. Reported a fail only for the injected `routes/web.php` route, which was reverted. |
| `vendor/bin/phpstan analyse` | **pass** | Larastan level 1, "No errors" |
| `php artisan test` | **pass** | 408 passing after reverting the injected route (was 405/3-fail with it) |
| `npm run production` (Laravel Mix) | **pass** | Compiled in ~1.5s; `js/app.js` 138 KiB, `css/app.css` ~1 byte (Tailwind purged) |

No command failed on the committed codebase. The only failures were caused by the externally injected route documented in Section 0.

---

## Prioritized fix order (summary)

1. **Critical:** quarantine `AuthenticatedSessionController::dataReplace()` restore/mutation path; keep guard on injected routes.
2. **Critical:** replace SQL-dump schema with a baseline migration + migrations-only policy.
3. **High:** fix chat stored-XSS; convert state-changing GET routes; add policies + IDOR/ownership scoping.
4. **High:** decompose `ApiController` domain-by-domain (contract tests first).
5. **High:** restrict CORS; harden session cookies; SSRF-guard `get_url_contents`; `0755` uploads.
6. **Medium:** add CI; add factories (unblocks behavior tests); add ESLint/Stylelint/Prettier.
7. **Medium:** money/decimal + soft-delete reconciliation; N+1 query tests.
8. **Medium:** Mix → Vite; a11y pass; Blade componentization.

See `docs/senior-refactor-roadmap.md` for the phased plan.
