<!-- Keep behavior unchanged unless this PR fixes a proven bug/security issue that is tested and documented. -->

## Summary

<!-- What does this change and why. Link the roadmap phase / issue. -->

## Type of change

- [ ] Bug fix
- [ ] Security fix
- [ ] Refactor (no behavior change)
- [ ] Feature
- [ ] Docs / tooling

## Testing

- [ ] Added or updated tests for the changed behavior
- [ ] `vendor/bin/pint --test` passes
- [ ] `composer analyse` (PHPStan/Larastan) passes
- [ ] `php artisan test` passes
- [ ] `npm run production` passes (if frontend changed)

## Security checklist

- [ ] No secrets committed; `.env.example` uses placeholders only
- [ ] Input validated via Form Requests / `validated()` (no `request->all()` mass assignment)
- [ ] Authorization enforced server-side (policy/gate), not only hidden in Blade
- [ ] Output escaped by default in Blade; any `{!! !!}` is sanitized
- [ ] No raw SQL string concatenation; bindings/allowlists used

## Deployment notes

<!-- Migrations, config/route cache, queue restart, rollback steps, or "none". -->
