# Backup And Restore Runbook

Generated: 2026-07-02

This project still has legacy schema risk because the canonical install schema is the SQL dump plus additive migrations. Treat backup verification as a release prerequisite for any schema, upload, payment, or settings change.

## Backup Scope

- Database: full schema and data dump or point-in-time recovery snapshot.
- Uploaded files: `public/storage` and `storage/app` because legacy and private uploads both exist.
- Environment/config: production `.env` values stored in the host secret manager, not in git.
- Built assets: previous release artifact or reproducible `npm ci && npm run production` output.

## Before A Risky Deploy

- Capture database backup and verify it can be listed/restored in a non-production environment.
- Capture file-storage backup or snapshot.
- Record application commit, migration batch, PHP version, Composer lock hash, Node/npm versions, and asset build command.
- Record restore owner and escalation path.

## Restore Procedure

1. Stop writes or put the app in maintenance mode.
2. Snapshot the broken state for investigation before overwriting it.
3. Restore database to a staging clone first when time allows.
4. Restore file storage if uploaded files or generated media are part of the incident.
5. Deploy code matching the restored database schema.
6. Run cache rebuilds and smoke checks.
7. Reopen writes only after data and file checks pass.

## Data Integrity Checks

- Compare counts for users, posts, media files, marketplace products, job applications, payments, and settings.
- Confirm recent uploaded files referenced by `media_files`, marketplace images, and job applications exist on disk.
- Confirm private job attachments are not publicly web-accessible.
- Confirm removed generated-image provider settings have not been restored as active browser-facing fields.

## Known Gaps

- Production backup tooling and retention policy are not stored in this repo.
- Real production orphan/duplicate/nullability data quality is unknown.
- Schema rollback for legacy dump-derived tables may require manual forward fixes.
