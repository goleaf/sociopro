# Page Profile Backend

Generated: 2026-07-02

## Purpose

The page profile backend powers the authenticated web page list, page timeline, page photos tab, and page videos tab. This slice preserves the legacy Blade response contract while moving the page profile read workflow out of `PageController`.

## Routes And Endpoints

Web routes live in `routes/custom_routes.php` and keep their existing URLs and names:

| Method | URL | Route name | Controller method |
| --- | --- | --- | --- |
| `GET` | `/pages` | `pages` | `PageController::pages` |
| `GET` | `page/view/{id}` | `single.page` | `PageController::single_page` |
| `GET` | `page/photo/view/{id}` | `single.page.photos` | `PageController::page_photos` |
| `GET` | `/page/videos/{id}` | `page.videos` | `PageController::videos` |

API page routes remain in `routes/api.php` and were not refactored in this slice.

## Permissions

- Routes are protected by `auth`, `user`, `verified`, `activity`, and `prevent-back-history` middleware.
- Page write actions authorize through `PagePolicy`.
- Page profile read routes currently preserve legacy behavior and do not apply owner-only checks because public page viewing is part of the existing UI.

## Validation

- Page create/update use `StorePageRequest` and `UpdatePageRequest`.
- Cover photo updates use `UpdatePageCoverPhotoRequest`.
- Page info updates use `UpdatePageInfoRequest`.
- Profile read routes do not accept filter payloads; `{id}` remains the legacy page identifier route segment.

## Models And Tables

- `Page` / `pages`
- `PageLike` / `page_likes`
- `Posts` / `posts`
- `MediaFile` / `media_files`
- `Albums` / `albums`
- `Comments` / `comments`
- `Friendships` / `friendships`

## Services, Actions, And Queries

- `App\Actions\Pages\BuildPageProfileViewDataAction` assembles timeline, photos, and videos tab view data.
- `App\Queries\Pages\PageCardsQuery` owns reusable page-card select, like-count, current-user-like, profile eager-load, and suggested-page query shapes.
- `PageController` now delegates page-profile tab workflows to the action and returns the existing `frontend.index` Blade response.

## Jobs, Events, And Notifications

No jobs, events, or notifications are dispatched by the page profile read workflow. Page writes still preserve existing synchronous upload and persistence behavior.

## Tests

- `tests/Feature/PageSecurityPerformanceTest.php`
  - Form Request coverage for write methods.
  - Authorization coverage for owner-only writes and modals.
  - XSS/presentation coverage for page descriptions.
  - Query-budget coverage for page-card like lookups.
  - Regression coverage for `BuildPageProfileViewDataAction` timeline view data.
  - Architecture coverage ensuring page-profile query workflow stays out of `PageController`.

## Known Risks

- API page timeline/photo endpoints still contain duplicated query and response assembly logic in `ApiController`.
- Page profile read routes still use legacy `{id}` parameters instead of route model binding to avoid public URL/behavior drift.
- Page writes still contain upload/delete workflow logic in `PageController`; extracting that requires file-storage regression tests.
- Several legacy Blade views outside the page profile tabs still query models directly.

## Future Improvements

- Extract page create/update/cover-photo workflows into actions with storage fakes and rollback tests.
- Add API Resources for page API responses while preserving current mobile response shape.
- Add query-count tests for page timeline tabs once media/comment factories exist.
- Introduce route model binding for page profile routes only after not-found behavior and URL compatibility are documented.
