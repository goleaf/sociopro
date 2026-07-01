# Foreign Key Relationship Audit

Date: 2026-07-02

## Scope

Audited Eloquent relationships, legacy schema definitions in `public/assets/install.sql`, the local SQLite schema, existing lookup indexes, route/controller query usage, and relationship tests. The goal was to add only constraints that are safe for the current legacy schema and document cleanup work for relationships that would be risky in production.

The local SQLite database currently has no application foreign keys. It also has very little relationship data, so the new migration performs runtime orphan checks before adding each constraint. If production contains dirty rows, that relationship is skipped instead of breaking deployment.

## Safe Constraints Added

Added `database/migrations/2026_07_02_150000_add_safe_legacy_foreign_key_constraints.php`.

The migration adds child indexes when a leading index is missing, checks for existing constraints, checks `nullOnDelete` child-column nullability, checks for orphan rows, and then adds the constraint. Rollback drops constraints in reverse order and removes helper indexes created by the migration.

| Child | Parent | Delete behavior | Reason |
|---|---|---|---|
| `album_images.album_id` | `albums.id` | cascade | Album images are dependent rows and should not outlive the album. |
| `album_images.page_id` | `pages.id` | cascade | Page album-image rows are page-scoped content. |
| `album_images.group_id` | `groups.id` | cascade | Group album-image rows are group-scoped content. |
| `albums.page_id` | `pages.id` | cascade | Page albums are owned by the page lifecycle. |
| `albums.group_id` | `groups.id` | cascade | Group albums are owned by the group lifecycle. |
| `blogs.category_id` | `blogcategories.id` | nullOnDelete | Blog content can remain if an optional category is removed. |
| `events.group_id` | `groups.id` | cascade | Group events are group-scoped content. |
| `followers.page_id` | `pages.id` | cascade | Page follow rows are meaningless without the page. |
| `followers.group_id` | `groups.id` | cascade | Group follow rows are meaningless without the group. |
| `group_members.group_id` | `groups.id` | cascade | Group membership rows are dependent on the group. |
| `invites.event_id` | `events.id` | cascade | Event invitations are target-scoped. |
| `invites.page_id` | `pages.id` | cascade | Page invitations are target-scoped. |
| `invites.group_id` | `groups.id` | cascade | Group invitations are target-scoped. |
| `invites.post_id` | `posts.post_id` | cascade | Post invitations are target-scoped. |
| `marketplaces.currency_id` | `currencies.id` | restrict | Currency reference data should not be deleted while products use it. |
| `media_files.post_id` | `posts.post_id` | cascade | Post media is dependent content. |
| `media_files.story_id` | `stories.story_id` | cascade | Story media is dependent content. |
| `media_files.album_id` | `albums.id` | cascade | Album media is dependent content. |
| `media_files.product_id` | `marketplaces.id` | cascade | Marketplace product media is dependent content. |
| `media_files.page_id` | `pages.id` | cascade | Page media is page-scoped content. |
| `media_files.group_id` | `groups.id` | cascade | Group media is group-scoped content. |
| `media_files.chat_id` | `chats.id` | cascade | Chat attachments are dependent on the chat message. |
| `media_files.album_image_id` | `album_images.id` | cascade | Album-image media is dependent on the album image. |
| `notifications.event_id` | `events.id` | nullOnDelete | Notifications may remain as history after the target is removed. |
| `notifications.page_id` | `pages.id` | nullOnDelete | Notifications may remain as history after the target is removed. |
| `notifications.group_id` | `groups.id` | nullOnDelete | Notifications may remain as history after the target is removed. |
| `page_likes.page_id` | `pages.id` | cascade | Page-like rows are meaningless without the page. |
| `pages.category_id` | `pagecategories.id` | nullOnDelete | Page content can remain if an optional category is removed. |
| `post_shares.post_id` | `posts.post_id` | cascade | Post-share rows are dependent on the post. |
| `reports.post_id` | `posts.post_id` | cascade | Reports are dependent on the reported post. |
| `saved_products.product_id` | `marketplaces.id` | cascade | Saved-product rows are meaningless without the product. |
| `saveforlaters.video_id` | `videos.id` | cascade | Saved video rows are target-scoped. |
| `saveforlaters.group_id` | `groups.id` | cascade | Saved group rows are target-scoped. |
| `saveforlaters.post_id` | `posts.post_id` | cascade | Saved post rows are target-scoped. |
| `saveforlaters.marketplace_id` | `marketplaces.id` | cascade | Saved marketplace rows are target-scoped. |
| `saveforlaters.event_id` | `events.id` | cascade | Saved event rows are target-scoped. |
| `saveforlaters.blog_id` | `blogs.id` | cascade | Saved blog rows are target-scoped. |

## Constraints Deferred

| Relationship group | Why blocked | Safe cleanup path |
|---|---|---|
| Any child column pointing to `users.id` | `users.id` is `bigint unsigned` in the MySQL dump, while most legacy child columns are signed `int(11)`. MySQL rejects foreign keys with mismatched types/signedness. | Add new nullable unsigned-bigint shadow columns, backfill from legacy ids, dual-write in application code, verify no orphans, swap reads, then add user foreign keys in a follow-up migration. |
| `groups.user_id -> users.id` | `groups.user_id` is `text` in the dump. | Clean non-numeric values, add `owner_user_id` as unsigned bigint, backfill, update model relationship, then add a foreign key. |
| `marketplaces.category -> categories.id` and `marketplaces.brand -> brands.id` | Both child columns are `text` in the dump but model relationships treat them as integer ids. | Add `category_id` and `brand_id` integer columns, backfill only numeric valid rows, update writes/reads, then add constraints. |
| `posts.album_image_id -> album_images.id` | `posts.album_image_id` is `text` in the dump. | Add `album_image_ref_id`, backfill valid numeric references, update media/post code, then constrain. |
| `comments.id_of_type -> posts.post_id` | `comments.id_of_type` is polymorphic through `is_type`; non-post comments may point at blogs/videos/other content. | Split comments by target type or introduce explicit nullable target columns before adding per-target constraints. |
| `comments.parent_id -> comments.comment_id` | Root comments use sentinel `0`, not `NULL`. | Convert root rows from `0` to `NULL`, update application code/tests, then add a self-referencing `nullOnDelete` or cascade constraint. |
| `notifications.fundraiser_id -> fundraisers.id` | `fundraiser_id` is referenced by the model but absent from the local dump-backed `notifications` table. | Verify addon schema, add the column and relationship only in the fundraiser module migration, then constrain. |
| Addon tables such as jobs, badges, paid content, fundraisers | Several model classes exist but their tables are absent from the local dump-backed schema. | Audit each addon installer/schema source first, then add addon-specific constraints in module migrations. |

## Dirty Data Cleanup Steps

Use these steps before adding any deferred foreign key:

1. Export production schema metadata and compare column types, signedness, indexes, and table engines with `public/assets/install.sql`.
2. Run read-only orphan reports for each target relationship.
3. For `cascade` relationships, delete orphan child rows only after confirming they are meaningless without the parent.
4. For `nullOnDelete` relationships, null orphan child columns when the row can remain valid without the parent.
5. For `restrict` relationships, restore missing parent reference rows or block deletion until dependents are resolved.
6. Normalize mismatched column types with expand-contract migrations instead of in-place type changes.
7. Add a new follow-up FK migration after cleanup passes; do not rely on rerunning an already-applied migration that skipped dirty relationships.

## Verification

Added coverage to `tests/Feature/MigrationSafetyAuditTest.php` for the new foreign-key migration. The test verifies `up()`, `down()`, and `up()` again, including target table, target columns, delete behavior, and helper-index reversibility.
