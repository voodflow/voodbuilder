# GrapesJS page builder

Vpress Pro ships a **frontend GrapesJS editor** for Site Pages. Editors with permission can open `?edit=1` on a published page and edit layout visually.

---

## Enable on a page

**Admin → Site → Pages → Publish**

| Field | Value |
|-------|-------|
| **Builder** | GrapesJS |
| **Layout** | Home or Landing (full-width canvas) |
| **Sub-theme** | Usually Showcase (`events`) for marketing pages |

Install npm dependencies (host app):

```bash
npm install grapesjs grapesjs-blocks-basic
npm install -D esbuild react react-dom prop-types   # only for vpress:build-tailblocks
npm run build
```

`php artisan vpress:install` patches `vite.config.js` with GrapesJS entries when possible.

---

## Block libraries

Blocks appear in the GrapesJS sidebar when editing.

| Source | Category | Notes |
|--------|----------|-------|
| **Tailblocks** | `Tailblocks / …` | 60+ marketing sections; adaptive to light/dark via theme tokens |
| **Vpress** | `Vpress` | Hero, content section, CTA banner |
| **RichEditor blocks** | Per package | Dynamic server-rendered blocks (e.g. latest posts) |
| **Custom** | Your category | Registered in a ServiceProvider |

### Tailblocks catalog

Bundled JSON: `resources/grapesjs/tailblocks-blocks.json`

Regenerate from upstream Tailblocks (optional):

```bash
php artisan vpress:build-tailblocks --theme=indigo
npm run build
```

Blocks use **theme tokens** (`bg-vp-bg`, `text-vp-text-1`, `text-vp-brand-1`) so they follow light/dark and admin brand colours.

Config (`config/vpress.php`):

```php
'grapesjs' => [
    'tailblocks' => [
        'enabled' => true,
        'theme' => 'indigo',   // accent colour family in source HTML
        'modes' => ['adaptive'],
    ],
],
```

---

## Register custom blocks (static HTML)

In a ServiceProvider `boot()`:

```php
use Voodflow\Vpress\Vpress;

Vpress::grapesJsBlock(
    id: 'polito-hero',
    label: 'Polito hero',
    category: 'Politecnico',
    content: <<<'HTML'
<section class="vpress-gjs-section bg-vp-bg text-vp-text-2 py-24">
  <div class="container mx-auto px-6">
    <h1 class="title-font text-4xl font-bold text-vp-text-1">Titolo</h1>
    <p class="mt-4 max-w-2xl">Testo introduttivo.</p>
  </div>
</section>
HTML,
);
```

**Use theme tokens**, not fixed `bg-white` / `text-gray-900`, so dark mode works.

Blocks are **global** (all pages). Group by `category` (e.g. your theme name) for clarity.

---

## Register dynamic blocks (server-rendered)

Reuse Filament RichEditor custom blocks in GrapesJS:

```php
Vpress::grapesJsRichContentBlock('Dynamic', LatestBlogPostsBlock::class);
```

The block class must implement the RichEditor custom block contract. GrapesJS stores a placeholder; the server renders HTML on publish/view.

Use this for **latest posts**, **news lists**, **event cards**, etc.

---

## Blocks tied to a custom theme

There is no per-theme block registry yet. Recommended pattern:

1. Create theme with `vpress:make-subtheme polito`
2. Register blocks under category `Politecnico`
3. Use `vpress-gjs-section` + `bg-vp-bg` / `text-vp-text-*` classes
4. Override `--color-vp-*` in `resources/vpress/themes/polito/theme.css`

Pages using the Polito sub-theme will pick up those variables automatically.

---

## Permissions

`config/vpress.php` → `grapesjs` and `permissions.page_builder` / `page_builder_roles`.

Users need page-builder permission to see **Edit page** and use `?edit=1`.

---

## Canvas styles

The editor iframe loads compiled:

- `theme.css` (full Vpress bundle including sub-themes)
- `tailblocks-utilities.css` (when catalog exists)

After CSS changes: `npm run build`.

---

## Saving and rendering

Saved payload: `html`, `css`, `project` (GrapesJS JSON) on the Site Page record.

Public view:

- HTML rendered via `GrapesJsRenderer`
- Hardcoded light colours in saved HTML are migrated to theme tokens at render time (`TailblocksThemeTokenMigrator`)

---

## Related

- [VISUAL_THEMES.md](./VISUAL_THEMES.md) — when to use Showcase vs Documentation  
- [BUILD.md](./BUILD.md) — compilation
