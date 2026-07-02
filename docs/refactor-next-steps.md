# Refactor Next Steps

Generated: 2026-07-02

This file tracks backend refactors that were identified but not safely bundled into the current prompt slice.

## Page API Endpoints

- Files: `app/Http/Controllers/ApiController.php`, `routes/api.php`
- Risk: Page timeline/photo APIs contain duplicated query loops, unbounded collections, and per-post media/user lookups.
- Reason not fixed now: Mobile/API response shape needs contract tests before extraction.
- Next step: Add API contract tests for `pages_timeline` and `page_photos`, then extract query/resource classes without changing keys.

## Page Write Upload Workflows

- Files: `app/Http/Controllers/PageController.php`, `App\Support\Files\FileUploader`
- Risk: Create/update/cover-photo methods still combine validation, authorization, upload, persistence, old-file cleanup, flash messages, and JSON responses.
- Reason not fixed now: File deletion behavior needs Storage/File fakes and rollback tests before moving to an action.
- Next step: Add regression tests for logo/cover replacement and failed writes, then extract focused page write actions.

## Legacy Page Route Parameters

- Files: `routes/custom_routes.php`, `app/Http/Controllers/PageController.php`
- Risk: Page profile routes still use scalar `{id}` instead of route model binding.
- Reason not fixed now: Legacy 404 behavior and route helper usage must be preserved carefully.
- Next step: Add route-model-binding compatibility tests, then introduce scoped binding only if public behavior is unchanged.

## Blade Query Cleanup Outside Page Profile

- Files: `resources/views/frontend/events/*.blade.php`, `resources/views/frontend/album_details/*.blade.php`, `resources/views/frontend/search/*.blade.php`
- Risk: Some templates still query models or tables directly.
- Reason not fixed now: They are outside the page profile backend slice and need their own view-data tests.
- Next step: Move one view family at a time to controller/view-data objects with query-count assertions.
