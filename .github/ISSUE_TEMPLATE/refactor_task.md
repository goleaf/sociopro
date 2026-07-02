---
name: Refactor task
about: Track a slice from the senior refactor roadmap
title: "[Refactor] "
labels: refactor
---

## Roadmap phase

<!-- e.g. Phase 3 - routes/controllers/Form Requests/policies -->

## Target module

## Scope of this slice

Keep it small enough for one focused, reviewable PR.

## Behavior to preserve

- Routes / route names:
- Response shapes / redirects / flash messages:
- Database contracts:
- File/storage paths:
- API JSON fields:

## Characterization tests required before refactor

- [ ]

## Refactor plan

- [ ] Move validation to Form Request or explicit validator
- [ ] Move authorization to policy/gate/middleware
- [ ] Move workflow to action/service
- [ ] Move repeated query to scope/query class
- [ ] Keep Blade presentation-only

## Definition of done

- [ ] Behavior preserved by tests
- [ ] No query added in Blade or loops
- [ ] No `$request->all()` mass assignment
- [ ] No raw SQL string concatenation
- [ ] Pint, PHPStan/Larastan, tests green
- [ ] Docs/debt register updated
- [ ] Deployment/rollback impact documented
