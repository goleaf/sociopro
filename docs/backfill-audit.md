# Backfill Audit

Date: 2026-07-02

## Current Backfill Command

Command: `php artisan legacy:backfill-json-columns`

Purpose: prepare the safe JSON column migration for legacy payment payload columns by normalizing blank strings to an empty JSON object and canonicalizing slash-escaped JSON payloads that decode safely.

Supported targets:

- `payment_gateways.keys`
- `payment_histories.transaction_keys`

Options:

- `--dry-run`: scan and report cleanable rows without writing changes.
- `--chunk=500`: control how many rows are scanned per chunk.

Safety behavior:

- The command is idempotent. A row already containing valid JSON is not changed.
- Only blank strings and safely decodable slash-escaped JSON payloads are backfilled automatically.
- Malformed non-blank JSON values are blockers and are reported, not guessed or overwritten.
- The command exits with a failure status when blockers remain.
- Progress logging prints per-target chunk summaries and a final summary.

Suggested production sequence:

1. Run `php artisan legacy:backfill-json-columns --dry-run --chunk=500`.
2. Review blockers, if any.
3. Run `php artisan legacy:backfill-json-columns --chunk=500`.
4. Re-run the dry run and confirm `blockers=0`.
5. Run the JSON column migration after the report is clean.

## Backfill Candidates

| Area | Backfill need | Safe action now | Why broader automation is deferred |
| --- | --- | --- | --- |
| `payment_gateways.keys` | Blank strings and slash-escaped JSON payloads block `2026_07_02_200000_add_safe_legacy_json_column_constraints.php`. | Use `legacy:backfill-json-columns`. | Malformed non-blank JSON may contain provider secrets or copied config and needs human review. |
| `payment_histories.transaction_keys` | Blank strings and slash-escaped JSON payloads block the same JSON migration. | Use `legacy:backfill-json-columns`. | Malformed non-blank transaction metadata needs provider-specific review before editing. |
| `languages.name + languages.phrase` | Duplicates block a future unique constraint. | Produce duplicate reports and pick canonical rows. | Deleting duplicate language rows is destructive and may lose translations. |
| `settings.type` | Duplicate settings block a future `settings_type_unique` constraint. | Compare duplicate descriptions and merge canonical content manually. | Existing reads use `first()` / `value()`, so changing the winner can alter public settings pages. |
| `marketplaces.price` | Non-numeric or over-precision values block decimal conversion. | Generate a report and decide business values per product. | Values such as free-text prices require product-owner decisions, not mechanical coercion. |
| `payment_histories.amount` | Invalid money values block decimal conversion. | Review provider records and reconcile with gateway history. | Payment amounts are financial data and must not be rounded or guessed. |
| `sponsors.paid_amount` | Invalid money values block decimal conversion. | Review sponsor/payment history before correction. | Sponsor billing state may depend on external payment records. |
| `personal_access_tokens.expires_at` | Invalid datetimes block datetime conversion. | Revoke invalid tokens or set expiry explicitly in a separate security-reviewed slice. | Token cleanup affects authentication and should be coordinated with access policy. |

## Future Rules

- Do not backfill large tables inside schema migrations.
- Make every backfill idempotent and restartable.
- Include `--dry-run` for write commands.
- Process rows in chunks and log progress.
- Preserve existing behavior unless the cleanup is explicitly part of the migration plan.
- Document destructive cleanup and rollback before deleting or merging rows.
