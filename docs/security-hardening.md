# Security Hardening

Generated: 2026-07-02

## Page Ownership Hardening

| Item | Details |
| --- | --- |
| Risk before | Any authenticated user could submit page edit, cover-photo, or profile-info updates for another user's page by changing the `id` in the URL. Non-owners could also load owner-only page modals. |
| Fix applied | Added `App\Policies\PagePolicy`, registered it in `AuthServiceProvider`, and enforced `Gate::authorize('update', $page)` in page update, cover-photo update, info update, and page owner modal loading. |
| Tests added | `tests/Feature/PageSecurityPerformanceTest.php` covers non-owner modal denial, non-owner update denial, and ownership preservation. |
| Remaining risk | Other page actions such as like/dislike and the API page endpoints still need a dedicated authorization and rate-limit review. |
| Deployment notes | No database migration is required. Deploy code and clear/rebuild config and route caches. |

## Page Request Validation

| Item | Details |
| --- | --- |
| Risk before | Page create/update used inline validation and accepted category IDs without existence checks. File validation was MIME-only and did not enforce file/image/extension/size constraints. |
| Fix applied | Added Page Form Requests for create, edit, cover-photo, and info updates. Page create/update now validate category existence and constrain image uploads to image files, allowed extensions, and 5 MB. |
| Tests added | `test_page_store_validates_category_and_keeps_legacy_error_shape` preserves the legacy `validationError` response shape while proving stricter validation. |
| Remaining risk | Legacy API page create/update endpoints still have separate validation paths and should be handled in a follow-up API contract slice. |
| Deployment notes | Existing clients still receive HTTP 200 JSON validation responses for web page create/update validation failures. |

## Page XSS Reduction

| Item | Details |
| --- | --- |
| Risk before | The page edit modal rendered `$page->description` through `{!! script_checker(..., false) !!}`, which returned raw stored HTML. |
| Fix applied | The edit textarea now uses escaped Blade output. Owner modals receive preloaded data from the controller instead of querying in the view. |
| Tests added | `test_page_edit_modal_escapes_description` uses a stored script payload and asserts the raw payload is not rendered. |
| Remaining risk | Other rich-text surfaces still intentionally render raw HTML and remain listed in `docs/security-audit.md`. |
| Deployment notes | Users editing legacy HTML page descriptions will see the markup as text inside the edit textarea. Public rendering outside this modal was not changed in this pass. |
