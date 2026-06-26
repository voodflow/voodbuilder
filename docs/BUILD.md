# Build and compile assets

Vpress ships **source CSS/JS** in the package. Your Laravel app compiles them with Vite.

---

## First-time setup

```bash
php artisan vpress:install --with-npm-build
```

That command:

1. Patches `package.json` with Tailwind, fonts, and GrapesJS dependencies
2. Patches `vite.config.js` with vpress Vite entries
3. Runs `npm install`
4. Runs `npm run build` (includes `vpress:sync-theme-imports`)

Use `--skip-npm` if your CI or monorepo manages Node dependencies separately.

### Manual file edits (only when needed)

| File | When you must edit it |
|------|------------------------|
| Filament panel provider | Register `VpressPlugin::make()` once |
| `vite.config.js` | Add `tailwindcss()` plugin if your app does not use Tailwind v4 yet |
| `resources/js/app.js` | Only if you have a custom dark-mode toggle that conflicts with vpress |

`vpress:install` patches `vite.config.js` to include:

| Vite input | Purpose |
|------------|---------|
| `…/resources/css/theme.css` | Public site + all sub-themes |
| `…/resources/js/grapesjs/editor.js` | GrapesJS frontend editor |
| `…/resources/css/grapesjs/editor.css` | Editor chrome styles |
| `…/resources/css/grapesjs/tailblocks-utilities.css` | Tailblocks classes in the canvas |

Paths differ for `vendor/voodflow/vpress` installs — `VpressPaths` resolves them.

### Regenerating Tailblocks (optional)

Only needed when you run `php artisan vpress:build-tailblocks`:

```bash
npm install -D esbuild react react-dom prop-types
php artisan vpress:build-tailblocks
npm run build
```

---

## When to run `npm run build`

| Change | Rebuild? |
|--------|----------|
| Edit sub-theme CSS (`resources/vpress/themes/*/theme.css`) | **Yes** |
| `vpress:make-subtheme` (adds `@import`) | **Yes** |
| Edit `theme.css`, landing.css, events/blog/news CSS | **Yes** |
| Edit GrapesJS `editor.js` | **Yes** |
| Edit theme-map React (`resources/js/theme-map/`) | **Yes** — see below |
| `vpress:build-tailblocks` | **Yes** (regenerates catalog + utilities scan) |
| Blade layout only (no new Tailwind classes) | Usually no |
| New Tailwind classes in Blade | **Yes** (Tailwind scans `@source` paths) |

Development:

```bash
npm run dev
```

### Theme map (Filament Settings → Themes)

The theme assignment UI is a **standalone React Flow bundle** inside vpress. It does **not** use voodflow or the host app Vite entries.

```bash
cd packages/voodflow/vpress   # or vendor/voodflow/vpress
npm install
npm run build:theme-map       # output: resources/dist/theme-map.js
```

Watch mode during development:

```bash
npm run dev:theme-map
```

Commit `resources/dist/theme-map.js` when shipping the package, or run the build in CI before deploy.

---

## Custom sub-theme import chain

```
theme.css (package)
  @import blog/theme.css
  @import news/theme.css
  @import events/theme.css
  @import ../../../../resources/vpress/themes/polito/theme.css   ← your theme
```

`vpress:make-subtheme` appends the last line automatically.

---

## Tailwind `@source` scanning

`theme.css` scans Blade views and GrapesJS catalogs so utilities used in blocks are generated. If a new block HTML string uses a class that does not appear in scanned files, add it to a scanned path or a CSS file with `@source`.

---

## GrapesJS Tailblocks pipeline

```bash
php artisan vpress:build-tailblocks [--theme=indigo]
npm run build
```

Requires `esbuild`, `react`, `react-dom`, `prop-types` in the host app `node_modules`.

---

## Host app.js and theme toggle

Vpress public layouts expose `window.__vpressTheme` and load `site-scripts` for light/dark mode. If your host `resources/js/app.js` also toggles `document.documentElement.classList`, guard it:

```js
if (window.__vpressTheme) {
    return;
}
```

Otherwise both scripts fight and the toggle appears broken on vpress pages.

---

## Docker / CI

Run `npm run build` in the same environment that serves the app, or in CI before deploy. Missing build → GrapesJS shows “assets not built” and theme CSS 404s.

---

## Related

- [VISUAL_THEMES.md](./VISUAL_THEMES.md)  
- [GRAPESJS.md](./GRAPESJS.md)
