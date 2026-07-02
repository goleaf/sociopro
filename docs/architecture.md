# Architecture

Generated: 2026-07-02

This document describes the current checkout, not the desired end state.

## Stack

- Laravel `13.18.0` with PHP requirement `^8.3`.
- Blade server-rendered frontend.
- Laravel Sanctum API authentication.
- Laravel Mix / Webpack frontend build.
- PHPUnit, Pint, Larastan/PHPStan, Rector, ESLint, Stylelint, and Prettier are installed.
- SQLite is used for local/CI tests. Production-like schema history still depends on `public/assets/install.sql` plus additive migrations.
- No Filament dependency or `app/Filament` panel is present in this checkout.

## Modules

- Authentication and account status: `routes/auth.php`, `app/Http/Controllers/Auth`, `app/Http/Controllers/Account`.
- Timeline/posts/stories/profile: `MainController`, `StoryController`, `Profile`, Blade views under `resources/views/frontend`.
- Chat/messages: `ChatController`, API chat methods in `ApiController`, `Message`/`Message_thrade`, and chat Blade partials.
- Marketplace: marketplace methods in web/API controllers, `Marketplace` model, marketplace query classes, factories, and validation tests.
- Jobs/applications: job controllers, `app/Actions/Jobs`, `JobApplication`, private attachment streaming, and export tests.
- Payments: `PaymentController`, `app/Services/Payments`, gateway resolver and gateway-specific tests.
- Addons/install: `InstallController`, `app/Actions/Install`, `app/Actions/Addons`, `ImportInstallSqlDumpJob`, and addon import tests.
- Admin: legacy controller/view routes in `routes/custom_routes.php`; currently not Filament-backed.

## Routes And Controllers

- Web routes live in `routes/web.php`, `routes/auth.php`, `routes/custom_routes.php`, `routes/payment.php`, and `routes/user.php`.
- API routes live in `routes/api.php` under the legacy unversioned `api.` name prefix.
- Controllers are still mixed-concern in several high-traffic areas. New work should keep controllers thin and move workflows into `app/Actions` or `app/Services`.
- Several legacy web routes still use GET for state-changing operations. Preserve public behavior until each route has caller coverage and a migration plan.

## Services And Actions

- Business workflows belong in `app/Actions`.
- Integration/domain services belong in `app/Services`.
- Shared upload and path safety helpers live in `app/Support/Files` and `app/Support/Security`.
- Payment provider code lives in `app/Services/Payments` and should stay behind tested gateway interfaces.

## Models And Database

- Eloquent models are the query layer. Do not add raw SQL strings in controllers, Blade views, jobs, resources, or helpers.
- Add model scopes or query classes for repeated filters and relationship loading.
- Add only safe new migrations. Do not edit existing production migrations or the legacy install SQL unless the change is explicitly reviewed.
- Add indexes for new filters, foreign keys, unique lookups, and cursor/sort columns.
- Use factories for tests and seeders. Avoid hardcoded production IDs.

## Authorization

- Policies live in `app/Policies` and should be preferred for new authorization.
- Middleware protects most route groups, but legacy controllers still contain inline access checks.
- New tests should include allowed and denied paths for authentication, role authorization, ownership, and private media access.

## Validation

- New write endpoints should use Form Requests in `app/Http/Requests`.
- Existing inline validation should be moved gradually behind regression tests.
- Never mass assign `$request->all()`. Use `$request->validated()`, explicit arrays, DTOs, or action method arguments.

## Jobs, Events, And Notifications

- Queued jobs currently include addon and install SQL imports.
- Production deployments with non-sync queues must run queue workers and call `php artisan queue:restart` after each release.
- New jobs must be idempotent when practical and tested with fakes for mail, notifications, storage, queue, events, and HTTP.

## Frontend Structure

- Blade templates live under `resources/views`.
- JavaScript lives under `resources/js`; CSS lives under `resources/css`.
- Assets are built with Laravel Mix. Do not use Vite helpers unless Vite is introduced in a dedicated migration.
- Blade should receive preloaded data from controllers/view models; do not add queries or business decisions inside templates.
- Escape output by default. Any raw output must have an explicit sanitizer contract and tests.

## External Integrations

- Payment providers: Stripe, Razorpay, Paytm, PayPal/Paystack callback surfaces, and Flutterwave package dependency.
- File storage: local/public storage and optional S3 configuration.
- Mail: Laravel mailers configured by `.env` and legacy admin settings stored in `config/config.json`.
- Zoom integration services exist and should use faked HTTP in automated tests.
- Browser-facing generated-image provider integrations have been removed and must not be reintroduced without a new server-side design.
