# Upload and Download Security Audit

Date: 2026-07-02

## Scope

Audited upload, download, file deletion, and generated-file paths across controllers, requests, helpers, support classes, routes, and tests. The review focused on validation, filename/path safety, executable upload prevention, private storage for sensitive files, download authorization, safe cleanup, and `Storage::fake()` coverage.

## Safe Fixes Applied

| Area | Risk | Fix | Tests |
| --- | --- | --- | --- |
| Shared uploads | Public local uploads had drifted away from Laravel's storage disk and could not be verified with `Storage::fake()`. | Restored `FileUploader` writes to `Storage::disk('public')` and kept public visibility assertions. | `FileUploaderTest` |
| Executable uploads | A controller could pass an explicit target such as `payload.php` to the shared uploader. | Added a denylist for server-executable extensions in `FileUploader` for both generated and explicit targets. | `FileUploaderTest` |
| Path traversal | Shared uploader already rejected `..` path segments; legacy cleanup did not. | Hardened `remove_file()` to reject traversal segments and only delete files under `public/storage`. | `SecurityHardeningTest` |
| Job CV uploads | API job applications wrote PDFs directly into public storage. | New API job CV uploads now use Laravel's local disk at `storage/app/job/cv`; legacy public files still stream for backward compatibility. | `JobApplicationExportTest` |
| Job CV downloads | Download streaming trusted stored attachment names when constructing paths. | Centralized safe PDF filename validation and private-first path resolution in `StreamJobApplicationAttachmentAction`. | `JobApplicationExportTest` |
| Media downloads | Authenticated users could download another user's private media by ID, and stored names could contain traversal. | Added owner/public visibility checks and safe path resolution for image/video downloads. | `MediaDownloadSecurityTest` |
| Media deletion | Legacy media delete removed database rows without safely cleaning files. | Media delete now authorizes the actor and deletes the resolved file plus optimized derivative only when the stored path is safe. | `MediaDownloadSecurityTest` |

## Current Upload Surfaces

| Surface | Current posture | Notes |
| --- | --- | --- |
| Post media uploads | Partially hardened | `PostMediaFile` validates allowed extensions and size. Images/video still go to public storage for legacy feed behavior. |
| Marketplace image uploads | Hardened for API | API requests validate `file`, `image`, `mimes`, `extensions`, size, and dimensions. Existing web marketplace request classes still need upload rules if web upload paths remain active. |
| Blog images | Hardened | `BlogRequest` validates image type, extension, size, and dimensions; tests use `Storage::fake('public')`. |
| Job CV uploads | Improved | New API CV uploads are private on the local disk. Existing legacy public CV files remain readable through the authorized stream action for compatibility. |
| Chat attachments | Deferred | Chat image uploads use `FileUploader`, but video paths still use direct `move()` in web/API controllers and need a focused refactor. |
| Sponsor/user ad/page/group/profile uploads | Deferred | These paths use `FileUploader` in several controllers but need Form Request validation parity and route-specific `Storage::fake()` tests. |
| Addon package uploads | Existing guardrails | Addon ZIP import validates zip packages and rejects escaping entries. Keep this surface reviewed before any package installer expansion. |
| Generated-image provider surface | Not reviewed in this slice | The worktree contains unrelated generated-image changes; keep them out of this upload/download hardening commit unless intentionally reviewed later. |

## Current Download Surfaces

| Surface | Current posture | Notes |
| --- | --- | --- |
| Media image/video downloads | Hardened | Downloads require owner or public visibility and reject unsafe stored names. |
| Job CV downloads | Hardened | Downloads stream from private storage first, fall back to legacy public files, and reject traversal/non-PDF names. |
| Public asset display helpers | Legacy public behavior | `get_image()`, `get_post_image()`, and related helpers still return public URLs; they should not be used for private files. |

## Deferred High-Risk Work

| Priority | Risk | Safe first step |
| --- | --- | --- |
| P0 | Direct `move()` calls remain in chat, post update video, fundraiser/campaign, and some API upload paths. | Move one feature at a time to `FileUploader` or a purpose-specific action with `Storage::fake()` tests. |
| P0 | Some upload validation remains inline in controllers instead of Form Requests. | Extract Form Requests for chat attachments and profile/page/group uploads. |
| P1 | Existing post/chat media are public by design, including files with privacy metadata. | Introduce a private media storage migration plan before changing URLs, or preserve public media and restrict only explicit download routes. |
| P1 | SVG is still accepted for post media by legacy validation. | Decide whether SVG is required; if not, remove it with regression tests and migration notes. |
| P1 | Old public job CV files remain in `public/storage/job/cv`. | Backfill them into `storage/app/job/cv` with an idempotent command, then remove public fallback. |
| P2 | Cleanup of old replaced files is inconsistent across profile/page/group/sponsor upload flows. | Add tests around replacement flows and delete old files only after the database write succeeds. |

## Verification

Focused verification for this audit:

```bash
php artisan test tests/Unit/FileUploaderTest.php tests/Feature/SecurityHardeningTest.php tests/Feature/JobApplicationExportTest.php tests/Feature/MediaDownloadSecurityTest.php tests/Feature/ApiMarketplaceValidationTest.php tests/Feature/MainControllerValidationTest.php
```

Full suite verification now passes through the default Laravel test runner:

```bash
php artisan test
```
