# Editor page builder

Voodbuilder Pro ships a **frontend visual editor** for Site Pages. Editors with permission can open `?edit=1` on a published page and edit layout visually.

---

## Enable on a page

**Admin → Site → Pages → Publish**

| Field | Value |
|-------|-------|
| **Builder** | Editor |
| **Layout** | Home or Landing (full-width canvas) |
| **Sub-theme** | Usually Showcase (`events`) for marketing pages |

Install npm dependencies (host app):

```bash
# Host install pulls Editor npm deps via voodbuilder:install / SyncNpmDeps.
# Canvas engine package is pinned in package.json — do not patch node_modules.
npm run build
```

Optional Editor plugins (forms, background styles, tabs, custom HTML) ship enabled by default. Toggle in `config/voodbuilder.php` → `editor.plugins` (legacy key `grapesjs.plugins` still accepted).

The **image editor** opens from the canvas toolbar on selected images (and background-image sections). Toggle with `editor.image_editor` / `VOODBUILDER_EDITOR_IMAGE_EDITOR` (legacy key `grapesjs.image_editor`).

`php artisan voodbuilder:install` patches `vite.config.js` with Editor entries when possible.

---

## Block libraries

Blocks appear in the Editor sidebar when editing.

| Source | Category | Notes |
|--------|----------|-------|
| **Section library** | Marketing sections | 60+ marketing sections; adaptive to light/dark via theme tokens |
| **Voodbuilder** | `Voodbuilder` | Hero, content section, CTA, **site header/footer** (live menus) |
| **RichEditor blocks** | Per package | Dynamic server-rendered blocks (e.g. latest posts) |
| **Server blocks** | Per package | Third-party packages without Filament RichEditor |
| **Custom** | Your category | Static HTML registered in a ServiceProvider |
| **Forms / Tabs / …** | Editor plugins | Optional npm plugins (see below) |

### Section library catalog

Bundled JSON: `resources/editor/section-library-blocks.json` (legacy `tailblocks-blocks.json` filename may still exist during migration)

Regenerate section library assets (optional):

```bash
php artisan voodbuilder:build-sections --theme=indigo
npm run build
```

Blocks use **theme tokens** (`bg-vp-bg`, `text-vp-text-1`, `text-vp-brand-1`) so they follow light/dark and admin brand colours.

Config (`config/voodbuilder.php`):

```php
'editor' => [
    'sections' => [
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
use Voodflow\Voodbuilder\Voodbuilder;

Voodbuilder::editorBlock(
    id: 'polito-hero',
    label: 'Polito hero',
    category: 'Politecnico',
    content: <<<'HTML'
<section class="voodbuilder-editor-section bg-vp-bg text-vp-text-2 py-24">
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

For layered heroes, dropzones, Content-panel media traits, and editor≈public CSS rules, see **[EDITOR_BLOCK_AUTHORING.md](./EDITOR_BLOCK_AUTHORING.md)**.

---

## Register dynamic blocks (server-rendered)

Reuse Filament RichEditor custom blocks in Editor:

```php
Voodbuilder::editorRichContentBlock('Dynamic', LatestBlogPostsBlock::class);
```

The block class must implement the RichEditor custom block contract. Editor stores a placeholder; the server renders HTML on publish/view.

Use this for **latest posts**, **news lists**, **event cards**, etc.

When the block already exists for Filament RichEditor (with `configureEditorAction()`), register it once for both editors:

```php
Voodbuilder::richContentBlock('News', LatestNewsBlock::class);
Voodbuilder::editorRichContentBlock('UltiNews', LatestNewsBlock::class);
```

---

## Register server blocks (third-party packages)

For packages that **do not** use Filament RichEditor — e.g. a standalone UltiNews widget or a Filament Form rendered as HTML — implement `EditorServerBlock` and register in `boot()`:

```php
use Voodflow\Voodbuilder\Contracts\EditorServerBlock;
use Voodflow\Voodbuilder\Voodbuilder;

final class LatestNewsEditorBlock implements EditorServerBlock
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
        return view('example::editor.latest', compact('config'))->render();
    }

    public static function toPreviewHtml(array $config, array $context): string
    {
        return static::toHtml($config, $context);
    }
}

// UltiNewsServiceProvider::boot()
Voodbuilder::editorServerBlock('UltiNews', LatestNewsEditorBlock::class);
```

Editor stores only a placeholder (`data-voodbuilder-block`, `data-voodbuilder-config`). HTML is rendered on every page view — same pipeline as RichEditor blocks.

### Which API to choose?

| Need | API |
|------|-----|
| Static HTML snippet | `Voodbuilder::editorBlock()` |
| Block with Filament modal config in RichEditor | `Voodbuilder::editorRichContentBlock()` + `RichContentCustomBlock` |
| Third-party package, server render only | `Voodbuilder::editorServerBlock()` + `EditorServerBlock` |
| Filament Form on the page | `EditorServerBlock` that renders a Blade view with `@livewire` or `{{ $form }}` |

**Do not** patch `node_modules/grapesjs`. Extend via ServiceProvider registration only.

---

## Make dynamic (field bindings)

Editors can connect any selected element (title, image, link, …) to **live data** from installed packages — without writing PHP.

1. Build or paste your layout in the canvas (section library, copied HTML, etc.).
2. Select an element (`h1`, `p`, `img`, `a`, `button`, …).
3. Click **Make dynamic** (🔗) in the Editor toolbar.
4. Choose **Data source** (e.g. *Latest tutorial* from Vtuts) and **Field** (Title, URL, Image, …).
5. Save the page.

Saved HTML stores `data-voodbuilder-bind="vtuts.latest.title"` (or similar). The server resolves values on every page view.

| Concern | Detail |
|---------|--------|
| Remove binding | Select element → **Clear dynamic binding** (✕) |
| URL on `button` / `a` | Only the link is dynamic; **button/link label stays editable** |
| Plugin API | `Voodbuilder::editorBindingSource($source)` implementing `EditorBindingSource` |
| Full guide | [BINDINGS.md](./BINDINGS.md) — contract, field types, Vtuts fields, plugin tutorial |
| Catalog API | `GET /voodbuilder/editor/bindings` (auth + page-builder permission) |
| Preview API | `GET /voodbuilder/editor/bindings/preview/{sitePage}` |
| Vtuts | Registers `vtuts.latest` (title, introduction, url, image, category, author, dates, tags, …) |

---

## Site chrome (header / footer)

Landing pages can:

1. Toggle **Hide site header** / **Hide site footer** in Admin → Site → Pages (layout Home/Landing).
2. Drop **Site header (menu)** / **Site footer (menu)** blocks in Editor — they render real items from Admin → Menus.

While editing (`?edit=1`), the global nav/footer are hidden so the canvas matches the published layout.

---

## Optional Editor npm plugins

Config (`config/voodbuilder.php`):

```php
'editor' => [
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
| Forms plugin | Form/input blocks; submits to `POST /voodbuilder/editor/forms/{page}` with CSRF |
| Background style plugin | Background images / gradients in Style Manager |
| Tabs plugin | Tab component |
| Custom code plugin | Custom HTML embed; stripped of `<script>` on save |

Listen for form submissions in the host app:

```php
use Voodflow\Voodbuilder\Events\EditorFormSubmitted;

Event::listen(EditorFormSubmitted::class, function (EditorFormSubmitted $event) {
    // $event->page, $event->payload (name, email, message, …)
});
```

---

## Blocks tied to a custom theme

There is no per-theme block registry yet. Recommended pattern:

1. Create theme with `voodbuilder:make-subtheme polito`
2. Register blocks under category `Politecnico`
3. Use `voodbuilder-editor-section` + `bg-vp-bg` / `text-vp-text-*` classes
4. Override `--color-vp-*` in `resources/voodbuilder/themes/polito/theme.css`

Pages using the Polito sub-theme will pick up those variables automatically.

---

## Permissions

`config/voodbuilder.php` → `editor` (legacy key `grapesjs` still accepted) and `permissions.page_builder` / `page_builder_roles`.

Users need page-builder permission to see **Edit page** and use `?edit=1`.

---

## Canvas styles

The editor iframe loads compiled:

- `theme.css` (full Voodbuilder bundle including sub-themes)
- `tailblocks-utilities.css` (when catalog exists)

After CSS changes: `npm run build`.

---

## Editor UI (Voodbuilder shell)

The frontend builder uses a **custom 3-column shell** on top of Editor — no patches to `node_modules`:

| Column | Content |
|--------|---------|
| **Left** | Block library + search |
| **Center** | Device toolbar + canvas |
| **Right** | Inspector tabs: Content / Style / Dynamic / Layers |

Implementation lives in `resources/js/editor/editor-layout.js`, `resources/css/editor/editor-theme.css`, and `resources/js/editor/editor-icons.js`.

Toolbar icons use [Lucide](https://lucide.dev) (ISC License — commercial-friendly), inlined to avoid extra npm dependencies in distributions.

The theme remaps Editor `--gjs-*` variables to Voodbuilder tokens (`--color-vp-*`, `--vx-*`), replacing the default brown UI.

### Surviving Editor engine upgrades

1. Pin the Editor canvas package (and image editor) in `package.json` (semver range, not `*`)
2. Never edit files inside `node_modules` for those packages
3. Customise only via public APIs: `Panels`, `Commands`, `appendTo`, events, and our plugins under `resources/js/editor/`
4. After `npm update`, smoke-test: open `?edit=1`, drag a block, edit an image (toolbar pencil), bind a field, save, reload

---

## Saving and rendering

Saved payload: `html`, `css`, `project` (Editor JSON) on the Site Page record.

Public view:

- HTML rendered via `EditorRenderer`
- Hardcoded light colours in saved HTML are migrated to theme tokens at render time (`SectionThemeTokenMigrator`; legacy class name may still appear in older installs)

---

## Related

- [VISUAL_THEMES.md](./VISUAL_THEMES.md) — when to use Showcase vs Documentation  
- [BUILD.md](./BUILD.md) — compilation
