# API Idempotency

Date: 2026-07-02

## Contract

Retryable API writes may accept an `Idempotency-Key` header. The key must be 8 to 128 printable ASCII characters without spaces.

When a supported endpoint receives a new key, it stores the JSON response for the authenticated actor, route, request payload, and uploaded-file metadata. A later retry with the same actor, route, key, and payload replays the stored response and does not run the write again.

Response headers:

- `Idempotency-Replayed: false` means the request ran normally and was recorded.
- `Idempotency-Replayed: true` means a cached response was replayed.

Reusing the same key with a different payload returns `409 CONFLICT` with the standard API error shape.

## Current Coverage

| Endpoint | Risk Covered | Status |
| --- | --- | --- |
| `POST /api/create_marketplace` (`api.marketplace.store`) | Duplicate marketplace products and duplicate upload side effects during client retries. | Protected with `Idempotency-Key`. |

## Implementation Notes

- The implementation uses Laravel cache atomic locks and a cache-backed response store.
- Default TTL is `API_IDEMPOTENCY_TTL_MINUTES=1440`.
- Lock timing is controlled by `API_IDEMPOTENCY_LOCK_SECONDS` and `API_IDEMPOTENCY_WAIT_SECONDS`.
- The idempotency scope is per authenticated actor, operation, and request key.

## Next Safe Targets

1. Payment and webhook callbacks once provider event IDs/signatures are audited.
2. Chat message creation to prevent duplicate messages on mobile retry.
3. Post/media creation to prevent duplicate media writes.
4. Import/backfill job enqueue endpoints when exposed over HTTP.
