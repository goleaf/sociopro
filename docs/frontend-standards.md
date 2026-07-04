# Frontend Standards

Generated: 2026-07-02

This project uses Laravel Blade for server-rendered frontend views and Vite/SCSS for first-party compiled assets. Preserve existing public UI behavior unless a change fixes a tested accessibility, security, or performance issue.

## Blade Rules

- Keep Blade presentation-only: no model queries, raw DB calls, aggregates, or business workflow decisions in views.
- Pass all page data from controllers, actions, view models, or composers.
- Use escaped output with `{{ }}` for user-generated content.
- Use `{!! !!}` only for audited, trusted HTML that has a documented sanitizer contract.
- Prefer `@forelse` for rendered collections when an empty state is visible to users.
- Keep authorization enforced in controllers, Form Requests, policies, or gates. Blade conditionals can hide controls, but they are not access control.

## Accessibility Rules

- Use semantic landmarks and elements: `main`, `nav`, `header`, `footer`, `section`, `article`, `aside`, `button`, `a`, lists, tables, and form controls.
- Use `button type="button"` for JavaScript actions and `a href` for navigation.
- Pair labels with form inputs and link validation errors with `aria-describedby` where practical.
- Give meaningful `alt` text to informative images and empty `alt=""` to decorative images.
- Preserve visible focus states and keyboard access when replacing clickable `div`, `span`, or `a href="javascript:void(0)"` patterns.
- Avoid unnecessary ARIA when native HTML already communicates the role.

## CSS Rules

- The compiled app style entry is `resources/scss/app.scss`, processed through Vite, PostCSS, Tailwind, Sass, and Autoprefixer.
- New shared styles should be organized by intent: tokens, base, layout, components, utilities, and page-specific rules.
- Prefer CSS custom properties for reusable colors, spacing, shadows, borders, and z-index values.
- Avoid ID selectors for styling, deep nesting, and new `!important` rules.
- Keep responsive constraints explicit so long text, media, tables, and buttons do not overflow small screens.

## JavaScript Rules

- The compiled app entry is `resources/js/app.js`.
- Use ES modules for new compiled JavaScript.
- Avoid accidental globals, inline event handlers, `eval`, `Function`, and unsafe `innerHTML`.
- Any AJAX/fetch helper must send the CSRF token for web routes and handle loading, success, validation, and network failure states.
- Remove `console.log` and `debugger` before commit.
- Preserve accessibility for dynamic UI: focus management, keyboard support, and announced loading/error states where relevant.

## Current Page Profile Pattern

The page profile sidebar/header now uses controller-supplied values:

- `$page->liked_by_users_count`
- `$page->liked_by_current_user`
- `$page->posts_count`
- `$pageIntro`
- `$suggestedpages`
- `$comments`

Do not reintroduce `PageLike::where`, `Posts::where`, `DB::table`, or `App\Models` lookups in `resources/views/frontend/pages/*.blade.php`.

## Known Frontend Debt

- Many legacy views still use inline handlers and `href="javascript:void(0)"`.
- Several rich-text views still render trusted HTML through `{!! !!}` and need sanitizer-specific tests.
- Legacy global theme/vendor assets under `public/assets/frontend` remain outside the Vite module pipeline.
- Legacy global scripts under `public/assets/frontend` are outside the compiled module pipeline.
