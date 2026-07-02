# Code Cleanup Standards

Generated: 2026-07-02

These standards describe safe, small refactor slices for this Laravel codebase. Preserve behavior first. When a legacy public route, API payload, database column, queue payload, or serialized value uses an old name, document the compatibility risk before changing it.

## Naming

- PHP classes use StudlyCase / PascalCase.
- PHP file names match the class name exactly.
- PHP class names and class-backed PHP file names do not use underscores.
- Methods and variables use camelCase in new or touched code.
- Database tables and columns may stay snake_case because they are persisted contracts.
- Route names should use dot notation, for example `users.index` and `admin.users.index`.
- Blade file names should use kebab-case for new files. Existing legacy snake_case Blade names may be migrated only in a route/view-reference-safe slice.

Wrong:

```text
Message_thrade
MessageThrade
get_user_data()
$message_thrade
userProfile.blade.php
```

Correct:

```text
MessageThread
getUserData()
$messageThread
message-thread.blade.php
```

## Model And Class Names

- Eloquent model classes represent one entity and must be singular StudlyCase: `User`, `Message`, `Order`, `MessageThread`.
- Database tables may remain plural snake_case: `users`, `messages`, `orders`, `message_threads`.
- Avoid vague model and class names such as `Data`, `Info`, `Setting`, `Log`, `History`, `Helper`, `Common`, `Manager`, or `MainService` unless the name includes the real domain context.
- Avoid unnecessary abbreviations such as `Msg`, `Usr`, `Ord`, `Cfg`, `Req`, `Resp`, `Ctrl`, `Svc`, `Btn`, and `Txt`.
- Traits must describe one focused capability, for example `HasUuid`, `HasSlug`, `BelongsToTenant`, or `FormatsMoney`.
- Do not turn a `BaseController`, trait, helper, or service into a dumping ground for unrelated upload, email, pricing, authorization, or formatting behavior.

Wrong:

```text
Users
Messages
Data
UserTrait
CommonHelper
MsgController
```

Correct:

```text
User
Message
UserProfile
HasUuid
NormalizeUploadedFilename
MessageController
```

## Laravel Structure

- Controllers authorize, validate, delegate to an action/service/query/model, and return a response.
- Write validation in Form Requests for write endpoints.
- Put authorization in policies, gates, or middleware instead of Blade-only checks.
- Put repeated query conditions in Eloquent scopes or query classes.
- Put workflow logic in focused actions or services.
- Shape non-trivial JSON through API Resources.
- Inject actions and services through Laravel's container instead of creating them with `new` inside controllers.
- Use one action method convention in new action classes. This project prefers `execute()` for focused action classes unless an existing local pattern requires otherwise.
- Reserve `handle()` for jobs, commands, listeners, middleware, and existing Laravel conventions unless there is a clear project-level reason.
- Do not send slow email, notifications, file processing, or external API work directly from controllers when the work should be queued or delegated.

## Request And Model Safety

- Do not mass assign `$request->all()` or `request()->all()`.
- Use `$request->validated()`, `$request->safe()->only([...])`, or explicit field mapping.
- Define explicit `$fillable` or equivalent guarded behavior on models.
- Add casts for booleans, integers, decimals, arrays, JSON, dates, datetimes, and enums where supported.
- Hide sensitive attributes such as passwords, remember tokens, API tokens, secret keys, and internal notes.
- Prefer `$request->user()` in controllers, assign it once, and pass the `User` explicitly into actions and services.
- Do not call `Auth::user()` from services/actions unless authentication itself is the responsibility being tested.

## Query Safety

- Avoid `Model::all()` in application flows. Use a named scope, pagination, chunking, or a clearly bounded lookup query.
- Add deterministic ordering before paginating or slicing result sets.
- Eager-load relationships rendered in Blade or API resources.
- Use `withCount()`, `withExists()`, or database aggregates instead of loading full relationships just to count them.
- Avoid raw SQL. If unavoidable, isolate it, bind parameters, document the exception, and add tests.
- Do not run queries inside loops; eager-load relationships, pre-load aggregates, or batch the lookup.
- Prefer `find()` or `findOrFail()` over `where('id', $id)->first()` for primary key lookups.
- Scope ownership in the query when possible, for example `$request->user()->messages()->findOrFail($id)`.
- Do not update one model with repeated `save()` calls. Use one `update([...])` call when the fields belong to the same state change.

## External Services And Logging

- Store external service URLs, keys, and timeouts in config files backed by `.env` values.
- Every HTTP request to an external service must define a timeout and handle failed responses.
- Do not call external services inside a database transaction. Persist local state first, then dispatch a job with `afterCommit()` when needed.
- Empty `catch` blocks are not allowed. Catch the specific exception, log safe context, and rethrow or return an explicit result.
- Do not log raw request payloads, full model objects, passwords, tokens, cookies, secrets, personal identifiers, or headers.
- Log messages must describe what happened and include safe identifiers such as model IDs.
- Do not use `abort(500)` for business failures. Throw a domain exception or return a typed result object with a clear failure reason.
- Avoid `return false` from business logic when the caller needs to know why a workflow failed.

Wrong:

```php
Log::info($request->all());
Http::post('https://api.example.com/v1/send', $data);
```

Correct:

```php
Log::info('Message thread archived', ['thread_id' => $thread->id]);
Http::timeout(config('services.example.timeout'))->post(config('services.example.url').'/v1/send', $data);
```

## Database Naming And State

- Do not make columns nullable by default. Nullable means the business value can truly be absent.
- Use `null` for missing timestamps instead of sentinel values such as `0`.
- Boolean columns should read as questions: `is_admin`, `is_active`, `is_blocked`, `is_verified`, `has_avatar`, `can_reply`.
- Date/time columns should normally end in `_at`: `published_at`, `verified_at`, `paid_at`, `archived_at`, `expires_at`.
- Count columns should end in `_count`: `messages_count`, `views_count`, `likes_count`.
- Money columns must communicate amount and currency or minor units: `amount_cents`, `price_amount`, `price_currency`, `total_amount`.
- Status fields require an enum or constants, validation, a database default when applicable, transition rules, and tests.
- Important status transitions should live in actions/services instead of direct assignments scattered across controllers.

## Blade, CSS, And JavaScript

- Blade receives prepared data from controllers or ViewModels.
- Do not query, aggregate, or run business workflows inside Blade.
- Escape user output with `{{ }}` by default.
- Use `{!! !!}` only for deliberately sanitized HTML.
- Use named routes instead of hardcoded URLs.
- Move repeated markup into components or partials when the duplication is stable.
- Prefer CSS classes over inline styles.
- Avoid inline JavaScript for new work; keep feature JavaScript in `resources/js` for the current Mix/Webpack pipeline.
- Do not commit `console.log`, `debugger`, `dd()`, `dump()`, `var_dump()`, `print_r()`, `die()`, or `exit()`.
- Split very large Blade, SCSS, and JavaScript files into focused components, page modules, feature modules, or utilities.
- Do not connect random scripts and styles from individual Blade views. Use the project's configured asset pipeline and page-specific entries only when the project already has that convention.

## Tests And Verification

- Use factories instead of hardcoded IDs.
- Tests must not depend on production data or real external services.
- Add regression or characterization tests before risky refactors.
- Run Pint, static analysis when installed, the Laravel test suite, and boot/cache checks before committing application cleanup.
- Factory default states must create valid records.
- Add factory states for common scenarios such as admin, blocked, unread, paid, archived, or verified.
- Seeders should be idempotent and should not depend on numeric production IDs.
- Seeders must use example domains, placeholder phone numbers, and safe demo values instead of real personal data.
- Freeze time in tests that assert time-sensitive behavior.
- Do not compare floats for money in tests. Compare decimal strings or integer minor units.
- Avoid uncontrolled randomness in tests; prefer explicit values when the value matters.

## Files, Jobs, And Events

- Uploaded files belong on Laravel disks, not random public folders.
- Do not build stored filenames directly from user input. Generate safe names and store the original display name separately when needed.
- Store a file's disk and path clearly so storage can move from local disk to S3 without changing consumers.
- Authorize every download.
- Decide and test whether related files are deleted, retained, or deleted only on force delete.
- Jobs should carry small payloads, usually IDs, and re-load current state in `handle()`.
- Retryable jobs must be idempotent and should define `tries`, `backoff()`, timeout, and failure handling for important workflows.
- Events should be facts that already happened, for example `OrderPaid`, `UserCreated`, and `MessageSent`.
- Do not create events, traits, services, interfaces, helpers, or abstractions unless they reduce real duplication or complexity.

## Comments And Dead Code

- Remove useless comments, commented-out dead code, and TODOs that do not explain a concrete risk.
- Comments should explain why something unusual exists, not repeat what the next line of code does.
- Temporary compatibility comments should name the legacy input and the condition for removal.

## Compatibility Notes

- Legacy database names such as `message_thrades` and columns such as `message_thrade` remain database compatibility contracts until a separate migration plan changes them.
- Legacy API keys such as `thrade` must not be renamed without versioning or a compatibility adapter.
- Public route URIs and controller method names can be cleaned only when route definitions, tests, and external consumers are updated together.
- Legacy plural model classes such as `Posts`, `Comments`, `Users`, `Albums`, `Stories`, `Friendships`, and `PaidContentPackages` should be renamed only in a dedicated compatibility slice. Before changing them, check factories, seeders, policies, route model binding, queue payloads, serialized data, morph types, API resources, tests, and public contracts.
- `Users` is especially risky because this codebase also has the canonical Laravel `User` model. Prefer deprecating `Users` behind a tested migration plan instead of making a broad blind rename.
