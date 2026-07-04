# Code Quality Standards

Generated: 2026-07-02

These standards define the foundation for future refactors in this Laravel project. The current stack is Laravel 13, PHP `^8.3`, PHPUnit 12, Laravel Pint, Larastan/PHPStan level 1, Vite, SCSS, ESLint, Stylelint, and Prettier. Do not add incompatible tools without first proving they support the installed versions.

## Required Gates

- Run `composer quality` before committing changes that touch PHP, Blade, configuration, tests, or frontend assets.
- Run `composer ci` before larger refactor or release work after installing both Composer and npm dependencies.
- Run `npm run quality` directly when only frontend source changed.
- Keep `vendor/bin/pint --test`, `composer analyse`, `php artisan test`, `npm run lint`, `npm run stylelint`, `npm run format:check`, and `npm run build` passing.
- Do not hide failing checks. If a legacy issue cannot be fixed safely in the current scope, document it in `docs/quality-known-failures.md`.

## Behavior And Testing

- No behavior change without a regression or feature test proving the intended behavior.
- Add characterization tests before risky refactors.
- Use factories and fakes for tests. Do not depend on production data, real payment providers, real email providers, real object storage, or live social APIs.
- Do not use sleeps or order-dependent assertions.
- Assert authorization failures, validation errors, database changes, file storage effects, and sensitive-field hiding where relevant.

## PHP And Laravel

- Follow Laravel Pint formatting and PSR-12-compatible style.
- Keep controllers thin: receive the request, authorize, validate, delegate to an action/service/query, and return a response.
- Use Form Requests for validation on write endpoints.
- Use policies, gates, or middleware for authorization. Blade conditionals are visibility only.
- Use services or action classes for workflows and side effects.
- Use API Resources for non-trivial JSON output.
- Use Eloquent relationships, local scopes, query classes, and eager loading for repeated query logic.
- Do not call `env()` outside config files. Read runtime settings through `config()`.
- Do not mass assign `$request->all()` or raw payloads. Use validated data or explicit field maps.
- Do not concatenate raw SQL strings. Prefer Eloquent and query builder bindings.
- Do not use `DB::select()`, `DB::statement()`, `DB::raw()`, or `DB::unprepared()` outside documented, isolated exceptions.
- Do not store secrets, credentials, tokens, private keys, database dumps, or real provider values in the repository.

## Static Analysis

- Larastan/PHPStan currently runs at level 1 without a baseline.
- Fix obvious real issues instead of suppressing them: undefined methods, wrong relation names, nullable assumptions, bad PHPDoc, invalid return types, and wrong collection shapes.
- Add native parameter and return types only when they are obvious and safe for legacy dynamic behavior.
- Use PHPDoc collection generics where native types cannot express the shape.
- Do not raise the PHPStan level until a focused cleanup pass proves the next level is stable.

## Database And Migrations

- Migrations must be safe, additive where possible, reversible, and scoped to one concern.
- Do not edit old production migrations. Create a new migration.
- Destructive schema changes require an expand-and-contract plan, backup notes, and rollback notes.
- Add indexes for foreign keys, common filters, unique lookups, sorting columns, and cursor pagination paths.
- Do not use floats for money. Use decimals with explicit scale or integer minor units.
- Use transactions for multi-step writes that must succeed or fail together.

## Blade, Accessibility, And Frontend

- Keep Blade presentation-only. Do not query, aggregate, or run business workflows in views.
- Escape user output with `{{ }}` by default. Use `{!! !!}` only for audited and sanitized HTML.
- Use semantic HTML, logical headings, accessible forms, correct labels, useful image alt text, and keyboard-friendly controls.
- Keep SCSS/CSS modular: tokens, base, layout, components, utilities, and page-level rules where practical.
- Avoid deep specificity, ID selectors for styling, and new `!important` rules.
- Keep JavaScript in ES modules when practical. Avoid globals, inline handlers, `eval`, `Function`, unsafe `innerHTML`, `console.log`, and `debugger`.
- AJAX and fetch calls must include CSRF for web routes and handle loading, success, validation, and network failure states.
- First-party compiled assets use Vite. Do not reintroduce Laravel Mix/Webpack.

## Repository Hygiene

- Keep commits atomic and use Conventional Commits.
- Stage only files that belong to the task.
- Preserve `.gitkeep` and `.gitignore` placeholders required for Laravel storage/cache directories.
- Do not commit generated caches, local uploads, logs, `.env` files, or dependency directories.
- Vite build output under `public/build` is generated and ignored by git. Keep deploy packaging responsible for running `npm run build` or publishing a build artifact.
