# Rollback Plan

Generated: 2026-07-02

Rollback must preserve user data first. Prefer a forward fix for schema/data issues unless the migration is explicitly reversible, recently deployed, and covered by a verified backup.

## First Response

- Stop or pause the deployment pipeline.
- Identify the deployed commit, previous healthy commit, migration batch, asset artifact, and deploy timestamp.
- Check `storage/logs`, failed jobs, web server errors, and external provider dashboards.
- Decide whether the incident is code-only, asset-only, config-only, queue-only, database, storage, or mixed.
- If data may be lost or corrupted, snapshot the broken state before changing it.

## Code Rollback

- Use `git revert <sha>` for shared `main` history. Do not rewrite published history.
- Rebuild assets with the same Node/npm versions used for the original release.
- Redeploy the reverted commit.
- Clear and rebuild Laravel caches:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Database Rollback

- Inspect the latest migration batch:

```bash
php artisan migrate:status
```

- For additive index/config migrations, rollback may be safe:

```bash
php artisan migrate:rollback --step=1 --force
```

- Do not blindly rollback destructive, type-changing, rename, or data migrations. Use a forward fix or restore affected rows from backup.
- Before any migration rollback, record:
  - migration file path,
  - deployed batch number,
  - affected tables,
  - whether `down()` is non-destructive,
  - backup timestamp,
  - expected user-visible impact,
  - verification owner.

## Irreversible Migrations

If a migration cannot be safely reversed:

- Keep the current database state.
- Deploy a forward corrective migration or application hotfix.
- Restore only affected rows/tables from backup when possible.
- Document the incident and add a debt item for future zero-downtime design.

## Queue Rollback

- Pause workers before rolling back code that changes queued job payload shape.
- Restart workers after code rollback:

```bash
php artisan queue:restart
```

- Inspect failed jobs:

```bash
php artisan queue:failed
```

- Retry only idempotent jobs. Delete or manually reconcile jobs that target removed code paths or stale payload formats.

## Cache And Config Rollback

- Always run `php artisan optimize:clear` after restoring old code/config.
- Rebuild config, route, and view caches only after confirming `.env` values match the rollback commit.
- Clear application/cache stores if a cached value caused or preserved the incident.

## Asset Rollback

- Prefer restoring the previous built asset artifact.
- If assets are built on the host, run:

```bash
npm ci
npm run production
```

- Clear CDN/proxy caches only for changed asset paths.

## Feature Flags

No first-class feature flag system is committed in this checkout. If a release adds config-driven flags, document:

- env/config key,
- default value,
- owner,
- rollback value,
- cache clearing requirement,
- user-visible behavior.

## Backups

- Use `docs/backup-and-restore.md` for database and file-storage restore steps.
- Confirm backup encryption/access control before copying production data to non-production.
- Keep restore logs and verification evidence with the incident notes.

## Smoke Checks After Rollback

- Login and authenticated homepage.
- Contact form validation and mail handoff.
- Create/edit post with image and video upload.
- Web/API chat video upload.
- Marketplace create/update with image upload.
- Job attachment download by admin.
- Private media access denied to non-owner.
- Payment configuration page loads and provider test callbacks are not replayed unexpectedly.
- `php artisan route:list --except-vendor` succeeds.
- `storage/logs` has no fresh fatal errors.
