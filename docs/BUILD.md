# Build and compile assets

Vpress ships **source CSS/JS** in the package. Your Laravel app compiles them with Vite.

---

## First-time setup

```bash
php artisan vpress:install

npm install -D @fontsource-variable/inter @fontsource/jetbrains-mono tailwindcss @tailwindcss/vite
npm install grapesjs grapesjs-blocks-basic
npm install -D esbuild react react-dom prop-types   # Tailblocks export only
npm run build
```

`vpress:install` patches `vite.config.js` to include:

| Vite input | Purpose |
|------------|---------|
| `packages/voodflow/vpress/resources/css/theme.css` | Public site + all sub-themes |
| `…/resources/js/grapesjs/editor.js` | GrapesJS frontend editor |
| `…/resources/css/grapesjs/editor.css` | Editor chrome styles |

Paths differ for `vendor/voodflow/vpress` installs — `VpressPaths` resolves them.

---

## When to run `npm run build`

| Change | Rebuild? |
|--------|----------|
| Edit sub-theme CSS (`resources/vpress/themes/*/theme.css`) | **Yes** |
| `vpress:make-subtheme` (adds `@import`) | **Yes** |
| Edit `theme.css`, landing.css, events/blog/news CSS | **Yes** |
| Edit GrapesJS `editor.js` | **Yes** |
| `vpress:build-tailblocks` | **Yes** (regenerates catalog + utilities scan) |
| Blade layout only (no new Tailwind classes) | Usually no |
| New Tailwind classes in Blade | **Yes** (Tailwind scans `@source` paths) |

Development:

```bash
npm run dev
```

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

## Docker / CI

Run `npm run build` in the same environment that serves the app, or in CI before deploy. Missing build → GrapesJS shows “assets not built” and theme CSS 404s.

---

## Related

- [VISUAL_THEMES.md](./VISUAL_THEMES.md)  
- [GRAPESJS.md](./GRAPESJS.md)
