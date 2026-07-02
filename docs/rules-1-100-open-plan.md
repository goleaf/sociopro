# Rules 1-100 Open Refactor Plan

Generated: 2026-07-02

This is a living plan for the cleanup standards documented in
`docs/code-cleanup-standards.md` and related senior-refactor docs. It does not
claim the legacy application is fully refactored. It records what is still open
so each future cleanup can be handled as a small, tested, reversible slice.

## Source Documents Reviewed

- `docs/code-cleanup-standards.md`
- `docs/known-technical-debt.md`
- `docs/senior-refactor-audit.md`
- `docs/senior-refactor-roadmap.md`
- `docs/refactor-checklist.md`
- `docs/test-audit.md`
- `docs/chat-legacy-behavior.md`

The repository currently contains explicit numbered cleanup coverage for rules
`41-100`. I did not find a local markdown source that enumerates rules `1-40`
as numbered items. The general standards for the missing early rule range are
covered indirectly by `docs/project-standards-bible.md`,
`docs/coding-standards.md`, `docs/refactor-checklist.md`, and
`docs/senior-refactor-roadmap.md`.

## Current Evidence Snapshot

This snapshot is from quick repository scans on 2026-07-02. It is a planning
aid, not a formal static-analysis report.

| Evidence | Current finding |
| --- | --- |
| Branch state | `main` clean and synced with `origin/main` before this doc was added |
| Full PHP suite last recorded | `532 passed`, `14677 assertions` after chat characterization work |
| Plural/vague model names still present | `Albums`, `Comments`, `Friendships`, `PaidContentPackages`, `Posts`, `Setting`, `Stories`, `Users` |
| Large files | `ApiController.php` 7442 lines, `AdminCrudController.php` 1515 lines, `MainController.php` 1235 lines, `CommonHelper.php` 797 lines, `ApiHelper.php` 344 lines |
| Raw DB helper/controller/view hits | 61 `DB::table/raw/select/statement/unprepared` hits in `app`, `resources`, and `routes` |
| Blade query/raw/auth hotspots | 344 hits for raw output, `@php`, model queries, counts, sums, or repeated `auth()->user()` in Blade |
| Repeated `auth()->user()` | 368 hits in `app` and `resources` |
| `return false` in app code | 44 hits, concentrated around payment/gateway-style flows and middleware |
| `where('id', ...)->first()` style | 68 hits in app code |
| `::all()` in app/resources | 6 hits |
| State-changing-looking GET routes | 73 route-list hits by heuristic name/URI scan |
| Factories vs models | 13 factories for 54 model files |

## What Is Already In Good Shape

- Pint, PHPStan/Larastan, Rector config, ESLint, Stylelint, Prettier, npm
  quality scripts, and CI exist.
- The full PHPUnit suite is currently green after the chat characterization
  slice.
- Marketplace and page areas already have better examples of Requests,
  policies, query objects/actions, resources, and tests.
- Logging sanitizer support exists and is covered by tests.
- Security headers, production exposure checks, API error format, rate limits,
  idempotency, and several payment/webhook paths have guardrail tests.
- Legacy chat/message-thread behavior now has focused web/API characterization
  tests and a documented rename plan.

## Not Done By Rule Group

| Rule group | Status | What remains |
| --- | --- | --- |
| 1-40 | Source missing locally | No local numbered rule list was found. Treat project bible/coding standards as the active baseline unless the missing 1-40 rules are supplied. |
| 41, 42, 45 | Open | Legacy plural/vague model names remain. Do not broad rename; each rename needs tests, compatibility aliases, route/model binding checks, morph/queue/API review, and docs. |
| 43, 44 | Open | `CommonHelper`, `ApiHelper`, and large controllers still carry unrelated behavior. Extract upload, settings, language, friendship, payment, and media logic gradually. |
| 46, 47, 48 | Open | The action/service pattern is partial. New actions should prefer `execute()`, but many controllers still build workflows directly. |
| 49, 50, 51, 52 | Partially open | Some payment/HTTP flows have tests and timeouts, but provider SDK calls and gateway failures still need clean clients/results and no-network tests. |
| 53, 54, 55 | Partially open | Sensitive log sanitizer exists. Remaining work is module-level structured logging standards and removing any unsafe/noisy logs found during domain refactors. |
| 56, 57 | Open | `return false` remains in gateway/business-style paths. Replace with typed results or domain exceptions only after callers are tested. |
| 58, 59, 60, 61, 62, 63 | Open | Legacy schema still has nullable/default/date/count/money/naming debt. Use additive migrations and production-data audits; do not rename persisted columns blindly. |
| 64, 65, 66 | Open | Status fields and transitions are not uniformly enum-backed or centralized. Add tests before extracting transition actions. |
| 67, 68, 69, 70, 71 | Open | Queries in loops/views, unscoped global lookups, repeated `auth()->user()`, and service auth coupling remain. Fix one screen/workflow at a time. |
| 72, 73, 74, 75, 76, 77, 78 | Open | Factory coverage is still far behind model count. Add valid factories and states before broad tests/refactors. |
| 79, 80, 81, 82, 83, 84, 85, 86 | Open | Huge controllers, huge Blade views, inline JS, ad hoc Blade scripts, and legacy asset structure remain. Pint is green, but frontend cleanup is still large. |
| 87, 88, 89, 90, 91 | Open | Upload/download handling is still split across controllers/helpers. Centralize disks, generated names, authorization, metadata, and cleanup policies with storage tests. |
| 92, 93, 94, 95, 96, 97, 98, 99, 100 | Open | Queue/job/event architecture is still minimal. Slow emails, notifications, media processing, exports/imports, and external calls need idempotent jobs with retries/backoff/failure plans. |

## Never-Ending Implementation Loop

Use this loop for every future cleanup slice:

1. Pick one domain and one rule group.
2. Read the existing docs and current code for that domain.
3. Add characterization tests for current behavior.
4. Make the smallest behavior-preserving cleanup.
5. Run focused tests.
6. Run the relevant quality gate.
7. Update docs/debt if any risk remains.
8. Commit one scoped change.
9. Repeat.

Do not skip step 3 for risky code. Do not combine unrelated rule groups in one
commit.

## Phase 0: Keep Baseline Honest

**Goal:** Never let cleanup work start from an unknown state.

Tasks:

- [ ] Keep `main` clean before each slice.
- [ ] Run `git status --short --branch` before edits.
- [ ] Run focused tests for the target module before changing behavior.
- [ ] Run `vendor/bin/pint --test` for PHP changes.
- [ ] Run `composer analyse` when PHP types/contracts are touched.
- [ ] Run `php artisan test` before commits that affect behavior.
- [ ] Update this plan after every completed/refused/deferred rule group.

Acceptance:

- [ ] Every cleanup commit states the rule group it advances.
- [ ] Remaining risks are in docs, not hidden in chat.

## Phase 1: Finish Characterization And Factory Coverage

**Goal:** Make refactors possible without guessing.

Tasks:

- [ ] Add factories for `Posts`, `Comments`, `Stories`, `Albums`, `Group`,
  `GroupMember`, `Event`, `Video`, `Job`, `JobApply`, `Sponsor`, `Report`,
  `Notification`, `PaymentGateway`, and `PaymentHistory`.
- [ ] Add factory states for owner/non-owner, unread/read, active/disabled,
  public/private, paid/unpaid, approved/pending/rejected.
- [ ] Add API contract smoke tests for representative endpoints in albums,
  blogs, events, groups, jobs, paid content, profile media, videos,
  fundraisers, comments/reactions, search, and notification accept/decline.
- [ ] Add authorization matrix tests for posts, comments, stories, media,
  groups, events, jobs, badges, sponsors, paid content, reports, admin CRUD,
  and remaining chat risks.
- [ ] Add query-count tests for feed, search, profile, groups, events, chat,
  notifications, and admin lists.

Acceptance:

- [ ] No new refactor starts without at least one behavior test for the target
  domain.
- [ ] Factories create valid models by default.

## Phase 2: Naming Cleanup Without Breaking Contracts

**Goal:** Move toward rules 41, 42, and 45 without unsafe DB/API renames.

Tasks:

- [ ] Create a model rename inventory for `Posts`, `Comments`, `Albums`,
  `Stories`, `Users`, `Friendships`, `PaidContentPackages`, and `Setting`.
- [ ] For each model, list controllers, Blade files, factories, policies,
  route model binding, API payloads, queue payloads, morph types, and docs.
- [ ] Rename only one model family at a time.
- [ ] Keep table names stable unless a separate expand/backfill/contract
  migration is approved.
- [ ] Add compatibility aliases only when public contracts need them.

Acceptance:

- [ ] The selected model family has characterization tests before rename.
- [ ] `composer dump-autoload`, Pint, static analysis, and tests pass.

## Phase 3: Chat Security And Naming Follow-Up

**Goal:** Use the new chat tests to repair the most dangerous chat problems.

Tasks:

- [x] Fix standalone `chat.read` so it no longer 500s on direct use.
- [x] Replace raw `$_GET` access in `chat.load` and `search.chat` with typed
  `Request` input.
- [x] Add policies or participant-scoped queries for chat read, delete, react,
  and API message lookup.
- [x] Make cross-user chat delete and global thread lookup fail safely.
- [x] Move chat search HTML rendering into an escaped Blade partial or return a
  JSON contract with a compatibility shim.
- [x] Add XSS tests for chat search names and last-message text.
- [ ] Remove the per-contact last-message query loop from chat search.
- [ ] Only after behavior is protected, plan the persisted rename:
  `message_thrades` -> `message_threads`, `message_thrade` ->
  `message_thread_id`, `reciver_id` -> `receiver_id`, `chatcenter` ->
  `chat_center`.

Acceptance:

- [x] Existing legacy chat tests are updated intentionally.
- [x] Unauthorized users cannot read/delete/react to unrelated chat records.

## Phase 4: Destructive GET Routes

**Goal:** Reduce CSRF/crawler risk from rule groups 67-71 and security docs.

Tasks:

- [ ] Produce a route inventory of all state-changing-looking GET routes.
- [ ] Add current behavior tests for one low-risk route family.
- [ ] Convert that family to POST/PATCH/DELETE with CSRF/method spoofing.
- [ ] Update Blade/JS callers.
- [ ] Keep route names where possible.
- [ ] Repeat route family by route family.

Acceptance:

- [ ] Guests are rejected.
- [ ] Unauthorized users are forbidden or redirected safely.
- [ ] Authorized users can complete the action.
- [ ] No destructive action remains callable by GET in the touched family.

## Phase 5: ApiController Decomposition

**Goal:** Stop the 7442-line API controller from being the center of the app.

Tasks:

- [ ] Choose one API domain at a time: chat, notifications, marketplace, feed,
  profile media, groups, events, jobs, payments.
- [ ] Add endpoint contract tests for current response shape.
- [ ] Move validation into Form Requests.
- [ ] Move authorization into policies/Form Request `authorize()`.
- [ ] Move workflows into actions/services.
- [ ] Move list/filter logic into query objects/scopes.
- [ ] Move non-trivial JSON output into API Resources while preserving shape.
- [ ] Remove manual bearer-token checks once middleware/guard tests prove it.

Acceptance:

- [ ] The extracted controller is thin.
- [ ] Public API response shape is protected by tests.

## Phase 6: Blade Query And Raw Output Cleanup

**Goal:** Move Blade toward presentation-only code.

Tasks:

- [ ] Start with the highest-risk Blade files from scans:
  search views, album detail views, event views, post modals, admin blog/page
  category selects, and right sidebar.
- [ ] Move queries/counts from Blade to controllers/actions/ViewModels.
- [ ] Pass prepared collections/counts/booleans to views.
- [ ] Replace repeated `auth()->user()` with prepared `$currentUser` or view
  data where practical.
- [ ] Replace raw rich-text output with a documented sanitizer.
- [ ] Add XSS payload tests for every changed rich-text surface.
- [ ] Add render tests proving the expected content still appears.

Acceptance:

- [ ] No new `DB::table`, model query, or aggregate is introduced in Blade.
- [ ] User content is escaped by default.

## Phase 7: Upload, Download, And Storage Contract

**Goal:** Complete rules 87-91.

Tasks:

- [ ] Inventory upload paths for posts, stories, chat, profile media, pages,
  groups, events, jobs, marketplace, badges, sponsors, and paid content.
- [ ] Add `Storage::fake()` tests for valid image/video, invalid extension,
  MIME mismatch, oversized files, executable uploads, path traversal, and
  unauthorized delete/download.
- [ ] Move validation to Form Requests.
- [ ] Generate stored filenames; store original names separately when needed.
- [ ] Store disk/path metadata instead of bare public paths.
- [ ] Decide delete policy for each model: retain, delete on delete, or delete
  on force delete.
- [ ] Move private media behind authorized download routes.

Acceptance:

- [ ] No touched upload path writes user-controlled filenames as storage paths.
- [ ] Unauthorized downloads/deletes fail.

## Phase 8: Payment, External Services, And `return false`

**Goal:** Complete rules 49-57 for payment/provider flows.

Tasks:

- [ ] Add gateway result objects or domain exceptions for Stripe, Razorpay,
  Paytm, Flutterwave, Paystack, PayPal-style flows.
- [ ] Replace ambiguous `return false` only after callers have tests.
- [ ] Wrap SDK/static clients behind testable adapter classes.
- [ ] Ensure every HTTP call has timeout, config-backed URL/keys, failure
  handling, and safe logs.
- [ ] Verify no external service is called inside a database transaction.
- [ ] Add no-real-network tests for every provider.
- [ ] Add webhook signature, replay, and idempotency tests provider by
  provider.

Acceptance:

- [ ] Callers can distinguish missing credentials, provider failure, invalid
  state, and successful payment.
- [ ] No tests hit real provider networks.

## Phase 9: Status And State Transition Cleanup

**Goal:** Complete rules 64-66.

Tasks:

- [ ] Inventory status fields across users, posts, pages, groups, events,
  jobs, notifications, payments, badges, sponsors, marketplace, reports, and
  addons.
- [ ] Add enum/constants for stable status domains.
- [ ] Add validation rules and tests for every status input.
- [ ] Extract important transitions into actions:
  activate/deactivate, approve/reject, mark paid/refunded, mark read/unread,
  publish/archive, accept/decline.
- [ ] Add transition matrix tests.
- [ ] Replace repeated save-after-each-field updates with one update call.

Acceptance:

- [ ] Important status changes happen through one tested path.

## Phase 10: Database And Migration Safety

**Goal:** Complete schema-related rules without data loss.

Tasks:

- [ ] Compare production schema/data against `database/schema/install.sql` and
  additive migrations.
- [ ] Produce a baseline migration strategy.
- [ ] Audit dirty data before adding more FKs, unique constraints,
  nullability, money precision, JSON shape, and datetime constraints.
- [ ] Add indexes only with query evidence and duplicate-index checks.
- [ ] Standardize money fields with decimal/minor-unit casts and tests.
- [ ] Align date columns with `_at` naming only through additive
  expand/backfill/contract plans.
- [ ] Document cleanup commands in `docs/data-cleanup-needed.md`.

Acceptance:

- [ ] Every schema-affecting change has rollback and deployment notes.

## Phase 11: Frontend Accessibility And Asset Cleanup

**Goal:** Complete rules 83-86 plus frontend standards.

Tasks:

- [ ] Add page-level Blade render tests for critical forms and modals.
- [ ] Add browser smoke tests or a small accessibility runner for login,
  timeline post, marketplace, chat, profile media, admin CRUD, and payments.
- [ ] Replace clickable `div/span/a href="javascript:void(0)"` actions with
  semantic buttons where behavior is not navigation.
- [ ] Add labels, error descriptions, fieldsets, landmarks, table headers, and
  focus states page by page.
- [ ] Move inline JS into `resources/js` modules.
- [ ] Keep Laravel Mix stable until a dedicated Vite migration is planned and
  tested.
- [ ] Plan Mix-to-Vite as a separate asset-only migration with build and visual
  checks.

Acceptance:

- [ ] `npm run quality` passes.
- [ ] Critical forms remain usable by keyboard and screen-reader conventions.

## Phase 12: Jobs, Queues, Events, And Notifications

**Goal:** Complete rules 92-100.

Tasks:

- [ ] Inventory slow synchronous work: emails, notifications, media processing,
  exports/imports, external API calls, payment reports, webhook side effects.
- [ ] Move one workflow at a time into jobs dispatched after commit.
- [ ] Jobs should carry IDs, not large arrays/model dumps.
- [ ] Add idempotency checks for every retryable job.
- [ ] Add `tries`, `backoff()`, timeout, and failure handling.
- [ ] Add `Queue::fake()`, `Notification::fake()`, and failure-path tests.
- [ ] Rename/create events as past-tense facts only when they decouple real
  side effects.
- [ ] Document failed-job retry and cleanup procedure.

Acceptance:

- [ ] Retrying important jobs does not duplicate payments, emails, media, or
  notifications.

## Phase 13: CI, Deployment, And Production Reality

**Goal:** Make the cleanup sustainable.

Tasks:

- [ ] Keep `composer ci` and GitHub Actions green.
- [ ] Add optional MySQL-compatible migration rehearsal if production uses
  MySQL/MariaDB.
- [ ] Add JUnit/coverage artifacts if coverage tracking is required.
- [ ] Add scheduled flake/random-order test checks if suite instability
  appears.
- [ ] Document queue supervisor, scheduler, backup owner, restore drill,
  RTO/RPO, health checks, smoke tests, and rollback steps per environment.
- [ ] Keep `docs/known-technical-debt.md` current after each refactor.

Acceptance:

- [ ] Production deployment steps are repeatable without hidden local knowledge.

## Recommended Next 20 Slices

1. Done: fix `chat.read` route/method contract with tests.
2. Done: add chat participant authorization for web/API read/delete/react/message
   lookup.
3. Done: replace raw `$_GET` in chat load/search with `Request`.
4. Done: move chat search HTML to escaped rendering with XSS coverage.
5. Add route-risk tests for one destructive GET route family.
6. Convert that route family to CSRF-protected POST/DELETE.
7. Add factories for `Posts`, `Comments`, `Stories`, `Group`, and `Event`.
8. Add `PostPolicy` with owner/non-owner/admin tests.
9. Add API contract smoke tests for feed endpoints.
10. Move one feed upload path to a Form Request plus `Storage::fake()` tests.
11. Remove DB queries from one search Blade view.
12. Extract one `ApiController` chat/feed/profile method into a thin
    controller/action/resource slice.
13. Replace one payment gateway `return false` path with a typed result.
14. Add no-real-network tests for one unwrapped provider SDK.
15. Add status enum/transition action for one high-risk status field.
16. Add query-count test for timeline or search.
17. Add browser/accessibility smoke test for login and one post form.
18. Add MySQL migration rehearsal documentation or CI job.
19. Add queue job for one slow external/mail/media workflow.
20. Update this plan and `docs/known-technical-debt.md` after each slice.

## Definition Of Done For Any Rule Slice

- [ ] Scope is one domain or one rule group.
- [ ] Current behavior is tested before risky code changes.
- [ ] Public routes/API payloads/database contracts are preserved or migrated
  with compatibility notes.
- [ ] No unrelated files are changed.
- [ ] No secrets are added.
- [ ] `vendor/bin/pint --test` passes for PHP changes.
- [ ] `composer analyse` passes when PHP contracts/types are touched.
- [ ] Focused tests pass.
- [ ] Full `php artisan test` is attempted before behavior commits.
- [ ] Docs are updated with remaining risk.
