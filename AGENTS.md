# AGENTS.md - Strict Laravel Agent Instructions

These instructions apply to the entire repository. Follow them for every code, test, documentation, database, frontend, configuration, and deployment change unless a more specific nested `AGENTS.md` overrides them.

## Project Context

- This is a legacy Laravel social application with known refactor and security debt.
- Current detected baseline: PHP `^8.3`, Laravel `13.18.0`, Laravel Sanctum `4.3.2`, PHPUnit `12.5.30`, Laravel Pint `1.29.3`, Node `v22.22.3`, npm `10.9.8`.
- Frontend build is Laravel Mix / Webpack. Do not assume Vite is installed until `vite.config.*` and package files prove it.
- Installed quality tools include PHPUnit and Pint. Do not assume Larastan/PHPStan, Rector, ESLint, Stylelint, or Prettier are available until dependency files prove it.
- Canonical project docs: `docs/project-standards-bible.md`, `docs/coding-standards.md`, `docs/refactor-audit.md`, `docs/refactor-checklist.md`, `docs/enterprise-refactor-rulebook.md`, and `docs/refactor-roadmap-unreal.md`.

## Project Detection Rules

Before changing files, agents must inspect the live checkout:

- Run or inspect `git status --short --branch`.
- Read `composer.json`, `composer.lock`, `package.json`, lock files, `webpack.mix.js`, `vite.config.*` if present, `phpunit.xml`, `pint.json`, and relevant config files.
- Detect Laravel, PHP, PHPUnit, Pint, Node, npm, build tool, database drivers, queue/cache drivers, and installed static-analysis/linting tools from repository files, not memory.
- Inspect relevant routes, controllers, Form Requests, policies, models, migrations, jobs, services/actions, Blade views, tests, and docs before editing.
- Treat older audit docs as context, not proof of current state. Current checkout evidence wins.
- If the tree is dirty, separate user changes from the requested scope. Do not overwrite or revert user work unless explicitly asked.

## Laravel Compatibility Rules

- Write code compatible with the installed Laravel version, currently Laravel `13.18.0`.
- Prefer documented Laravel APIs and existing project patterns over guessed or outdated conventions.
- Use Form Requests for validation and request-specific authorization on write endpoints.
- Use policies/gates for authorization. Do not rely on Blade conditionals, hidden buttons, or frontend checks for access control.
- Use Eloquent models, relationships, scopes, query classes, services, and actions for data access and workflows.
- Use API Resources for non-trivial JSON output.
- Use `config()` for runtime settings. Laravel production config caching means `env()` must only be called from config files.
- Keep controllers thin: authorize, validate, delegate, and return a response.
- Keep Blade presentation-only. No queries, aggregates, settings lookups, or business logic in views.
- Use eager loading and aggregate eager loading (`with()`, `loadMissing()`, `withCount()`, `withExists()`, `withSum()`, etc.) for relationships and counts rendered in views or resources.

## Non-Negotiable Safety Rules

- Do not change public behavior without tests proving the intended behavior.
- Before risky refactors, add characterization or regression tests that lock down current behavior.
- Do not commit secrets, credentials, tokens, private keys, provider keys, payment secrets, database dumps, or production `.env` values.
- Do not add debug code: no `dd()`, `dump()`, `ray()`, `var_dump()`, `print_r()`, `console.log()`, temporary debug routes, debug middleware, or debug-only Blade output.
- Do not leave commented-out code, temporary TODOs, local paths, test credentials, fake production values, or one-off scripts in committed changes.
- Do not call `env()` outside config files. Add new environment values to config files and `.env.example`.
- Do not mass assign unvalidated input. Use `$request->validated()`, `$request->safe()`, explicit DTOs, or explicit field mapping.
- Do not use `$request->all()` or raw request payloads in `Model::create()`, `update()`, `fill()`, `forceFill()`, relationship `create()`, or bulk update calls.
- Every mass-assignable model must define explicit `$fillable` or equivalent Laravel mass-assignment attributes. Do not introduce `$guarded = []`.
- Do not concatenate raw SQL. Use Eloquent/query builder parameter binding.
- Do not use `DB::select()`, `DB::statement()`, `DB::raw()`, `DB::unprepared()`, or raw expressions in controllers, views, jobs, helpers, or resources.
- If raw SQL is truly unavoidable, isolate it in a model scope or query object, bind parameters safely, document the exception, and add tests.

## Migration and Database Rules

- Do not create unsafe migrations.
- Do not edit existing production migrations; create a new migration.
- Every migration must have a reversible `down()` method unless the operation is explicitly irreversible and documented.
- Destructive migrations require explicit backup, rollback, and deployment notes.
- Add indexes for foreign keys, common filters, common sorting columns, unique lookups, and cursor pagination columns.
- Use foreign keys when lifecycle and delete behavior are understood.
- Use transactions for multi-step writes that must succeed or fail together.
- Do not use floats for money. Use decimals or integer minor units.
- Do not run destructive schema commands against non-test databases without explicit user approval.

## Required Structure

- Business commands: `app/Actions` or focused domain services.
- Integration/domain services: `app/Services`.
- Validation: `app/Http/Requests`.
- Authorization: `app/Policies`.
- API serialization: `app/Http/Resources`.
- Background work: `app/Jobs`.
- Events/listeners: `app/Events` and `app/Listeners`.
- Reusable model filters: local scopes, model concerns, or query classes.
- Blade components: `resources/views/components`.
- Tests: `tests/Feature` for HTTP/application behavior and `tests/Unit` for isolated logic.

## Testing Rules

- `php artisan test` is required before every commit.
- PHP code changes also require `vendor/bin/pint --test`.
- Run focused tests for the touched domain when available.
- Use factories instead of manual database inserts.
- Use `RefreshDatabase` or the project-standard reset strategy for persistence tests.
- Use `Http::fake()`, `Queue::fake()`, `Event::fake()`, `Notification::fake()`, `Storage::fake()`, and mail fakes when external side effects are involved.
- Never hit real payment providers, email providers, social APIs, object storage, or external webhooks from automated tests.
- If tests already fail before the change, record the exact failures and keep the commit scoped.
- Do not claim tests pass unless a fresh command output confirms it.

## Required Commands Before Commit

Minimum for documentation-only changes:

```bash
git diff --check
php artisan test
```

Minimum for PHP/application changes:

```bash
vendor/bin/pint --test
php artisan test
```

Run these when relevant:

```bash
composer validate --strict
npm run production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Only run optional tools when installed or intentionally added in the same scoped task:

```bash
vendor/bin/phpstan analyse
vendor/bin/rector --dry-run
npm run lint
npm run format:check
```

After cache verification commands, clear generated local caches if they create artifacts that should not be committed.

## Git and Commit Rules

- Keep commits small, atomic, and scoped to one concern.
- Use Conventional Commits.
- Commit message format: `<type>[optional scope]: <imperative summary>`.
- Use `docs:` for documentation-only changes.
- Use `fix:` for bug fixes.
- Use `refactor:` for behavior-preserving code restructuring.
- Use `test:` for tests-only changes.
- Use `ci:` for CI workflow changes.
- Use `build:` for dependency/build-tool changes.
- Do not use vague messages like `update`, `fix`, `changes`, or `misc`.
- Inspect `git status --short` before staging.
- Stage only files that belong to the task.
- Inspect `git diff --cached --stat` and `git diff --cached --name-only` before committing.
- Scan staged diffs for secrets before committing.
- Do not include unrelated user changes.
- Do not force push or rewrite shared history unless explicitly instructed.
- Push only after the intended commit is at `HEAD` and verification has completed.

## Response Rules for Agents

- Report what changed, what was intentionally not changed, verification commands, and any remaining risk.
- Include exact test/build results, including failures.
- If a requested check could not be run, say why.
- Never present guesses as facts. If dependency or runtime state matters, verify it from the repository first.
