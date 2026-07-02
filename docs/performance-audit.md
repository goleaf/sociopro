# Performance Audit

Generated: 2026-07-02

This is the current performance handoff for the senior-upgrade slice. It records safe fixes, known bottlenecks, and the next reviewable work.

## Current Baseline

- Laravel 13.18.0 with Eloquent as the preferred query layer.
- PHPUnit tests use sqlite `:memory:`; this does not reproduce production MySQL query plans.
- Frontend build is Laravel Mix / Webpack.
- Marketplace API list/filter endpoints use `simplePaginate`.
- Upload processing still resizes images synchronously in HTTP requests.

## Safe Improvements In This Slice

- Post and marketplace image upload tests assert public-path writes, which protects the current browser/API contract.
- Job application PDF uploads now write to private local storage and stream through an action before falling back to the legacy public path.
- Media download paths are resolved and constrained before reading/deleting files, reducing filesystem traversal risk without adding database load.

## High-Impact Bottlenecks To Tackle Next

| Area | Risk | Next reviewable slice |
| --- | --- | --- |
| `ApiController` list endpoints | N+1 queries and unbounded collections in social/job/group/fundraiser flows | Add contract tests and query-count tests for one endpoint, then eager load relationships and paginate. |
| Blade chat/feed views | Queries and raw rendering can repeat per row | Move one high-traffic partial to controller/view-model data and add a query-count regression test. |
| Image processing | Resizing runs synchronously in request/response cycle | Introduce an idempotent media-processing job after upload contracts are fully covered. |
| Legacy helper calls | Global helpers hide storage, settings, and HTTP costs | Replace one helper call path at a time with a typed service and tests. |
| Laravel Mix/Webpack | Full dev-tool audit noise and older build chain | Plan Mix-to-Vite as a dedicated build-tool migration with visual/build checks. |

## Measurement Plan

- Add query-count smoke tests for dashboard, timeline, profile media, chat, marketplace, and jobs.
- Use production-like MySQL data for EXPLAIN plans before adding composite indexes.
- Track response time and memory for exports/imports before moving them to queues.
- Use `Storage::fake`, `Queue::fake`, and `Http::fake` for automated tests; do not call real providers.

## Not Fixed In This Slice

- No broad `ApiController` split.
- No Vite migration.
- No queue worker/process manager configuration.
- No schema/index changes.
- No cache layer added, because invalidation rules need domain-specific tests first.
