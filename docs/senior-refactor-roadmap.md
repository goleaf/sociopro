# Senior Refactor Roadmap

Generated: 2026-07-02
Companion to `docs/senior-refactor-audit.md`.

This roadmap turns the audit into an ordered, testable, reversible plan. It is deliberately phased so each slice can be tested, reviewed, committed, deployed, and rolled back on its own.

## Baseline (verified 2026-07-02)

- Laravel `13.18.0`, PHP `^8.3` (runtime 8.5.7), Composer 2.9.5, Node 22.22.3, npm 10.9.8.
- Frontend: Laravel Mix 6 / Webpack 5 (not Vite). Tailwind 3, Alpine 3, Axios.
- Tests: PHPUnit 12. Pint, PHPStan/Larastan level 1, Composer validation, and Mix build are wired locally and in CI; record the exact fresh test count in each implementation summary.
- Schema bootstrapped from `public/assets/install.sql`; 10 additive migrations.
- GitHub Actions CI exists. Only `MarketplacePolicy` exists. 6 factories for 54 models.

## Ground rules (apply to every phase)

- **No behavior change without a test.** Add characterization/contract tests **before** risky refactors.
- Preserve public routes, route names, API response shapes, redirects, flash messages, and DB contracts unless a change is a proven bug/security fix that is tested and documented.
- Keep each slice small enough for one focused, reviewable commit.
- Run the phase verification gate before merging.
- Keep documentation-only commits separate from behavior commits.
- Treat any unexplained working-tree change under `routes/*`, auth, or payment code as a release blocker (see audit Section 0).

Per-phase verification gate (minimum): `vendor/bin/pint --test` · `vendor/bin/phpstan analyse` · `php artisan test` · `npm run production` when frontend changed · `php artisan config:cache && php artisan route:list` when routes/config changed.

---

## Phase 1 — Safety baseline and tests

**Goal:** lock in a trustworthy baseline and the ability to add behavior tests.

- Confirm clean working tree before each slice; investigate the injected-route source (audit Section 0) and keep `RouteAuditTest` as the guard.
- Refresh stale docs: mark `docs/refactor-audit.md` (says "Laravel 9") as historical; make `docs/senior-refactor-audit.md` the current reference.
- **Add factories** for high-traffic models (Posts, Comments, Group, Group_member, Event, Page, Job, Fundraiser, Stories, Chat/Message_thrade) — this unblocks all behavior testing.
- Add **characterization/contract tests** capturing current JSON shapes for the highest-traffic `ApiController` endpoints (timeline, marketplace, groups, notifications) before any refactor touches them.
- Quarantine plan (test-only, no delete yet): assert `AuthenticatedSessionController::dataReplace()` is unreachable via routes.

**Gate:** full suite green; new factories used by ≥1 test each.

## Phase 2 — Formatting, static analysis, CI

**Goal:** automated guardrails on every push.

- Keep `.github/workflows/ci.yml` green: `composer validate --strict`, `composer install`, `pint --test`, `phpstan`, `php artisan test` (sqlite `:memory:`), `npm ci`, frontend lint/style/format checks, and `npm run production`. Use safe test env vars; no real secrets/services.
- Maintain frontend tooling (no behavior change): ESLint, Stylelint, Prettier with legacy-friendly configs; npm scripts `lint`, `lint:fix`, `format`, `format:check`, `stylelint`, `build`.
- Add migration-safety CI step: fresh sqlite → run 10 migrations → rollback where safe.
- Raise PHPStan gradually: generate a baseline, then increase level one notch per subsequent phase.

**Gate:** CI green on a PR; frontend lint runs (may start non-blocking).

## Phase 3 — Routes, controllers, Form Requests, policies

**Goal:** thin controllers, correct verbs, real authorization. **Do one domain at a time**, starting with **Marketplace** (Requests/Resources/Query/Policy already exist — copy the pattern).

- Extract the domain's methods out of `ApiController`/`MainController` into a dedicated controller.
- Convert **state-changing GET routes → POST/DELETE** with `@csrf`; keep route names; update Blade/JS callers.
- Add **Form Requests** (rules, messages, `authorize()`, `prepareForValidation`); replace inline `validate()` and the remaining `request->all()` with `validated()`/`safe()`.
- Add **policies** per resource; enforce in controller + Form Request; adopt route model binding with ownership scoping to close **IDOR** gaps.
- Add **API Resources** for the domain's output (shape guarded by the Phase 1 contract test).
- **Authorization matrix tests:** guest / authenticated-no-permission / owner / non-owner / admin / tenant-mismatch.

**Gate:** contract tests unchanged (shape preserved); authorization matrix passes; `route:list` + `route:cache` succeed.

## Phase 4 — Models, services, actions, domain logic

**Goal:** move workflows out of controllers/helpers; resolve model duplication.

- Resolve the **two user models** (`User` vs `Users`): pick `User` canonical, bridge/deprecate `Users`, migrate references with tests.
- Extract multi-step workflows into `app/Actions`/`app/Services` with DI and transactions; dispatch jobs/events **after commit**.
- Relocate DB/business/network logic out of `CommonHelper`/`ApiHelper` into focused services; keep only pure formatting globals. Move `User::get_user_image()` to a presenter/view helper.
- Add local scopes for repeated query conditions; add unit tests for important actions.
- Normalize facade imports and remove dead `require`/duplicate `use` in the god controller as it is split.

**Gate:** unit tests for extracted actions; suite green; PHPStan level raised one notch.

## Phase 5 — Database, migrations, indexes, transactions

**Goal:** reproducible schema + safe constraints + transactional writes.

- **Baseline migration:** generate from current prod schema; freeze `install.sql` as install-only; adopt a **migrations-only** policy for future changes.
- Enable pending index/FK/unique/nullable constraints from the existing "safe legacy" migration set **only after** verifying data passes; record any violations in `docs/data-cleanup-needed.md`.
- Standardize **money** columns to decimal/integer-minor-units with matching casts + validation; test rounding.
- Reconcile **soft deletes** (trait ↔ `deleted_at`, unique constraints tolerate trashed rows); test delete/restore.
- Wrap multi-table writes in `DB::transaction`; add rollback tests.

**Gate:** `migrate:fresh` on sqlite + rollback pass; money/soft-delete/transaction tests pass.

## Phase 6 — Blade, HTML, SCSS, JS, Vite, accessibility

**Goal:** safe, semantic, maintainable frontend (preserve appearance).

- **Migrate Mix → Vite** (entrypoints, `@vite`, HMR, env safety, source-map policy); resolve npm audit vulns.
- Fix **chat stored-XSS** and other `{!! !!}` sites (escape or sanitize at write time); ensure JS never `innerHTML`s user data; standardize a CSRF-aware fetch helper; remove accidental globals.
- Establish a modular SCSS/token layer (`@use`/`@forward`, colors/spacing/typography/breakpoints); retire clearly-unused committed CSS under `public/assets`.
- Extract repeated markup into Blade components.
- Per-screen **WCAG 2.2 AA** pass: semantic elements, labels, heading order, focus management, contrast, reduced motion, touch targets.

**Gate:** `npm run build` (Vite) passes; XSS payload test passes; visual parity confirmed; lint/stylelint/prettier green.

## Phase 7 — Security hardening

**Goal:** close the remaining security gaps from audit Section 4.

- **Quarantine/remove** `AuthenticatedSessionController::dataReplace()` restore+mutation path (or gate behind a `local`-only console command).
- **SSRF-guard** `get_url_contents()` (scheme/host allowlist, block private ranges, timeouts) or move to a queued job.
- Restrict **CORS** `allowed_origins` to known domains; set session `same_site=lax`, force secure cookies in prod; reconsider `AuthenticateSession`.
- Confirm **webhook signature verification** per payment gateway; add replay/idempotency tests.
- Tighten uploads: `0755`, centralize via `FileUploader`, validate mime/ext/size, private disk + authorized downloads.
- Add security headers (X-Content-Type-Options, Referrer-Policy, frame-ancestors/X-Frame-Options, Permissions-Policy; HSTS if HTTPS guaranteed). Add logging redaction standard + test.

**Gate:** security tests (XSS, IDOR, upload auth, webhook signature, rate limits) pass; `config:cache` works.

## Phase 8 — Performance optimization

**Goal:** remove N+1s and unbounded work.

- Add eager loading (`with`/`load`/`loadMissing`), `withCount`/`withExists`, and pagination/`cursorPaginate` to hot list endpoints and chat/dashboard views; add **query-count regression tests**.
- Replace load-then-count/filter with DB aggregates and `exists()`/`doesntExist()`.
- Move slow side effects (mail, notifications, exports, image processing, external API calls) to **idempotent jobs** with retries/backoff/timeout; document a real queue driver.
- Add safe, context-scoped caching with TTLs and invalidation; test for no cross-user leakage.

**Gate:** query-count tests pass; no regression in the full suite.

## Phase 9 — Deployment, docs, rollback

**Goal:** production-ready delivery and recovery.

- Keep consolidated operations docs current: `docs/deployment-checklist.md`, `docs/rollback-plan.md`, `docs/backup-and-restore.md`, `docs/performance-audit.md`, and `docs/senior-upgrade-summary.md` (reuse existing zero-downtime/rollback fragments).
- Deployment checklist: `composer install --no-dev --optimize-autoloader`, `npm ci && npm run build`, `migrate --force`, `config:cache`, `route:cache`, `view:cache`, `storage:link`, queue restart, scheduler, health check, smoke tests, failed-jobs review.
- Production config review: `APP_DEBUG=false`, `LOG_LEVEL` sane, real cache/session/queue drivers, trusted proxies, CORS, cookies, Vite env; `.env.example` placeholders only.
- Add `.github/PULL_REQUEST_TEMPLATE.md` + issue templates (bug/feature/security/refactor) with testing/security/deployment checklists.
- Produce `docs/senior-upgrade-summary.md` after phases land: what changed, tests added, remaining risks, next 10 tasks.

**Gate:** `config:cache` + `route:cache` + `view:cache` succeed; `migrate:fresh` on a test DB passes; full suite + frontend build green in CI.

---

## Sequencing notes

- Phases 1–2 are safe to do immediately (tests, factories, tooling, CI) — no behavior change.
- Phase 3 must be **domain-by-domain**; never refactor the whole `ApiController` in one pass.
- The two **critical** items (restore/mutation path in Phase 7, schema-from-dump in Phase 5) can begin planning early but need explicit deploy/rollback plans before execution.
- Frontend (Phase 6) and performance (Phase 8) can proceed in parallel with backend phases once CI (Phase 2) is green.
