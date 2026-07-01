# Accessors and Mutators Audit

## Current Finding

No Eloquent accessors or mutators are currently defined in `app/Models`.

The current model layer does not define legacy `getFooAttribute` accessors, legacy `setFooAttribute` mutators, `Attribute::make()` accessors, typed `Attribute` return methods, or `$appends` computed serialization attributes. Model serialization is therefore mostly driven by stored attributes, casts, hidden fields, and loaded relationships.

## Existing Presentation Boundaries

- Heavy view data, repeated queries, and formatting belong in `app/ViewModels` or dedicated resources/view models, not in Eloquent accessors.
- `app/ViewModels/BladeViewData.php` currently centralizes many Blade-facing lookups and counters.
- `app/ViewModels/ProfileFollowList.php` currently prepares follower/following rows for profile views.
- `app/Http/Resources` does not currently exist. Add API resources there before introducing non-trivial JSON formatting.

## Safe Helper Methods Found

- `App\Models\Payment_gateway::isEnabled()`, `isInTestMode()`, and `decodedKeys()` read direct attributes only and do not issue queries.
- `App\Models\Friendships::otherUserId()` reads direct attributes only and does not issue queries.

These methods should remain regular explicit helpers. Do not convert them into appended accessors unless a consumer requires serialized output and tests cover the serialization contract.

## Risks Checked

| Risk | Current Status | Required Future Standard |
| --- | --- | --- |
| Hidden queries in accessors | No accessor/mutator definitions found | Accessors must not query. Use eager loading, scopes, resources, or view models. |
| Mutator side effects | No mutator definitions found | Mutators must not send mail, write files, dispatch jobs, touch sessions, call APIs, or mutate unrelated records. |
| Expensive formatting | No model formatting accessors found | Date, currency, image URL, and profile display formatting belongs in resources/view models. |
| Date/time bugs | No date formatting accessors found | Models should cast dates; timezone-aware display formatting belongs at the presentation boundary. |
| Serialization leaks | Payment and user secret fields are already covered by model audit tests | Never append provider keys, tokens, passwords, transaction IDs, or private storage paths to model serialization. |
| Inconsistent naming | Legacy model class names remain out of scope for this audit | New accessors must follow Laravel naming only when justified by a tested consumer. |

## Out-of-Scope But Related Debt

`App\Models\Badge::add_payment_success()` and `App\Models\Sponsor::add_payment_success()` perform payment persistence and status updates from static model methods. They are not accessors or mutators, but they are side-effect-heavy model methods and should be extracted later into payment actions or services with transaction tests.

## Rules For Future Changes

- Keep accessors pure, cheap, deterministic, and query-free.
- Keep mutators focused on normalizing a single assigned attribute.
- Do not add `$appends` unless the serialized field is required by an existing public contract and covered by tests.
- Do not perform date/time display formatting in models; return cast values and format them in resources/view models.
- Do not hide lazy relationship access behind an accessor. Load relationships in controllers, query objects, or resources.
- Do not expose secrets through computed attributes or helper methods that are later appended.
- Add or update focused tests before introducing any accessor, mutator, serialization override, or custom `toArray()` behavior.
