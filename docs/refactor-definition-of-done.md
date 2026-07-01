# Refactor Definition of Done

Generated: 2026-07-01

This document defines what must be true before any refactor in this Laravel project is accepted. It applies to behavior-preserving refactors, security refactors, migration refactors, frontend refactors, test-only refactors, and infrastructure refactors.

Use this with:

- `AGENTS.md`
- `docs/project-standards-bible.md`
- `docs/coding-standards.md`
- `docs/refactor-checklist.md`
- `docs/refactor-roadmap-unreal.md`
- `docs/module-inventory.md`
- `docs/risk-register.md`

## Acceptance Rule

A refactor is not done until every applicable item below is true, verified, and documented in the final handoff or pull request.

If an item cannot be completed safely in the current slice, the refactor can only be accepted when:

- The blocker is documented with exact scope and risk.
- The skipped check is explicitly named.
- A safe follow-up task is recorded.
- The current change does not make the skipped risk worse.

## 1. Scope and Behavior

- The refactor has one clear purpose and one bounded module or layer.
- Public behavior is preserved unless the task explicitly fixes a proven bug or security issue.
- Any intentional behavior change is described before merge and covered by tests.
- Public routes, route names, request parameters, response shapes, Blade output, database columns, config keys, and scheduled behavior remain backward compatible unless a breaking change is approved.
- No unrelated cleanup, package upgrade, formatting sweep, or design change is mixed into the refactor.
- Existing user changes in the working tree are not overwritten or reverted.
- Risky legacy behavior is characterized with tests before implementation changes.

## 2. Tests

Minimum test expectations:

- `php artisan test` passes before commit.
- Focused tests for the touched module pass before the full suite.
- New or updated tests cover the behavior protected by the refactor.
- Tests prove both success and failure paths when the refactor touches validation, authorization, payments, uploads, provider callbacks, account state, admin actions, or destructive operations.
- External providers are faked with Laravel fakes or HTTP client fakes. Tests must not call real payment providers, email providers, social APIs, object storage, AI APIs, or webhooks.
- Database tests use factories, seeders, or the project-standard database reset strategy. They do not depend on manual local state.
- Authorization tests cover guest, owner, non-owner, disabled user, unverified user, normal user, admin, and provider callback cases when those roles matter.
- If current tests fail before the refactor starts, the exact failing tests and failure text are documented, and the refactor does not add new failures.

Required for risky refactors:

- Add characterization tests before changing controller-heavy or helper-heavy legacy flows.
- Add regression tests for any bug being fixed.
- Add route-level or HTTP feature tests for state-changing endpoint changes.
- Add query behavior tests for extracted scopes, query objects, or eager-loading changes.
- Add queue, event, notification, mail, storage, and HTTP fakes when side effects are moved into jobs, events, or services.

## 3. Formatting and Style

- PHP changes pass `vendor/bin/pint --test`.
- Markdown changes are readable, structured, and free of trailing whitespace.
- New PHP code follows PSR-12 and the Laravel conventions in `docs/coding-standards.md`.
- New classes use the project placement conventions: requests in `app/Http/Requests`, policies in `app/Policies`, actions in `app/Actions`, services in `app/Services`, jobs in `app/Jobs`, resources in `app/Http/Resources`, and tests in `tests/Feature` or `tests/Unit`.
- Refactors do not introduce `dd()`, `dump()`, `ray()`, `var_dump()`, `print_r()`, `console.log()`, debug routes, temporary scripts, or commented-out code.
- Formatting-only changes are not mixed with behavior refactors unless the task explicitly requests a mechanical formatting pass.

## 4. Static Analysis and Tooling

Current installed baseline:

- PHPUnit is installed.
- Laravel Pint is installed.
- Larastan/PHPStan, Rector, ESLint, Stylelint, Prettier, and Vite are not currently installed unless dependency files later prove otherwise.

Definition of done:

- Run installed static-analysis and lint tools relevant to the touched code.
- For PHP refactors, run `vendor/bin/pint --test` and `php artisan test`.
- If Larastan/PHPStan is installed in the future, run the project PHPStan command and fix or document every new issue.
- If Rector is installed in the future, run safe dry-run rules before applying automated changes, and review every generated diff.
- If ESLint, Stylelint, or Prettier are installed in the future, run the configured check scripts for touched frontend assets.
- If a requested tool is not installed, do not silently add it in the same refactor unless the task is specifically about tooling. Document that it was unavailable.

## 5. Security Review

Every refactor must be reviewed for security regressions before acceptance:

- No secrets, credentials, tokens, private keys, production `.env` values, database dumps, or provider keys are committed.
- No `env()` calls are introduced outside config files.
- No raw request payload is passed into `create()`, `update()`, `fill()`, `forceFill()`, relation `create()`, or bulk update calls.
- No new `$guarded = []` is introduced.
- New or touched write endpoints use Form Requests or equivalent explicit validation.
- Sensitive model operations use policies or gates. UI hiding is never treated as authorization.
- State-changing routes use write methods and CSRF protection unless the route is a verified external callback with a documented verification strategy.
- External callbacks and webhooks validate signatures, tokens, provider status, and replay/duplicate behavior where applicable.
- File uploads validate MIME type, size, extension, dimensions when relevant, ownership, storage disk, and path handling.
- Output remains escaped in Blade unless sanitized trusted HTML is explicitly required and documented.
- Logs, exceptions, API responses, and Blade views do not expose secrets or sensitive personal/payment data.
- Raw SQL string concatenation is not introduced. If raw expressions are unavoidable, they are isolated, bound safely, documented, and tested.
- Cache keys, session data, and queue payloads do not leak sensitive values.

## 6. Migration and Database Safety

Schema or data refactors are accepted only when:

- Existing production migrations are not edited. A new migration is created.
- The migration has a reversible `down()` method, or an irreversible operation is explicitly documented with rollback notes.
- Destructive operations have a backup plan, deployment order, and rollback strategy.
- The migration is safe for the expected production database size and write load.
- Foreign keys, indexes, uniqueness, nullability, defaults, and column types are intentional.
- Money is stored as decimals or integer minor units, never floats.
- Multi-step writes use transactions when partial writes would corrupt state.
- Large data updates use chunking or a queued/batched process.
- Query changes are checked against indexes for common filters, joins, and sorts.
- `php artisan migrate`, rollback, or `migrate:fresh --seed` verification is run when safe for the environment and relevant to the change.
- If migration verification cannot be run locally, the exact reason and manual verification plan are documented.

## 7. Performance Review

Every refactor that touches queries, controllers, views, jobs, APIs, or frontend assets must answer the performance question:

- Does the change add queries, loops, eager loads, aggregates, external calls, or large payloads?

Acceptance criteria:

- No new N+1 query pattern is introduced.
- Relationships used in views or resources are eager loaded.
- Counts, sums, and existence checks use `withCount()`, `withSum()`, `withExists()`, or equivalent preloaded data when rendered repeatedly.
- No query or aggregate is added inside Blade loops or conditionals.
- Large result sets use pagination, cursor pagination, `chunkById()`, `lazyById()`, or streaming.
- External provider calls are moved out of hot request paths when practical, usually into jobs or services.
- Heavy provider calls have timeouts and safe error handling.
- Frontend bundle or asset changes do not ship unused large libraries without justification.
- Query-count or timing evidence is captured for high-traffic pages when the refactor could affect performance.
- Any known performance tradeoff is documented with a follow-up.

## 8. Accessibility Review

Frontend and Blade refactors are accepted only when:

- Semantic HTML is preserved or improved.
- Buttons are used for actions and links for navigation.
- Interactive controls are keyboard accessible.
- Focus states remain visible.
- Forms have labels and validation errors are readable and associated with the relevant fields.
- Images have meaningful `alt` text or empty decorative alt text.
- Color is not the only signal for state, validation, or destructive actions.
- Modals, dropdowns, and dynamic UI preserve focus and escape behavior where applicable.
- Heading order and landmark structure are not made worse.
- Table headings and scopes are preserved for tabular data.
- Blade output remains escaped unless sanitized trusted HTML is explicitly required.
- Accessibility regressions are covered by manual review notes or automated checks when tooling is available.

## 9. Documentation

Documentation is required when a refactor changes or clarifies architecture, workflow, deployment, schema, security, or provider behavior.

Acceptance criteria:

- Relevant docs in `docs/` are updated or a reason for not updating them is documented.
- New actions, services, policies, jobs, migrations, or integration boundaries are understandable from names, tests, and docs.
- Risky exceptions are documented near the decision point or in a relevant project doc.
- New environment variables are added to config files and `.env.example`, never read directly with `env()` at runtime.
- New commands, queues, scheduled tasks, provider settings, deployment steps, or rollback steps are documented.
- Any public API, route, response, or payload change includes compatibility notes.
- Follow-up debt is specific and actionable, not a vague TODO.

## 10. Backward Compatibility

Before acceptance, the refactor must preserve:

- Existing route names and URLs unless the task explicitly includes a route migration.
- Existing request parameters and validation behavior unless tests prove an intentional change.
- Existing response status codes and JSON shapes unless documented as a versioned change.
- Existing Blade view data contracts unless all callers are updated and tested.
- Existing database columns, indexes, values, and defaults unless a migration plan covers the change.
- Existing config keys and environment variable names unless a compatibility shim is provided.
- Existing asset paths used by Blade views unless views and build output are updated together.
- Existing user permissions and ownership behavior unless a security fix intentionally tightens access.

If backward compatibility cannot be preserved, the refactor must include:

- Migration notes.
- Deployment notes.
- Rollback notes.
- Tests for old and new behavior where both are supported.
- Explicit approval for the breaking change.

## 11. CI Status

Acceptance criteria:

- Required CI checks pass before merge when CI is configured.
- If CI is not configured for the repository, local verification commands must be run and documented.
- CI failures unrelated to the refactor are documented with exact job names and failure summaries.
- A refactor is not accepted if it adds new CI failures.
- New required checks are added to CI when the refactor introduces new required tooling.

Minimum local commands before commit:

```bash
git diff --check
php artisan test
```

Minimum local commands for PHP/application refactors:

```bash
vendor/bin/pint --test
php artisan test
```

Run when relevant and safe:

```bash
composer validate --strict
npm run production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

After cache verification commands, clear local generated caches that should not be committed.

## 12. Deployment Notes

Deployment notes are required for refactors that touch:

- Migrations or seeders.
- Config files or environment variables.
- Queues, jobs, events, listeners, notifications, or mail.
- Cache, sessions, routes, views, config caching, or scheduled commands.
- Payment, storage, mail, AI, live-video, webhook, or other external integrations.
- Frontend build tooling, public assets, or compiled asset paths.
- Authorization, account state, admin behavior, or destructive routes.

Deployment notes must include:

- Required commands.
- Required environment/config changes.
- Database migration order.
- Cache clear/build steps.
- Queue worker restart requirements.
- Expected downtime or zero-downtime notes.
- External provider dashboard or webhook changes.
- Smoke-test URLs or flows after deploy.

## 13. Rollback Notes

Every accepted refactor must be rollback-aware.

Rollback notes must include:

- The git commit or release that can be reverted.
- Whether rollback requires code-only revert, config revert, migration rollback, data restore, cache clear, queue restart, or asset rebuild.
- Whether the migration is reversible with `php artisan migrate:rollback`.
- What data, if any, cannot be restored automatically.
- How to disable the refactored behavior if rollback is too risky.
- Which smoke tests prove rollback restored the expected behavior.

For migrations and provider changes, rollback notes are mandatory even for small refactors.

## 14. Final Review Checklist

Before a refactor is accepted, confirm:

- [ ] Scope is limited and behavior-preserving, or intentional changes are tested and documented.
- [ ] Focused tests pass.
- [ ] `php artisan test` passes.
- [ ] `vendor/bin/pint --test` passes for PHP changes.
- [ ] Installed static-analysis or lint checks pass, or unavailable tools are documented.
- [ ] Security review completed.
- [ ] Migration safety reviewed and verified when applicable.
- [ ] Performance impact reviewed.
- [ ] Accessibility impact reviewed for UI changes.
- [ ] Documentation updated.
- [ ] Backward compatibility preserved or approved as a breaking change.
- [ ] CI is green, or local verification is documented when CI is absent.
- [ ] Deployment notes are present when needed.
- [ ] Rollback notes are present.
- [ ] Staged diff contains only intended files.
- [ ] Staged diff was scanned for secrets.
- [ ] Commit message follows Conventional Commits.

If any required box is unchecked, the refactor is not done.
