# Marketplace API Documentation

Date: 2026-07-02

## Scope

This document describes the current legacy marketplace API implemented in `routes/api.php` and `App\Http\Controllers\ApiController`.

The marketplace API is an authenticated, unversioned legacy API under `/api/*`. Existing mobile clients may depend on current URLs, response keys, transport statuses, and messages, so this document records the live contract rather than a redesigned REST contract.

## Base URL And Versioning

| Item | Current value |
| --- | --- |
| Base path | `/api` |
| Version prefix | None |
| Route name prefix | `api.` |
| Compatibility status | Legacy unversioned API |

Versioning notes:

- Keep these `/api/*` URLs available until a tested client migration proves they can be deprecated.
- Add future versioned routes beside this surface, such as `/api/v1/*`, not by moving these existing routes.
- Preserve existing response fields and legacy error behavior unless a security fix or versioned migration explicitly changes them.
- Follow `docs/api-versioning.md` before introducing breaking API changes.

## Authentication

All marketplace endpoints are inside the protected API group:

- Middleware: `api.token`
- Rate limiter: `throttle:api-authenticated`
- Auth guard behavior: Laravel Sanctum personal access token resolved from the bearer token.

Required request header:

```http
Authorization: Bearer <sanctum-personal-access-token>
```

Do not document, log, commit, or paste real bearer tokens. All examples in this file use placeholders.

Write endpoint token abilities:

| Endpoint | Required token ability |
| --- | --- |
| `POST /api/create_marketplace` | `marketplace:create` |
| `POST /api/update_marketplace/{id}` | `marketplace:update` |
| `POST /api/delete_marketplace/{product_id}` | `marketplace:delete` |

Read/list/save endpoints require a valid bearer token but do not currently declare a separate marketplace token ability in the current code.

## Common Headers

| Header | Required | Applies to | Notes |
| --- | --- | --- | --- |
| `Accept: application/json` | Recommended | All endpoints | Keeps client expectations explicit. |
| `Authorization: Bearer <sanctum-personal-access-token>` | Yes | All endpoints | Placeholder only; never expose a real token in docs or tests. |
| `Content-Type: application/json` | Yes for JSON writes | Create/update without files | Use JSON payloads for non-file requests. |
| `Content-Type: multipart/form-data` | Yes for uploads | Create/update with `multiple_files` | Required when sending image files. |
| `Idempotency-Key: <unique-key>` | Optional | `POST /api/create_marketplace` | Retry protection for create requests. |

## Rate Limits

| Limiter | Marketplace usage | Ceiling | Keying |
| --- | --- | ---: | --- |
| `api-authenticated` | All protected marketplace endpoints | 120 requests/minute | Authenticated user, bearer token hash, or IP |
| `api-expensive` | `GET /api/marketplace` | 30 requests/minute | User/client plus route |
| `api-search` | `GET /api/filter` | 20 requests/minute | User/client plus route |
| `api` | Global API fallback | 60 requests/minute | Authenticated user, bearer token hash, or IP |

Rate-limit failures return HTTP 429 with the standard API error envelope:

```json
{
  "success": false,
  "message": "Too many requests",
  "error": {
    "code": "RATE_LIMITED",
    "category": "rate_limit",
    "message": "Too many requests",
    "http_status": 429,
    "details": {
      "retry_after": 60
    }
  }
}
```

Clients should also inspect the `Retry-After` response header when present.

## Endpoint Summary

| Method | Path | Route name | Purpose | Extra limiter |
| --- | --- | --- | --- | --- |
| `GET` | `/api/marketplace` | `api.marketplace.index` | List marketplace products | `api-expensive` |
| `GET` | `/api/filter` | `api.marketplace.filter` | Filter/search marketplace products | `api-search` |
| `GET` | `/api/marketplace_category` | `api.marketplace.categories.index` | List marketplace categories | None |
| `GET` | `/api/marketplace_brand` | `api.marketplace.brands.index` | List marketplace brands | None |
| `GET` | `/api/currencies` | `api.currencies.index` | List currencies | None |
| `POST` | `/api/create_marketplace` | `api.marketplace.store` | Create a marketplace product | None |
| `POST` | `/api/update_marketplace/{id}` | `api.marketplace.update` | Update a marketplace product | None |
| `POST` | `/api/delete_marketplace/{product_id}` | `api.marketplace.destroy` | Delete a marketplace product | None |
| `POST` | `/api/save_for_later/{id}` | `api.marketplace.saves.store` | Toggle saved product state | None |
| `POST` | `/api/unsave_for_later/{id}` | `api.marketplace.saves.destroy` | Remove saved product state | None |

## Marketplace Object

Marketplace list responses use this legacy object shape:

```json
{
  "id": 123,
  "thrade": 0,
  "user_id": 45,
  "user": "Example User",
  "photo": "https://example.test/public/storage/userimage/default.png",
  "title": "Used camera",
  "price": "25.50",
  "category_id": "1",
  "status_id": "1",
  "brand_id": "2",
  "currency_id": 1,
  "condition": "used",
  "status": "1",
  "category": "Electronics",
  "brand": "Acme",
  "currency": "EUR",
  "is_Saved": "not_saved",
  "my_product": "not_my_product",
  "description": "A clean example listing.",
  "location": "Vilnius",
  "coverphoto": "https://example.test/public/storage/marketplace/coverphoto/example.jpg",
  "created_at": "02-07-2026"
}
```

Legacy notes:

- `thrade` is the current response key spelling.
- `is_Saved` returns the strings `saved` or `not_saved`.
- `my_product` returns the strings `my_product` or `not_my_product`.
- `created_at` is formatted as `dd-mm-YYYY`, not ISO 8601.
- The response includes relationship names as strings, not nested objects.

## Pagination

`GET /api/marketplace` and `GET /api/filter` use `simplePaginate`.

Default pagination:

| Parameter | Default | Limit |
| --- | ---: | ---: |
| `page` | `1` | Minimum `1` |
| `per_page` | `20` | Maximum `100` |
| `sort` | `id` | Allowed: `id`, `created_at`, `price`, `title` |
| `direction` | `desc` | Allowed: `asc`, `desc` |

Pagination response headers:

| Header | Meaning |
| --- | --- |
| `X-Pagination-Current-Page` | Current page number |
| `X-Pagination-Per-Page` | Page size |
| `X-Pagination-Has-More-Pages` | `true` or `false` |
| `X-Pagination-Next-Page-Url` | Present when a next page exists |
| `X-Pagination-Prev-Page-Url` | Present when a previous page exists |
| `Link` | RFC-style `first`, `prev`, and `next` links when available |

By default, list endpoints preserve the legacy array response shape:

```json
[
  {
    "id": 123,
    "title": "Used camera",
    "price": "25.50"
  }
]
```

When `include_pagination=true`, the response includes an envelope:

```json
{
  "marketplaces": [
    {
      "id": 123,
      "title": "Used camera",
      "price": "25.50"
    }
  ],
  "links": {
    "first": "https://example.test/api/filter?page=1",
    "prev": null,
    "next": "https://example.test/api/filter?page=2"
  },
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "from": 1,
    "to": 20,
    "has_more_pages": true,
    "sort": "id",
    "direction": "desc"
  }
}
```

## `GET /api/marketplace`

Lists marketplace products.

### Query Parameters

This endpoint accepts the shared pagination and sorting parameters:

| Parameter | Type | Validation |
| --- | --- | --- |
| `page` | integer | Optional, minimum `1` |
| `per_page` | integer | Optional, minimum `1`, maximum `100` |
| `sort` | string | Optional, one of `id`, `created_at`, `price`, `title` |
| `direction` | string | Optional, one of `asc`, `desc`; normalized to lowercase |
| `include_pagination` | boolean | Optional |

### Example Request

```http
GET /api/marketplace?page=1&per_page=20&sort=id&direction=desc HTTP/1.1
Accept: application/json
Authorization: Bearer <sanctum-personal-access-token>
```

### Success Response

HTTP 200:

```json
[
  {
    "id": 123,
    "thrade": 0,
    "user_id": 45,
    "user": "Example User",
    "photo": "https://example.test/public/storage/userimage/default.png",
    "title": "Used camera",
    "price": "25.50",
    "category_id": "1",
    "status_id": "1",
    "brand_id": "2",
    "currency_id": 1,
    "condition": "used",
    "status": "1",
    "category": "Electronics",
    "brand": "Acme",
    "currency": "EUR",
    "is_Saved": "not_saved",
    "my_product": "not_my_product",
    "description": "A clean example listing.",
    "location": "Vilnius",
    "coverphoto": "https://example.test/public/storage/marketplace/coverphoto/example.jpg",
    "created_at": "02-07-2026"
  }
]
```

### Empty Legacy Response

When no products are found and `include_pagination` is not enabled:

```json
{
  "success": false,
  "message": "No marketplace found"
}
```

## `GET /api/filter`

Filters and searches active marketplace products. The current query only returns products where `status = 1`.

### Flat Query Parameters

| Parameter | Type | Validation | Behavior |
| --- | --- | --- | --- |
| `search` | string | Optional, max `120` chars | Searches title and description with escaped `LIKE`. |
| `category` | integer | Optional, exists in `categories.id` | Exact category match. |
| `condition` | string | Optional, one of `new`, `used` | Exact condition match. |
| `min` | decimal | Optional, 0-2 decimal places, min `0`, max `9999999999.99` | Minimum price. |
| `max` | decimal | Optional, 0-2 decimal places, min `0`, max `9999999999.99`, `gte:min` | Maximum price. |
| `brand` | integer | Optional, exists in `brands.id` | Exact brand match. |
| `location` | string | Optional, max `255` chars | Searches location with escaped `LIKE`. |
| `date_from` | date | Optional browser date format | Minimum `created_at` date. |
| `date_to` | date | Optional browser date format, after or equal to `date_from` | Maximum `created_at` date. |
| `sort` | string | Optional, one of `id`, `created_at`, `price`, `title` | Primary sort. |
| `direction` | string | Optional, one of `asc`, `desc` | Sort direction. |
| `page` | integer | Optional, minimum `1` | Page number. |
| `per_page` | integer | Optional, minimum `1`, maximum `100` | Page size. |
| `include_pagination` | boolean | Optional | Enables pagination envelope. |

### Nested Query Parameters

The endpoint also accepts a nested `filters` shape:

| Parameter | Type | Validation |
| --- | --- | --- |
| `filters[search]` | string | Optional, max `120` chars |
| `filters[category]` | integer | Optional, exists in `categories.id` |
| `filters[condition]` | string | Optional, one of `new`, `used` |
| `filters[brand]` | integer | Optional, exists in `brands.id` |
| `filters[location]` | string | Optional, max `255` chars |
| `filters[price][min]` | decimal | Optional, 0-2 decimal places, min `0`, max `9999999999.99` |
| `filters[price][max]` | decimal | Optional, 0-2 decimal places, min `0`, max `9999999999.99`, `gte:filters.price.min` |
| `filters[created_between][from]` | date | Optional browser date format |
| `filters[created_between][to]` | date | Optional browser date format, after or equal to `filters.created_between.from` |

Flat parameters take precedence over nested `filters` values when both are present.

### Example Request

```http
GET /api/filter?search=camera&condition=used&min=10&max=100&sort=price&direction=asc&include_pagination=true HTTP/1.1
Accept: application/json
Authorization: Bearer <sanctum-personal-access-token>
```

### Success Response

HTTP 200:

```json
{
  "marketplaces": [
    {
      "id": 123,
      "thrade": 0,
      "user_id": 45,
      "user": "Example User",
      "photo": "https://example.test/public/storage/userimage/default.png",
      "title": "Used camera",
      "price": "25.50",
      "category_id": "1",
      "status_id": "1",
      "brand_id": "2",
      "currency_id": 1,
      "condition": "used",
      "status": "1",
      "category": "Electronics",
      "brand": "Acme",
      "currency": "EUR",
      "is_Saved": "not_saved",
      "my_product": "not_my_product",
      "description": "A clean example listing.",
      "location": "Vilnius",
      "coverphoto": "https://example.test/public/storage/marketplace/coverphoto/example.jpg",
      "created_at": "02-07-2026"
    }
  ],
  "links": {
    "first": "https://example.test/api/filter?page=1",
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "from": 1,
    "to": 1,
    "has_more_pages": false,
    "sort": "price",
    "direction": "asc"
  }
}
```

## `GET /api/marketplace_category`

Lists marketplace categories ordered by descending `id`.

### Example Response

HTTP 200:

```json
[
  {
    "category_id": 1,
    "category": "Electronics"
  }
]
```

Empty response:

```json
{
  "success": false,
  "message": "No category found"
}
```

## `GET /api/marketplace_brand`

Lists marketplace brands ordered by descending `id`.

### Example Response

HTTP 200:

```json
[
  {
    "category_id": 2,
    "category": "Acme"
  }
]
```

Legacy note: the brand endpoint currently returns `category_id` and `category` keys, not `brand_id` and `brand`.

## `GET /api/currencies`

Lists currencies ordered by descending `id`.

### Example Response

HTTP 200:

```json
[
  {
    "category_id": 1,
    "category": "EUR"
  }
]
```

Legacy note: the currency endpoint currently returns `category_id` and `category` keys, not `currency_id` and `currency`.

## `POST /api/create_marketplace`

Creates a marketplace product for the authenticated user.

### Authorization

Requires both:

- A valid bearer token.
- Token ability `marketplace:create`.
- Marketplace `create` policy approval.

### Idempotency

This endpoint supports retry protection with the `Idempotency-Key` header.

Rules:

- Optional header.
- Must be 8 to 128 printable ASCII characters.
- Spaces are not allowed.
- A repeated key with the exact same request replays the stored response.
- A repeated key with a different payload returns HTTP 409.

Response headers:

| Header | Meaning |
| --- | --- |
| `Idempotency-Replayed: false` | Request executed and response was recorded. |
| `Idempotency-Replayed: true` | Response was replayed from the idempotency cache. |

Default configuration:

| Config key | Environment key | Default |
| --- | --- | --- |
| `api.idempotency.ttl_minutes` | `API_IDEMPOTENCY_TTL_MINUTES` | `1440` |
| `api.idempotency.lock_seconds` | `API_IDEMPOTENCY_LOCK_SECONDS` | `10` |
| `api.idempotency.wait_seconds` | `API_IDEMPOTENCY_WAIT_SECONDS` | `3` |

### Request Schema

Use JSON for metadata-only create requests, or multipart form data when sending images.

| Field | Type | Required | Validation |
| --- | --- | --- | --- |
| `title` | string | Yes | Max `255` chars |
| `price` | decimal | Yes | Numeric, 0-2 decimal places, min `0`, max `9999999999.99` |
| `location` | string | Yes | Max `255` chars |
| `category` | integer | Yes | Exists in `categories.id` |
| `condition` | string | Yes | One of `new`, `used` |
| `status` | integer | Yes | One of `0`, `1` |
| `brand` | integer | Yes | Exists in `brands.id` |
| `currency` | integer | No | Exists in `currencies.id` |
| `buy_link` | URL string | No | Valid URL, max `2048` chars |
| `description` | string | No | Max `5000` chars |
| `multiple_files` | file array | No | Max `10` files |
| `multiple_files.*` | image file | No | `jpeg`, `jpg`, `png`, `gif`, or `webp`; max `5120` KB; max `4096x4096` pixels |

### Example JSON Request

```http
POST /api/create_marketplace HTTP/1.1
Accept: application/json
Authorization: Bearer <sanctum-personal-access-token>
Content-Type: application/json
Idempotency-Key: marketplace-create-example-001
```

```json
{
  "title": "Used camera",
  "price": "25.50",
  "location": "Vilnius",
  "category": 1,
  "condition": "used",
  "status": 1,
  "brand": 2,
  "currency": 1,
  "buy_link": "https://example.com/products/used-camera",
  "description": "A clean example listing."
}
```

### Success Response

HTTP 200:

```json
{
  "success": true,
  "message": "Marketplace created successfully"
}
```

## `POST /api/update_marketplace/{id}`

Updates an existing marketplace product.

### Authorization

Requires:

- A valid bearer token.
- Token ability `marketplace:update`.
- Marketplace `update` policy approval for the target product.

### Path Parameters

| Parameter | Type | Validation |
| --- | --- | --- |
| `id` | integer | Required, exists in `marketplaces.id` |

### Request Schema

The body uses the same validation rules as create. All create-required fields are also required for update.

Legacy note: `buy_link` is currently validated for update but the controller does not persist it during update. Treat it as a legacy no-op on this endpoint until the behavior is fixed with tests.

### Example Request

```http
POST /api/update_marketplace/123 HTTP/1.1
Accept: application/json
Authorization: Bearer <sanctum-personal-access-token>
Content-Type: application/json
```

```json
{
  "title": "Updated used camera",
  "price": "30.00",
  "location": "Vilnius",
  "category": 1,
  "condition": "used",
  "status": 1,
  "brand": 2,
  "currency": 1,
  "description": "Updated listing text."
}
```

### Success Response

HTTP 200:

```json
{
  "success": true,
  "message": "update successfully"
}
```

## `POST /api/delete_marketplace/{product_id}`

Deletes a marketplace product and attempts to remove associated media files.

### Authorization

Requires:

- A valid bearer token.
- Token ability `marketplace:delete`.
- Marketplace `delete` policy approval for the target product.

### Path Parameters

| Parameter | Type | Validation |
| --- | --- | --- |
| `product_id` | integer | No Form Request rule; controller checks whether the product exists. |

### Example Request

```http
POST /api/delete_marketplace/123 HTTP/1.1
Accept: application/json
Authorization: Bearer <sanctum-personal-access-token>
```

### Success Response

HTTP 200:

```json
{
  "alertMessage": "Product Deleted Successfully",
  "fadeOutElem": "#product-123"
}
```

### Not Found Legacy Response

HTTP 200:

```json
{
  "alertMessage": "Product not found",
  "fadeOutElem": ""
}
```

## `POST /api/save_for_later/{id}`

Toggles the saved state for a product for the authenticated user.

### Path Parameters

| Parameter | Type | Validation |
| --- | --- | --- |
| `id` | integer | No Form Request rule; used as `saved_products.product_id`. |

### Example Request

```http
POST /api/save_for_later/123 HTTP/1.1
Accept: application/json
Authorization: Bearer <sanctum-personal-access-token>
```

### Saved Response

HTTP 200:

```json
{
  "success": true,
  "message": "User saved the product"
}
```

### Toggled-Off Response

If the product was already saved, this same endpoint deletes the saved record:

```json
{
  "success": false,
  "message": "product unsave successfully"
}
```

## `POST /api/unsave_for_later/{id}`

Removes the authenticated user's saved-product row for the target product.

### Path Parameters

| Parameter | Type | Validation |
| --- | --- | --- |
| `id` | integer | No Form Request rule; used as `saved_products.product_id`. |

### Example Request

```http
POST /api/unsave_for_later/123 HTTP/1.1
Accept: application/json
Authorization: Bearer <sanctum-personal-access-token>
```

### Success Response

HTTP 200:

```json
{
  "success": true,
  "message": "unsave successfully"
}
```

### Missing Save Legacy Response

HTTP 200:

```json
{
  "success": false,
  "message": "Failed to unsave"
}
```

## Error Responses

The legacy API mixes modern error envelopes with legacy transport behavior. For compatibility, validation and authentication can return HTTP 200 while still reporting the canonical status inside `error.http_status`.

### Authentication Failure

Missing, invalid, expired, revoked, or non-owned bearer tokens return HTTP 200 from the protected API middleware:

```json
{
  "success": false,
  "message": "Unauthorized access",
  "error": {
    "code": "AUTHENTICATION_ERROR",
    "category": "authentication",
    "message": "Unauthorized access",
    "http_status": 401,
    "details": []
  }
}
```

### Authorization Failure

Policy or token ability failures return HTTP 403:

```json
{
  "message": "This action is unauthorized."
}
```

Some newer API endpoints may add the standard `AUTHORIZATION_ERROR` envelope. Verify the exact response before changing clients.

### Validation Failure

Form Request validation failures return HTTP 200 on the legacy API:

```json
{
  "success": false,
  "message": "Validation failed",
  "validationError": {
    "price": [
      "The marketplace price must be a number."
    ]
  },
  "error": {
    "code": "VALIDATION_ERROR",
    "category": "validation",
    "message": "Validation failed",
    "http_status": 422,
    "details": {
      "price": [
        "The marketplace price must be a number."
      ]
    }
  }
}
```

### Idempotency Conflict

Reusing an idempotency key with a different payload returns HTTP 409:

```json
{
  "success": false,
  "message": "Idempotency key was already used with a different request.",
  "error": {
    "code": "CONFLICT",
    "category": "conflict",
    "message": "Idempotency key was already used with a different request.",
    "http_status": 409,
    "details": []
  }
}
```

### Invalid Idempotency Key

Invalid idempotency keys return HTTP 422:

```json
{
  "success": false,
  "message": "Validation failed",
  "validationError": {
    "idempotency_key": [
      "The idempotency key must be 8 to 128 printable ASCII characters without spaces."
    ]
  },
  "error": {
    "code": "VALIDATION_ERROR",
    "category": "validation",
    "message": "Validation failed",
    "http_status": 422,
    "details": {
      "idempotency_key": [
        "The idempotency key must be 8 to 128 printable ASCII characters without spaces."
      ]
    }
  }
}
```

## Security Notes

- Never expose real bearer tokens, idempotency keys, user emails, uploaded file names from production, or provider secrets in API examples.
- Do not rely on client-side hiding for marketplace create/update/delete authorization.
- Keep write clients prepared for HTTP 403 when token abilities or policies deny access.
- Treat all uploaded marketplace files as untrusted. The current validation allows only common image types with size and dimension limits.
- Avoid logging full request bodies for create/update requests because descriptions, locations, URLs, and filenames may contain personal data.

## Known Compatibility Risks

| Risk | Impact | Safe first step |
| --- | --- | --- |
| Legacy verb-style URLs such as `/api/create_marketplace` | Cannot be renamed without breaking clients. | Add future REST-style/versioned routes in parallel only after tests. |
| Validation/authentication can return HTTP 200 | Clients may depend on legacy status behavior. | Preserve top-level legacy keys and add machine-readable `error` fields only additively. |
| Brand and currency lookup endpoints return `category_id` and `category` keys | Renaming keys would break clients. | Add corrected keys only as additive fields in a versioned or tested compatibility change. |
| Update validates `buy_link` but does not persist it | Clients may think `buy_link` changed when it did not. | Add tests that define intended behavior before fixing. |
