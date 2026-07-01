# AGENTS.md - Laravel Refactor Instructions

These instructions apply to the entire repository. Follow them for every code, test, or documentation change unless a more specific nested `AGENTS.md` overrides them.

## Project Context

- This is a legacy Laravel social application with known refactor/security debt documented in `docs/refactor-audit.md`, `docs/refactor-checklist.md`, and `docs/coding-standards.md`.
- Preserve existing behavior unless the user explicitly asks for a behavior change.
- Prefer small, verified refactor slices over broad rewrites.
- Keep documentation-only changes separate from application logic changes.

## Refactor Rules

- Preserve behavior first. Add characterization tests before refactoring risky legacy flows.
- Write or update tests with every behavior-affecting change.
- Prefer Form Requests for validation on write endpoints.
- Keep controllers thin: authorize, validate/delegate, and return responses.
- Do not put business logic, query construction, payment logic, upload handling, or notification fanout directly in controllers.
- Use Policies for authorization. Do not rely on Blade/UI hiding or scattered role checks.
- Use Eloquent relationships, scopes, query classes, actions, and services for data access.
- Use eager loading with `with()`, `loadMissing()`, `withCount()`, `withExists()`, or aggregate helpers for relationships shown in views/resources.
- Do not query from Blade views, loops, conditionals, or component templates.
- Avoid raw SQL. If there is no Laravel/Eloquent/schema-builder alternative, isolate the exception, document why it is necessary, and cover it with tests.
- Do not use `DB::select()`, `DB::statement()`, `DB::raw()`, or `DB::unprepared()` in controllers, views, jobs, or helpers.
- Avoid `env()` outside config files. Access environment-backed values through `config()`.
- Do not expose secrets, provider tokens, payment credentials, or environment values in Blade or JavaScript.

## Structure Preferences

- Put business commands in `app/Actions` or focused domain services.
- Put reusable query rules on models as scopes or in query classes.
- Put request validation in `app/Http/Requests`.
- Put API output shaping in `app/Http/Resources`.
- Put authorization in `app/Policies`.
- Put slow or external side effects in queued jobs.
- Use events/listeners for decoupled side effects after core state changes succeed.
- Keep models responsible for relationships, casts, fillable fields, scopes, and model-local behavior.

## Testing

- Run `php artisan test` before committing.
- For PHP code changes, also run `vendor/bin/pint --test`.
- Use factories rather than manual inserts in tests.
- Use `Http::fake()`, `Queue::fake()`, `Event::fake()`, `Notification::fake()`, and `Storage::fake()` where appropriate.
- If the suite is already failing, record the exact pre-existing failures and keep the commit scoped to the requested change.
- Do not claim tests pass unless the fresh command output confirms it.

## Git Workflow

- Keep commits small and atomic.
- Use Conventional Commit messages such as `docs: add agent instructions`, `fix(auth): guard disabled users`, or `refactor(stories): extract visibility query`.
- Inspect `git status --short` and staged diffs before committing.
- If unrelated files are staged or modified, do not include them. Use explicit pathspec commits when needed.
- Do not revert or overwrite user changes unless explicitly asked.
- Run relevant checks before commit and report failures honestly.
- Push only after verifying the intended commit is at `HEAD`.
