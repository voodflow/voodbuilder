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
npm install grapesjs grapesjs-blocks-basic grapesjs-plugin-forms grapesjs-style-bg grapesjs-tabs grapesjs-custom-code
npm install -D esbuild react react-dom prop-types   # only for vpress:build-tailblocks
npm run build
```

Optional GrapesJS plugins (forms, background styles, tabs, custom HTML) ship enabled by default. Toggle in `config/vpress.php` → `grapesjs.plugins`.

`php artisan vpress:install` patches `vite.config.js` with GrapesJS entries when possible.

---

## Block libraries

Blocks appear in the GrapesJS sidebar when editing.

| Source | Category | Notes |
|--------|----------|-------|
| **Tailblocks** | `Tailblocks / …` | 60+ marketing sections; adaptive to light/dark via theme tokens |
| **Vpress** | `Vpress` | Hero, content section, CTA, **site header/footer** (live menus) |
| **RichEditor blocks** | Per package | Dynamic server-rendered blocks (e.g. latest posts) |
| **Server blocks** | Per package | Third-party packages without Filament RichEditor |
| **Custom** | Your category | Static HTML registered in a ServiceProvider |
| **Forms / Tabs / …** | GrapesJS plugins | Optional npm plugins (see below) |

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

When the block already exists for Filament RichEditor (with `configureEditorAction()`), register it once for both editors:

```php
Vpress::richContentBlock('News', LatestNewsBlock::class);
Vpress::grapesJsRichContentBlock('UltiNews', LatestNewsBlock::class);
```

---

## Register server blocks (third-party packages)

For packages that **do not** use Filament RichEditor — e.g. a standalone UltiNews widget or a Filament Form rendered as HTML — implement `GrapesJsServerBlock` and register in `boot()`:

```php
use Voodflow\Vpress\Contracts\GrapesJsServerBlock;
use Voodflow\Vpress\Vpress;

final class LatestNewsGrapesJsBlock implements GrapesJsServerBlock
{
    public static function getId(): string
    {
        return 'ultinews_latest';
    }

    public static function getLabel(): string
    {
        return 'Latest news';
    }

    public static function defaultConfig(): array
    {
        return ['limit' => 6, 'category' => null];
    }

    public static function toHtml(array $config, array $context): string
    {
        return view('ultinews::grapesjs.latest', compact('config'))->render();
    }

    public static function toPreviewHtml(array $config, array $context): string
    {
        return static::toHtml($config, $context);
    }
}

// UltiNewsServiceProvider::boot()
Vpress::grapesJsServerBlock('UltiNews', LatestNewsGrapesJsBlock::class);
```

GrapesJS stores only a placeholder (`data-vpress-block`, `data-vpress-config`). HTML is rendered on every page view — same pipeline as RichEditor blocks.

### Which API to choose?

| Need | API |
|------|-----|
| Static HTML snippet | `Vpress::grapesJsBlock()` |
| Block with Filament modal config in RichEditor | `Vpress::grapesJsRichContentBlock()` + `RichContentCustomBlock` |
| Third-party package, server render only | `Vpress::grapesJsServerBlock()` + `GrapesJsServerBlock` |
| Filament Form on the page | `GrapesJsServerBlock` that renders a Blade view with `@livewire` or `{{ $form }}` |

**Do not** patch `node_modules/grapesjs`. Extend via ServiceProvider registration only.

---

## Make dynamic (field bindings)

Editors can connect any selected element (title, image, link, …) to **live data** from installed packages — without writing PHP.

1. Build or paste your layout in the canvas (Tailblocks, copied HTML, etc.).
2. Select an element (`h1`, `p`, `img`, `a`, `button`, …).
3. Click **Make dynamic** (🔗) in the GrapesJS toolbar.
4. Choose **Data source** (e.g. *Latest tutorial* from Vtuts) and **Field** (Title, URL, Image, …).
5. Save the page.

Saved HTML stores `data-vpress-bind="vtuts.latest.title"` (or similar). The server resolves values on every page view.

| Concern | Detail |
|---------|--------|
| Remove binding | Select element → **Clear dynamic binding** (✕) |
| URL on `button` / `a` | Only the link is dynamic; **button/link label stays editable** |
| Plugin API | `Vpress::grapesJsBindingSource($source)` implementing `GrapesJsBindingSource` |
| Full guide | [BINDINGS.md](./BINDINGS.md) — contract, field types, Vtuts fields, plugin tutorial |
| Catalog API | `GET /vpress/grapesjs/bindings` (auth + page-builder permission) |
| Preview API | `GET /vpress/grapesjs/bindings/preview/{sitePage}` |
| Vtuts | Registers `vtuts.latest` (title, introduction, url, image, category, author, dates, tags, …) |

---

## Site chrome (header / footer)

Landing pages can:

1. Toggle **Hide site header** / **Hide site footer** in Admin → Site → Pages (layout Home/Landing).
2. Drop **Site header (menu)** / **Site footer (menu)** blocks in GrapesJS — they render real items from Admin → Menus.

While editing (`?edit=1`), the global nav/footer are hidden so the canvas matches the published layout.

---

## Optional GrapesJS npm plugins

Config (`config/vpress.php`):

```php
'grapesjs' => [
    'plugins' => [
        'forms' => true,
        'style_bg' => true,
        'tabs' => true,
        'custom_code' => true,
    ],
    'forms' => [
        'success_message' => 'Thank you. Your message has been received.',
    ],
],
```

| Plugin | Purpose |
|--------|---------|
| `grapesjs-plugin-forms` | Form/input blocks; submits to `POST /vpress/grapesjs/forms/{page}` with CSRF |
| `grapesjs-style-bg` | Background images / gradients in Style Manager |
| `grapesjs-tabs` | Tab component (not in Tailblocks) |
| `grapesjs-custom-code` | Custom HTML embed; stripped of `<script>` on save |

Listen for form submissions in the host app:

```php
use Voodflow\Vpress\Events\GrapesJsFormSubmitted;

Event::listen(GrapesJsFormSubmitted::class, function (GrapesJsFormSubmitted $event) {
    // $event->page, $event->payload (name, email, message, …)
});
```

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

## Editor UI (Vpress shell)

The frontend builder uses a **custom 3-column shell** on top of GrapesJS — no patches to `node_modules`:

| Column | Content |
|--------|---------|
| **Left** | Block library + search |
| **Center** | Device toolbar + canvas |
| **Right** | Inspector tabs: Content / Style / Dynamic / Layers |

Implementation lives in `resources/js/grapesjs/editor-layout.js`, `resources/css/grapesjs/editor-theme.css`, and `resources/js/grapesjs/editor-icons.js`.

Toolbar icons use [Lucide](https://lucide.dev) (ISC License — commercial-friendly), inlined to avoid extra npm dependencies in distributions.

The theme remaps GrapesJS `--gjs-*` variables to Vpress tokens (`--color-vp-*`, `--vx-*`), replacing the default brown UI.

### Surviving GrapesJS upgrades

1. Pin `grapesjs` in `package.json` (semver range, not `*`)
2. Never edit files inside `node_modules/grapesjs`
3. Customise only via public APIs: `Panels`, `Commands`, `appendTo`, events
4. After `npm update`, smoke-test: open `?edit=1`, drag a block, bind a field, save, reload

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
