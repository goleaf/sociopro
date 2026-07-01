# Dead Code Audit

Date: 2026-07-01

## Scope

This pass searched for dead PHP classes, unused methods, obsolete traits, abandoned interfaces, duplicate services, unreachable branches, commented legacy blocks, and unused config. Only items with direct reference proof were removed. Items that could still be called through public routes, Artisan, Blade, JavaScript, package discovery, or external integrations were left in place and documented below.

## Removed With Proof

| Item | Type | Risk | Evidence | Result |
| --- | --- | --- | --- | --- |
| `app/Providers/CustomServiceProvider.php` | Provider | Low | No references and not registered in `config/app.php`. | Deleted. |
| `app/Providers/BroadcastServiceProvider.php` | Provider | Low | App provider was only present as a commented entry in `config/app.php`. | Deleted. |
| `routes/channels.php` | Route config | Low | Only referenced by the unregistered app broadcast provider. | Deleted. |
| `app/Http/Middleware/TrustHosts.php` | Middleware | Low | Only present as a commented global middleware entry in `app/Http/Kernel.php`. | Deleted. |
| `app/Http/Middleware/DisableFileUpload.php` | Middleware | Low | Route middleware alias existed, but no route used `fileUploadDisabled`; middleware only forwarded the request. | Deleted and alias/import removed. |
| `app/Models/CommonModel.php` | Model | Low | Only referenced by its own class declaration. | Deleted. |
| `app/View/Components/AppLayout.php` | Blade component class | Low | No `<x-app-layout>` or class references; only returned `layouts.app`. | Deleted. |
| `config/laravel-ffmpeg.php` | Config | Low | No Composer package or app references for Laravel FFmpeg/FFMpeg remained. | Deleted. |
| `config/paypal.php` | Config | Low | No `config('paypal...')` references or PayPal SDK package remained; active PayPal flow uses `Payment_gateway` rows. | Deleted. |
| Commented route/method/DataTables fallback blocks | Legacy comments | Low | Comment-only duplicate blocks sat beside active route/controller/view implementations. | Removed. |

## Uncertain Items Left In Place

| Item | Risk | Why It Was Not Removed | Safe First Step |
| --- | --- | --- | --- |
| `app/Console/Commands/FlushSessions.php` | Medium | No internal references found, but commands are auto-discovered and may be called by operators, cron, or deployment scripts. | Rename the placeholder signature and add a command test, or remove after confirming no scheduler/deployment usage. |
| `app/Helpers/CommonHelper.php` and `app/Helpers/ApiHelper.php` | High | Both are Composer file-autoloaded global helper files; per-function usage can be dynamic from Blade, controllers, packages, or legacy scripts. | Add helper usage inventory and feature tests for key UI/API flows before deleting individual helper functions. |
| Commented blocks inside `app/Http/Controllers/ApiController.php` | High | Several commented method names match API route names that currently resolve to missing controller methods. Removing the comments would hide useful forensic context before a route/API fix. | Add API route smoke tests, then either restore the endpoints or remove the routes intentionally. |
| Public controller methods without obvious direct references | Medium | Legacy Blade, modal loaders, route strings, and JavaScript can call public methods indirectly. | Generate a controller method inventory from route actions plus Blade/JS route calls, then test before deletion. |
| `config/image.php`, `config/laravel-share.php`, `config/broadcasting.php` | Medium | These are package/framework-backed configs and may be read dynamically by providers. | Keep until the owning package/framework feature is removed. |
| `app/Traits/ZoomMeetingTrait.php` | Low | Trait is used by `App\Http\Controllers\MainController`. | Keep. |
| `app/Actions/Install/*` | Low | Install actions are used by `InstallController`, seeders, and tests. | Keep. |

## Existing Broken Route Actions Found

The route table boots, but these routes currently point to controller methods that do not exist. They were not changed in this cleanup because disabling or replacing them is a behavior change that needs regression tests.

| Route | Missing action |
| --- | --- |
| `GET admin/addon/form` | `App\Http\Controllers\Updater@addon_form` |
| `GET album/details/page/list/{album_id}/{id}` | `App\Http\Controllers\GroupController@album_details_page_list` |
| `POST api/comment_reaction` | `App\Http\Controllers\ApiController@comment_reaction` |
| `GET api/data` | `App\Http\Controllers\ApiController@userdata` |
| `GET api/getPostReactions/{postId}` | `App\Http\Controllers\ApiController@getPostReactions` |
| `POST api/groups_join_remove/{id}` | `App\Http\Controllers\ApiController@groups_join_remove` |
| `GET api/job_wishlist` | `App\Http\Controllers\ApiController@job_wishlist` |
| `POST api/page_dislike/{id}` | `App\Http\Controllers\ApiController@page_dislike` |
| `GET api/profile_videos` | `App\Http\Controllers\ApiController@profile_videos` |
| `POST api/unsave_for_later/{id}` | `App\Http\Controllers\ApiController@unsave_for_later` |
| `GET profile/load_photo_and_videos` | `App\Http\Controllers\Profile@load_photo_and_videos` |
| `GET user/ad/payment_success/{identifier}` | `App\Http\Controllers\UserController@payment_success` |

## Evidence Commands

```bash
rg -n "CustomServiceProvider|App\\\\Providers\\\\BroadcastServiceProvider|fileUploadDisabled|TrustHosts|DisableFileUpload|CommonModel|AppLayout|x-app-layout|app-layout|routes/channels|channels\\.php" app config routes resources database tests composer.json
php artisan route:list --json
php -r 'require "vendor/autoload.php"; /* reflected route actions for missing controller methods */'
rg -n "pbmedia/laravel-ffmpeg|php-ffmpeg|FFMpeg|FFmpeg|laravel-ffmpeg|ffmpeg" composer.json composer.lock app routes resources config database tests
rg -n "config\\(['\\\"]paypal|paypal\\.php|PayPal\\\\|paypal/rest-api|paypal/" composer.json composer.lock app routes resources config database tests
```
