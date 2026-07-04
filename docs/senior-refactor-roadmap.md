# Senior Refactor Roadmap

Generated: 2026-07-02
Companion audit: `docs/senior-refactor-audit.md`

This roadmap is intentionally incremental. The project already has valuable quality tooling and tests, so the goal is not a rewrite. The goal is to turn the existing legacy monolith into a maintainable Laravel 13 application one tested domain slice at a time.

## Ground Rules For Every Phase

- Preserve existing public behavior unless a change fixes a proven bug/security issue and is covered by tests.
- Add characterization tests before moving risky legacy code.
- Keep commits scoped to one concern and use Conventional Commits.
- Do not edit old production migrations; create safe additive migrations only.
- Do not introduce raw SQL strings or new `DB::table()` usage in controllers, views, jobs, resources, or helpers.
- Do not move queries into Blade, Filament resources, or loops.
- Do not rely on hidden Blade buttons for authorization; enforce authorization server-side.
- Run the relevant quality gate before each commit.

Minimum gate for PHP/application changes:

```bash
vendor/bin/pint --test
composer analyse
php artisan test
```

Additional gates when relevant:

```bash
npm run quality
composer quality:cache
php artisan route:list --except-vendor
git diff --check
```

## Phase 1: Safety Baseline and Tests

Goal: make every future refactor testable and reversible.

Work items:

- Freeze this audit as the current baseline; mark older version-specific audit docs as historical when they conflict with current Laravel 13 evidence.
- Add missing factories for high-risk models: posts, comments, stories, albums, media files, groups, group members, events, pages, page likes, jobs, job applications, fundraisers, badges, sponsors, chats/message threads, reports, and payment history entries.
- Add characterization tests for the highest-traffic legacy flows before refactoring them:
  - feed/timeline creation and deletion
  - comments/reactions/share/save flows
  - profile media and album flows
  - group/event/page join/invite/follow flows
  - chat messages and attachments
  - admin CRUD delete/status flows
  - payment callbacks and history pages
- Add authorization matrix tests for every sensitive resource: guest, authenticated non-owner, owner, admin, disabled user if applicable.
- Add regression tests for all state-changing GET routes before changing route verbs.
- Add query-count tests for feed, search, profile, group, event, chat, and admin list pages.

Exit criteria:

- New factories are deterministic and do not rely on production IDs.
- Each target refactor domain has at least one current-behavior test.
- Full suite passes locally and in CI.

## Phase 2: Formatting, Static Analysis, and CI

Goal: strengthen guardrails without changing runtime behavior.

Current baseline:

- Pint is installed and scripted.
- Larastan/PHPStan is installed and scripted at a legacy-safe level.
- Rector is installed for conservative dry runs.
- ESLint, Stylelint, Prettier, npm audit, and Mix production build are scripted.
- GitHub Actions already runs PHP and frontend checks.

Work items:

- Keep `composer ci` as the local all-in quality command.
- Raise PHPStan one level only after the touched files have reliable types/PHPDoc or a documented baseline.
- Fix obvious static-analysis issues inside scoped refactors: wrong null assumptions, missing imports, invalid relation PHPDoc, dead branches, and inaccurate collection generics.
- Add CI artifact notes for test counts and build results if deployment reporting needs them.
- Add an optional MySQL-compatible CI job or scheduled migration rehearsal before production schema phases.

Exit criteria:

- CI stays green on `main`.
- No static-analysis suppression is added without a documented reason.
- No production secrets are required by CI.

## Phase 3: Routes, Controllers, Form Requests, and Policies

Goal: turn legacy controller flows into thin, authorized Laravel endpoints.

Recommended order:

1. Marketplace follow-ups, because it already has Requests, Query objects, Resources, and a Policy.
2. Pages, because `PagePolicy` exists and page view data already has an Action.
3. Chat/media, because upload/download security risk is high.
4. Groups/events, because membership and invitation actions are IDOR-prone.
5. Posts/comments/stories/feed, because this is the largest user-facing surface.
6. Admin CRUD and payment history, because it needs careful permission and route-verb migration.

Work items per domain:

- Keep existing route names and public URLs unless a tested security fix requires a change.
- Convert state-changing GET routes to POST/PATCH/DELETE with `@csrf`; leave temporary compatibility redirects only if needed and documented.
- Move inline validation into Form Requests with `authorize()`.
- Replace request payload mass assignment with `$request->validated()`, `$request->safe()`, or explicit DTO mapping.
- Add policies and call `$this->authorize()` in controllers.
- Use route model binding where ownership scoping is clear.
- Move output shaping to API Resources for JSON endpoints.
- Keep controllers to request/auth/delegate/respond.

Exit criteria:

- No route closures with logic.
- Route cache works.
- Authorization matrix tests pass.
- API response contract tests prove shape compatibility.

## Phase 4: Models, Services, Actions, and Domain Logic

Goal: remove hidden workflows from controllers, helpers, and models.

Work items:

- Resolve `User` vs `Users` model duplication by making `App\Models\User` canonical and deprecating/removing the legacy wrapper only after references are covered by tests.
- Extract multi-table workflows into `app/Actions` or focused `app/Services`:
  - create/delete post with media
  - comment/reaction/share/save
  - friend request accept/decline
  - group/page/event membership
  - chat message with attachment
  - marketplace purchase/save/download
  - badge/sponsor/payment history creation
- Move repeated query fragments to model scopes or query classes.
- Keep models focused on relationships, casts, scopes, and small domain invariants.
- Remove DB/business/network logic from `CommonHelper` and `ApiHelper` gradually.
- Dispatch slow side effects through jobs after transaction commit.

Exit criteria:

- New actions/services have unit or feature tests.
- No new god service replaces the god controller.
- Static analysis improves or remains green.

## Phase 5: Database, Migrations, Indexes, and Transactions

Goal: make schema changes reviewable and production-safe.

Work items:

- Generate a reviewed Laravel baseline migration from the current schema; keep `database/schema/install.sql` as legacy installer input until the installer is migrated.
- Audit production data before enabling any pending foreign key, unique, nullable, money, datetime, or JSON constraints.
- Add missing foreign keys only when lifecycle behavior is clear:
  - cascade for true child rows
  - restrict for protected parent records
  - null on delete where orphaning is intentional
- Add indexes only with evidence from route/query usage and no duplicate index risk.
- Standardize money fields using decimals or integer minor units with matching casts, validation, and rounding tests.
- Align `SoftDeletes` traits and `deleted_at` columns.
- Wrap multi-table write workflows in transactions; dispatch jobs/events after commit.
- Document dirty-data cleanup in `docs/data-cleanup-needed.md`.

Exit criteria:

- `migrate:fresh` passes on SQLite and a MySQL-compatible rehearsal database before production changes.
- Rollback behavior is documented for every migration.
- Transaction rollback tests exist for critical workflows.

## Phase 6: Blade, HTML, SCSS, JS, Vite, and Accessibility

Goal: preserve appearance while making frontend code safer, semantic, and maintainable.

Work items:

- Remove all queries, aggregates, and business decisions from Blade views.
- Convert duplicated UI fragments to Blade components with explicit props/slots.
- Improve semantics page by page:
  - landmarks (`main`, `nav`, `header`, `footer`, `section`, `article`)
  - heading order
  - buttons for actions and links for navigation
  - accessible labels, help text, errors, and fieldsets
  - table captions/headers/scopes where data tables exist
- Escape user content by default; sanitize trusted rich text explicitly.
- Replace inline `onclick` and `javascript:void(0)` patterns with unobtrusive JS modules.
- Add a CSRF-aware fetch helper for web AJAX.
- Add modular frontend architecture:
  - tokens
  - base
  - layout
  - components
  - utilities
  - pages
- Keep Vite entrypoints stable during unrelated frontend cleanup; do not mix asset-system changes with behavior refactors casually.
- When changing the asset pipeline, update entrypoints, Blade directives, env usage, source maps, and deployment docs in one isolated PR.

Exit criteria:

- Important forms render labels/errors/accessibility attributes.
- XSS payload tests pass.
- `npm run quality` and production build pass.
- Visual regressions are reviewed on the touched screens.

## Phase 7: Security Hardening

Goal: close high-impact security issues after tests are in place.

Work items:

- Finish GET-to-POST/DELETE conversion for mutating routes.
- Enforce policies/gates for every sensitive action.
- Centralize file uploads/downloads through validated services and authorized private storage.
- Add path traversal, executable upload, MIME mismatch, size limit, and unauthorized download tests.
- Lock CORS origins for production.
- Enforce secure cookies, same-site policy, trusted proxies, and production `APP_DEBUG=false`.
- Verify every payment/webhook provider signature, replay protection, and idempotency behavior.
- Add security headers where compatible:
  - `X-Content-Type-Options`
  - `Referrer-Policy`
  - `Permissions-Policy`
  - `X-Frame-Options` or CSP `frame-ancestors`
  - HSTS only when HTTPS is guaranteed
- Ensure logs redact passwords, tokens, cookies, authorization headers, payment data, and private payloads.

Exit criteria:

- Security regression tests pass.
- Config cache works.
- Deployment checklist documents required production values.

## Phase 8: Performance Optimization

Goal: reduce query count, memory use, and synchronous latency without changing behavior.

Work items:

- Replace relationship access in loops with eager loading.
- Replace load-then-count/sum/existence checks with `withCount()`, `withSum()`, `withExists()`, `exists()`, and aggregate queries.
- Replace unbounded `all()`/`get()` on growing tables with pagination, cursor pagination, chunking, or bounded select lists.
- Add composite indexes for common tenant/user/status/date/order filters after EXPLAIN review.
- Move slow work out of HTTP:
  - email
  - notifications
  - image processing
  - imports/exports
  - external API calls
  - reports
- Add cache only where invalidation is clear; include user/account/context in cache keys.
- Audit frontend bundle size and public assets after the Mix/Vite decision.

Exit criteria:

- Query-count tests protect hot pages.
- No cross-user cache leaks.
- Queue jobs are idempotent and have timeout/retry/backoff settings.

## Phase 9: Deployment, Docs, and Rollback

Goal: make production delivery repeatable and recoverable.

Work items:

- Keep these docs current:
  - `docs/deployment-checklist.md`
  - `docs/rollback-plan.md`
  - `docs/backup-and-restore.md`
  - `docs/database-standards.md`
  - `docs/security-hardening.md`
  - `docs/performance-improvements.md`
  - `docs/known-technical-debt.md`
- Add production-environment ownership details:
  - PHP/FPM or hosting runtime
  - web server config
  - queue supervisor
  - scheduler
  - backup system
  - restore drill owner
  - RTO/RPO
  - health check and smoke test URLs
- For each release, document:
  - migration risk
  - rollback command
  - cache clear/rebuild
  - queue restart
  - asset deployment
  - smoke tests
  - failed jobs/log review
- Maintain `.github/PULL_REQUEST_TEMPLATE.md` and issue templates with testing/security/deployment checklists.

Exit criteria:

- `composer ci` passes.
- `composer quality:cache` passes.
- A fresh test database can migrate.
- Rollback notes exist for every schema-affecting release.

## Recommended Next 10 Refactor Slices

1. Add characterization tests for state-changing GET routes and convert one low-risk admin delete route to DELETE.
2. Add `PostPolicy` plus owner/non-owner tests for post update/delete/report flows.
3. Move one post media upload path to `FileUploader` with `Storage::fake()` tests.
4. Extract group membership accept/decline into Actions with transactions.
5. Remove DB queries from `resources/views/frontend/search/group.blade.php` by preloading counts and membership flags.
6. Replace `AdminCrudController` category/brand `::all()` calls with bounded query methods and view tests.
7. Add factories for `Posts`, `Comments`, `Group`, `Event`, and `MediaFile`.
8. Add API contract tests for the most-used `ApiController` feed endpoints.
9. Add a production-like MySQL migration rehearsal job or documented local command.
10. Move legacy public frontend assets into Vite-managed modules as separate asset-only slices after frontend tests are stable.
