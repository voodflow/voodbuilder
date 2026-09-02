# Build and compile assets

Voodbuilder ships **source CSS/JS** in the package. Your Laravel app compiles them with Vite.

---

## First-time setup

```bash
php artisan voodbuilder:install --with-npm-build
```

That command:

1. Patches `package.json` with Tailwind, fonts, and Editor dependencies
2. Patches `vite.config.js` with voodbuilder Vite entries
3. Runs `npm install`
4. Runs `npm run build` (includes `voodbuilder:sync-theme-imports`)

Use `--skip-npm` if your CI or monorepo manages Node dependencies separately.

### Manual file edits (only when needed)

| File | When you must edit it |
|------|------------------------|
| Filament panel provider | Register `VoodbuilderPlugin::make()` once |
| `vite.config.js` | Add `tailwindcss()` plugin if your app does not use Tailwind v4 yet |
| `resources/js/app.js` | Only if you have a custom dark-mode toggle that conflicts with voodbuilder |

`voodbuilder:install` patches `vite.config.js` to include:

| Vite input | Purpose |
|------------|---------|
| `…/resources/css/theme.css` | Public site + all sub-themes |
| `…/resources/js/editor/editor.js` | Editor frontend editor |
| `…/resources/css/editor/editor.css` | Editor chrome styles |
| `…/resources/css/editor/tailblocks-utilities.css` | Tailblocks classes in the canvas |

Paths differ for `vendor/voodflow/voodbuilder` installs — `VoodbuilderPaths` resolves them.

### Editor chunk splitting (recommended)

The editor entry pulls in GrapesJS, which is about half its weight and only changes on a
deliberate upgrade. Without a chunking strategy it shares one chunk with our editor
modules, so every deploy of ours invalidates the vendor half too and returning authors
re-download the whole bundle for a one-line change.

Wire the strategy the package ships — the installer does not patch this, because it edits
your `build` block rather than the input list:

```js
// vite.config.js
import { voodbuilderManualChunks } from './packages/voodflow/voodbuilder/bin/voodbuilder-manual-chunks.js';

export default defineConfig({
    build: {
        // The editor entry is large by nature; silence the size warning for it.
        chunkSizeWarningLimit: 3500,
        rollupOptions: {
            output: { manualChunks: voodbuilderManualChunks },
        },
    },
    // …
});
```

Measured on the reference install, this moves ~425 KiB raw (~110 KiB gzip) off the boot
path and leaves ~278 KiB gzip of GrapesJS cached independently of our releases.

### Regenerating Tailblocks (optional)

Only needed when you run `php artisan voodbuilder:build-tailblocks`:

```bash
npm install -D esbuild react react-dom prop-types
php artisan voodbuilder:build-tailblocks
npm run build
```

---

## When to run `npm run build`

| Change | Rebuild? |
|--------|----------|
| Edit sub-theme CSS (`resources/voodbuilder/themes/*/theme.css`) | **Yes** |
| `voodbuilder:make-subtheme` (adds `@import`) | **Yes** |
| Edit `theme.css`, landing.css, events/blog/news CSS | **Yes** |
| Edit Editor `editor.js` | **Yes** |
| Edit theme-map React (`resources/js/theme-map/`) | **Yes** — see below |
| `voodbuilder:build-tailblocks` | **Yes** (regenerates catalog + utilities scan) |
| Blade layout only (no new Tailwind classes) | Usually no |
| New Tailwind classes in Blade | **Yes** (Tailwind scans `@source` paths) |

Development:

```bash
npm run dev
```

### Theme map (Filament Theme Studio)

Build the React theme-map bundle, then open **VoodBuilder → Theme Studio** in admin.

The theme assignment UI is a **standalone React Flow bundle** inside voodbuilder. It does **not** use voodflow or the host app Vite entries.

```bash
cd packages/voodflow/voodbuilder   # or vendor/voodflow/voodbuilder
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
  @import ../../../../resources/voodbuilder/themes/polito/theme.css   ← your theme
```

`voodbuilder:make-subtheme` appends the last line automatically.

---

## Tailwind `@source` scanning

`theme.css` scans Blade views and Editor catalogs so utilities used in blocks are generated. If a new block HTML string uses a class that does not appear in scanned files, add it to a scanned path or a CSS file with `@source`.

---

## Editor Tailblocks pipeline

```bash
php artisan voodbuilder:build-tailblocks [--theme=indigo]
npm run build
```

Requires `esbuild`, `react`, `react-dom`, `prop-types` in the host app `node_modules`.

---

## Host app.js and theme toggle

VoodBuilder public layouts expose `window.__voodbuilderTheme` and load `site-scripts` for light/dark mode. If your host `resources/js/app.js` also toggles `document.documentElement.classList`, guard it:

```js
if (window.__voodbuilderTheme) {
    return;
}
```

Otherwise both scripts fight and the toggle appears broken on voodbuilder pages.

---

## Docker / CI

Run `npm run build` in the same environment that serves the app, or in CI before deploy. Missing build → Editor shows “assets not built” and theme CSS 404s.

---

## Related

- [VISUAL_THEMES.md](./VISUAL_THEMES.md)  
- [EDITOR.md](./EDITOR.md)
