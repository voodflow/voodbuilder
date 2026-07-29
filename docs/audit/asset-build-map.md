# Asset build map

## Host Vite (app) — visual editor

Editor JS/CSS are consumed by the **host Laravel app** Vite config (not this package’s root `package.json`). Package sources live under:

- `resources/js/editor/**`
- `resources/css/editor/**`
- `resources/css/landing.css`, `theme.css`, etc.

Installer/docs (`docs/BUILD.md`, `ConfigureViteForVoodbuilder`) describe host wiring. **Do not patch Editor in node_modules.**

## Package-local builds

| Script | Tool | Output |
|---|---|---|
| `npm run build:theme-map` | esbuild (`bin/build-theme-map.js`) | `resources/dist/theme-map.js`, `theme-map.css` |
| `bin/build-tabler-icons-catalog.js` | node | `resources/js/editor/generated/tabler-icons-full.json` |
| `scripts/compile-component-tailwind.mjs` | node | Component Tailwind compile helper |

Package `package.json` dependencies: React, `@xyflow/react`, esbuild (theme-map only).

## Public / vendor assets

Brand marks and published vendor assets via `BrandMarkAssets`, Filament asset registration.

## Baseline size notes

- Theme-map dist ≈ 404KB folder
- Editor source tree ≈ 1.8MB JS (pre-minify)
- Runtime editor bundle size must be measured in host after `npm run build` (Phase 0 deferred metric; record in Phase 2)

## Frontend CI

Vitest smoke: `tests/js/modular-refactor.test.js` (core/settings registries). Wired in `.github/workflows/tests.yml`.
