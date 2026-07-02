# API Abuse Rate Limit Audit

## Scope

Audited public and authenticated API-adjacent abuse surfaces on 2026-07-02:
legacy mobile API routes in `routes/api.php`, browser auth routes in
`routes/auth.php`, public contact submission, search endpoints, and payment
callback-style routes.

Existing URLs, HTTP verbs, response bodies below the limit, and legacy API
status-code behavior remain unchanged. The new behavior is only reached after a
client exceeds a named limiter.

## Limiters

| Limiter | Applied to | Ceiling | Key |
| --- | --- | --- | --- |
| `api` | Existing global API group fallback | 60/min | Authenticated user, bearer token hash, or IP |
| `api-token` | `POST /api/login` token generation | 10/min | Email + IP |
| `api-registration` | `POST /api/signup` | 10/min | IP |
| `api-password-reset` | `POST /api/forgot_password` | 5/min | Email + IP |
| `api-authenticated` | Authenticated API group | 120/min | Authenticated user, bearer token hash, or IP |
| `api-search` | API marketplace filter/search | 20/min | User/client + route |
| `api-expensive` | Heavy API reads such as timeline, marketplace, notifications, chat, users, and public data bootstrap | 30/min | User/client + route |
| `login` | Browser login submit | 10/min | Email + IP |
| `registration` | Browser registration submit | 10/min | IP |
| `password-reset` | Browser password reset email submit | 5/min | Email + IP |
| `search` | Browser search endpoints | 30/min | User/client + route |
| `contact` | Public contact form submit | 5/min | Email + IP |
| `webhook` | Payment callback-style routes | 30/min | Client + route |

## Abuse Risks Covered

- Credential stuffing and API token minting through `POST /api/login`.
- Account creation bursts through API and browser registration.
- Password reset mail amplification through API and browser reset routes.
- Expensive authenticated list/search routes that can stress database filters,
  JSON membership checks, pagination, or notification/chat reads.
- Public contact form mail spam.
- Payment provider callback flooding.

## Compatibility Notes

- API throttle responses use the existing `ApiErrorResponse` envelope with
  `RATE_LIMITED` and HTTP 429.
- The previous `throttle:api` fallback remains in the API middleware group.
- Web/contact/payment throttles use Laravel's standard 429 response.
- No public URL, route name, controller method, validation shape, or successful
  payload changed in this pass.

## Follow-Ups

1. Add signed provider verification and replay protection to payment callbacks.
2. Move remaining inline API validation to Form Requests before changing error
   envelopes.
3. Add per-action idempotency keys for payment, upload, chat, and notification
   mutation endpoints.
4. Tune limits from production logs once traffic patterns are known.
