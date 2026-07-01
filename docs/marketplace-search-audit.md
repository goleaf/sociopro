# Marketplace Search Audit

## Scope

This audit covers the authenticated API marketplace filter endpoint and the `App\Queries\Marketplace\MarketplaceProductsQuery` query object.

## Current Behavior

- Search is limited to marketplace `title` and `description`.
- Location filtering is separate but keeps the legacy `search OR location` behavior.
- Only active marketplace rows are returned through `status = 1`.
- Sort columns are allowlisted to `id`, `created_at`, `price`, and `title`.
- Non-`id` sorts now add `id` as a deterministic tiebreaker.
- Search and location `LIKE` values escape `%`, `_`, and backslashes so user input is treated as literal text.
- The endpoint keeps the legacy response shape and validation error shape.

## Raw SQL Exception

`MarketplaceProductsQuery` uses one isolated `whereRaw()` fragment for `LIKE ? ESCAPE '\'`. Laravel's structured query builder does not provide a portable escape-clause helper for literal wildcard searches. The exception is constrained to fixed, allowlisted columns, uses parameter bindings for all user input, and is covered by wildcard regression tests.

## Index Coverage

Added safe, non-unique marketplace lookup indexes:

- `marketplaces_status_id_idx` on `status, id`
- `marketplaces_status_created_id_idx` on `status, created_at, id`
- `marketplaces_status_price_id_idx` on `status, price, id`
- `marketplaces_status_title_id_idx` on `status, title, id`

These support permission-aware active-row filtering plus deterministic ordering. They do not make `%term%` contains searches fully index-backed because leading-wildcard `LIKE` scans are not a good fit for normal B-tree indexes.

## Validation

- `search` and `filters.search` are capped at 120 characters.
- Existing filter validation still blocks arbitrary category, brand, condition, sort, direction, page, per-page, price, and date input.
- Sort field validation is duplicated defensively in the query object so callers cannot pass arbitrary SQL column names by bypassing the Form Request.

## Upgrade Options

1. Database full-text search
   - Add database-specific full-text indexes for `marketplaces.title` and `marketplaces.description`.
   - Use this when the production database is standardized and the team accepts database-specific migration syntax.
   - Requires relevance ordering, minimum token length decisions, stop-word review, and fallback behavior for SQLite tests.

2. Laravel Scout with a search service
   - Add Scout plus Meilisearch, Typesense, Algolia, or another supported engine.
   - Use this when marketplace search needs relevance, typo tolerance, facets, highlighting, or fast large-catalog queries.
   - Requires index sync jobs, failure handling, reindex commands, environment configuration, and tests for search-index lag.

3. Dedicated search table
   - Maintain a normalized searchable document table populated from marketplace rows.
   - Use this if external search infrastructure is not acceptable but search needs more structure than `LIKE`.
   - Requires write-side synchronization and clear rollback/backfill commands.

## Safe Next Steps

1. Measure production marketplace row counts and query plans for common filters.
2. Decide the production database target before adding database-specific full-text indexes.
3. Add an explicit relevance contract before changing ordering away from the current deterministic sort behavior.
4. Add query-count and response-time budgets around marketplace search before introducing a search service.
