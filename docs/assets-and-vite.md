# Assets And Vite

Generated: 2026-07-04

This checkout now uses Laravel Vite for first-party compiled assets. Do not reintroduce Laravel Mix/Webpack.

## Detected Asset Pipeline

- JavaScript entry: `resources/js/app.js`
- SCSS entry: `resources/scss/app.scss`
- Build config: `vite.config.js`
- PostCSS config: `postcss.config.cjs`
- Blade loading: `@vite(['resources/scss/app.scss', 'resources/js/app.js'])`
- Build output: `public/build` with Vite's manifest.
- Package manager scripts are defined in `package.json`.

## Commands

```bash
npm run dev
npm run watch
npm run build
npm run lint
npm run stylelint
npm run format:check
npm run quality
```

`npm run quality` runs ESLint, Stylelint, Prettier check, the production dependency audit, and the Vite production build.

## Environment Rules

- Do not expose secrets in frontend assets.
- Treat every `VITE_*` variable as public browser-readable configuration.
- Runtime secrets must remain server-side in `.env` and be accessed through Laravel `config()` only.
- Do not use old `MIX_*` frontend environment names.

## Dependency Rules

- Keep `vite`, `laravel-vite-plugin`, and `sass` as the build-tool dependencies.
- Do not add `laravel-mix` or `webpack`.
- Remove dependencies only after confirming no import, global script, Blade, or public asset usage remains.
- Safe dependency updates should include a lockfile update plus `npm run quality`.

## Legacy Public Assets

The application still has large legacy theme/vendor assets under `public/assets/**` and the standalone `public/js/share.js`. They are not part of the first-party Vite bundle. Retire them only with route/page evidence, visual smoke checks, and rollback notes.
