# Coding Standards

Generated: 2026-07-01

These standards apply to new code and to refactor slices in this Laravel project. Existing legacy code may not comply yet; use `docs/refactor-audit.md` and `docs/refactor-checklist.md` to prioritize cleanup without mixing unrelated refactors into feature commits.

## Core Principles

- Prefer Laravel conventions over custom framework patterns.
- Keep behavior changes, refactors, formatting, tests, and documentation in separate commits.
- Write Eloquent queries through models, relationships, scopes, query classes, actions, or services.
- Do not write raw SQL strings in application code.
- Do not use `DB::select()`, `DB::statement()`, `DB::raw()`, or `DB::unprepared()` in controllers, views, jobs, or helpers.
- Do not query from Blade views, loops, conditionals, or component templates.
- Do not expose secrets, provider tokens, payment credentials, or internal config values to the browser.
- Add tests before risky refactors and record the verification command output in the change summary.

## PSR-12 Style

- Follow PSR-12 and Laravel Pint formatting.
- Use `declare(strict_types=1);` only if the surrounding namespace already uses strict types or a refactor explicitly adopts it for a coherent slice.
- Use one class per file and match namespace to path.
- Put imports at the top, sorted by Pint.
- Use short array syntax.
- Use trailing commas in multiline arrays and argument lists where Pint applies them.
- Prefer early returns over deeply nested conditionals.
- Avoid commented-out code; git history keeps the old version.
- Comments should explain why something is unusual, not what obvious code does.
- Run `vendor/bin/pint --test` before committing code changes; use `vendor/bin/pint` only when the formatting-only diff is intended.

## Laravel Naming Conventions

- Controllers: singular domain name plus `Controller`, for example `StoryController` or `PaymentController`.
- Form Requests: action-based names ending in `Request`, for example `StoreStoryRequest`, `UpdateProfileRequest`, `ConfirmPaymentRequest`.
- API Resources: names ending in `Resource` or `Collection`, for example `StoryResource`, `UserCollection`.
- Actions: imperative verb phrase ending in `Action`, for example `CreateStoryAction`, `DeleteMediaFileAction`.
- Services: integration or domain capability ending in `Service`, for example `PaystackService`, `SettingsService`.
- Policies: model name plus `Policy`, for example `StoryPolicy`, `MediaFilePolicy`.
- Jobs: imperative verb phrase, for example `ProcessStoryMedia`, `SendFriendRequestNotification`.
- Events: past-tense domain events, for example `StoryCreated`, `PaymentConfirmed`.
- Listeners: imperative handler names, for example `SendStoryCreatedNotifications`.
- Models: singular StudlyCase class names backed by Laravel table conventions unless legacy schema requires explicit `$table`.
- Migrations: Laravel timestamped names that describe one concern, for example `add_status_index_to_stories_table`.
- Tests: behavior-based names ending in `Test`, grouped under `tests/Feature` or `tests/Unit`.

## Controllers

- Keep controllers thin: parse the request, authorize, call an action/service/query, and return a response.
- Do not put business rules, query construction, file handling, or payment verification directly in controllers.
- Do not manually parse bearer tokens in controller methods; use middleware and guards.
- Use route model binding instead of manual `find()` calls where possible.
- Use named routes for redirects.
- Return API Resources for JSON responses instead of raw arrays from complex domains.
- Move repeated logic into actions, services, policies, model scopes, or query classes.
- Keep one public controller method focused on one route action.
- Avoid controller methods that mix HTML response logic and JSON response logic; split when behavior diverges.

## Form Requests

- Use Form Requests for validation on all write endpoints.
- Put input validation in `rules()` and permission checks in `authorize()` when authorization is specific to the command.
- Keep controller validation out of controller methods except for temporary characterization work.
- Validate enum-like values with `Rule::in()` or PHP enums where possible.
- Validate uploads with MIME type, max size, dimensions/duration where relevant, and ownership/context rules.
- Normalize API validation errors through Laravel response handling or explicit JSON resources.
- Add tests for required fields, invalid values, unauthorized users, oversized uploads, and malformed payloads.

## Resources

- Use `JsonResource` and Resource Collections for API output shaping.
- Do not return full Eloquent models directly from API endpoints when relationships or sensitive attributes may leak.
- Keep resource classes presentation-focused; do not query inside `toArray()`.
- Ensure relationships used by resources are eager loaded by the controller/query layer.
- Use `whenLoaded()`, `whenCounted()`, and `when()` for optional fields.
- Keep Blade view data explicit: controllers should pass named data, DTOs, or ViewModels, not expect views to query.

## Services and Actions

- Use single-purpose Actions for business commands such as creating stories, deleting media, accepting requests, and changing statuses.
- Use Services for integrations or cohesive domain capabilities such as payments, settings, translations, uploads, and notifications.
- Prefer constructor injection over static service locators.
- Keep Actions testable without HTTP requests.
- Keep Services free of Blade rendering and controller response details.
- Do not hide database writes inside global helpers.
- Return explicit results, DTOs, models, or exceptions instead of loosely shaped arrays when the contract matters.
- Add unit tests for complex Actions and integration-style feature tests for end-to-end behavior.

## Policies

- Use Policies for all model authorization.
- Do not rely on UI hiding as authorization.
- Do not scatter role checks across controllers and Blade views.
- Cover guest, owner, non-owner, disabled/unverified user, normal user, and admin cases.
- Use `$this->authorize()` in controllers and `can` middleware where route-level authorization is clear.
- Use policy methods that match Laravel conventions: `viewAny`, `view`, `create`, `update`, `delete`, `restore`, `forceDelete`.
- Add policy tests before moving sensitive routes from legacy checks to policies.

## Jobs and Queues

- Queue work that can exceed 200ms or depends on external systems: email, media processing, provider calls, notification fanout, webhooks, exports, and cache refreshes.
- Jobs must be idempotent when retrying could duplicate side effects.
- Set explicit tries, timeout, backoff, and failure behavior for non-trivial jobs.
- Pass IDs or small DTOs into jobs, not large hydrated models with unnecessary relationships.
- Re-load models inside the job and handle missing records safely.
- Use `Queue::fake()` in tests for dispatch expectations.
- Document queue worker requirements in deployment docs when adding new queued work.

## Events and Listeners

- Use Events for domain facts that already happened, not commands.
- Keep event payloads small and serializable.
- Keep listeners focused on side effects such as notifications, audit logs, and cache invalidation.
- Queue listeners when they call external services or perform expensive work.
- Do not use events to obscure required synchronous validation or authorization.
- Test event dispatch separately from listener side effects when it keeps tests clearer.

## Tests

- Add Feature tests for HTTP routes, middleware, policies, validation, redirects, and user-visible workflows.
- Add Unit tests for Actions, Services, model scopes, query classes, and complex pure logic.
- Use factories instead of manual inserts.
- Use `RefreshDatabase` or the project-standard database reset strategy for tests touching persistence.
- Use `Http::fake()` for external API calls.
- Use `Queue::fake()`, `Event::fake()`, `Notification::fake()`, and `Storage::fake()` where appropriate.
- Write behavior assertions, not implementation-detail assertions, unless the test is a temporary characterization guard for a refactor.
- Keep test names descriptive: `guest_cannot_delete_another_users_story`.
- Run `php artisan test` before committing application logic changes.
- If the suite is red before your work, record the exact pre-existing failures and keep your commit scoped.

## Migrations

- Use one concern per migration.
- Always implement both `up()` and `down()`.
- Do not edit existing production migrations; create a new migration.
- Add indexes when adding foreign keys or frequently filtered columns.
- Use foreign keys when the lifecycle and delete behavior are understood.
- Use explicit column types and nullable/default behavior.
- Avoid raw SQL in migrations unless there is no Laravel schema-builder equivalent and the exception is documented.
- Keep data migrations separate from schema migrations when possible.
- Verify `php artisan migrate:fresh --seed` or the project-approved equivalent after schema changes.

## Models

- Define `$fillable` on every model that accepts mass assignment.
- Do not use `$guarded = []`.
- Define `$casts` for booleans, dates, JSON, arrays, encrypted fields, money-like values, and enums.
- Define relationships instead of repeating joins in controllers.
- Use local scopes for reusable filters, one responsibility per scope.
- Use `with()`, `loadMissing()`, `withCount()`, `withExists()`, and aggregate helpers to avoid N+1 queries.
- Do not call `Model::all()` without a limit, scope, pagination, or clear bounded dataset.
- Keep model methods focused on model behavior; move workflows with side effects to Actions or Services.
- Use observers for cross-cutting model side effects such as audit logs and cache invalidation.
- Hide sensitive fields with `$hidden` or API Resources.

## Configuration Usage

- Access runtime settings through `config()`, typed services, or model-backed settings services.
- Do not call `env()` outside config files.
- Do not query settings directly from Blade.
- Cache expensive settings reads at the service/model layer and invalidate on update.
- Keep provider configuration in `config/*` files with clear keys.
- Verify `php artisan config:cache` after changing config files.
- Do not store secrets in config files unless they read from environment variables.

## Environment Variables

- Every new environment variable must be added to `.env.example`.
- Environment variable names should be uppercase snake case and grouped by provider/domain.
- Never commit `.env`, production secrets, API keys, private keys, OAuth secrets, webhook signing secrets, or payment credentials.
- Do not render environment-backed secrets into Blade or JavaScript.
- Use safe defaults for local development and fail closed in production when required secrets are missing.
- Document required production variables when adding new integrations.

## Git Workflow

- Keep commits atomic and scoped to one concern.
- Use Conventional Commits, for example `docs: add coding standards`, `fix(auth): guard disabled users`, or `refactor(stories): extract visibility query`.
- Do not mix formatting-only changes with behavior changes.
- Do not mix documentation-only changes with application logic changes.
- Inspect `git status --short` and the staged diff before every commit.
- If unrelated files are already staged, commit with an explicit pathspec so they are not included accidentally.
- Do not revert or overwrite work you did not create unless explicitly asked.
- Run relevant checks before committing and record failures honestly.
- Push only after verifying the intended commit is on `HEAD`.
- Prefer small refactor slices with tests over broad cleanup commits.

## Pre-Commit Checklist

- [ ] The change has one clear purpose.
- [ ] No unrelated files are staged.
- [ ] No secrets or credentials appear in the diff.
- [ ] No new raw SQL, DB facade query, Blade query, or aggregate-in-loop pattern was introduced.
- [ ] Controllers stay thin and delegate validation, authorization, business logic, and queries.
- [ ] New write endpoints use Form Requests.
- [ ] New model actions are policy-protected.
- [ ] New queries use Eloquent relationships, scopes, or query classes.
- [ ] New visible behavior has Feature tests.
- [ ] New actions/services/scopes have Unit or focused Feature tests.
- [ ] `php artisan test` result is recorded.
- [ ] `vendor/bin/pint --test` result is recorded for PHP changes.
- [ ] Config changes pass `php artisan config:cache` where practical.
