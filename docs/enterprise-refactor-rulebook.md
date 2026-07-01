# Enterprise Laravel Refactor Rulebook and Prompt Library

Generated: 2026-07-01

This document is a strict refactor contract and copy-ready prompt library for this legacy Laravel social application. Use it together with:

- `AGENTS.md`
- `docs/coding-standards.md`
- `docs/refactor-audit.md`
- `docs/refactor-checklist.md`

Use one prompt at a time. Replace placeholders such as `{feature}`, `{module}`, `{model}`, `{controller}`, `{table}`, `{route}`, `{component}`, `{api_endpoint}`, `{service}`, and `{job}` before running a task.

## Detected Project Baseline

- Laravel: `13.18.0`
- PHP requirement: `^8.3`
- PHPUnit: `12.5.30`
- Formatter: Laravel Pint `1.29.3`
- Frontend build tool: Laravel Mix / Webpack, not Vite yet
- JavaScript dependencies: Alpine, Axios, Tailwind 3, Laravel Mix, Webpack
- Optional tools not currently installed: Larastan/PHPStan, Rector, ESLint, Stylelint, Prettier

Do not assume Vite, Larastan, Rector, ESLint, Stylelint, or Prettier exist in this project until the dependency files prove they do.

## Verified Reference Map

Use official or primary sources first:

- PHP-FIG PSR-1, PSR-4, and PSR-12: https://www.php-fig.org/psr/
- PSR-12 extended coding style: https://www.php-fig.org/psr/psr-12/
- Laravel 13 validation and Form Requests: https://laravel.com/docs/13.x/validation
- Laravel 13 authorization, gates, and policies: https://laravel.com/docs/13.x/authorization
- Laravel 13 Eloquent: https://laravel.com/docs/13.x/eloquent
- Laravel 13 migrations: https://laravel.com/docs/13.x/migrations
- Laravel 13 testing: https://laravel.com/docs/13.x/testing
- Laravel 13 configuration: https://laravel.com/docs/13.x/configuration
- Laravel 13 deployment: https://laravel.com/docs/13.x/deployment
- Laravel Pint: https://laravel.com/docs/13.x/pint
- OWASP Laravel Cheat Sheet: https://cheatsheetseries.owasp.org/cheatsheets/Laravel_Cheat_Sheet.html
- OWASP SQL Injection Prevention: https://cheatsheetseries.owasp.org/cheatsheets/SQL_Injection_Prevention_Cheat_Sheet.html
- OWASP Query Parameterization: https://cheatsheetseries.owasp.org/cheatsheets/Query_Parameterization_Cheat_Sheet.html
- OWASP Mass Assignment: https://cheatsheetseries.owasp.org/cheatsheets/Mass_Assignment_Cheat_Sheet.html
- MDN accessible semantic HTML: https://developer.mozilla.org/en-US/docs/Learn_web_development/Core/Accessibility/HTML
- WCAG 2.2: https://www.w3.org/TR/WCAG22/
- Vite env and modes, for future Vite migration only: https://vite.dev/guide/env-and-mode
- Sass `@use` and `@forward`: https://sass-lang.com/documentation/at-rules/use/ and https://sass-lang.com/documentation/at-rules/forward/
- Sass `@import` deprecation: https://sass-lang.com/documentation/breaking-changes/import/
- ESLint rules: https://eslint.org/docs/latest/use/configure/rules
- Stylelint getting started: https://stylelint.io/user-guide/get-started/
- Prettier option philosophy: https://prettier.io/docs/option-philosophy
- Rector: https://getrector.com/
- Larastan: https://github.com/larastan/larastan

## Global Refactor Contract

Every refactor must preserve existing behavior unless the prompt explicitly says to fix a bug. Every changed behavior must be covered by tests. No hidden breaking changes. No unreviewed package upgrades. No real secrets in code. No debug statements. No uncontrolled mass assignment. No raw SQL string concatenation. No `env()` outside config files.

Before changing code, detect the actual installed versions from `composer.json`, `composer.lock`, `package.json`, `package-lock.json`, `webpack.mix.js`, `vite.config.*`, and existing config files. Apply modern rules only when they match the installed stack or when the task explicitly includes a migration.

Every change should be small enough to review. Prefer one behavior-preserving refactor slice per commit. Do not combine broad formatting, package upgrades, behavior changes, and documentation updates unless the user explicitly asks for a single combined commit.

## Mandatory Workflow

1. Read `AGENTS.md`, this file, and the relevant docs in `docs/`.
2. Inspect the live code before proposing or editing.
3. Identify the smallest safe slice.
4. Add or update characterization tests before risky refactors.
5. Refactor using Laravel conventions and existing project patterns.
6. Run the relevant checks.
7. Review the diff for secrets, raw SQL, unsafe routes, accidental behavior drift, and unrelated files.
8. Summarize what changed, what was not changed, verification evidence, and remaining risks.

## Verification Gates

For documentation-only changes:

- `git diff --check`

For PHP application changes:

- `vendor/bin/pint --test`
- `php artisan test`
- `composer validate --strict`
- `composer audit`

For frontend/build changes:

- `npm run production`
- Use `npm audit` only when the task includes dependency/security review or when package files change.

For config/deployment-sensitive changes:

- `php artisan config:cache`
- `php artisan route:cache`
- `php artisan view:cache`
- Clear generated caches afterwards if they create local artifacts.

For optional tools:

- Run Larastan/PHPStan, Rector, ESLint, Stylelint, or Prettier only if installed or explicitly added as part of the task.

## Non-Negotiable Engineering Rules

- No queries in Blade, loops, conditionals, or component templates.
- No `Model::all()` without a hard bounded dataset, scope, limit, or pagination.
- No `count()`, `sum()`, or relationship aggregate calls inside loops.
- No new raw SQL unless no Laravel/Eloquent/schema-builder alternative exists; isolate and document any exception.
- No `DB::select()`, `DB::statement()`, `DB::raw()`, or `DB::unprepared()` in controllers, views, jobs, or helpers.
- Use Form Requests for write validation.
- Use policies/gates for authorization; UI hiding is not authorization.
- Keep controllers thin: authorize, validate, delegate, return.
- Use Eloquent relationships, scopes, query classes, actions, services, and ViewModels for reusable behavior.
- Use API Resources for API output shaping.
- Use queued jobs for slow external side effects.
- Use factories and fakes in tests.
- Keep `.env` secrets out of version control and out of browser-visible output.
- Use `config()` for runtime settings because production config caching changes `.env` loading behavior.

## Source-Aware Refactor Prompt Preamble

Use this preamble before any prompt in the library when you want maximum discipline:

```text
You are refactoring a legacy Laravel 13 application. Preserve behavior unless the prompt explicitly asks for a behavior change. Read AGENTS.md, docs/coding-standards.md, docs/refactor-audit.md, docs/refactor-checklist.md, and docs/enterprise-refactor-rulebook.md first. Detect the installed PHP, Laravel, Composer, Node, npm, and frontend build versions before making changes. Use official Laravel/PHP/OWASP/MDN/WCAG/tooling docs for framework-specific decisions. Do not change application logic outside the requested scope. Add or update tests for behavior changes. Run relevant checks and report exact results.
```

## Prompt Library

### Orientation and Baseline Prompts

1. **Repository baseline audit**

```text
Audit the current repository baseline for {module}. Read the relevant routes, controllers, models, requests, services, Blade views, tests, config files, and docs. Identify installed package versions, current conventions, risky dependencies, existing tests, and unsafe patterns. Do not change code. Produce a concise report with prioritized refactor slices, risk level, suggested tests, and verification commands.
```

2. **Dirty tree safety check**

```text
Inspect the git working tree before touching {module}. Identify staged, unstaged, and untracked files. Separate existing user work from the requested task. Do not revert anything. Explain which files belong to the requested scope and which should be left alone.
```

3. **Behavior characterization plan**

```text
Create a behavior characterization plan for {feature}. Identify current inputs, outputs, redirects, validation errors, authorization outcomes, events, jobs, database writes, external calls, and Blade/API responses. Propose the smallest tests needed before refactoring. Do not refactor yet.
```

4. **Legacy risk inventory**

```text
Inventory legacy risks in {module}: raw SQL, Blade queries, controller business logic, missing policies, missing Form Requests, unsafe uploads, unbounded queries, N+1 risk, secrets exposure, state-changing GET routes, missing tests, and deployment hazards. Group findings by severity and implementation order. Do not change code.
```

5. **Current-stack verification**

```text
Detect the exact project stack from composer.json, composer.lock, package.json, lock files, build config, and artisan output. List Laravel, PHP, PHPUnit, Pint, frontend build tool, Node packages, and missing optional quality tools. Do not recommend tools as installed unless the files prove it.
```

6. **One-slice implementation plan**

```text
For {task}, create a one-slice implementation plan that fits in one reviewable commit. Include files to inspect, tests to add/update, refactor steps, rollback risks, commands to run, and exact acceptance criteria. Do not modify code until the plan is complete.
```

### Architecture Prompts

7. **Controller extraction**

```text
Refactor {controller} by extracting business logic into Actions or Services while preserving behavior. Add characterization tests first for the affected routes. Keep the controller responsible only for authorization, validation, delegation, and response selection. Do not change route URLs or response formats unless explicitly required.
```

8. **Action class extraction**

```text
Extract the {workflow} workflow from {controller} into a single-purpose Action class under app/Actions. Preserve existing behavior, exceptions, redirects, events, jobs, and database writes. Add focused tests for the Action and Feature tests for the route.
```

9. **Service extraction**

```text
Extract {integration_or_domain_capability} into a service class with constructor-injected dependencies. Remove duplicated controller/helper logic. Keep HTTP response details out of the service. Add tests using Laravel fakes for external systems.
```

10. **ViewModel introduction**

```text
Introduce a ViewModel for {page_or_component}. Move view data shaping out of Blade and controllers without changing visible output. Ensure all relationships and aggregates are loaded before rendering. Add a view/feature test that proves the page renders with the expected data and no Blade queries.
```

11. **Query class extraction**

```text
Extract repeated query logic for {domain_query} into a query class or model scopes. Use Eloquent relationships/scopes and explicit eager loading. Preserve ordering, filters, pagination, selected columns, and authorization assumptions. Add tests that compare old behavior to new behavior.
```

12. **Domain boundary review**

```text
Review {module} for mixed responsibilities across controllers, models, helpers, Blade, jobs, and services. Propose a domain boundary with Actions, Services, Policies, Requests, Resources, Query classes, Events, and Jobs. Do not refactor code yet.
```

13. **Helper deglobalization**

```text
Refactor global helper logic related to {helper_function} into a service/action/query class. Keep the helper temporarily as a thin compatibility wrapper if it is widely used. Add tests around current behavior before moving the implementation.
```

14. **Legacy compatibility wrapper**

```text
Create a compatibility wrapper for {legacy_entrypoint} that delegates to a new tested service/action while preserving the public function signature and behavior. Mark the wrapper as transitional in documentation, not with noisy comments.
```

### Routes, Controllers, and Requests Prompts

15. **Route grouping**

```text
Refactor routes for {module} into named groups with explicit middleware, prefix, and name prefix. Preserve route names and URLs unless the prompt asks for a breaking route change. Add route registration tests and update Blade links only when necessary.
```

16. **State-changing route hardening**

```text
Find state-changing GET routes in {module}. Convert them to POST, PUT, PATCH, or DELETE with CSRF protection and method spoofing. Add tests proving guests are rejected, unauthorized users are forbidden, valid users succeed, and old unsafe methods no longer mutate state.
```

17. **Form Request migration**

```text
Move validation for {route_or_controller_method} into a Form Request. Put validation rules in rules() and command-specific authorization in authorize() when appropriate. Preserve existing validation messages and response shape. Add tests for valid input, missing fields, invalid enum-like values, unauthorized users, and malformed payloads.
```

18. **Policy migration**

```text
Move authorization for {model_or_action} into a Laravel policy or gate. Replace inline role/owner checks in controllers and Blade. Add tests for guest, owner, non-owner, disabled user, unverified user, admin, and normal authenticated user cases.
```

19. **Route model binding**

```text
Replace manual find/findOrFail loading in {controller} with route model binding where safe. Preserve 404 behavior and authorization. Add tests for valid IDs, missing IDs, unauthorized access, and soft-deleted records if applicable.
```

20. **API response resource**

```text
Replace raw model/array JSON output for {api_endpoint} with JsonResource or Resource Collection. Preserve response fields unless the prompt explicitly authorizes a response contract change. Use whenLoaded/whenCounted for optional relationships and add response-shape tests.
```

21. **Controller method split**

```text
Split {controller} methods that mix HTML and JSON behavior into separate methods or controllers. Preserve routes unless explicitly changing them. Add tests for both response modes and ensure validation/authorization stays consistent.
```

22. **Token middleware cleanup**

```text
Remove manual bearer token parsing from {controller_or_route_group}. Use Laravel guards/middleware instead. Add tests for missing token, invalid token, expired token if applicable, valid token, and unauthorized access to protected resources.
```

### Models, Eloquent, and Database Prompts

23. **Model fillable/casts pass**

```text
Audit {model} for fillable, guarded, casts, hidden fields, dates, enum-like statuses, JSON columns, money-like values, and relationships. Add safe fillable/casts changes with tests proving mass assignment and serialization behavior remain secure.
```

24. **Relationship definition**

```text
Add missing Eloquent relationships for {model}. Replace repeated joins or manual foreign-key lookups where safe. Add tests for relationship results, eager loading, and null/missing related records.
```

25. **Scope extraction**

```text
Extract repeated where/order/filter logic for {model} into local scopes. One scope should have one responsibility. Preserve selected columns, ordering, pagination, and filters. Add scope tests and update callers incrementally.
```

26. **N+1 elimination**

```text
Find and fix N+1 queries on {page_or_endpoint}. Use with(), loadMissing(), withCount(), withExists(), or aggregate eager loading. Add a query-count regression test where practical. Report before/after query count or an evidence-backed estimate.
```

27. **Aggregate-in-loop cleanup**

```text
Find count/sum/avg/min/max calls inside loops in {module}. Replace them with withCount(), withSum(), withAvg(), withMin(), withMax(), cached aggregates, or preloaded collections. Add tests proving output is unchanged.
```

28. **Unbounded query cleanup**

```text
Find unbounded get(), all(), or collection-heavy queries in {module}. Replace with pagination, simplePaginate, cursorPaginate, chunkById, lazyById, or a bounded scope as appropriate. Add tests for pagination/filter preservation.
```

29. **Migration safety review**

```text
Review the migration strategy for {table}. Do not edit production migrations. Propose an expand-contract plan for risky schema changes, including indexes, nullable/default behavior, backfill, deployment ordering, rollback, and tests.
```

30. **Index audit**

```text
Audit {table} indexes against actual where, orderBy, join, foreign-key, and pagination usage. Do not add indexes yet. Produce a prioritized index plan with expected query benefit, write overhead, migration risk, and tests/EXPLAIN steps.
```

31. **Raw SQL replacement**

```text
Replace raw SQL usage in {file_or_module} with Eloquent, query builder, model scopes, relationships, or schema builder. If any raw SQL remains necessary, isolate it behind a model-owned method, document why, and add tests around inputs and outputs.
```

32. **Transaction boundary**

```text
Audit {workflow} for multi-step writes that require a transaction. Add a transaction boundary around the domain operation, keep external side effects outside the transaction or dispatch after commit, and add tests for partial-failure behavior.
```

### Security Prompts

33. **OWASP Laravel security audit**

```text
Audit {module} against OWASP Laravel guidance: input validation, authorization, IDOR, mass assignment, SQL injection, XSS, CSRF, file upload safety, secret exposure, session/cookie settings, rate limiting, sensitive logging, and dependency risk. Do not change code. Prioritize fixes by exploitability and blast radius.
```

34. **IDOR hardening**

```text
Review {route_or_controller} for IDOR risk. Enforce policy checks and scoped queries so users cannot access or mutate records they do not own. Add tests for owner, non-owner, admin, guest, and disabled user cases.
```

35. **Mass assignment hardening**

```text
Audit {model_or_controller} for mass assignment risk. Add or tighten $fillable, use validated request data only, reject unexpected fields where practical, and add tests proving privileged fields cannot be overwritten.
```

36. **File upload hardening**

```text
Harden file uploads for {feature}. Add Form Request validation for required file, MIME type, size, dimensions/duration where relevant, extension mismatch, storage disk, visibility, ownership, and cleanup on failure. Use Storage::fake() tests.
```

37. **Secret exposure sweep**

```text
Search {module_or_repo} for hard-coded secrets, API keys, tokens, private keys, credentials, provider config rendered to Blade/JavaScript, and sensitive log output. Do not print secret values. Produce a remediation plan and safe redaction strategy.
```

38. **Rate limiting**

```text
Add rate limiting to {route_group_or_endpoint}. Use Laravel middleware/rate limiters, preserve legitimate user flows, and add tests for normal requests, throttled requests, authenticated users, and IP/user-key behavior.
```

39. **Webhook verification**

```text
Secure {provider} webhook handling. Verify signatures, reject replay/stale payloads where supported, make processing idempotent, dispatch slow work to a job, and add tests for valid signatures, invalid signatures, duplicate events, and missing records.
```

40. **Sensitive logging review**

```text
Review logging in {module}. Remove secrets, tokens, passwords, payment payloads, personal data beyond what is needed, and full request dumps. Add structured safe context and tests or static checks where practical.
```

### Blade, HTML, Accessibility, and Frontend Prompts

41. **Blade query removal**

```text
Remove queries from {blade_view}. Preload all data in the controller, ViewModel, query class, or view composer. Preserve rendered output. Add a test or instrumentation proving the view no longer queries directly.
```

42. **Blade component extraction**

```text
Extract repeated Blade markup for {component} into an anonymous or class-based component. Keep props explicit with defaults, preserve escaped output, named routes, classes, and accessibility attributes. Add render tests where practical.
```

43. **Semantic HTML upgrade**

```text
Refactor {blade_view_or_component} to semantic HTML using the correct element for the job. Preserve visual output. Add labels, landmarks, headings, button/link correctness, table headings, alt text, and keyboard behavior. Avoid ARIA when native HTML is enough.
```

44. **Accessible form cleanup**

```text
Refactor the {form_name} Blade form for accessibility and Laravel correctness. Ensure label/input associations, error messages, old() values, CSRF, method spoofing, disabled/loading states, keyboard navigation, and validation feedback are correct.
```

45. **Button/link semantics**

```text
Audit {view_or_component} for click-only divs, anchors used as buttons, buttons used as links, missing type attributes, and inaccessible controls. Replace with semantic elements and add tests or manual QA notes.
```

46. **Blade escaping review**

```text
Review {view_or_component} for unsafe unescaped output. Replace raw output with escaped output unless trusted sanitized HTML is explicitly required. Document any allowed raw HTML path and add tests around malicious input.
```

47. **Frontend secret removal**

```text
Remove secrets and provider credentials from browser-visible JavaScript or Blade in {feature}. Move provider calls to backend endpoints/services/jobs. Add tests proving no secret config is rendered and the backend flow still works.
```

48. **Empty/loading/error states**

```text
Add explicit empty, loading, validation-error, authorization-error, and failure states to {view_or_component}. Preserve existing successful behavior. Use @forelse for lists and named reusable components where appropriate.
```

### CSS, SCSS, JavaScript, and Build Tooling Prompts

49. **SCSS module-system plan**

```text
Audit SCSS in {asset_area} for legacy @import, global leaks, deep nesting, duplicated tokens, and selector specificity problems. Do not rewrite yet. Propose a migration to @use/@forward, design tokens, and Stylelint-compatible organization.
```

50. **CSS accessibility pass**

```text
Audit {css_or_scss_file} for inaccessible focus states, low contrast risk, fixed sizes that break zoom, hidden content problems, motion without reduced-motion handling, and layout issues on mobile. Propose fixes with WCAG 2.2 AA as the minimum target.
```

51. **JavaScript entrypoint audit**

```text
Audit JavaScript entrypoints for {feature}. Identify global pollution, inline scripts, duplicated DOM logic, unused code, unsafe HTML injection, missing event cleanup, dependency bloat, and build-tool assumptions. Do not change code yet.
```

52. **Laravel Mix safe cleanup**

```text
Refactor Laravel Mix/Webpack assets for {feature} without migrating build tools. Preserve current npm scripts and generated public paths. Run npm run production and verify the compiled assets still match the app expectations.
```

53. **Vite migration plan**

```text
Create a Vite migration plan for this Laravel app. First confirm the project currently uses Laravel Mix. Do not migrate yet. Inventory entrypoints, public assets, Blade asset references, env variable usage, production build output, cache busting, and rollback steps.
```

54. **Frontend env safety**

```text
Audit frontend environment usage for {feature}. Treat Vite import.meta.env variables as public/build-time values and Laravel Mix environment exposure as browser-visible if bundled. Move secrets server-side and document allowed public env keys.
```

55. **ESLint/Prettier introduction plan**

```text
Plan the introduction of ESLint and Prettier for this project without changing application behavior. Detect current JS style, dependencies, scripts, legacy browser support, ignored files, and CI impact. Propose a minimal first configuration and rollout steps.
```

56. **Stylelint introduction plan**

```text
Plan Stylelint for SCSS/CSS in this project. Detect current SCSS/CSS structure, generated/vendor assets, ignored paths, and build constraints. Propose a minimal configuration that excludes vendor/public generated assets and focuses on source files first.
```

### Testing Prompts

57. **Feature test coverage**

```text
Add Feature tests for {route_or_workflow}. Cover guest, authenticated, unauthorized, authorized, validation failure, success, redirect/JSON response, database writes, events/jobs, and external HTTP calls with fakes. Preserve current behavior.
```

58. **Unit test coverage**

```text
Add Unit tests for {action_service_scope_or_value_object}. Keep tests independent of HTTP where possible. Cover success, invalid input, missing records, permission assumptions, external failures via fakes/mocks, and edge cases.
```

59. **Policy test matrix**

```text
Create a policy test matrix for {model}. Cover guest, owner, non-owner, admin, disabled user, unverified user, soft-deleted records if relevant, create/view/update/delete/restore/forceDelete where applicable.
```

60. **Validation test matrix**

```text
Create validation tests for {form_request}. Cover required fields, type errors, enum-like values, file constraints, array nesting, foreign-key existence, authorization failure, successful validated data, and JSON/HTML response behavior.
```

61. **External HTTP fake coverage**

```text
Replace live external calls in tests for {provider_or_service} with Http::fake(). Cover success, provider error, timeout/exception, malformed response, missing credentials, and no-call cases when required input is absent.
```

62. **Queue fake coverage**

```text
Add Queue::fake() coverage for {workflow}. Assert the correct jobs are dispatched with small payloads after authorization and validation pass. Add job tests for idempotency, missing records, retries, and failure behavior.
```

63. **View render regression**

```text
Add regression tests for {blade_view}. Render with full data, empty data, unauthorized user where applicable, missing optional relationships, and malicious user-provided text to prove escaping and layout-critical data flow.
```

64. **Query-count regression**

```text
Add a query-count regression test for {page_or_endpoint}. Establish the current count, refactor to eager loading or aggregates, and assert the count does not grow with additional records.
```

### Static Analysis and Tooling Prompts

65. **Larastan introduction plan**

```text
Plan a Larastan/PHPStan rollout for this project. Detect Laravel/PHP versions, current dynamic patterns, magic properties, baseline noise, ignored paths, CI budget, and first target level. Do not install or change code yet.
```

66. **Larastan cleanup slice**

```text
Fix Larastan/PHPStan findings in {module} without changing behavior. Prefer real type fixes over ignores. Add tests when type changes affect runtime assumptions. Keep the slice small and report remaining baseline items.
```

67. **Rector introduction plan**

```text
Plan a Rector rollout for safe PHP modernization. Detect PHP/Laravel versions and current coding patterns. Propose rule sets, dry-run command, excluded paths, review strategy, and rollback plan. Do not run mass refactors yet.
```

68. **Rector safe slice**

```text
Run Rector only for {specific_rule_or_path} in dry-run first. Review every changed file, keep behavior unchanged, avoid broad style churn, run tests, and commit only the reviewed safe slice.
```

69. **Composer dependency audit**

```text
Audit Composer dependencies for abandoned packages, security advisories, Laravel 13 compatibility, PHP constraints, unused packages, and risky transitive dependencies. Do not upgrade yet. Produce an ordered upgrade/removal plan with tests.
```

70. **NPM dependency audit**

```text
Audit npm dependencies for vulnerabilities, abandoned packages, legacy build constraints, public asset risk, and migration candidates. Do not upgrade yet. Produce an ordered plan that separates security fixes from build-tool migrations.
```

### CI/CD, Deployment, and Operations Prompts

71. **CI pipeline plan**

```text
Design a CI pipeline for this Laravel app. Include composer install, npm ci, Pint, php artisan test, composer validate, composer audit, npm run production, optional npm audit, config cache, route cache, view cache, and artifact strategy. Do not add CI files until approved.
```

72. **CI implementation**

```text
Implement CI for this Laravel app using the plan in {plan_file_or_summary}. Keep the first pipeline minimal and reliable. Cache Composer/npm dependencies safely. Do not require services the test suite does not need. Add documentation for required secrets.
```

73. **Deployment checklist**

```text
Create a deployment checklist for {environment}. Include dependency install, env verification, APP_KEY, storage permissions, migrations, backups, config/route/view cache, queue restart, scheduler, asset build, smoke tests, rollback, and log review.
```

74. **Zero-downtime migration plan**

```text
Plan a zero-downtime migration for {schema_change}. Use expand-contract where needed. Include deploy order, backwards compatibility, backfill, index creation, queue/worker considerations, rollback, and tests.
```

75. **Queue deployment plan**

```text
Document queue deployment for {job_or_workflow}. Include driver, worker command, process manager, retries, timeout, memory, failed jobs, idempotency, monitoring, and restart strategy.
```

76. **Production hardening audit**

```text
Audit production hardening for this app: APP_DEBUG, APP_ENV, HTTPS, secure cookies, session settings, logging, backups, queue workers, scheduler, cache, storage permissions, exposed install/update routes, and dependency advisories. Do not change code yet.
```

77. **Rollback plan**

```text
Create a rollback plan for {release_or_change}. Include code rollback, database rollback or forward fix, asset rollback, config/cache clear, queue worker restart, failed job handling, and user-facing smoke checks.
```

78. **Smoke test script plan**

```text
Design post-deploy smoke checks for {environment}. Cover homepage, login, registration, password reset, profile, post/story creation, upload, payment callback, admin dashboard, queue worker, scheduler, and logs. Do not automate until approved.
```

### Git, Review, and Commit Prompts

79. **Pre-commit review**

```text
Review the current diff before commit. Identify behavior changes, docs-only changes, formatting churn, secrets, raw SQL, unsafe routes, missing tests, and unrelated files. Recommend a Conventional Commit message and exact verification commands.
```

80. **Split mixed diff**

```text
The working tree contains mixed changes. Split them into safe commits: docs, formatting-only, dependency/build, tests, and behavior changes. Do not discard anything. Use explicit pathspecs and report what remains unstaged.
```

81. **Code review mode**

```text
Review {branch_or_diff} as a senior Laravel reviewer. Lead with findings ordered by severity. Include file/line references, exploitability or regression risk, missing tests, and concrete fix direction. Keep summary brief after findings.
```

82. **Refactor acceptance check**

```text
Check whether {refactor_task} is complete. Compare the implementation to the original prompt, AGENTS.md, docs/coding-standards.md, and docs/refactor-checklist.md. Verify tests and static checks. List any gaps before calling it done.
```

83. **Commit and push**

```text
Commit and push the completed {task}. First run relevant verification. Inspect git status and staged diff. Ensure no secrets or unrelated files are included. Use a Conventional Commit message. Push only after confirming the intended commit is HEAD.
```

84. **Branch cleanup**

```text
Merge completed work into main, push main, and remove merged branches. First verify tests pass and inspect local/remote branches. Do not delete unmerged work unless explicitly confirmed. After cleanup, report local and remote branch state.
```

## Refactor Completion Report Template

Use this report shape after each completed slice:

```text
PROBLEM
What was wrong or risky.

SOLUTION
What changed, with files.

BEHAVIOR
What behavior was preserved or intentionally changed.

QUERY DELTA
Before/after query count or performance impact when relevant.

SECURITY
Authorization, validation, secrets, input/output, and OWASP notes.

TESTS
Exact tests added/updated and command results.

BUILD/STATIC CHECKS
Exact formatter, static analysis, audit, and build results.

CAVEATS
Known residual risk, rollout notes, and follow-up tasks.
```

## Enterprise Refactor Definition of Done

- The selected task is one coherent slice.
- Behavior preservation is backed by tests or explicit user approval for changed behavior.
- Controllers remain thin.
- Validation lives in Form Requests for writes.
- Authorization lives in policies/gates or middleware.
- Queries live in Eloquent relationships, scopes, query classes, actions, or services.
- Blade receives preloaded data and does not query.
- External side effects are faked in tests and queued where appropriate.
- No secrets are exposed to code, logs, Blade, JavaScript, or committed files.
- No unsafe raw SQL is introduced.
- Accessibility is not made worse.
- Deployment impact is understood for schema, queue, cache, config, and route changes.
- `php artisan test` and relevant checks pass, or failures are documented as pre-existing with evidence.
