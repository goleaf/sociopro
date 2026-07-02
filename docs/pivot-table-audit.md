# Pivot Table Audit

Date: 2026-07-02

## Scope

Audited legacy many-to-many and pivot-like tables from `public/assets/install.sql`, Eloquent relationship definitions, schema hardening migrations, and relationship tests.

This project uses several join tables as full legacy models with `id` columns and direct controller/model writes. They remain Eloquent models instead of being converted to `Pivot` subclasses so existing `new Model; ...->save()` flows keep their behavior.

## Pivot-Like Tables

| Table | Relationship | Metadata | Status |
| --- | --- | --- | --- |
| `block_users` | User blocks another user | `id`, timestamps | True owner-target pivot; composite unique exists. |
| `followers` | User follows user, page, or group | `id`, nullable target columns, timestamps | Multi-target pivot-like table; owner-target unique indexes exist for each target. |
| `group_members` | User joins group | `id`, `is_accepted`, `role`, timestamps | True pivot with metadata; composite unique exists. |
| `page_likes` | User likes page | `id`, `role`, timestamps | True pivot with metadata; composite unique exists. |
| `saved_products` | User saves marketplace product | `id`, timestamps | True pivot; composite unique exists. |
| `saveforlaters` | User saves one of video, group, post, marketplace, event, or blog | `id`, nullable target columns, timestamps | Multi-target pivot-like table; owner-target unique indexes exist for each target. |

## Not Treated as True Pivots

| Table | Reason |
| --- | --- |
| `friendships` | Directional workflow table with requester/accepter, pending/accepted state, importance, and reverse-direction behavior. A unique pair constraint is deferred until friend-request state transitions are normalized. |
| `post_shares` | Share history/action table with `shared_on` metadata. Multiple shares by the same user may be valid legacy behavior, so no composite unique pivot constraint was added. |
| `invites` | Invitation workflow table with sender, receiver, target, and acceptance state. Repeated invites may be meaningful until an active-invite state model is introduced. |

## Constraints and Indexes

- Composite unique constraints are enforced by `2026_07_02_160000_add_safe_legacy_unique_constraints.php` for owner-target pivot rows where duplicate data is safe to reject.
- Reverse lookup and feed/query indexes are covered by the lookup, relationship, and query-pattern index migrations.
- Target-side foreign keys with clear lifecycle ownership are covered by `2026_07_02_150000_add_safe_legacy_foreign_key_constraints.php` and use cascade deletes for target-owned pivot rows.
- User-side foreign keys are deferred because `users.id` is an unsigned bigint in the dump while most pivot owner columns are signed `int(11)`. Adding those constraints safely requires an expand-contract migration with backfilled unsigned owner columns.
- Foreign-key helper indexes are retained on rollback because rollback cannot prove whether a helper-named index existed before the migration.

## Relationship Definitions

Explicit many-to-many relationships now name their pivot tables and expose required pivot metadata:

- `User::blockedUsers()` / `User::blockedByUsers()`
- `User::followingUsers()` / `User::followedByUsers()`
- `User::followedPages()` / `Page::followedByUsers()`
- `User::followedGroups()` / `Group::followedByUsers()`
- `User::joinedGroups()` / `Group::members()`
- `User::likedPages()` / `Page::likedByUsers()`
- `User::savedProducts()` / `Marketplace::savedByUsers()`
- `User::savedVideos()` / `Video::savedByUsers()`
- `User::savedGroups()` / `Group::savedByUsers()`
- `User::savedPosts()` / `Posts::savedByUsers()`
- `User::savedMarketplaceItems()` / `Marketplace::savedForLaterByUsers()`
- `User::savedEvents()` / `Event::savedByUsers()`
- `User::savedBlogs()` / `Blog::savedByUsers()`

The pivot-like model classes also expose parent `belongsTo` relationships for their foreign-key columns while preserving legacy method names such as `GroupMember::getGroup()`, `GroupMember::getUser()`, `PageLike::pageData()`, `SavedProduct::productData()`, and `Saveforlater::getVideo()`.

## Safe Follow-Up Work

1. Add production duplicate reports for `friendships`, `post_shares`, and `invites` before considering stricter uniqueness.
2. Normalize user foreign-key column types with shadow unsigned-bigint columns, backfill, dual-write, and swap reads before adding user-side FKs.
3. Split multi-target tables such as `followers` and `saveforlaters` into one target per table only after API/UI behavior is covered by regression tests.
4. Add dedicated custom `Pivot` subclasses only if application code starts attaching/syncing through `belongsToMany()` instead of writing the existing full model classes directly.

## Verification

`tests/Feature/PivotTableAuditTest.php` verifies pivot table names, columns, timestamps, composite unique constraints, lookup indexes, target-side cascade foreign keys, and this audit document.

`tests/Feature/EloquentRelationshipAuditTest.php` verifies explicit `belongsToMany()` definitions, pivot keys, `withPivot`/timestamp metadata, and parent relationships on pivot-like model classes.
