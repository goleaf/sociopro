# Performance Improvements

Generated: 2026-07-02

## Page Card Query Reduction

| Item | Details |
| --- | --- |
| Risk before | Page list Blade partials queried `page_likes` inside loops to count likes and check whether the current user liked each page. The liked-page tab also called `Page::find()` inside the loop. |
| Fix applied | `PageController::pages()` now builds page card collections with `withCount('likedByUsers')` and `withExists()` for current-user like state. The liked-page tab now receives page models directly. |
| Tests added | `test_page_listing_blades_use_preloaded_like_aggregates` prevents model queries from returning to page card Blade files. `test_pages_index_batches_page_like_lookups_for_rendered_cards` enforces the page-like query budget. |
| Remaining risk | Page timeline/sidebar partials still contain query and aggregate hotspots and need separate controller/view-model refactors. |
| Deployment notes | No schema changes are required; existing `page_likes` indexes from the safe legacy index migrations support this access pattern. |

## Page Modal Lookup Reduction

| Item | Details |
| --- | --- |
| Risk before | Page create/edit modals queried categories from Blade, and owner modals loaded pages from Blade. |
| Fix applied | `ModalController` now preloads page categories and owner page records before rendering the modal. Authorization is checked before rendering owner-only page modal content. |
| Tests added | `test_page_modal_blades_do_not_query_models_or_render_raw_descriptions` locks the presentation-only modal contract. |
| Remaining risk | Non-page modal partials still need the same review. |
| Deployment notes | No frontend asset build is required for these Blade-only changes, but the normal release build remains recommended. |
