# Code Cleanup Standards

Generated: 2026-07-02

These standards describe safe, small refactor slices for this Laravel codebase. Preserve behavior first. When a legacy public route, API payload, database column, queue payload, or serialized value uses an old name, document the compatibility risk before changing it.

## Rules 41-100 Coverage

The following micro-cleanup rules apply to all new and touched code. Existing legacy code should be moved toward these standards in small, tested slices.

| Rules | Standard | Current repo action |
| --- | --- | --- |
| 41, 42, 45 | Eloquent model classes represent one entity, use singular StudlyCase, and avoid vague or abbreviated names. | Do not broad-rename legacy classes without a compatibility slice. Document offenders in the backlog below. |
| 43, 44 | `BaseController`, traits, helpers, and common classes must not collect unrelated behavior. | Extract upload, email, pricing, authorization, formatting, and workflow logic into focused actions, policies, services, or model concerns when touching the area. |
| 46, 47, 48 | Use one action method convention, inject services/actions through the container, and avoid `new` in controllers. | New focused action classes should use `execute()` unless a local feature already has a tested convention. |
| 49, 50, 51, 52 | External integrations need named clients/services, config-backed URLs, timeouts, and explicit failure handling. | Keep facade usage in simple framework boundary code; wrap complex payment, mail, storage, and HTTP workflows behind testable classes. |
| 53, 54, 55 | Empty catches and noisy/sensitive logs are not allowed. | Catch specific exceptions, log safe identifiers, and never log raw requests, full users, tokens, passwords, cookies, or secrets. |
| 56, 57 | Business failures should return domain exceptions or typed results, not `abort(500)` or unexplained `false`. | Gateway-style legacy booleans require a gateway contract refactor and tests before changing behavior. |
| 58, 59, 60, 61, 62, 63 | Columns must communicate business meaning: nullable only when real, timestamps use `_at`, booleans read as questions, counts use `_count`, and money fields describe amount/currency or minor units. | Do not rename persisted columns in place. Add additive migrations or document an expand-and-contract plan. |
| 64, 65, 66 | Status fields require enum/constants, validation, defaults, transition rules, tests, and centralized transition actions. | Replace scattered direct status assignment only inside feature-specific refactors with regression coverage. |
| 67, 68, 69, 70, 71 | Do not query in loops, prefer primary-key helpers, scope ownership in queries, cache `$request->user()`, and pass users into services/actions. | Blade and helper hotspots are documented for gradual controller/ViewModel cleanup. |
| 72, 73, 74, 75, 76, 77, 78 | Tests, factories, and seeders must be deterministic, valid by default, use states, avoid hardcoded production IDs, and never contain real personal data. | New tests must use factories and fakes; legacy seeders should be made idempotent when touched. |
| 79, 80, 81, 82, 83, 84, 85, 86 | Comments, formatting, huge methods, huge Blade files, huge SCSS files, huge JS files, and ad hoc assets must be cleaned in focused slices. | Pint is the PHP formatting authority. Frontend files follow the current Mix/Webpack pipeline until a tested asset migration exists. |
| 87, 88, 89, 90, 91 | Uploaded files belong on Laravel disks, must not use user input as stored filenames, must store disk/path, require download authorization, and need deletion policy tests. | Public-upload compatibility cleanup needs storage regression tests before moving paths. |
| 92, 93, 94, 95, 96, 97, 98, 99, 100 | Slow mail/external work belongs in jobs after commit; jobs use small payloads, retries/backoff/timeouts, idempotency, and failure plans; notifications need tests; events are facts that already happened. | Add queue, notification, and event coverage before changing existing synchronous workflows. |

## Current Legacy Rename Backlog

These names violate the preferred singular/contextual naming standard, but they are compatibility-sensitive and must not be renamed blindly.

| Current name | Preferred direction | Why not changed in this cleanup | Safest first fix |
| --- | --- | --- | --- |
| `Posts` | `Post` | Referenced across models, controllers, views, factories, notifications, and serialized feed behavior. | Add post regression tests, introduce compatibility aliases if needed, then rename references in one dedicated compatibility slice. |
| `Comments` | `Comment` | Comment rendering, notification, and relationship names can affect Blade and API output. | Lock down comment CRUD/rendering tests before renaming. |
| `Albums` | `Album` | Album routes, helpers, and legacy public storage paths are tightly coupled. | Refactor album reads behind relationships/ViewModels before renaming. |
| `Stories` | `Story` | Story feed behavior and route/model references need UI coverage. | Add feature tests for story listing, creation, and deletion first. |
| `Users` | Prefer canonical `User` | This codebase already has Laravel's canonical `User` model, so changing `Users` can break auth-adjacent legacy flows. | Deprecate `Users` behind a tested migration plan and replace references feature by feature. |
| `Friendships` | `Friendship` | Friendship actions and JSON friend-list compatibility are sensitive. | Add authorization and friend-request regression tests, then rename in an isolated pass. |
| `PaidContentPackages` | `PaidContentPackage` | Payment, package, and creator flows can affect billing behavior. | Add package purchase/state tests before renaming. |
| `Setting` | `SystemSetting` or feature-specific setting model | The generic name hides whether the record is system, user, payment, or feature configuration. | Inventory setting keys and introduce contextual accessors before renaming. |
| `Share` | Contextual share model, for example `PostShare` when applicable | Generic sharing terminology can overlap with post, page, group, or external-share behavior. | Replace only after mapping current relationships and route consumers. |

Do not rename database tables, persisted column names, morph types, route names, queue payload keys, or serialized API fields as part of a cosmetic cleanup. Those changes need tests, compatibility adapters, and migration notes.

## Current Code-Smell Follow-Ups

This repository still has legacy examples of several rules above. Treat these as refactor targets, not permission to do a broad unsafe rewrite.

| Smell | Example area | Risk | Next safe step |
| --- | --- | --- | --- |
| Repeated `auth()->user()` in views | Chat, headers, badge, right sidebar, search views | Hidden auth assumptions and repeated ViewModel/helper calls. | Prepare current user in the controller or view data object and add render tests. |
| Queries inside Blade | Badge, search, album detail, page/group checks | N+1 queries and authorization leaks. | Move lookups into controllers/actions/ViewModels with eager loading and authorization tests. |
| `return false` in payment gateways/helpers | `app/Services/Payments/Gateways/*`, legacy helpers | Callers cannot tell configuration, provider, validation, and network failures apart. | Introduce a gateway result object or gateway exception per provider with current behavior tests. |
| Legacy `where('id', ...)` primary-key lookups | Helpers, payment gateways, friend actions | Ownership scoping can be missed and intent is unclear. | Replace with `find()`, `findOrFail()`, or relationship-scoped lookups inside tested feature slices. |
| File path and public upload compatibility | Legacy helpers and public assets | Moving paths can break existing uploads and URLs. | Add storage fake tests and document disk/path migration before changing storage. |

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

- Chat code must use canonical names in PHP: `MessageThread`, `message_thread_id`, `receiver_id`, `receiver`, `chat_center`, and `message_thread`.
- Legacy chat storage names such as `message_thrades`, `message_thrade`, `reciver_id`, and `chatcenter` remain persisted database compatibility contracts until a separate expand/backfill/contract migration changes them.
- New chat code must go through the `MessageThread` and `Chat` model accessors/scopes instead of querying legacy chat column names directly outside those models.
- Legacy chat API keys such as `message_thrade` and `reciver_id` may remain as additive response/request compatibility fields, but new clients and tests should prefer `message_thread_id` and `receiver_id`.
- Public route URIs and controller method names can be cleaned only when route definitions, tests, and external consumers are updated together.
- Legacy plural model classes such as `Posts`, `Comments`, `Users`, `Albums`, `Stories`, `Friendships`, and `PaidContentPackages` should be renamed only in a dedicated compatibility slice. Before changing them, check factories, seeders, policies, route model binding, queue payloads, serialized data, morph types, API resources, tests, and public contracts.
- `Users` is especially risky because this codebase also has the canonical Laravel `User` model. Prefer deprecating `Users` behind a tested migration plan instead of making a broad blind rename.
