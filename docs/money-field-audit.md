# Money Field Audit

Date: 2026-07-02

## Scope

Audited schema, models, requests, controllers, payment services, Blade payment views, factories, and tests for fields and calculations named like money, price, amount, tax, discount, fee, balance, and currency.

## Safe Changes Applied

| Area | Field or code path | Previous risk | Change | Risk |
| --- | --- | --- | --- | --- |
| Database | `payment_histories.amount` | Stored as `double` / SQLite `real`, which is unsafe for money precision. | Added reversible migration to convert clean values to nullable `decimal(12, 2)`. | Medium |
| Database | `sponsors.paid_amount` | Stored as `double` / SQLite `real`, which is unsafe for money precision. | Added reversible migration to convert clean values to nullable `decimal(12, 2)`. | Medium |
| Database | `marketplaces.price` | Stored as `varchar(15)` while range-filtered and sorted as money. | Added reversible migration to convert clean values to nullable `decimal(12, 2)`. | Medium |
| Models | `Marketplace::price` | No decimal cast; persisted values could round-trip as inconsistent strings. | Added `decimal:2` cast. | Small |
| Validation | Web/API marketplace price and filters | Accepted arbitrary text or more than two decimal places in some flows. | Standardized `numeric`, `decimal:0,2`, `min:0`, and `max:9999999999.99` rules. | Small |
| Payment calculations | Stripe/Razorpay/Paystack minor units | Used direct `* 100` or `round(... * 100)` calculations. | Added `App\Support\Money\Money::toMinorUnits()` and pass integer minor units to providers/views. | Medium |

## Migration Safety

Migration: `database/migrations/2026_07_02_180000_add_safe_legacy_money_precision_constraints.php`

The migration checks existing values before changing each column. A column is skipped if it contains non-numeric text, negative values, more than two decimal places, or values outside `decimal(12, 2)` range. This prevents silent truncation or rounding in production. The `down()` method restores the legacy column types.

## Deferred or Documented Fields

| Area | Finding | Reason deferred | Safe first step |
| --- | --- | --- | --- |
| Settings | `settings.description` stores values for `badge_price`, `job_price`, `ad_charge_per_day`, `commission_rate`, and `system_currency`. | The table is generic text storage, so changing column type would affect unrelated settings. | Add typed settings accessors and validation Form Requests before moving money settings to dedicated columns. |
| Addon models | `Fundraiser.goal_amount`, `Fundraiser.raised_amount`, `Fundraiser_donation.amount`, `Fundraiser_payout.amount`, `PaidContentPayout.amount`, and `PaidContentPackages.price`. | Related addon tables are optional or absent from the canonical local install dump, so schema conversion cannot be safely verified here. | When addon migrations are present, add decimal casts, request validation, and table-specific money migrations. |
| Currency fields | `payment_gateways.currency`, `payment_histories.currency`, and `marketplaces.currency_id`. | Currency identifiers/codes are not money amounts. | Validate ISO 4217 codes where codes are accepted from admin input. |
| Non-money float | `addons.version`. | This is a version number, not money. | Leave as-is unless addon versioning is redesigned. |
| Percent/rates | `commission_rate`, `discount_percentage`, and tax-like display values. | These are rates/percentages rather than currency amounts. | Validate as bounded decimals in their own settings/request refactor. |

## Tests Added or Updated

- `MigrationSafetyAuditTest` covers decimal migration, rollback, and dirty-data skipping.
- `EloquentCastAuditTest` covers marketplace price decimal casting.
- `MarketplaceAuthorizationTest` covers web marketplace price validation.
- `ApiMarketplaceValidationTest` covers API price and price-filter precision validation.
- `PaymentPageViewDataTest` covers Paystack minor-unit view data.
- `MoneyTest` covers exact money-to-minor-unit conversion and invalid amount rejection.

## Remaining Risks

- Existing production data with dirty money text will cause the migration to skip that specific column. Run a pre-deploy data audit and clean invalid values before expecting conversion.
- Payment settings remain string-backed until a typed settings refactor is implemented.
- Gateway-specific currency exponent differences are not modeled yet. This change preserves the legacy two-decimal assumption used by the current payment code.
