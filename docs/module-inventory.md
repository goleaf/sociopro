# Module Inventory

Generated: 2026-07-01

This document inventories the business modules and feature surfaces found in the current Laravel checkout. It is documentation-only and should be read with `docs/architecture-map.md`, `docs/risk-register.md`, and `docs/refactor-checklist.md`.

## Priority Scale

- `P0`: Security, data integrity, payment, install/update, or authorization risk. Refactor only behind tests.
- `P1`: High-change, high-traffic, or controller-heavy module. Add characterization tests first.
- `P2`: Important domain module with moderate blast radius.
- `P3`: Lower-risk cleanup, documentation, or isolated presentation work.

## Global Inventory Notes

- Framework/runtime: Laravel `13.18.0`, PHP `^8.3`, PHPUnit `12.5.30`, Pint `1.29.3`.
- Route files: `routes/web.php`, `routes/custom_routes.php`, `routes/api.php`, `routes/auth.php`, `routes/user.php`, `routes/payment.php`, `routes/channels.php`, `routes/console.php`.
- Route surface: 483 non-vendor routes. The largest route surfaces are `api/*` with 134 routes and `admin/*` with 104 routes.
- Requests: only `App\Http\Requests\Auth\LoginRequest` and installer requests under `App\Http\Requests\Install` are present.
- Migrations: only `database/migrations/2026_07_01_150000_add_safe_legacy_lookup_indexes.php` is present. Schema creation still comes from `database/schema/install.sql` through `DatabaseSeeder`.
- Jobs: no `app/Jobs` directory detected.
- Events/listeners: no custom `app/Events` or `app/Listeners` directories detected. `EventServiceProvider` only maps Laravel's `Registered` event to email verification notification.
- Policies: no `app/Policies` directory detected and `AuthServiceProvider` has no policy mappings.
- Services: no `app/Services` directory detected.
- Frontend build: Laravel Mix / Webpack. `resources/js/app.js`, `resources/js/bootstrap.js`, and `resources/css/app.css` are the build entrypoints. No `vite.config.*` exists.
- SCSS: no `resources/scss` or `resources/sass` directory exists. Legacy SCSS lives under `public/assets/backend/css`, `public/assets/backend/sass/_base.zip`, and fundraiser public asset folders.
- Shared frontend assets: Bootstrap, jQuery, SweetAlert2, CKEditor/Summernote, DataTables, Owl Carousel, Venobox, Plyr, Leaflet, Tagify, rich text/date/time picker assets, uploader scripts, toaster assets, and addon-specific paid-content/fundraiser bundles.

## Module Summary

| Module | Active surface | Main controllers | Main tables | Tests | Priority |
| --- | --- | --- | --- | --- | --- |
| Mobile/API surface | `routes/api.php`, 134 endpoints | `ApiController` | Many domain tables | Partial API response tests | `P0` |
| Authentication/account | `routes/auth.php`, `web`, `api`, account enable/disable | Auth controllers, `ApiController`, `CustomUserController`, `Profile` | `users`, `account_active_requests`, `personal_access_tokens`, `password_resets` | Auth tests, account-disable tests | `P0` |
| Profile/social graph | `profile/*`, `user/*`, follow/friend/block routes | `Profile`, `CustomUserController`, `FollowController`, `MainController`, `ApiController` | `users`, `friendships`, `followers`, `block_users`, `media_files`, `albums` | `FriendshipsQueryTest` | `P1` |
| Feed/posts/comments | root timeline, post/comment/reaction/report routes, API feed | `MainController`, `ApiController`, `ModalController`, `MemoriesController` | `posts`, `comments`, `media_files`, `post_shares`, `shares`, `reports`, `activities` | API response tests | `P0` |
| Stories | story routes and API story endpoints | `StoryController`, `ApiController` | `stories`, `media_files`, `users` | `StoryControllerRefactorTest` | `P1` |
| Media/albums/uploads/live | album, media delete/download, live/Jitsi/Zoom | `MainController`, `Profile`, `GroupController`, `PageController`, `VideoController`, `SettingController`, `ApiController` | `media_files`, `albums`, `album_images`, `live_streamings`, `settings` | Limited indirect tests | `P0` |
| Groups | `groups`, `group/*`, admin group routes, API group endpoints | `GroupController`, `AdminCrudController`, `ApiController`, `NotificationController` | `groups`, `group_members`, `posts`, `media_files`, `albums`, `invites` | None module-specific | `P1` |
| Pages | `pages`, `page/*`, admin page routes, API page endpoints | `PageController`, `AdminCrudController`, `ApiController`, `SettingController` | `pages`, `page_likes`, `pagecategories`, `posts`, `media_files`, `albums` | None module-specific | `P1` |
| Events | `events`, `event/*`, invites, API event endpoints | `EventController`, `ApiController`, `NotificationController` | `events`, `invites`, `shares`, `posts`, `groups` | None module-specific | `P1` |
| Marketplace/products | `products`, `product/*`, admin category/brand routes, API marketplace endpoints | `MarketplaceController`, `AdminCrudController`, `ApiController` | `marketplaces`, `categories`, `brands`, `currencies`, `saved_products`, `saveforlaters` | None module-specific | `P1` |
| Blogs | `blogs`, `blog/*`, admin blog/category routes, API blog endpoints | `BlogController`, `AdminCrudController`, `ApiController` | `blogs`, `blogcategories`, `saveforlaters` | None module-specific | `P2` |
| Video shorts | `videos`, `shorts`, save/unsave video, API video endpoints | `VideoController`, `ApiController`, `Profile`, `PageController` | `videos`, `saveforlaters`, `media_files` | None module-specific | `P2` |
| Chat/messaging | `chat/*`, API chat endpoints, marketplace chat entry | `ChatController`, `ApiController` | `chats`, `message_thrades`, `users`, `marketplaces` | None module-specific | `P1` |
| Notifications/invites | `all/notification`, accept/decline routes, API notification endpoints | `NotificationController`, `ApiController`, `ViewServiceProvider` | `notifications`, `invites`, domain target tables | None module-specific | `P0` |
| Search/discovery | `search/*`, type-specific search, invite/tag search | `SearchController`, `GroupController`, `EventController`, `MainController`, `BlogController`, `ChatController` | `users`, `posts`, `groups`, `pages`, `events`, `marketplaces`, `videos`, `blogs` | None module-specific | `P2` |
| Admin/settings/language/moderation | `admin/*`, settings, language, reports, dashboard | `AdminCrudController`, `SettingController`, `LanguageController`, `SponsorController`, `Updater` | `settings`, `languages`, `addons`, many domain tables | Some indirect tests | `P0` |
| Payments/gateways | `routes/payment.php`, admin gateway routes, payment history | `PaymentController`, `PaymentHistory`, `AdminCrudController`, gateway models | `payment_gateways`, `payment_histories`, `settings` | Payment gateway/status tests | `P0` |
| Badges/sponsors/user ads | badge routes, sponsor admin routes, user ad routes | `BadgeController`, `SponsorController`, `UserController`, `AdminCrudController` | `batchs`, `sponsors`, `payment_histories`, `users` | Limited indirect payment tests | `P1` |
| Installer/updater/addons | `install/*`, updater/addon admin routes | `InstallController`, `Updater`, install actions | `settings`, `addons`, legacy install SQL | Installer/import/configure tests | `P0` |
| Addon stubs: jobs/fundraisers/paid content | API endpoints and conditional/dormant web routes | `ApiController`; missing `JobController`/`PaidContent` controllers | Optional/missing addon tables and models | None module-specific | `P0` |
| Static/legal/contact/localization | about/privacy/terms/contact/language routes and views | `SettingController`, `LanguageController` | `settings`, `languages` | None module-specific | `P2` |

## Detailed Module Inventory

### Mobile/API Surface

- Routes: `routes/api.php`; 134 endpoints under `api/*`; includes auth, feed, stories, profile, friends, groups, pages, marketplace, videos, events, blogs, paid content, jobs, fundraisers, notifications, chat, and settings.
- Controllers: `App\Http\Controllers\ApiController`.
- Requests: none; inline `$request->validate()` and manual validation are used.
- Models: `User`, `Users`, `Posts`, `Comments`, `Stories`, `Group`, `GroupMember`, `Page`, `PageLike`, `Marketplace`, `Video`, `Event`, `Blog`, `Notification`, `Chat`, `MessageThread`, `Fundraiser`, `PaidContentCreator`, and several addon model references.
- Database tables: broad cross-module access; see each module below.
- Migrations: legacy lookup index migration covers several API-heavy tables; no API-specific migrations.
- Views: none directly; JSON response surface.
- JS: none directly.
- SCSS: none.
- Jobs: none.
- Events/listeners: uses Laravel auth `Registered` event in signup flow; no module-specific custom event/listener.
- Policies: none.
- Tests: `tests/Feature/ApiControllerResponseTest.php` covers response-format and internal route redirect conventions; endpoint coverage is otherwise thin.
- External dependencies: Laravel Sanctum tokens, Intervention Image, Guzzle/HTTP clients indirectly through gateway/payment code, addon model references.
- Refactor priority: `P0`. Split by domain, add Form Requests/API Resources, enforce auth groups, and characterize high-risk endpoints first.

### Authentication and Account Lifecycle

- Routes: `routes/auth.php`; `routes/web.php` for `account-disable`, `account-enble-req/{user}`, `auth-checker`; `routes/api.php` for `api/login`, `api/signup`, `api/forgot_password`, `api/update_password`, `api/user`.
- Controllers: Breeze auth controllers under `app/Http/Controllers/Auth`, including invokable one-command email verification notification routing; `ApiController`, `CustomUserController`, `Profile`, `AdminCrudController`, and invokable web utility controllers.
- Requests: `App\Http\Requests\Auth\LoginRequest`; API auth uses inline validation.
- Models: `User`, `Users`, `AccountActiveRequest`.
- Database tables: `users`, `account_active_requests`, `personal_access_tokens`, `password_resets`.
- Migrations: lookup indexes for `account_active_requests`; no user-table migration in source.
- Views: `resources/views/auth/*`, `resources/views/frontend/disable_view.blade.php`, `resources/views/backend/admin/users/*`.
- JS: shared auth/frontend/backend scripts only; no module-specific source entrypoint.
- SCSS: auth uses public frontend/authentication theme assets; no tracked `resources/scss`.
- Jobs: none.
- Events/listeners: Laravel `Registered` event to email verification notification.
- Policies: none.
- Tests: `tests/Feature/Auth/*`, `tests/Feature/AccountDisableRouteTest.php`.
- External dependencies: Laravel Sanctum, Laravel auth/email verification, Flasher for account-enable feedback.
- Refactor priority: `P0`. Authorization and account state changes must be locked down before broader refactors.

### Profile and Social Graph

- Routes: `routes/web.php` profile group (`profile`, `profile/photos`, `profile/album/{action_type?}`, `profile/friends`, `profile/profile-lock`, etc.); `routes/custom_routes.php` user/friend/follow/block/profile media routes; `routes/api.php` profile/friend/follow endpoints.
- Controllers: `Profile`, `CustomUserController`, `FollowController`, `MainController`, `ApiController`.
- Requests: none module-specific.
- Models: `User`, `Users`, `Friendships`, `Follower`, `BlockUser`, `Posts`, `MediaFile`, `Albums`, `AlbumImage`, `Saveforlater`.
- Database tables: `users`, `friendships`, `followers`, `block_users`, `posts`, `media_files`, `albums`, `album_images`, `saveforlaters`.
- Migrations: lookup indexes for `friendships`, `followers`, `block_users`, `albums`, `album_images`, `media_files`, `posts`, `saveforlaters`.
- Views: `resources/views/frontend/profile/*`, `resources/views/frontend/user/single_user/*`, related header/sidebar partials.
- JS: shared `resources/js/app.js`, `public/assets/frontend/js/custom.js`, uploader assets, profile inline Blade scripts.
- SCSS: public frontend CSS; no module-owned SCSS under `resources`.
- Jobs: none.
- Events/listeners: none.
- Policies: none.
- Tests: `tests/Feature/FriendshipsQueryTest.php`; profile routes otherwise lack direct coverage.
- External dependencies: frontend uploader, Flasher, shared jQuery/Bootstrap assets.
- Refactor priority: `P1`. Extract social graph actions/scopes and add authorization tests before changing controller logic.

### Feed, Posts, Comments, Reactions, Reports, and Memories

- Routes: `routes/web.php` timeline root, create/edit/delete post, comments, reactions, shares, reports, memories; `routes/api.php` timeline/post/comment/reaction/report endpoints.
- Controllers: `MainController`, `ApiController`, `ModalController`, `MemoriesController`, `SettingController` for reported posts.
- Requests: none module-specific.
- Models: `Posts`, `Comments`, `MediaFile`, `PostShare`, `Share`, `Report`, `FeelingAndActivity`, `User`, `Group`, `Page`, `Event`.
- Database tables: `posts`, `comments`, `media_files`, `post_shares`, `shares`, `reports`, `activities`, `feeling_and_activities`, `users`.
- Migrations: lookup indexes for `posts`, `comments`, `media_files`, `post_shares`, `reports`, `shares`.
- Views: `resources/views/frontend/main_content/*`, `resources/views/frontend/index.blade.php`, `resources/views/frontend/modal.blade.php`.
- JS: `resources/js/app.js`, `resources/js/bootstrap.js`, `public/assets/frontend/js/custom.js`, `public/assets/frontend/jquery-form`, `public/assets/frontend/emojionarea`, inline feed scripts.
- SCSS: public frontend CSS; no module-owned `resources` SCSS.
- Jobs: none.
- Events/listeners: none.
- Policies: none.
- Tests: `ApiControllerResponseTest.php` covers some API response conventions; no full feed CRUD coverage.
- External dependencies: Intervention Image, uploader assets, Laravel Share, Flasher, jQuery form/emojionarea assets.
- Refactor priority: `P0`. This is a high-traffic write surface with reports/deletes and no policy layer.

### Stories

- Routes: `routes/web.php` `create_story`, `stories/{offset?}/{limit?}`, `story_details/{story_id}/{offset?}/{limit?}`, `single_story_details/{story_id}`; `routes/api.php` `api/stories`, `api/create_story`.
- Controllers: `StoryController`, `ApiController`.
- Requests: none.
- Models: `Stories`, `MediaFile`, `User`, `Friendships`.
- Database tables: `stories`, `media_files`, `users`, `friendships`.
- Migrations: lookup indexes for `stories`, `media_files`, `friendships`.
- Views: `resources/views/frontend/story/*`; note `resources/views/frontend/story/scripts.php` is a PHP view-side script file.
- JS: story Blade scripts plus shared frontend JS.
- SCSS: public frontend CSS only.
- Jobs: none.
- Events/listeners: none.
- Policies: none.
- Tests: `tests/Feature/StoryControllerRefactorTest.php`.
- External dependencies: uploader/media assets.
- Refactor priority: `P1`. Existing tests make this a good candidate for the next safe extraction.

### Media, Albums, Uploads, and Live Video

- Routes: album routes in `routes/web.php` and `routes/custom_routes.php`; media delete/download routes; live routes `live/{post_id}`, `live-ended/{post_id}`, `streaming/live/{id}`; settings routes for live/Jitsi; API media/profile/album endpoints.
- Controllers: `MainController`, `Profile`, `GroupController`, `PageController`, `VideoController`, `SettingController`, `CustomUserController`, `ApiController`.
- Requests: none module-specific.
- Models: `MediaFile`, `Albums`, `AlbumImage`, `LiveStreaming`, `FileUploader`, `Posts`, `Setting`, `User`.
- Database tables: `media_files`, `albums`, `album_images`, `live_streamings`, `posts`, `settings`.
- Migrations: lookup indexes for `media_files`, `albums`, `album_images`, `posts`, `settings`.
- Views: `resources/views/frontend/live_streaming/*`, `resources/views/frontend/main_content/jitsi_streaming.blade.php`, profile/page/group media views.
- JS: `public/assets/frontend/uploader/*`, `public/assets/frontend/js/jitsi.js`, `public/assets/frontend/plyr/*`, `public/assets/frontend/js/plyr*`, shared frontend JS.
- SCSS: public frontend CSS and addon/fundraiser assets; no module source SCSS in `resources`.
- Jobs: none, even though uploads/video/live-provider calls are slow side effects.
- Events/listeners: none.
- Policies: none.
- Tests: no direct media/live tests.
- External dependencies: AWS S3 Flysystem, Intervention Image, FFMPEG config, Firebase JWT/Zoom trait, Jitsi/JaaS.
- Refactor priority: `P0`. Storage/provider calls need service extraction, tests with fakes, and authorization.

### Groups

- Routes: `routes/custom_routes.php` `groups`, `group/*`, group invites, group photos/albums; admin group routes; `routes/api.php` group endpoints; notification accept/decline group routes.
- Controllers: `GroupController`, `AdminCrudController`, `ApiController`, `NotificationController`.
- Requests: none.
- Models: `Group`, `GroupMember`, `Posts`, `MediaFile`, `Albums`, `AlbumImage`, `Invite`, `Notification`, `User`.
- Database tables: `groups`, `group_members`, `posts`, `media_files`, `albums`, `album_images`, `invites`, `notifications`, `users`.
- Migrations: lookup indexes for `groups`, `group_members`, `media_files`, `albums`, `album_images`, `invites`, `notifications`.
- Views: `resources/views/frontend/groups/*`, `resources/views/backend/admin/group/*`.
- JS: group Blade scripts, shared frontend JS, uploader assets.
- SCSS: public frontend CSS only.
- Jobs: none.
- Events/listeners: none.
- Policies: none.
- Tests: none module-specific.
- External dependencies: uploader assets, Flasher, shared frontend libraries.
- Refactor priority: `P1`. Membership and invite writes need policies and Form Requests before controller extraction.

### Pages

- Routes: `routes/custom_routes.php` `pages`, `page/*`, page like/dislike, page videos/photos; admin page and page-category routes; `routes/api.php` page endpoints.
- Controllers: `PageController`, `AdminCrudController`, `ApiController`, `SettingController`.
- Requests: none.
- Models: `Page`, `PageLike`, `Pagecategory`, `Posts`, `MediaFile`, `Albums`, `User`.
- Database tables: `pages`, `page_likes`, `pagecategories`, `posts`, `media_files`, `albums`, `users`.
- Migrations: lookup indexes for `pages`, `page_likes`, `media_files`, `albums`, `posts`.
- Views: `resources/views/frontend/pages/*`, `resources/views/backend/admin/page/*`, `resources/views/backend/admin/page_category/*`.
- JS: shared frontend JS, page view partial scripts.
- SCSS: public frontend CSS only.
- Jobs: none.
- Events/listeners: none.
- Policies: none.
- Tests: none module-specific.
- External dependencies: uploader/media assets.
- Refactor priority: `P1`. Page ownership and admin mutations need authorization coverage.

### Events

- Routes: `routes/custom_routes.php` `events`, `event/*`, event invite/search/share routes; `routes/api.php` event endpoints; notification accept/decline event routes.
- Controllers: `App\Http\Controllers\Event\EventController`, `ApiController`, `NotificationController`, `GroupController` for group event views.
- Requests: none.
- Models: `Event`, `Invite`, `Share`, `Posts`, `Group`, `Notification`, `User`.
- Database tables: `events`, `invites`, `shares`, `posts`, `groups`, `notifications`, `users`.
- Migrations: lookup indexes for `events`, `invites`, `shares`, `posts`, `notifications`.
- Views: `resources/views/frontend/events/*`, group event partials.
- JS: `resources/views/frontend/events/script.blade.php`, shared frontend JS.
- SCSS: public frontend CSS only.
- Jobs: none.
- Events/listeners: none.
- Policies: none.
- Tests: none module-specific.
- External dependencies: shared frontend date/time picker assets.
- Refactor priority: `P1`. RSVP/cancel/delete routes include state-changing GET routes and need characterization tests.

### Marketplace and Products

- Routes: `routes/custom_routes.php` `products`, `product/*`, save/unsave product, user products; admin product category/brand routes; `routes/api.php` marketplace/category/brand/currency/filter endpoints.
- Controllers: `MarketplaceController`, `AdminCrudController`, `ApiController`, `SearchController`.
- Requests: none.
- Models: `Marketplace`, `Category`, `Brand`, `Currency`, `SavedProduct`, `Saveforlater`, `MediaFile`, `User`.
- Database tables: `marketplaces`, `categories`, `brands`, `currencies`, `saved_products`, `saveforlaters`, `media_files`, `users`.
- Migrations: lookup indexes for `marketplaces`, `saved_products`, `saveforlaters`, `media_files`.
- Views: `resources/views/frontend/marketplace/*`, `resources/views/backend/admin/product_category/*`, `resources/views/backend/admin/brand/*`.
- JS: shared frontend JS, marketplace load-image partials, chat entrypoints.
- SCSS: public frontend CSS only.
- Jobs: none.
- Events/listeners: none.
- Policies: none.
- Tests: none module-specific.
- External dependencies: uploader/media assets, chat integration.
- Refactor priority: `P1`. Product mutations and saved-product routes need authorization plus route method cleanup.

### Blogs

- Routes: `routes/custom_routes.php` `blogs`, `blog/*`, create/edit/update/delete/search; admin blog/category routes; `routes/api.php` blog/category endpoints.
- Controllers: `BlogController`, `AdminCrudController`, `ApiController`.
- Requests: none.
- Models: `Blog`, `Blogcategory`, `Saveforlater`, `User`.
- Database tables: `blogs`, `blogcategories`, `saveforlaters`, `users`.
- Migrations: lookup indexes for `blogs`, `saveforlaters`.
- Views: `resources/views/frontend/blogs/*`, `resources/views/backend/admin/blog/*`, `resources/views/backend/admin/blog_category/*`.
- JS: shared frontend JS and rich text/editor assets.
- SCSS: public frontend/backend CSS only.
- Jobs: none.
- Events/listeners: none.
- Policies: none.
- Tests: none module-specific.
- External dependencies: CKEditor/Summernote/rich text public assets.
- Refactor priority: `P2`. Lower risk than payments/feed but needs Form Requests and admin policy coverage.

### Video Shorts

- Routes: `routes/custom_routes.php` `videos`, `videos/sorts/store`, `shorts`, video detail/delete/save/unsave/load routes; profile/page/user video routes; `routes/api.php` video endpoints.
- Controllers: `VideoController`, `ApiController`, `Profile`, `PageController`, `CustomUserController`, `SettingController` for live-video settings.
- Requests: none.
- Models: `Video`, `Saveforlater`, `MediaFile`, `User`, `Setting`.
- Database tables: `videos`, `saveforlaters`, `media_files`, `users`, `settings`.
- Migrations: lookup indexes for `videos`, `saveforlaters`, `media_files`, `settings`.
- Views: `resources/views/frontend/video-shorts/*`, profile/page/user video partials, backend live-video settings.
- JS: Plyr assets, shared frontend JS.
- SCSS: public frontend CSS only.
- Jobs: none.
- Events/listeners: none.
- Policies: none.
- Tests: none module-specific.
- External dependencies: Plyr, FFMPEG config, uploader/media assets.
- Refactor priority: `P2`. Media ownership and deletes still need tests before deeper cleanup.

### Chat and Messaging

- Routes: `routes/custom_routes.php` `chat/inbox/*`, `chat/save`, `my_message_react`, search chat; `routes/api.php` `api/chat`, `api/chat_msg`, `api/chat_save`, `api/thread_save`, `api/remove_chat`, `api/chat_read_option`, `api/react_chat`.
- Controllers: `ChatController`, `ApiController`.
- Requests: none.
- Models: `Chat`, `MessageThread`, `User`, `Marketplace`.
- Database tables: `chats`, `message_thrades`, `users`, `marketplaces`.
- Migrations: lookup indexes for `chats`, `message_thrades`.
- Views: `resources/views/frontend/chat/*`.
- JS: shared frontend JS, emojionarea assets, chat-specific Blade interactions.
- SCSS: public frontend CSS, plus some fundraiser CSS included from chat view.
- Jobs: none.
- Events/listeners: none.
- Policies: none.
- Tests: none module-specific.
- External dependencies: emojionarea, jQuery, marketplace product chat entrypoint.
- Refactor priority: `P1`. Private message authorization and read/delete behavior need coverage.

### Notifications and Invites

- Routes: `routes/custom_routes.php` `all/notification`, accept/decline friend/group/event/fundraiser request routes, mark-as-read; `routes/api.php` notification accept/decline/mark/count endpoints.
- Controllers: `NotificationController`, `ApiController`, `ViewServiceProvider`.
- Requests: none.
- Models: `Notification`, `Invite`, `Friendships`, `GroupMember`, `Event`, `Group`, `Fundraiser`, `User`.
- Database tables: `notifications`, `invites`, `friendships`, `group_members`, `events`, `groups`, optional fundraiser tables, `users`.
- Migrations: lookup indexes for `notifications`, `invites`, `friendships`, `group_members`, `events`, `groups`.
- Views: `resources/views/frontend/notification/notification.blade.php`, header notification widgets.
- JS: shared frontend ajax helpers in Blade/common scripts.
- SCSS: public frontend CSS and fundraiser icon assets for addon notifications.
- Jobs: none.
- Events/listeners: none.
- Policies: none.
- Tests: none module-specific.
- External dependencies: Flasher/shared AJAX scripts.
- Refactor priority: `P0`. State-changing notification routes are authorization-sensitive and many use GET.

### Search and Discovery

- Routes: `routes/custom_routes.php` `search`, `search/people`, `search/post`, `search/video`, `search/product`, `search/page`, `search/group`, `search/event`, group/event invite search, friend tagging search, blog/chat search; `routes/api.php` has `filter` and domain list endpoints.
- Controllers: `Report\SearchController`, `GroupController`, `EventController`, `MainController`, `BlogController`, `ChatController`, `ApiController`.
- Requests: none.
- Models: `User`, `Posts`, `Group`, `Page`, `Event`, `Marketplace`, `Video`, `Blog`, `Friendships`.
- Database tables: `users`, `posts`, `groups`, `pages`, `events`, `marketplaces`, `videos`, `blogs`, `friendships`.
- Migrations: lookup indexes across searched tables.
- Views: `resources/views/frontend/search/*`.
- JS: shared frontend JS and infinite-scroll/search Blade snippets.
- SCSS: public frontend CSS only.
- Jobs: none.
- Events/listeners: none.
- Policies: none.
- Tests: none module-specific.
- External dependencies: none beyond shared frontend stack.
- Refactor priority: `P2`. Add query objects/scopes and pagination tests before optimization.

### Admin, Settings, Language, and Moderation

- Routes: `routes/custom_routes.php` `admin/*`; settings routes in `SettingController`; language routes; admin dashboard/users/pages/groups/blogs/categories/products/payment/report/sponsor/badge routes; `routes/web.php` updater/admin addon routes.
- Controllers: `AdminCrudController`, `SettingController`, `LanguageController`, `SponsorController`, `Updater`, `PaymentHistory`.
- Requests: none module-specific.
- Models: broad access to `User`, `Setting`, `Language`, `Addon`, `Page`, `Blog`, `Group`, `Category`, `Brand`, `PaymentGateway`, `Report`, `Sponsor`, `Badge`, and domain models.
- Database tables: `settings`, `languages`, `addons`, `users`, `pages`, `blogs`, `groups`, `categories`, `brands`, `payment_gateways`, `reports`, `sponsors`, `batchs`, and more.
- Migrations: lookup indexes for several admin-managed tables; no admin-specific schema migrations.
- Views: `resources/views/backend/admin/*`, `resources/views/backend/*`, `resources/views/frontend/settings/*`, `resources/views/emails/contact.blade.php`.
- JS: backend assets under `public/assets/backend/js`, DataTables, SweetAlert2, select2, chart scripts, Summernote/CKEditor.
- SCSS: `public/assets/backend/css/style.scss`, backend `_*.scss` partials, `public/assets/backend/sass/_base.zip`.
- Jobs: none.
- Events/listeners: none.
- Policies: none.
- Tests: indirect tests around account disable, payment page data, install safety; no broad admin CRUD coverage.
- External dependencies: Yajra DataTables, Flasher, Mail, AWS S3 config, Jitsi/Zoom settings, payment providers.
- Refactor priority: `P0`. Admin routes mutate users/settings/payments and need policy/Form Request coverage.

### Payments and Gateway Billing

- Routes: `routes/payment.php` `payment`, gateway ajax, success/create/status/order routes; `routes/custom_routes.php` admin payment gateway settings and histories; `routes/user.php` user payment history; badge/ad payment configuration routes.
- Controllers: `PaymentController`, `PaymentHistory`, `AdminCrudController`, `UserController`, `BadgeController`.
- Requests: none.
- Models: `PaymentGateway`, payment gateway model classes under `app/Models/payment_gateway/*`, `Badge`, `Sponsor`, `Setting`, `User`.
- Database tables: `payment_gateways`, `payment_histories`, `settings`, `batchs`, `sponsors`, `users`.
- Migrations: lookup indexes for `payment_gateways`, `payment_histories`, `settings`, `sponsors`.
- Views: `resources/views/payment/*`, `resources/views/backend/admin/payment/*`, `resources/views/backend/admin/payment_history/*`, `resources/views/backend/user/payment_history/*`.
- JS: gateway-specific Blade/inline JS; Paystack inline view; Razorpay view; shared payment assets under `public/assets/payment`.
- SCSS: public payment/backend/frontend CSS only.
- Jobs: none, even though provider callbacks and mail/payment side effects would normally be queued or isolated.
- Events/listeners: none.
- Policies: none.
- Tests: `PaymentControllerGatewayQueryTest.php`, `PaymentPageViewDataTest.php`, `PaypalPaymentStatusTest.php`, `PaystackPaymentStatusTest.php`.
- External dependencies: Paytm wallet, Stripe, Razorpay, PayPal HTTP calls, Paystack HTTP calls, Flutterwave SDK.
- Refactor priority: `P0`. Keep tests green, fake providers, and extract gateway services before behavior changes.

### Badges, Sponsors, and User Ads

- Routes: `routes/web.php` badge routes; `routes/custom_routes.php` admin badge/sponsor routes; `routes/user.php` user ads and ad payment routes; admin payment history/gateway routes overlap.
- Controllers: `BadgeController`, `SponsorController`, `UserController`, `AdminCrudController`, `PaymentHistory`.
- Requests: none.
- Models: `Badge`, `Sponsor`, `PaymentGateway`, `User`, `Payment_history` table without local model file.
- Database tables: `batchs`, `sponsors`, `payment_gateways`, `payment_histories`, `users`.
- Migrations: lookup indexes for `sponsors`, `payment_gateways`, `payment_histories`.
- Views: `resources/views/frontend/badge/*`, `resources/views/backend/admin/badge/*`, `resources/views/backend/admin/sponsor/*`, `resources/views/backend/user/ad_*`, `resources/views/backend/user/ads.blade.php`.
- JS: backend scripts, DataTables, payment gateway scripts.
- SCSS: backend/frontend public CSS only.
- Jobs: none.
- Events/listeners: none.
- Policies: none.
- Tests: indirect payment tests only.
- External dependencies: payment gateway packages, Yajra DataTables for admin lists.
- Refactor priority: `P1`. Money-adjacent mutations need policies, Form Requests, and provider/payment isolation.

### Installer, Updater, and Addons

- Routes: `routes/web.php` `install/*` and updater/addon admin routes; `routes/custom_routes.php` repeats addon route surface; `RouteServiceProvider` conditionally loads `routes/fundraiser.php` when fundraiser addon is active, but that file is not present in the checkout.
- Controllers: `InstallController`, `Updater`, `MainController` for addon settings entry.
- Requests: `FinalizeInstallationRequest`, `PrepareDatabaseConnectionRequest`, `ValidatePurchaseCodeRequest`.
- Actions/services: `CheckInstallRequirements`, `ConfigureDatabase`, `FinalizeInstallation`, `ImportInstallSqlDump`, `PrepareDatabaseConnection`, `UpdateEnvironmentFile`.
- Models: `Setting`, `Addon`, `User`.
- Database tables: `settings`, `addons`, `users`, all legacy tables imported from `database/schema/install.sql`.
- Migrations: legacy lookup index migration; install schema bootstrap is SQL-dump backed.
- Views: `resources/views/install/*`, `resources/views/frontend/addons/*`, `resources/views/backend/admin/addons/*`.
- JS: shared frontend/backend scripts; addon layout JS under `public/assets/frontend/js/addon_layout.js`.
- SCSS: public addon CSS and fundraiser/paid-content bundles.
- Jobs: none.
- Events/listeners: none.
- Policies: none.
- Tests: `ConfigureDatabaseTest.php`, `ImportInstallSqlDumpTest.php`, `InstallWizardTest.php`.
- External dependencies: filesystem/env editing, SQL dump import, addon status helpers.
- Refactor priority: `P0`. Updater/install paths can rewrite runtime state and must stay isolated behind tests.

### Addon Stubs: Jobs, Fundraisers, and Paid Content

- Routes: `routes/api.php` exposes jobs, fundraisers, and paid-content endpoints. `routes/custom_routes.php` has conditional web/admin route blocks for missing `JobController` and missing `PaidContent` controller. `RouteServiceProvider` references conditional `routes/fundraiser.php`, but the route file is missing.
- Controllers: `ApiController`; missing or dormant web controllers: `JobController`, `PaidContent`.
- Requests: none.
- Models: present `Fundraiser`, `FundraiserDonation`, `PaidContentCreator`; missing references include `FundraiserCategory`, `FundraiserPayout`, `PaidContentPackages`, `PaidContentPayout`, `Job`, `JobApply`, `JobCategory`, `JobWishlist`.
- Database tables: optional/missing local tables include `fundraisers`, `fundraiser_donations`, `paid_content_creators` and related payout/package/job tables; local SQLite does not contain these addon tables.
- Migrations: none for addon schemas.
- Views: `resources/views/frontend/addons/*`, paid-content/fundraiser references in sidebars and feed partials; no full first-class addon view tree for all referenced route names.
- JS: `public/assets/frontend/paid-content/*`, `public/assets/frontend/css/fundraiser/*`.
- SCSS: fundraiser SCSS under `public/assets/frontend/css/fundraiser/app/scss` and `public/assets/frontend/css/fundraiser/css/new_scss`.
- Jobs: no Laravel jobs.
- Events/listeners: none.
- Policies: none.
- Tests: none module-specific.
- External dependencies: payment providers, CKEditor/Summernote, date/time picker assets, addon helper checks.
- Refactor priority: `P0`. Treat as incomplete/dormant addon surface; do not enable or refactor without schema and route characterization tests.

### Static, Legal, Contact, and Localization

- Routes: `routes/custom_routes.php` about/privacy/terms/settings/contact routes; language switch in `routes/web.php`; admin language phrase routes.
- Controllers: `SettingController`, `LanguageController`, closure route for language switch.
- Requests: none.
- Models: `Setting`, `Language`.
- Database tables: `settings`, `languages`.
- Migrations: lookup index for `settings`; no language migration in source.
- Views: `resources/views/frontend/settings/*`, `resources/views/backend/admin/language/*`, `resources/views/emails/contact.blade.php`, auth/layout shared views.
- JS: shared frontend/backend scripts.
- SCSS: public frontend/backend CSS only.
- Jobs: none; contact email is sent synchronously.
- Events/listeners: none.
- Policies: none.
- Tests: none module-specific.
- External dependencies: Laravel Mail, Flasher.
- Refactor priority: `P2`. Lower blast radius, but contact mail/settings writes need validation and anti-abuse coverage.

## Cross-Cutting Refactor Priorities

1. Add policy coverage around admin, feed deletes, payments, profiles, groups/pages/events, media, and notifications.
2. Replace state-changing `GET` routes with write methods gradually, one module at a time, behind regression tests.
3. Split `ApiController`, `AdminCrudController`, and `MainController` by module after tests lock current behavior.
4. Add Form Requests for all write routes before changing validation behavior.
5. Move provider/payment/media/live/AI calls into services or actions and fake them in tests.
6. Replace Blade queries and helper-driven query logic with preloaded controller/view-model data.
7. Decide whether dormant addon modules should be removed, restored with migrations/controllers, or explicitly disabled.
8. Add CI coverage for `php artisan test`, Pint, Composer validation/audit, and frontend build checks.
