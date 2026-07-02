<!-- Keep behavior unchanged unless this PR fixes a proven bug/security issue that is tested and documented. -->

## Summary

<!-- What does this change and why? Link the roadmap phase / issue. -->

## Type of change

- [ ] Bug fix
- [ ] Security fix
- [ ] Refactor (no behavior change)
- [ ] Feature
- [ ] Docs / tooling
- [ ] Deployment / CI

## Risk and rollback

- Risk level: Low / Medium / High
- Rollback plan:
- Migration impact: None / Additive / Destructive / Data migration
- Queue/job impact: None / Worker restart / Payload compatibility risk

## Testing

- [ ] Added or updated tests for the changed behavior
- [ ] Authorization failures covered where relevant
- [ ] Validation errors covered where relevant
- [ ] Database/storage side effects asserted where relevant
- [ ] External services faked
- [ ] `composer validate --strict --no-interaction` passes
- [ ] `composer audit --no-interaction` passes or accepted risk is documented
- [ ] `vendor/bin/pint --test` passes
- [ ] `composer analyse` passes
- [ ] `php artisan test` passes
- [ ] `npm run quality` passes when frontend/assets changed

## Security checklist

- [ ] No secrets committed; `.env.example` uses placeholders only
- [ ] Input validated via Form Requests / `validated()` / explicit field mapping
- [ ] No `$request->all()` mass assignment
- [ ] Authorization enforced server-side, not only hidden in Blade
- [ ] Output escaped by default in Blade; any raw output has sanitizer coverage
- [ ] No raw SQL string concatenation; bindings/allowlists used
- [ ] Upload/download paths are validated and tested with storage fakes
- [ ] Logs do not expose secrets, tokens, cookies, or sensitive payloads

## Deployment checklist

- [ ] No migration required
- [ ] Migrations reviewed and rollback notes documented
- [ ] `composer quality:cache` passes
- [ ] `php artisan route:list --except-vendor` succeeds
- [ ] `php artisan storage:link` impact reviewed
- [ ] Queue restart documented when needed
- [ ] Scheduler impact documented when needed
- [ ] Smoke checks listed

## Documentation

- [ ] README/docs updated
- [ ] `docs/known-technical-debt.md` updated for deferred risk
- [ ] Deployment/rollback/backup notes updated when operations changed
