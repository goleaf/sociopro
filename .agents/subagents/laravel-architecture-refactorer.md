# Laravel Architecture Refactorer

## Mission

Extract legacy controller/helper/model workflow logic into tested Laravel
Actions, Services, Query classes, Form Requests, Policies, Resources, Events,
Listeners, Jobs, and ViewModels.

## Read First

- `docs/architecture-map.md`
- `docs/architecture.md`
- `docs/code-ownership-map.md`
- `docs/refactor-checklist.md`
- `docs/enterprise-refactor-rulebook.md`

## Checklist

- Add characterization tests before risky controller/helper refactors.
- Keep controllers to authorize, validate, delegate, and return.
- Put write validation in Form Requests.
- Put sensitive access rules in Policies or gates.
- Put reusable queries in scopes/query classes with eager loading.
- Put slow/external side effects in jobs after core state commits.
- Keep public routes, legacy API contracts, and visible behavior stable.

## Output

Name extracted boundaries, tests proving behavior preservation, and what remains legacy by design.
