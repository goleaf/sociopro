# API Error Format

## Scope

This project still exposes a legacy unversioned API. Existing mobile and public clients expect many failed requests to return HTTP 200 with top-level `success` and `message` fields, so refactors must preserve those fields unless a versioned client migration proves a safer change.

New API error responses should add a machine-readable `error` envelope without removing legacy keys:

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

`error.http_status` is the canonical HTTP meaning. The actual transport status may remain `200` on the legacy unversioned API when required for backwards compatibility.

## Categories

| Category | Code | Canonical Status | Use |
| --- | --- | --- | --- |
| validation | `VALIDATION_ERROR` | 422 | Request body, query string, route parameter, or file input failed validation. |
| authentication | `AUTHENTICATION_ERROR` | 401 | Bearer token is missing, invalid, expired, revoked, or not owned by the user. |
| authorization | `AUTHORIZATION_ERROR` | 403 | Authenticated user cannot perform the action or access the resource. |
| not_found | `NOT_FOUND` | 404 | Requested model or route-scoped resource does not exist. |
| conflict | `CONFLICT` | 409 | Request conflicts with current state, such as duplicate or already-processed work. |
| rate_limit | `RATE_LIMITED` | 429 | Throttle or abuse protection blocked the request. Include retry details when available. |
| domain | `DOMAIN_ERROR` | 422 | Business rule failed even though the request shape was valid. |
| server | `SERVER_ERROR` | 500 | Unexpected failure. Do not expose exception messages, stack traces, SQL, secrets, or file paths. |

Validation responses keep the legacy `validationError` key and duplicate those errors into `error.details`:

```json
{
  "success": false,
  "message": "Validation failed",
  "validationError": {
    "title": ["The title field is required."]
  },
  "error": {
    "code": "VALIDATION_ERROR",
    "category": "validation",
    "message": "Validation failed",
    "http_status": 422,
    "details": {
      "title": ["The title field is required."]
    }
  }
}
```

## Notification API

The notification index response shape remains unchanged on success:

```json
{
  "new_notifications": [],
  "older_notifications": []
}
```

Notification errors now use the standard envelope while keeping legacy top-level fields. For example, `mark_as_read` returns `NOT_FOUND` when the notification ID does not exist and `AUTHORIZATION_ERROR` when an authenticated user tries to mark another user's notification as read.
