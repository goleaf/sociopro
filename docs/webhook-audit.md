# Webhook Endpoint Audit

Last updated: 2026-07-02

## Scope

Audited public payment callback-style routes in `routes/payment.php`, CSRF handling in `VerifyCsrfToken`, webhook rate limiting in `RouteServiceProvider`, payment gateway credential lookup, replay/idempotency controls, logging, and focused regression tests.

## Endpoint Findings

| Endpoint | Provider | Purpose | Current controls | Risk | Safe next step |
| --- | --- | --- | --- | --- | --- |
| `POST paystack/payment/{identifier}` (`make.payment`) | Paystack | Server-side payment notification surface retained by legacy routes | `throttle:webhook`, `payment.webhook:paystack`, Paystack HMAC verification, replay cache, safe structured logs, documented CSRF exception | Medium | Move payment side effects to an idempotent queued job after controller extraction. |
| `GET payment/make/{identifier}/status` (`payment.status`) | Paytm | Paytm wallet package callback | `throttle:webhook`; provider package response object is still responsible for callback validation | High | Add Paytm package-level checksum tests before changing callback behavior. |
| `GET payment/success/{identifier}` (`payment.success`) | Browser return flow | User-browser return after inline gateways such as Paystack | Server-side gateway status verification through payment services; not treated as a signed webhook | Medium | Split browser returns from provider webhooks so response codes and security controls are explicit. |

## Paystack Controls Added

- Signature: validates `X-Paystack-Signature` with `hash_hmac('sha512', raw_body, gateway_secret)`.
- Secret source: uses the existing `payment_gateways.keys` secret for the routed Paystack gateway; no webhook secret is committed to code.
- Timestamp: optional `X-Sociopro-Timestamp` support is available through `PAYSTACK_WEBHOOK_REQUIRE_TIMESTAMP`; default remains `false` because Paystack's standard signature header does not include a timestamp.
- Replay/idempotency: stores a cache key from `X-Paystack-Event` when present, otherwise from the signed payload hash. Duplicate deliveries return `200 OK` without reprocessing.
- Rate limiting: keeps `throttle:webhook`.
- CSRF: only `paystack/payment/*` is exempted, and only because it now requires provider HMAC verification.
- Logging: rejection and duplicate logs include provider, route, reason, and client IP only. Payloads, signatures, and secrets are not logged.
- Response codes: invalid signatures return `401`, stale or missing required timestamps return `400`, missing gateway secrets return `503`, duplicate deliveries return `200`.

## Queueing Status

Payment side effects still run synchronously through `PaymentController::payment_success()` and legacy static model callbacks. This audit intentionally avoided moving money-flow behavior into jobs without broader regression coverage. The safe implementation order is:

1. Add contract tests for successful and failed gateway callbacks.
2. Extract `payment_success()` into a payment confirmation action.
3. Make the action idempotent at the database layer.
4. Dispatch slow side effects with `afterCommit()` jobs.

## Tests

- `tests/Feature/PaymentWebhookSecurityTest.php`
- `tests/Feature/CsrfProtectionAuditTest.php`
- `tests/Feature/ApiRateLimitAuditTest.php`
