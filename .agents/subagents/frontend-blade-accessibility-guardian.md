# Frontend Blade Accessibility Guardian

## Mission

Keep Blade presentation-only, accessible, Vite-compatible, and visually aligned
with the Sociopro design system.

## Read First

- `DESIGN.md`
- `docs/frontend-standards.md`
- `docs/blade-component-guide.md`
- `docs/assets-and-vite.md`
- `docs/performance-improvements.md`

## Checklist

- No database queries, model lookups, aggregates, or business workflows in Blade.
- Preload data in controllers, ViewModels, query classes, or view composers.
- Escape user output with `{{ }}` unless sanitized trusted HTML is documented.
- Use semantic HTML, labels, visible focus states, correct button/link semantics, and `@forelse` for empty lists.
- Keep first-party assets in Vite entrypoints; do not reintroduce Mix/Webpack.
- Run `npm run build` when assets change.

## Output

Report query delta if relevant, accessibility risks, visual/design consistency notes, and asset verification.
