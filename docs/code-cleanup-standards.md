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

## Laravel Structure

- Controllers authorize, validate, delegate to an action/service/query/model, and return a response.
- Write validation in Form Requests for write endpoints.
- Put authorization in policies, gates, or middleware instead of Blade-only checks.
- Put repeated query conditions in Eloquent scopes or query classes.
- Put workflow logic in focused actions or services.
- Shape non-trivial JSON through API Resources.

## Request And Model Safety

- Do not mass assign `$request->all()` or `request()->all()`.
- Use `$request->validated()`, `$request->safe()->only([...])`, or explicit field mapping.
- Define explicit `$fillable` or equivalent guarded behavior on models.
- Add casts for booleans, integers, decimals, arrays, JSON, dates, datetimes, and enums where supported.
- Hide sensitive attributes such as passwords, remember tokens, API tokens, secret keys, and internal notes.

## Query Safety

- Avoid `Model::all()` in application flows. Use a named scope, pagination, chunking, or a clearly bounded lookup query.
- Add deterministic ordering before paginating or slicing result sets.
- Eager-load relationships rendered in Blade or API resources.
- Use `withCount()`, `withExists()`, or database aggregates instead of loading full relationships just to count them.
- Avoid raw SQL. If unavoidable, isolate it, bind parameters, document the exception, and add tests.

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

## Tests And Verification

- Use factories instead of hardcoded IDs.
- Tests must not depend on production data or real external services.
- Add regression or characterization tests before risky refactors.
- Run Pint, static analysis when installed, the Laravel test suite, and boot/cache checks before committing application cleanup.

## Compatibility Notes

- Legacy database names such as `message_thrades` and columns such as `message_thrade` remain database compatibility contracts until a separate migration plan changes them.
- Legacy API keys such as `thrade` must not be renamed without versioning or a compatibility adapter.
- Public route URIs and controller method names can be cleaned only when route definitions, tests, and external consumers are updated together.
