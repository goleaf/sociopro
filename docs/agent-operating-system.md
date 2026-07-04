# Agent Operating System

Last updated: 2026-07-04

This document turns the repository's Markdown rules, audits, and live checkout
evidence into a practical operating system for future agents.

## Current Evidence

- Live checkout: legacy Laravel social application, Blade SSR frontend, Vite
  with `resources/scss/app.scss` and `resources/js/app.js`.
- Locked stack from repository files: PHP `^8.3`, Laravel `v13.18.0`,
  Sanctum `v4.3.2`, PHPUnit `12.5.30`, Pint `v1.29.3`, Larastan `v3.10.0`,
  Rector `2.5.2`, npm lockfile v3.
- The repository tracks more than 70 Markdown docs. Several older audit docs
  are historical and must lose to live evidence when they mention Laravel 9,
  Laravel Mix/Webpack, missing quality tools, or old failing gates.
- The worktree was dirty when this automation was added. Future agents must
  inspect `git status --short --branch` before edits and stage only their own
  slice.

## Stable Operating Rules

1. Read `AGENTS.md` first, then the relevant docs for the touched domain.
2. Detect installed versions from `composer.json`, `composer.lock`,
   `package.json`, `package-lock.json`, Vite/PostCSS config, PHPUnit/Pint/PHPStan
   config, and current source, not from memory.
3. Preserve legacy behavior unless a bug or security issue is proven with tests.
4. Add characterization tests before risky controller, API, route, upload,
   payment, migration, or Blade-query refactors.
5. Keep controllers thin, Blade presentation-only, queries in Eloquent scopes or
   query classes, writes in Actions/Services, validation in Form Requests, and
   authorization in Policies/Gates.
6. Update documentation when behavior, commands, dependencies, deployment,
   security posture, or agent rules change.
7. Commit and push only after verification, staged-diff review, secret scan, and
   confirmation that the intended commit is at `HEAD`.

## Installed Hooks

The project hook manifest is `.codex/hooks.json`.

- `task-start.mjs` injects current stack, dirty-tree state, docs, and suggested
  subagents at session/prompt start.
- `pre-tool-guard.mjs` blocks risky shell/git patterns: broad staging,
  destructive reset/clean/restore, force push, broad `rm -rf`, and destructive
  migration commands that are not clearly pointed at testing/temporary data.
- `post-edit-guard.mjs` scans added diff lines after edits for common violations:
  debug output, raw DB APIs, Blade queries, runtime `env()`, `$request->all()`,
  `$guarded = []`, unbounded `::all()`, and documentation drift.
- `guarded-publish.mjs` runs at stop time and reminds the agent which checks,
  staging discipline, commit, and push steps remain. It can block dirty final
  states when `SOCIOPRO_HOOK_ENFORCE_PUBLICATION=1`.
- The existing Impeccable UI hook remains active for UI-relevant edits.

The hooks are intentionally not blind mutators. They do not rewrite docs,
auto-stage files, or push code without checks because the project rules require
human-reviewable, scoped commits.

## Subagent Roster

Project-local briefs live in `.agents/subagents/`.

- `repo-steward`: task orientation, dirty-tree safety, scope discipline.
- `api-contract-guardian`: API/Sanctum/JSON compatibility.
- `frontend-blade-accessibility-guardian`: Blade, SCSS, Vite, accessibility.
- `database-query-migration-guardian`: Eloquent, indexes, migrations, query
  performance.
- `security-payment-guardian`: auth, policies, uploads, payments, webhooks,
  secrets, logging.
- `quality-release-guardian`: test/build/lint/cache/CI gate truthfulness.
- `documentation-context-curator`: docs alignment and stale-doc detection.
- `laravel-architecture-refactorer`: controller/helper decomposition.
- `design-system-guardian`: `DESIGN.md` and product UI consistency.

Use subagents for independent review or parallel analysis. The main agent still
owns final integration, staging, verification, commit, and push.

## Recommended Future Hooks

These are good next hooks if the project wants stricter enforcement:

1. `PreToolUse` shell guard for destructive git and schema commands.
2. Diff-based secret scanner before commit or Stop.
3. Route-change hook that requires `php artisan route:cache` and route tests.
4. Migration-change hook that requires rollback notes and safe database target.
5. Package-file hook that requires `composer validate`, audit/build checks, and
   docs updates.
6. Blade-change hook that runs a focused no-query static scan and view tests
   when known high-risk views are touched.
7. API-change hook that routes to `tests/Feature/Api/Contracts`.
8. Docs-staleness hook that flags historical baseline language after dependency
   changes.
9. Quality-known-failure hook that requires a new entry when a required gate is
   red for a pre-existing reason.

Add stricter hooks one at a time. A noisy hook that agents learn to ignore is
worse than a small hook with a reliable signal.
