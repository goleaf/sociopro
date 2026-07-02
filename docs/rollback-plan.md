# Rollback Plan

Generated: 2026-07-02

Rollback must preserve user data first. Prefer forward fixes for schema/data issues unless the migration is explicitly reversible and recently deployed.

## Code Rollback

- Identify the deployed commit and the previous healthy commit.
- Revert with `git revert <sha>` for shared `main` history; do not rewrite published history.
- Rebuild assets with the same Node/npm versions used for the original release.
- Redeploy the reverted commit and clear caches:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Database Rollback

- For additive index/config migrations, rollback may be safe with `php artisan migrate:rollback --step=1 --force` after confirming the latest batch.
- For destructive, type-changing, or data migrations, do not rollback blindly. Use a forward fix or restore affected rows from backup.
- Before rolling back a migration, record:
  - migration file path,
  - deployed batch number,
  - affected tables,
  - whether `down()` is non-destructive,
  - backup timestamp,
  - expected user-visible impact.

## Asset Rollback

- Restore the previous built assets from the previous release artifact when possible.
- If assets are built on the host, run `npm ci && npm run production` from the rollback commit.
- Clear CDN/proxy caches only for changed asset paths.

## Queue and Scheduler Rollback

- Restart queue workers after code rollback.
- Pause workers before rolling back code that changes queued job payload shape.
- Inspect failed jobs after rollback; retry only idempotent jobs.
- Confirm scheduler tasks still point to the rollback code path.

## Smoke Checks After Rollback

- Login and authenticated homepage.
- Create post with image upload.
- Marketplace create with image upload.
- Job attachment download by admin.
- Private media access denied to non-owner.
- Admin settings page loads without removed generated-image fields.
- `storage/logs` has no fresh fatal errors.
