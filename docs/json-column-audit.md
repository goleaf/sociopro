# JSON Column Audit

This audit covers legacy JSON-like columns discovered in the install dump, models, controllers, Blade views, and tests. The safe implementation path is intentionally conservative: only fields that already behave as structured payloads at the Eloquent boundary were migrated toward native JSON columns.

## Applied Safe Changes

| Column | Current shape | Boundary protection | Cast status | Query/index status |
| --- | --- | --- | --- | --- |
| `payment_gateways.keys` | Object keyed by gateway credential names | Admin gateway update now validates `currency` and each known key as nullable strings; unexpected keys remain ignored | Cast to `array` and hidden from serialization | Not queried by JSON path; no index or generated columns needed |
| `payment_histories.transaction_keys` | Object/array of provider transaction references | Written from payment flows as structured transaction metadata | Cast to `array` and hidden from serialization | Not queried by JSON path; no index or generated columns needed |

The migration `2026_07_02_200000_add_safe_legacy_json_column_constraints.php` converts those two columns to JSON only when every non-null value decodes to an array. Dirty values cause the migration to skip the column so deployment does not fail on legacy production data.

## Deferred Casts

These columns are JSON-like but are not safe to cast yet because active code still expects raw JSON strings, performs manual `json_decode`, or uses mixed string/JSON semantics.

Treat these as deferred casts until the listed callers are migrated behind typed helpers or DTOs.

| Column | Expected schema | Why deferred | Safe first step |
| --- | --- | --- | --- |
| `live_streamings.details` | Object containing Zoom/Jitsi meeting fields such as `id`, `topic`, `join_url`, `link`, `status`, and `join_pass` | Controllers and tests call `json_decode($stream->details, true)` and direct inserts write encoded strings | Add a `detailsPayload()` accessor/helper, migrate callers, then add an array cast |
| `users.friends` | List of user IDs | Queried with `whereJsonContains` and decoded in helpers; membership queries are unindexed JSON scans | Normalize friendships into relational rows or add engine-specific generated columns only after measuring hot paths |
| `users.followers` | List of user IDs | Legacy longtext payload with unclear write boundaries | Add request/service normalization tests before any cast |
| `users.save_post` | List of saved post IDs | Save/unsave flows manually decode and encode strings | Move save/unsave logic behind a model method or action before casting |
| `users.payment_settings` | Object of per-user payment provider keys and booleans | Views and payment services expect decoded object/string behavior | Add a typed DTO/action boundary and keep secrets hidden from logs/serialization |
| `posts.tagged_user_ids` | List of user IDs | Profile queries use `whereJsonContains`; views and tests still create encoded strings | Replace repeated conditions with a scope, then migrate callers to a cast or normalized pivot |
| `posts.user_reacts` | Object keyed by reaction type/user IDs | Blade and tests decode raw JSON strings | Extract reaction read/write helpers before casting |
| `posts.shared_user` | Object/list depending on legacy share flow | Mixed semantics in legacy post sharing | Document canonical shape with regression tests first |
| `settings.description` | Mixed scalar strings and JSON objects by `type` | Same column stores plain text pages, emails, config objects, and addon payloads | Keep uncast; use typed accessors for known JSON setting types |
| `comments.user_reacts` | Object keyed by reaction type/user IDs | Legacy reaction flow has no typed boundary yet | Add model helper and serialization tests |
| `stories.media_files` | List/object of story media metadata | Story flows use both `stories` and `media_files`; ownership of this payload is unclear | Prefer `media_files.story_id` relationship over more JSON payload expansion |

## Unindexed JSON Queries

The current code still contains JSON membership filters, especially `whereJsonContains('users.friends', ...)` and `whereJsonContains('posts.tagged_user_ids', ...)`. These are valid Eloquent queries but are expensive on large tables because the legacy JSON arrays are not naturally covered by normal b-tree indexes.

No generated columns were added in this pass. Generated columns and JSON indexes are database-engine-specific, and array membership indexing differs between MySQL, MariaDB, PostgreSQL, and SQLite. The safer first implementation is to measure the hot routes, then either normalize the membership data into pivot tables or add narrowly scoped generated columns where the production database supports them.

## Schema Expectations

- JSON objects that may contain secrets must stay hidden from model serialization.
- Request boundaries must accept only known keys and scalar values unless a typed nested schema exists.
- Empty strings are dirty JSON values for migration purposes.
- Native JSON conversion must be skipped when any existing value cannot be decoded into an array.
- Do not add casts to columns that active Blade, controller, helper, or payment code still reads as raw JSON strings.
- Do not add JSON path indexes unless the exact path is stable, heavily queried, and supported by the production database engine.

## Suggested Order

1. Keep `payment_gateways.keys` and `payment_histories.transaction_keys` as the only native JSON conversions.
2. Add query-count tests around friend/tagged-user feeds before changing JSON membership storage.
3. Extract helpers/actions for `live_streamings.details`, `users.save_post`, and `posts.user_reacts`.
4. Replace hot JSON membership arrays with relational tables or database-specific generated columns after measurement.
5. Add casts only after callers no longer manually decode the same attribute.
