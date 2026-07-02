# Assets And Vite

Generated: 2026-07-02

Despite older upgrade prompts mentioning Vite, this checkout currently uses Laravel Mix/Webpack. No `vite.config.*` file is present.

## Detected Asset Pipeline

- JavaScript entry: `resources/js/app.js`
- CSS entry: `resources/css/app.css`
- Build config: `webpack.mix.js`
- Output paths: `public/js` and `public/css`
- Package manager scripts are defined in `package.json`.

## Commands

```bash
npm run development
npm run watch
npm run production
npm run build
npm run lint
npm run stylelint
npm run format:check
npm run quality
```

`npm run quality` runs ESLint, Stylelint, Prettier check, and the production Mix build.

## Environment Rules

- Do not expose secrets in frontend assets.
- Treat any future `VITE_*` variable as public browser-readable configuration.
- Runtime secrets must remain server-side in `.env` and be accessed through Laravel `config()` only.
- If Vite is introduced later, update Blade asset references from Mix to `@vite` in the same tested migration.

## Dependency Rules

- Do not rewrite the asset stack during unrelated frontend cleanup.
- Remove dependencies only after confirming no import, global script, Blade, or public asset usage remains.
- Safe dependency updates should be minor/patch level and include a lockfile update plus `npm run quality`.

## Future Vite Migration Notes

A Mix-to-Vite migration should be a dedicated change with:

- Inventory of every asset included from Blade and `public/assets/frontend`.
- Visual smoke tests for authenticated pages, profile/page timelines, modals, media uploads, payments, and admin screens.
- Replacement of `mix()` or static compiled references where applicable.
- Explicit source map policy for production.
- Release rollback notes that keep old Mix-built assets available until the new build is verified.
