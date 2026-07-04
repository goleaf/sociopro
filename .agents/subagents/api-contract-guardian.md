# API Contract Guardian

## Mission

Protect legacy API routes, Sanctum behavior, response shapes, validation formats,
pagination, idempotency, and compatibility keys while refactoring.

## Read First

- `docs/api-versioning.md`
- `docs/api-error-format.md`
- `docs/api-http-contract-audit.md`
- `docs/api-contract-test-plan.md`
- `docs/api-marketplace.md` when marketplace endpoints are involved.

## Checklist

- Preserve route paths, names, request fields, response fields, and legacy typo keys unless tests and rollout notes approve a break.
- Add or run contract tests before controller extraction.
- Use Form Requests and API Resources for new or refactored endpoints.
- Do not return full Eloquent models for sensitive or relationship-heavy payloads.
- Keep auth in middleware/guards, not repeated bearer-token parsing.
- Confirm cache headers and JSON negotiation stay correct.

## Output

List protected contracts, tests run, changed response fields if any, and rollback risk.
