# Model Lifecycle Audit

## Current Finding

No model observers are currently registered.

No custom Eloquent lifecycle hooks are currently defined in `app/Models`. The current model layer does not use observer classes, `Model::observe()`, `$dispatchesEvents`, model `boot()` / `booted()` methods, or `static::created()` / `static::updated()` / `static::deleted()` lifecycle callbacks.

## Event Map

- `app/Providers/EventServiceProvider.php` only maps Laravel's `Registered` auth event to `SendEmailVerificationNotification`.
- No application `app/Events`, `app/Listeners`, `app/Observers`, or `app/Jobs` directories currently exist.
- No raw `eloquent.*` event listeners are registered.
- No model files dispatch jobs, mail, notifications, HTTP calls, or broadcasts directly.

## Create/Update/Delete Behavior

The regression test covers a representative `Category` model create/update/delete flow and verifies:

- the `eloquent.created`, `eloquent.updated`, and `eloquent.deleted` events fire once;
- no duplicate lifecycle events are observed;
- no queued work is pushed;
- no mail is sent;
- no notifications are sent;
- the deleted record is removed from the database.

## Side Effects Removed

None. The audit did not find hidden business side effects wired through observers or Eloquent model events.

## Related Model Debt Outside This Scope

Some legacy model static methods still contain business side effects, especially payment success helpers on `App\Models\Badge` and `App\Models\Sponsor`. Those are not observers or Eloquent model events, but they should be extracted later into explicit payment actions or services with transactions and rollback tests.

## Future Rules

- Do not add observers for core business workflows unless the side effect is intentionally lifecycle-driven and covered by create/update/delete tests.
- Do not hide mail, notifications, webhooks, file writes, API calls, or payment work behind model events.
- Slow lifecycle side effects must dispatch named queued jobs, not run synchronously in observers.
- Multi-write lifecycle work must run inside explicit application service/action transactions.
- Queued lifecycle jobs that depend on committed records must be dispatched after the database commit.
- Prevent duplicate events with idempotent handlers, unique jobs, or explicit state checks.
- Prefer explicit actions/services for workflows where the caller needs to understand what happens after create/update/delete.
