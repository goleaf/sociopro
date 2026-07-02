# Architecture Map

Generated: 2026-07-01

This document describes the current Laravel application architecture from the live checkout. It is a documentation-only map; no application behavior was changed.

Use this with:

- `AGENTS.md`
- `docs/project-standards-bible.md`
- `docs/refactor-roadmap-unreal.md`
- `docs/risk-register.md`
- `docs/refactor-checklist.md`
- `docs/migration-audit.md`

## Current Baseline

- Framework: Laravel `13.18.0`
- PHP requirement: `^8.3`
- Test runner: PHPUnit `12.5.30`
- Formatter: Laravel Pint `1.29.3`
- Frontend build: Laravel Mix / Webpack
- Frontend source entrypoints: `resources/js/app.js`, `resources/js/bootstrap.js`, `resources/css/app.css`
- Compiled frontend outputs currently present: `public/js/app.js`, `public/js/share.js`, `public/css/app.css`
- Vite assets: none detected; there is no `vite.config.*`
- Database source: local SQLite database plus legacy installer dump at `public/assets/install.sql`
- Current Laravel migration status: `2026_07_01_150000_add_safe_legacy_lookup_indexes` is run

## High-Level Shape

The application is a Blade-rendered Laravel monolith for a social network-style product. It contains web, API, admin, installer, payment, media, chat, marketplace, pages, groups, blogs, stories, jobs, sponsors, badges, notification, and live-streaming features.

The current architecture is controller-heavy:

- `ApiController`: 7,919 lines, 134 registered routes
- `AdminCrudController`: 1,403 lines, 66 registered routes
- `MainController`: 1,251 lines, 34 registered routes
- `Profile`: 573 lines
- `EventController`, `GroupController`, `MarketplaceController`, `PaymentController`, `SettingController`, and domain controllers carry most web behavior

Most user-facing HTML is rendered through Blade under `resources/views/frontend`, `resources/views/backend`, `resources/views/payment`, `resources/views/install`, and `resources/views/auth`.

## Module Map

| Module | Primary controllers | Route files | Main models/tables | Main views |
| --- | --- | --- | --- | --- |
| Authentication/account | Auth controllers, `CustomUserController`, `UserController`, `Profile`, `AdminCrudController` | `routes/auth.php`, `routes/web.php`, `routes/custom_routes.php`, `routes/api.php` | `users`, `account_active_requests`, `followers`, `friendships`, `block_users` | `auth/*`, `frontend/profile/*`, `frontend/user/*`, `backend/admin/users/*` |
| Feed/posts/comments | `MainController`, `ApiController`, `ModalController` | `routes/web.php`, `routes/api.php` | `posts`, `comments`, `media_files`, `post_shares`, `reports`, `activities`, `feeling_and_activities` | `frontend/main_content/*`, `frontend/index.blade.php` |
| Stories | `StoryController`, `ApiController` | `routes/web.php`, `routes/api.php` | `stories`, `media_files`, `users` | `frontend/story/*` |
| Admin/settings | `AdminCrudController`, `SettingController`, `LanguageController`, `Updater` | `routes/custom_routes.php`, `routes/web.php` | `settings`, `languages`, `addons`, `payment_gateways`, content category tables | `backend/admin/*`, `backend/index.blade.php` |
| Installer/update | `InstallController`, `Updater`, install actions | `routes/web.php`, `routes/custom_routes.php` | `settings`, `addons`, legacy install SQL | `install/*` |
| Payments | `PaymentController`, `PaymentHistory`, payment gateway model classes | `routes/payment.php`, `routes/custom_routes.php`, `routes/user.php` | `payment_gateways`, `payment_histories`, `sponsors`, `batchs` | `payment/*`, `backend/admin/payment/*`, `backend/user/payment_history/*` |
| Marketplace | `MarketplaceController`, `ApiController` | `routes/custom_routes.php`, `routes/api.php` | `marketplaces`, `categories`, `brands`, `currencies`, `saved_products` | `frontend/marketplace/*`, admin product category/brand views |
| Groups/pages/events/blogs/videos | `GroupController`, `PageController`, `EventController`, `BlogController`, `VideoController`, `ApiController` | `routes/custom_routes.php`, `routes/api.php` | `groups`, `group_members`, `pages`, `page_likes`, `events`, `invites`, `blogs`, `blogcategories`, `videos`, `saveforlaters` | `frontend/groups/*`, `frontend/pages/*`, `frontend/events/*`, `frontend/blogs/*`, `frontend/video-shorts/*` |
| Chat/notifications | `ChatController`, `NotificationController`, `ApiController`, `ViewServiceProvider` | `routes/custom_routes.php`, `routes/api.php` | `chats`, `message_thrades`, `notifications` | `frontend/chat/*`, `frontend/notification/*`, shared headers |
| Media/live streaming | `MainController`, `Profile`, `GroupController`, `PageController`, `VideoController`, `SettingController`, `FileUploader`, `ZoomMeetingTrait` | `routes/web.php`, `routes/custom_routes.php`, `routes/api.php` | `media_files`, `album_images`, `albums`, `live_streamings`, `settings` | `frontend/live_streaming/*`, `frontend/main_content/jitsi_streaming.blade.php`, media/profile/page/group views |
| Addons/fundraising/paid content | `Updater`, `MainController`, `ApiController` | `routes/web.php`, `routes/custom_routes.php`, conditional missing `routes/fundraiser.php` hook | `addons`, optional fundraiser/paid-content model classes; local DB lacks some mapped tables | `frontend/addons/*`, addon/admin views |

## Route Architecture

Application routes are loaded by `App\Providers\RouteServiceProvider`:

- `routes/api.php`: 135 route declarations, loaded with the `api` middleware group and `/api` prefix.
- `routes/web.php`: 104 route declarations, loaded with the `web` middleware group.
- `routes/custom_routes.php`: 328 route declarations, largest route file; contains most admin, social, marketplace, group, page, video, chat, follow, and settings routes.
- `routes/user.php`: 15 route declarations for user ad/payment history flows.
- `routes/payment.php`: 9 route declarations for payment page and gateway callbacks.
- `routes/auth.php`: 16 Breeze-style auth routes.
- `routes/channels.php`: broadcast channel for `App.Models.User.{id}`.
- `routes/console.php`: closure command for `inspire`.

Route method mix from `php artisan route:list --except-vendor --json`:

- Total routes: 483
- `GET|HEAD`: 305
- `POST`: 168
- Catch-all method routes: 8
- Mixed `GET|POST|HEAD`: 2

The heaviest routed controllers are:

- `App\Http\Controllers\ApiController`: 134 routes
- `App\Http\Controllers\AdminCrudController`: 66 routes
- `App\Http\Controllers\MainController`: 34 routes
- `App\Http\Controllers\SettingController`: 24 routes
- `App\Http\Controllers\Profile`: 22 routes
- `App\Http\Controllers\GroupController`: 21 routes
- `App\Http\Controllers\Event\EventController`: 16 routes

## Controllers

Controller directories:

- `app/Http/Controllers`
- `app/Http/Controllers/Auth`
- `app/Http/Controllers/Event`
- `app/Http/Controllers/Report`

Primary controller roles:

- `ApiController`: mobile/API surface for auth, feed, posts, stories, comments, profiles, groups, pages, marketplace, videos, events, blogs, jobs, notifications, chat, and paid content.
- `AdminCrudController`: admin dashboard and CRUD for users, pages, blogs, groups, jobs, payment settings/history, badges, categories, products, reports, sponsors, purchase code, and server-side user data.
- `MainController`: web timeline/feed, live streaming/Zoom, memories, addons, and general frontend pages.
- `Profile`: profile edit, photos/videos, albums, friend requests, blocks, saved posts, and profile media.
- `SettingController`: system, SMTP, S3, live-video/Jitsi, about/privacy/terms, reported posts, and contact mail flows.
- `PaymentController`: payment gateway selection, Paytm callback, gateway model dispatch, gateway view data, and payment creation/status routes.
- `Updater`: product/addon update and addon manager/status/delete routes.
- Smaller domain controllers: `BlogController`, `EventController`, `GroupController`, `MarketplaceController`, `PageController`, `VideoController`, `ChatController`, `NotificationController`, `FollowController`, `SponsorController`, `StoryController`, `LanguageController`, `BadgeController`, `UserController`, `CustomUserController`, `MemoriesController`, `ModalController`, and `SearchController`.
- Single-command auth controllers use invokable routing where it clarifies intent; `EmailVerificationNotificationController` handles only the `verification.send` resend command.

## Models and Tables

Current model-to-table mappings detected through Laravel:

| Model | Table |
| --- | --- |
| `AccountActiveRequest` | `account_active_requests` |
| `Addon` | `addons` |
| `AlbumImage` | `album_images` |
| `Albums` | `albums` |
| `Badge` | `batchs` |
| `BlockUser` | `block_users` |
| `Blog` | `blogs` |
| `Blogcategory` | `blogcategories` |
| `Brand` | `brands` |
| `Category` | `categories` |
| `Chat` | `chats` |
| `Comments` | `comments` |
| `Currency` | `currencies` |
| `Event` | `events` |
| `FeelingAndActivity` | `feeling_and_activities` |
| `Follower` | `followers` |
| `Friendships` | `friendships` |
| `Group` | `groups` |
| `GroupMember` | `group_members` |
| `Invite` | `invites` |
| `Language` | `languages` |
| `LiveStreaming` | `live_streamings` |
| `Marketplace` | `marketplaces` |
| `MediaFile` | `media_files` |
| `MessageThread` | `message_thrades` |
| `Notification` | `notifications` |
| `Page` | `pages` |
| `PageLike` | `page_likes` |
| `Pagecategory` | `pagecategories` |
| `PaymentGateway` | `payment_gateways` |
| `PostShare` | `post_shares` |
| `Posts` | `posts` |
| `Report` | `reports` |
| `SavedProduct` | `saved_products` |
| `Saveforlater` | `saveforlaters` |
| `Setting` | `settings` |
| `Share` | `shares` |
| `Sponsor` | `sponsors` |
| `Stories` | `stories` |
| `User` / `Users` | `users` |
| `Video` | `videos` |

Payment gateway model classes under `app/Models/payment_gateway`:

- `Flutterwave`
- `Paypal`
- `Paystack`
- `Paytm`
- `Razorpay`
- `StripePay`

Models currently mapped to tables not present in the local SQLite schema:

- `CommonModel` -> `common_models`
- `FileUploader` -> `file_uploaders`
- `Fundraiser` -> `fundraisers`
- `FundraiserDonation` -> `fundraiser_donations`
- `PaidContentCreator` -> `paid_content_creators`

Tables without first-class local model files:

- `activities`
- `failed_jobs`
- `migrations`
- `password_resets`
- `payment_histories`
- `personal_access_tokens`

## Database Tables

Current local SQLite tables:

- Account/auth: `users`, `account_active_requests`, `password_resets`, `personal_access_tokens`
- Social graph: `followers`, `friendships`, `block_users`
- Feed/content: `posts`, `comments`, `post_shares`, `shares`, `reports`, `activities`, `feeling_and_activities`
- Media/albums/stories/videos: `media_files`, `albums`, `album_images`, `stories`, `videos`, `saveforlaters`
- Pages/groups/events: `pages`, `page_likes`, `pagecategories`, `groups`, `group_members`, `events`, `invites`
- Marketplace/jobs/sponsors/badges: `marketplaces`, `categories`, `brands`, `currencies`, `saved_products`, `sponsors`, `batchs`
- Blog: `blogs`, `blogcategories`
- Messaging/notifications: `chats`, `message_thrades`, `notifications`
- Settings/install/addons/localization: `settings`, `addons`, `languages`, `migrations`
- Payments/queues: `payment_gateways`, `payment_histories`, `failed_jobs`
- Live streaming: `live_streamings`

Schema source notes:

- `database/migrations` contains only `2026_07_01_150000_add_safe_legacy_lookup_indexes.php`.
- `database/seeders/DatabaseSeeder.php` imports `public/assets/install.sql` through `App\Actions\Install\ImportInstallSqlDump` when the `settings` table is absent.
- `database/factories` contains only `UserFactory`.

## Services, Actions, Queries, and ViewModels

There is no `app/Services` directory.

Action classes exist only for installer/setup flows:

- `CheckInstallRequirements`
- `ConfigureDatabase`
- `FinalizeInstallation`
- `ImportInstallSqlDump`
- `PrepareDatabaseConnection`
- `UpdateEnvironmentFile`

Query classes:

- `FriendshipsQuery`: accepted/important/recent friendship builders.
- `StoriesQuery`: story visibility and story-with-owner lookup.

ViewModels:

- `BladeViewData`: shared helper object injected into all views; handles comments, reacts, media, profile friends, page/group/blog/event metadata, and other read helpers.
- `ProfileFollowList`: builds followers/following lists and mutual-friend metadata.

Helpers:

- `app/Helpers/CommonHelper.php`
- `app/Helpers/ApiHelper.php`

Helpers are autoloaded through Composer and still contain database/business logic.

## Jobs, Queues, Events, Listeners, and Policies

Jobs:

- No `app/Jobs` directory is present.
- Queue config defaults to `QUEUE_CONNECTION=sync`.
- Configured drivers include `sync`, `database`, `beanstalkd`, `sqs`, and `redis`, but the application has no job classes mapped in source.

Scheduled commands:

- `app/Console/Kernel.php` has no active schedule entries.
- `app/Console/Commands/FlushSessions.php` exists with placeholder signature `command:name` and returns `0`.
- `routes/console.php` defines only the default `inspire` closure command.

Events/listeners:

- `EventServiceProvider` maps `Illuminate\Auth\Events\Registered` to `SendEmailVerificationNotification`.
- No custom `app/Events` or `app/Listeners` directories are present.

Policies:

- `AuthServiceProvider` has an empty `$policies` map.
- No `app/Policies` directory is present.

Broadcasting:

- `BroadcastServiceProvider` registers broadcast routes.
- `routes/channels.php` authorizes `App.Models.User.{id}` for the matching authenticated user id.

## Views

Main view areas:

- `resources/views/auth`: login, registration, password reset, email verification.
- `resources/views/backend`: admin/user layouts, dashboards, settings, categories, users, payment history, reports, sponsors, badges.
- `resources/views/frontend`: social feed, header/sidebar, profile, user pages, groups, pages, events, marketplace, chat, stories, notifications, blogs, video shorts, live streaming, settings.
- `resources/views/install`: installation wizard and final setup.
- `resources/views/payment`: gateway selection and gateway-specific views for Stripe, Paytm, Razorpay, PayPal, Paystack, and Flutterwave.
- `resources/views/components`: default Blade components from the auth scaffold.
- `resources/views/emails`: contact email.

Largest view clusters by file count:

- `backend/admin`: 50
- `frontend/main_content`: 30
- `frontend/profile`: 28
- `frontend/groups`: 23
- `frontend/pages`: 15
- `frontend/user`: 11
- `frontend/events`: 10
- `frontend/search`: 9
- `frontend/marketplace`: 9

View composers:

- `ViewServiceProvider` injects `BladeViewData` into all views.
- It also injects auth user data, auth layout settings, error page settings, footer settings, analytics IDs, toaster session messages, frontend theme/logo values, header message/notification counts, Zoom configuration, and Jitsi configuration.

## Frontend Assets

Current build tool:

- Laravel Mix / Webpack via `webpack.mix.js`.

Source entrypoints:

- JavaScript: `resources/js/app.js`
- Bootstrap/Axios setup: `resources/js/bootstrap.js`
- CSS: `resources/css/app.css`

Build output targets:

- JavaScript: `public/js`
- CSS: `public/css`

Package scripts:

- `npm run development` -> `mix`
- `npm run watch` -> `mix watch`
- `npm run hot` -> `mix watch --hot`
- `npm run production` -> `mix --production`

Vite:

- No `vite.config.*` exists.
- No Vite entrypoint directives were detected in the build config.
- Future Vite migration should be treated as a dedicated build-tool migration.

Public asset/storage directories:

- Static theme assets under `public/assets/authentication`, `public/assets/backend`, `public/assets/frontend`, and `public/assets/payment`.
- Public storage subdirectories include album, blog, chat, cover photo, event, groups, marketplace, pages, post, profile background, sponsor, story, thumbnails, user images, and videos.

## External Integrations

Payment:

- Paytm: `anandsiddharth/laravel-paytm-wallet`, `PaytmWallet` facade, `App\Models\payment_gateway\Paytm`, Paytm callback routes.
- Stripe: `stripe/stripe-php`, `StripePay`, Stripe checkout/payment intent usage.
- Razorpay: `razorpay/razorpay`, Razorpay checkout views and model.
- PayPal: HTTP-based token/payment status calls in `Paypal`.
- Paystack: HTTP-based transaction verification in `Paystack` and inline JS view.
- Flutterwave: `flutterwavedev/flutterwave-v3` and Flutterwave checkout view.

Media/storage:

- Local public storage via `config/filesystems.php`.
- S3 config via AWS env keys and admin S3 settings.
- `FileUploader` can configure S3 disk values dynamically from settings.
- `intervention/image` and `config/laravel-ffmpeg.php` support image/video processing concerns.

Communication/social:

- Mail contact flow through `SettingController` and `ContactMail`.
- Laravel Share package.
- Flasher notifications.

Live/video:

- Zoom integration through `ZoomMeetingTrait`, Firebase JWT, and Zoom SDK views.
- Jitsi/JaaS integration through settings and `frontend/main_content/jitsi_streaming.blade.php`.

Admin/data tooling:

- Yajra DataTables provider/facade registered in `config/app.php`.

## Known Architectural Smells

- Controller concentration: `ApiController`, `AdminCrudController`, and `MainController` are too large and mix many domains.
- Route concentration: `routes/custom_routes.php` and `routes/api.php` hold large, mixed route surfaces.
- State-changing `GET` routes remain for delete/status/follow/mark-read/admin toggles.
- API routes mostly rely on controller behavior rather than route-level authenticated groups.
- No policy layer is present.
- No service layer is present.
- No custom job/event/listener architecture is present.
- Queue defaults to synchronous execution, while payment, mail, media, live video, and provider calls are slow/side-effect-heavy domains.
- Helpers are globally autoloaded and still contain query/business logic.
- Blade views still contain database queries, logic, debug logging, and browser-visible provider configuration in some areas.
- View composers centralize convenience data but also perform database reads for every matching layout render.
- Schema is still dump-backed; only one current Laravel migration is present.
- Some model/table mappings do not match the current local database schema.
- Model naming now follows StudlyCase for PHP class/file names; legacy table names such as `message_thrades` remain compatibility constraints.
- `$fillable` and `$casts` coverage is partial across the model layer.
- Payment gateway secret ownership is split across config, DB settings, admin views, and gateway model classes.
- Vite is not installed even though modern standards docs reference Vite as a target state.
- CI workflows are not present in `.github/workflows`.

## Safe Refactor Entry Points

1. Add characterization tests for updater access, auth/demo restore behavior, and one sensitive API endpoint.
2. Disable or remove executable updater and demo restore paths.
3. Convert one destructive `GET` route to a CSRF-protected write route with tests.
4. Move one Blade query-heavy page to controller-preloaded data.
5. Add the first policy around a destructive content/admin action.
6. Extract one payment gateway path into a tested service with `Http::fake()`.
7. Add CI for `php artisan test`, Pint, Composer validation/audit, and frontend audit/build checks.
8. Produce a schema baseline comparison before adding foreign keys or replacing the SQL dump bootstrap.
