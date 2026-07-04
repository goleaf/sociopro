# API Contract Smoke Test Plan

Generated: 2026-07-04

This plan records the behavior locked by `tests/Feature/Api/Contracts` before splitting or refactoring `App\Http\Controllers\ApiController`. These tests are intentionally broad smoke contracts. They protect route registration, legacy authentication behavior, response shapes, and simple database side effects without changing production API logic.

## Covered Contract Tests

- `ApiRouteContractTest` verifies the important legacy API route names, HTTP methods, and current URI templates. The route contract uses current repository facts where they differ from older prompt text, including `/api/delete_album/{id}` and `/api/chat_msg/{message_thread}`.
- `ApiAuthContractTest` verifies public auth routes, missing/invalid token behavior, valid API token access across representative modules, login success/failure shapes, signup/forgot-password validation shapes, and logout token revocation.
- `ApiFeedProfileSocialContractTest` verifies timeline/load-timeline responses, post create/edit/delete behavior, comment create/list/delete behavior, profile/other-profile responses, and simple friend/follow side effects.
- `ApiDomainModuleContractTest` verifies pages, groups, events, marketplace, blogs, jobs, fundraisers, and paid-content route smoke behavior. It includes list/show contracts, write validation contracts where stable, and simple like/join/going/save/view side effects.
- `ApiNotificationChatContractTest` verifies notification route authentication, notification list/read behavior, friend notification accept/decline behavior, chat route authentication, and legacy chat response keys such as `msg_thrade`, `message_thrade`, `reciver_id`, and `messagecenter`.

## Coverage Boundaries

- These tests do not assert exhaustive JSON bodies. They assert stable top-level keys, selected nested keys, statuses, and safe side effects.
- These tests do not split `ApiController`, rename routes, rename request fields, rename response fields, or normalize legacy spelling.
- These tests do not add demo seed data and do not depend on production IDs.
- These tests use factories, `RefreshDatabase`, fake local fixtures, and Sanctum API tokens only.

## Deferred Deeper Tests

- File upload contracts: stories, post media files, profile photo, cover photo, page cover photo, group cover photo, albums, videos, marketplace media, blogs with media, events with media, and fundraisers.
- Authorization and IDOR contracts: ownership checks for profile updates, page updates/deletes, group updates, event updates/deletes, marketplace updates/deletes, blog updates/deletes, job updates/deletes, fundraiser updates, notification cross-user access, and chat participant access.
- Payment and monetization contracts: paid content packages, payment callbacks, wallet/balance flows, webhook signature verification, replay handling, and idempotency.
- Notification side effects: group invitation accept/decline, event invitation accept/decline, fundraiser invitation accept/decline, and unread count edge cases.
- Jobs and fundraisers: deeper fixture-backed assertions for optional/addon-style schema and controller branches.
- Query performance contracts: N+1 guards and query-count baselines for timelines, notifications, chat, marketplace filtering, pages, groups, and profile surfaces.

## Risky Or Conditional Endpoints

- `api.pages.likes.destroy` is registered but targets `ApiController::page_dislike`, which is not callable in the current controller.
- `api.groups.members.destroy` is registered but targets `ApiController::groups_join_remove`, which is not callable in the current controller.
- `api.jobs.wishlist.index` is registered but targets `ApiController::job_wishlist`, which is not callable in the current controller.
- File upload endpoints are intentionally left for a storage-faked pass because many legacy controllers invoke upload helpers directly.
- Payment/webhook and paid-content flows are intentionally left for a provider-faked pass.

## Suggested Extraction Order

1. Auth and token lifecycle: `login`, `signup`, `forgot_password`, `logout`, and password update.
2. Feed, posts, media, comments, and reactions.
3. Profile, friends, followers, and friend requests.
4. Pages and groups.
5. Marketplace and videos.
6. Events and blogs.
7. Notifications and chat.
8. Jobs, fundraisers, and paid content.

Run `php artisan test tests/Feature/Api/Contracts` before and after each extraction. Then run the focused module tests and the normal quality gate for the touched files.
