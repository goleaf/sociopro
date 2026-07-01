# Validation Audit

Last updated: 2026-07-01

## Scope

Audited Form Requests and inline validation in `app/Http/Controllers`, `app/Http/Requests`, route-bound web/API flows, and existing validation tests. This pass applies a safe code change to the duplicated blog write validation and records the remaining project-wide validation risks for follow-up slices.

## Current Findings

| Area | Status | Risk | Finding | Safe first step |
| --- | --- | --- | --- | --- |
| Form Requests | Improving | Medium | Only auth, install, and blog write flows currently use Form Requests. Most controllers still validate inline. | Move one controller action pair at a time into Form Requests with regression tests. |
| Duplicated rules | Improved for blogs | Medium | `StoreBlogRequest` and `UpdateBlogRequest` duplicated the same blog rules. | Added shared `BlogRequest` for common blog write rules and normalization. |
| Required / nullable / sometimes | Mixed | Medium | Legacy rules often use only `required` or `nullable` without type constraints. Optional nested inputs are not consistently marked with `sometimes`. | Prefer `required` for mandatory fields, `nullable` for nullable scalar fields, and `sometimes` for optional nested structures. |
| Nested arrays | Improved for blogs | High | Tag payloads were accepted as raw JSON strings and array submissions could crash the controller. | Normalize legacy `tag` input into `tags`, then validate `tags` and `tags.*.value`. |
| Files | Improved for blogs | High | Several upload rules check only MIME strings or allow files to reach upload helpers without full validation. | Use `nullable`, `file`, `image` where applicable, explicit `mimes`, and `max` before touching uploaded files. |
| IDs / foreign keys | Improved for blogs | High | Many request IDs and category IDs are only checked as required, not as integers that exist. | Add `integer` plus `exists:table,id` rules at request boundaries. |
| Dates | Open | Medium | Event, sponsor, job, and fundraiser flows accept date-like values with weak or missing date validation. | Add `date`, `date_format`, and ordering rules such as `after_or_equal` in feature-specific requests. |
| Booleans | Open | Medium | Status/toggle fields are often read directly from the request. | Use `boolean` or enum-backed validation before writing flags. |
| Enums | Partial | Medium | Some flows use `Rule::enum()` for privacy/status values, while many legacy string status/type values remain unvalidated. | Use existing enums such as `Visibility`, `ContentStatus`, `UserRole`, `MediaFileType`, and `PaymentGatewayIdentifier`. |
| Slugs | Open | Low | Slug-like route/input fields are not consistently validated. | Use `alpha_dash`, `max`, and uniqueness rules where slugs are persisted. |
| Pagination / sorting | Open | Medium | Offset, limit, page, and sort values are often read directly. | Validate `page`, `per_page`, `offset`, `limit` as bounded integers and sorting fields with `Rule::in()`. |
| Unique constraints | Open | Medium | Registration has unique email validation, but admin/user/category create and update flows are inconsistent. | Use `Rule::unique()->ignore($model)` in update Form Requests where database uniqueness matters. |

## Blog Validation Changes

- Added `App\Http\Requests\Blog\BlogRequest` as the shared rule source for blog store/update requests.
- Standardized blog title, category, description, legacy `tag`, normalized `tags`, nested `tags.*.value`, and image upload rules.
- Normalized legacy JSON `tag` payloads and submitted nested arrays into one validated `tags` structure.
- Replaced duplicated tag parsing in `BlogController` with validated request helpers.

## Regression Coverage

- Blog store/update methods must use dedicated Form Requests.
- Blog write requests must share one common rules source.
- Missing required fields are rejected.
- Unknown blog category IDs are rejected.
- Invalid image uploads are rejected before upload helpers run.
- Nested tags without `value` are rejected.
- Legacy JSON tag payloads and nested tag arrays persist the same normalized tag values.

## Follow-Up Order

| Priority | Target | Reason |
| --- | --- | --- |
| 1 | `PageController` create/update | Same image/category/title pattern as blogs, currently inline with raw request reads. |
| 2 | `EventController` create/update | Needs date, time, location, enum privacy, and image validation in Form Requests. |
| 3 | `SponsorController` create/update | Needs date ordering, file size/type, and ID validation around admin uploads. |
| 4 | `ApiController` blog/job/event/fundraiser writes | Large API surface with duplicated web rules, file uploads, dates, IDs, and inconsistent JSON errors. |
| 5 | Pagination/search endpoints | Add bounded `offset`, `limit`, `page`, `per_page`, `sort`, and `direction` validation. |
