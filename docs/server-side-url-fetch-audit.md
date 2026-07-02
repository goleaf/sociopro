# Server-Side URL Fetch Audit

## Scope

Audited code paths that fetch URLs, callbacks, imports, feeds, images, and provider endpoints from application code.

## User-Influenced Fetches

| Surface | Current status | Controls |
| --- | --- | --- |
| Link preview metadata via `get_url_contents()` | Allowed for public web previews | Central `ServerSideUrl` guard requires HTTP(S), no userinfo credentials, configured host allowlist, public DNS/IP resolution, no private/reserved IPs, no redirect following, timeout, user agent, and response byte limit. |
| Uploaded/imported add-on packages | Local uploaded zip/files only | No remote URL fetch. Zip traversal protections are covered by add-on import tests. |
| Uploaded images/videos/files | Multipart/local temp files only | No remote URL fetch. Upload MIME/extension/size checks are covered by upload tests. |

## Fixed Provider Fetches

| Surface | Host control | Timeout |
| --- | --- | --- |
| Zoom meetings | Fixed `https://api.zoom.us/v2/` base URL | `10s` |
| PayPal status checks | Fixed PayPal sandbox/production API base URLs | `10s` |
| Paystack status checks | Fixed `https://api.paystack.co/transaction/verify/` URL prefix | `10s` |

## Configuration

Server-side URL fetch controls live in `config/security.php`:

| Key | Default | Notes |
| --- | --- | --- |
| `SERVER_SIDE_URL_ALLOWED_HOSTS` | `*` | Preserves public link previews. Use comma-separated exact hosts, IPs, or wildcard subdomains such as `example.com,*.example.com` to restrict production. |
| `SERVER_SIDE_URL_ALLOWED_SCHEMES` | `http,https` | Do not add `file`, `ftp`, `gopher`, or other local-capable schemes. |
| `SERVER_SIDE_URL_TIMEOUT_SECONDS` | `5` | Bounds slow external pages. |
| `SERVER_SIDE_URL_MAX_REDIRECTS` | `0` | Redirect following remains disabled to avoid redirect-to-private-IP bypasses. |
| `SERVER_SIDE_URL_MAX_RESPONSE_BYTES` | `1048576` | Limits preview HTML reads to 1 MiB by default. |
| `SERVER_SIDE_URL_USER_AGENT` | `SocioproLinkPreview/1.0` | Stable outbound identifier for logs and provider blocks. |

## Deferred Risks

- DNS rebinding still has a time-of-check/time-of-use gap because PHP resolves DNS again during `file_get_contents()`. For high-risk remote ingestion, replace direct streams with an HTTP client that pins the resolved IP or route through an egress proxy/firewall.
- Public link previews intentionally allow arbitrary public hosts by default. Production can tighten `SERVER_SIDE_URL_ALLOWED_HOSTS` when the product accepts a smaller preview domain set.
- Payment callbacks/webhooks are inbound provider requests, not server-side URL fetches. Signature and replay verification remain tracked separately in the payment security debt.
