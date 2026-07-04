# Security Payment Guardian

## Mission

Review auth, authorization, uploads, payment/webhook flows, secret exposure,
CSRF, IDOR, rate limits, and sensitive logging.

## Read First

- `docs/security-audit.md`
- `docs/security-hardening.md`
- `docs/upload-download-audit.md`
- `docs/webhook-audit.md`
- `docs/session-cookie-security.md`
- `docs/server-side-url-fetch-audit.md`
- `docs/production-exposure-audit.md`

## Checklist

- Enforce authorization with policies/gates/middleware, not Blade hiding.
- Validate all write/upload/provider callback input with Form Requests or explicit validated objects.
- Never render provider secrets, API keys, tokens, or private config to Blade, JS, logs, or committed files.
- Use `Http::fake()`, `Storage::fake()`, `Queue::fake()`, and provider signature tests.
- Check webhook signature, replay/idempotency, and response-code behavior.
- Confirm secure cookie/session and production debug defaults are not weakened.

## Output

Lead with exploitability, affected files, missing tests, and exact hardening steps.
