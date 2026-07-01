# Domain Values

This project still contains many legacy string and numeric flags. New and refactored code should prefer the enum classes in `app/Enums` for stable persisted values instead of repeating literals in controllers, views, services, or tests.

## Centralized Values

| Domain | Enum | Stored values | Current usage |
| --- | --- | --- | --- |
| User roles | `App\Enums\UserRole` | `admin`, `general`, `member` | Install flow, registration, middleware, admin queries, tests |
| Page/group membership roles | `App\Enums\MembershipRole` | `admin`, `general` | Page likes and group membership ownership |
| User account status | `App\Enums\UserAccountStatus` | `0`, `1` | Registration, middleware, account-disable tests |
| Account activation request status | `App\Enums\AccountActivationStatus` | `pending` | Admin dashboard view data |
| Visibility / privacy | `App\Enums\Visibility` | `public`, `friends`, `private` | Post/story/event/video/page queries, validation, tests |
| Content status | `App\Enums\ContentStatus` | `active`, `inactive` | Post/story query filters, creation paths, tests |
| Media file type | `App\Enums\MediaFileType` | `image`, `video` | Media library filters and uploaded media metadata |
| Post type | `App\Enums\PostType` | `general`, `event`, `live_streaming`, `share`, `profile_picture`, `cover_photo`, `fundraiser` | Post creation and share paths |
| Video category | `App\Enums\VideoCategory` | `video`, `shorts` | Video/short listing queries |
| Payment gateway identifier | `App\Enums\PaymentGatewayIdentifier` | `stripe`, `razorpay`, `flutterwave`, `paypal`, `paystack`, `paytm` | Gateway lookup, payment controller, payment services, tests |
| Paytm transaction state | `App\Enums\PaytmTransactionStatus` | `0`, `1`, `2` | Paytm callback updates, `PaymentHistoryEntry` cast |

## Rules

- Use `Rule::enum(SomeEnum::class)` for validation when a request accepts one of these values.
- Store enum-backed values with `$enum->value`; do not persist enum objects directly in mass updates.
- Add Eloquent casts only after checking that existing Blade, API, and JSON paths will tolerate enum objects on read.
- Keep enum case values stable unless a migration and regression tests prove the database/UI/API behavior is intentionally changing.
- Do not introduce new magic statuses, roles, payment states, visibility values, post types, or categories without adding them here and covering them with tests.

## Deferred Legacy Work

Several tables use generic `status`, `type`, `category`, or boolean-like numeric columns with table-specific meanings. Those values were not blindly converted because the same literal can mean different things across jobs, groups, notifications, reports, marketplaces, settings, and API response payloads.

Safe next steps:

1. Inventory one table or feature at a time.
2. Confirm stored values from the schema, seed data, and active queries.
3. Add regression tests around the current behavior.
4. Introduce a narrowly named enum such as `JobStatus`, `NotificationStatus`, or `MarketplaceStatus`.
5. Add casts only when read behavior is proven safe for Blade/API output.
