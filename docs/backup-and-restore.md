# Backup And Restore Runbook

Generated: 2026-07-02

This project still has legacy schema risk because the canonical install schema is the SQL dump plus additive migrations. Treat backup verification as a release prerequisite for schema, upload, payment, settings, import/export, or storage changes.

## Backup Scope

- Database: full schema/data dump or point-in-time recovery snapshot.
- Uploaded files: `public/storage` and `storage/app`.
- Environment/config: production `.env` values in the host secret manager, not git.
- Built assets: previous release artifact or reproducible `npm ci && npm run production` output.
- Operational records: deploy commit, migration batch, queue worker version, scheduler version, PHP/Node/npm versions, and Composer/package lock hashes.

## Database Backup

Use the database platform's native backup tooling. Examples:

```bash
# MySQL-compatible example; adapt host, port, TLS, and credentials to the platform secret store.
mysqldump --single-transaction --routines --triggers --set-gtid-purged=OFF "$DB_DATABASE" > sociopro-$(date +%Y%m%d%H%M%S).sql
```

For SQLite local/test environments:

```bash
cp database/database.sqlite backups/database-$(date +%Y%m%d%H%M%S).sqlite
```

Never store production backups in the repository.

## File Storage Backup

Back up both public and private storage locations:

```bash
tar -czf sociopro-storage-$(date +%Y%m%d%H%M%S).tar.gz public/storage storage/app
```

For S3/object storage, use bucket versioning, lifecycle rules, or provider snapshots. Record the bucket, prefix, and restore point.

## Retention

Recommended baseline until the hosting platform defines stricter rules:

- Hourly point-in-time recovery for at least 24 hours.
- Daily backups for at least 14 days.
- Weekly backups for at least 8 weeks.
- Monthly backups for at least 12 months when compliance or business requirements need it.

Adjust retention for privacy/data-deletion obligations.

## Encryption And Access Control

- Encrypt backups at rest and in transit.
- Restrict backup read/delete permissions to the smallest operational group.
- Store database credentials in the host secret manager.
- Do not copy production personal data to local laptops unless explicitly approved and protected.
- Redact secrets and personal data from restore-test notes.

## Before A Risky Deploy

- Capture database backup and verify the backup exists.
- Capture file-storage backup or snapshot.
- Record application commit, migration batch, PHP version, Composer lock hash, Node/npm versions, and asset build command.
- Record restore owner and escalation path.
- Verify enough disk/object-store space exists for backup and restore operations.

## Restore Procedure

1. Stop writes or put the app in maintenance mode.
2. Snapshot the broken state for investigation before overwriting it.
3. Restore database to a staging clone first when time allows.
4. Restore file storage if uploaded files or generated media are part of the incident.
5. Deploy code matching the restored database schema.
6. Run `php artisan optimize:clear`.
7. Rebuild config, route, and view caches.
8. Restart queue workers.
9. Run smoke checks.
10. Reopen writes only after data and file checks pass.

## Restore Test

At least once per release cycle, run a non-production restore test:

- Restore the latest database backup into an isolated environment.
- Restore a representative sample of public/private uploads.
- Run `php artisan migrate:status`.
- Run `php artisan route:list --except-vendor`.
- Confirm login, timeline, profile media, marketplace media, chat media, job attachments, and payment settings screens load.
- Record duration, missing prerequisites, and any manual corrections.

## Data Integrity Checks

- Compare counts for users, posts, media files, marketplace products, job applications, payments, and settings.
- Confirm recent uploaded files referenced by `media_files`, marketplace images, chat attachments, and job applications exist on disk.
- Confirm private job attachments are not publicly web-accessible.
- Confirm removed generated-image provider settings have not been restored as active browser-facing fields.
- Confirm failed jobs and scheduled tasks are compatible with the restored code.

## Known Gaps

- Production backup tooling and retention policy are not stored in this repo.
- Real production orphan/duplicate/nullability data quality is unknown.
- Schema rollback for legacy dump-derived tables may require manual forward fixes.
- No automated restore-test CI job exists because it requires production-like backup artifacts and isolated infrastructure.
