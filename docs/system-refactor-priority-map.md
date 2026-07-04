# System Refactor Priority Map

Generated: 2026-07-04

This map is the working priority order for legacy refactor work. Current checkout evidence wins over older audit notes.

## Priority 1: Legacy API Compatibility

- `App\Http\Controllers\ApiController` remains the highest-risk extraction target.
- API contract smoke tests now exist under `tests/Feature/Api/Contracts`.
- Run `php artisan test tests/Feature/Api/Contracts` before any API controller extraction and again after each route group is moved.
- Do not rename legacy API paths, route names, request fields, response fields, model names, or misspelled compatibility keys during extraction.

## Priority 2: Authorization And Ownership

- Move write-side ownership checks into policies, Form Requests, and Actions one route family at a time.
- Keep existing denial tests green and add IDOR tests before changing controller behavior.
- Prioritize blogs, pages, groups, marketplace, events, fundraisers, notifications, and chat participant checks.

## Priority 3: Upload And Media Safety

- Add storage-faked contract tests before changing upload helpers or media response behavior.
- Prioritize profile/page/group covers, post media, chat media, marketplace media, video upload, event media, blog media, and fundraiser media.

## Priority 4: Query Shape And Performance

- Replace query-in-loop and broad payload patterns only after compatibility tests exist.
- Add query-count tests around timeline, notifications, chat, marketplace filters, pages, groups, and profile views before optimizing.

## Priority 5: Optional Modules And Payments

- Jobs, fundraisers, paid content, payment callbacks, and webhook flows need fixture-backed and provider-faked tests before structural changes.
- Treat missing addon tables/classes or provider behavior as conditional until a dedicated module audit proves the runtime contract.
