# Logging Sensitive Data Audit

Last updated: 2026-07-02

## Scope

Audited explicit application logging, exception reporting, log channel configuration, request/header/body logging patterns, payment webhook logging, Zoom integration logging, and tests that guard production debug instrumentation.

## Findings

| Area | Status | Notes |
| --- | --- | --- |
| Passwords and auth tokens | Hardened | All real log channels now use `App\Logging\SanitizeLogContext`, which redacts password, token, cookie, authorization, signature, session, and credential keys. |
| Personal data | Hardened | Common direct personal fields such as email, phone, address, and person-name fields are masked in structured context and embedded messages. Operational identifiers such as numeric IDs remain available for diagnostics. |
| Payment data | Hardened | Card/bank/payment payload keys and card-like strings are masked. Raw payment payload arrays are summarized by shape and keys instead of values. |
| Raw request payloads and headers | Hardened | Production tests prevent direct logging of `$request->all()`, empty `$request->input()`, raw headers, raw body content, and `file_get_contents()` output. |
| File contents | Hardened | File-content keys are redacted. Uploaded files are summarized by metadata only. |
| Exception reporting | Improved | Log messages and Monolog context are sanitized at the channel boundary. Existing source tests continue to reject direct logging of raw exception messages. |
| Payment webhook logs | Pass | Paystack webhook logs use stable event names and minimal context; the global sanitizer is a second defense. |
| Queueing / async logs | Watch | No queue-specific raw payload logs were found in this pass. Future jobs should log IDs and statuses, not serialized payloads. |

## Implementation

- `App\Support\Logging\SensitiveLogContext` sanitizes structured arrays, strings, uploaded files, resources, objects, and exception context.
- `App\Logging\SanitizeLogContext` registers a Monolog v3 processor for configured channels.
- `config/logging.php` applies the sanitizer tap to concrete file, Slack, Papertrail, stderr, syslog, errorlog, and emergency channels.

## Rules Going Forward

- Do not log raw requests, headers, cookies, authorization headers, uploaded files, file contents, or payment/provider payloads.
- Prefer event names plus stable IDs, route names, operation names, provider names, status codes, and sanitized error classes.
- If a payload is needed for diagnostics, log a shape summary and non-sensitive IDs only.
- Keep secrets, PII, payment values, tokens, signatures, cookies, and raw file bytes out of logs even in test/local environments.
