# HTTP Security Headers

Date: 2026-07-02

## Current Policy

The global `SecurityHeaders` middleware emits browser hardening headers on every web and API response:

- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: SAMEORIGIN`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(self), fullscreen=(self)`
- `Content-Security-Policy` with `frame-ancestors 'self'`, `object-src 'none'`, and self-first defaults.
- `Strict-Transport-Security` is emitted only on HTTPS requests.

The policy is configured in `config/security_headers.php`. Runtime code must read these values through `config()`.

## Tested Pages

Automated middleware coverage verifies headers on:

- `/login`
- `/api/login`
- `/live/{post_id}`
- Direct secure and insecure middleware requests for HSTS behavior.

## Compatibility Exceptions

The legacy Blade frontend still depends on inline scripts and styles, so CSP temporarily allows `inline scripts and styles` through `'unsafe-inline'`. Several older JavaScript widgets also require `'unsafe-eval'`. Remove these only after replacing inline handlers and legacy widgets with compiled assets.

The payment provider scripts are intentionally allowed through HTTPS script/connect/frame directives because payment views load Razorpay, PayPal, Paystack, and Flutterwave browser SDKs.

Zoom live video needs camera and microphone access on `/live/{post_id}`. That route receives a narrower documented permissions exception: `camera=(self), microphone=(self)`, plus CSP support for HTTPS/WSS connections and blob workers/media.

Product and shared-post previews are rendered in same-origin iframes. `X-Frame-Options: SAMEORIGIN` and `frame-ancestors 'self'` preserve those flows while blocking third-party framing.

HSTS is emitted only on HTTPS requests. Enable preload only after production is HTTPS-only across the apex domain and every subdomain.

## Safe Tightening Order

1. Move inline event handlers and scripts into compiled assets.
2. Remove `'unsafe-eval'` after legacy widgets no longer require it.
3. Replace broad `https:` CSP sources with explicit provider hostnames.
4. Add CSP report-only monitoring before removing compatibility exceptions.
5. Enable HSTS preload only after a production HTTPS/subdomain audit.
