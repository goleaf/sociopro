# Unique Constraint Audit

Date: 2026-07-02

## Scope

Audited validation-only uniqueness rules, natural keys, legacy lookup tables, owner-scoped relationship tables, the dump-backed SQLite schema, and `public/assets/install.sql`.

No tenant table, tenant middleware, tenant key, or tenant route boundary is present in this checkout. Composite unique constraints in this pass therefore use the actual business boundary available in the schema: global reference keys or owner-target pairs.

## Existing Database Constraints

The legacy dump already enforces these unique indexes:

- `users.email`
- `failed_jobs.uuid`
- `personal_access_tokens.token`

## Safe Constraints Added

Added `database/migrations/2026_07_02_160000_add_safe_legacy_unique_constraints.php`.

The migration checks table existence, column existence, existing unique indexes, index-name collisions, and duplicate data before adding each unique index. If production data contains duplicates for a candidate key, the migration skips that key instead of failing deployment.

Repeated-action insert paths for follows, friend-request follower rows, page likes, group joins, saved products, saved videos, and block rows now check for an existing owner-target row before inserting. This keeps the legacy success response while avoiding duplicate-key exceptions after the unique indexes are active.

| Table | Unique columns | Scope | Reason |
|---|---|---|---|
| `addons` | `unique_identifier` | Global | Addon import uses `unique_identifier` as the package identity. |
| `blogcategories` | `name` | Global lookup | Admin validation treats blog category names as unique. |
| `brands` | `name` | Global lookup | Admin validation treats marketplace brand names as unique. |
| `categories` | `name` | Global lookup | Admin validation treats marketplace category names as unique. |
| `currencies` | `code` | Global reference | Currency code is the stable lookup key. |
| `payment_gateways` | `identifier` | Global reference | Payment routing resolves gateways by identifier. |
| `pagecategories` | `name` | Global lookup | Admin validation treats page category names as unique. |
| `block_users` | `user_id`, `block_user` | Owner-target | A user should not have duplicate block rows for the same target user. |
| `followers` | `user_id`, `follow_id` | Owner-target | A user should not follow the same profile more than once. |
| `followers` | `user_id`, `page_id` | Owner-target | A user should not follow the same page more than once. |
| `followers` | `user_id`, `group_id` | Owner-target | A user should not follow the same group more than once. |
| `group_members` | `user_id`, `group_id` | Owner-target | Membership is one row per user per group. |
| `page_likes` | `user_id`, `page_id` | Owner-target | A user can like a page once. |
| `saved_products` | `user_id`, `product_id` | Owner-target | A user can save a marketplace product once. |
| `saveforlaters` | `user_id`, `video_id` | Owner-target | A user can save a video once. |
| `saveforlaters` | `user_id`, `group_id` | Owner-target | A user can save a group once. |
| `saveforlaters` | `user_id`, `post_id` | Owner-target | A user can save a post once. |
| `saveforlaters` | `user_id`, `marketplace_id` | Owner-target | A user can save a marketplace item once. |
| `saveforlaters` | `user_id`, `event_id` | Owner-target | A user can save an event once. |
| `saveforlaters` | `user_id`, `blog_id` | Owner-target | A user can save a blog once. |

## Deferred Constraints

| Candidate | Why deferred | Safe first step |
|---|---|---|
| `languages.name`, `languages.phrase` | The local dump contains duplicate `english` / `404 page not found` rows. | Add a cleanup migration that preserves the newest or translated row, deletes redundant duplicates, then add `languages_name_phrase_unique`. |
| `settings.type` | The local dump contains duplicate `about`, `policy`, and `term` settings. Several legacy reads use `value()` / `first()` and depend on whichever row wins. | Audit which duplicate row is canonical, merge content, delete redundant rows, then add `settings_type_unique`. |
| `friendships` requester/accepter pairs | Friend requests are directional and the legacy flow can contain pending, accepted, and reverse-direction rows. | Normalize friend-request state transitions and add tests before considering a directional or canonical-pair unique constraint. |
| `invites` sender/receiver/target tuples | Invite behavior can be repeated and accepted/rejected over time; current code does not consistently define one active invite. | Add an explicit invite state model or active-only uniqueness rule before adding constraints. |
| Addon-owned tables absent from the dump | Some models are present without dump-backed tables. | Audit each addon installer schema before adding module-specific uniqueness. |

## Dirty Data Cleanup Steps

1. Run read-only duplicate reports for every proposed key in production.
2. For lookup/reference tables, decide the canonical row and update dependents before deleting duplicates.
3. For owner-target tables, keep the oldest row unless the newest row carries important status metadata.
4. For language/settings duplicates, merge translated or rich-text content before deletion.
5. Add a follow-up unique-index migration after cleanup passes; do not rely on rerunning an already-applied migration that skipped dirty keys.

## Verification

`tests/Feature/MigrationSafetyAuditTest.php` verifies the unique-index migration can run `up()`, `down()`, and `up()` again. It also seeds duplicate dirty data and confirms the migration skips that blocked unique index until the duplicate rows are cleaned.
