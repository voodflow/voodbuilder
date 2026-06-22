# Visual themes — mental model and admin guide

Vpress separates **what you see** (visual themes / sub-themes) from **how you edit content** (RichEditor, GrapesJS, vdocs, vtuts) and from **global site settings** (logo, SEO, dark/light toggle, menus, analytics).

This guide explains the admin UI in plain language and maps it to real site setups.

---

## Three layers (keep these separate)

| Layer | What it controls | Where in admin |
|-------|------------------|----------------|
| **Global appearance** | Light/dark default, theme toggle, logo, menus | Settings → Appearance, Site, Navigation |
| **Visual theme per area** | Layout + colours for marketing pages vs docs vs blog | Settings → Theme → **Visual themes by area** |
| **Page content** | HTML, GrapesJS canvas, RichEditor blocks | Pages, vdocs, vtuts, companion packages |

**Light/dark mode** is global. It does not pick a visual theme — it only switches CSS variables on the active theme.

---

## Visual themes by area (formerly “theme bindings”)

**Settings → Theme → Visual themes by area** answers one question:

> *Which layout/skin should this part of the public site use?*

| Admin field | Applies to | Example |
|-------------|------------|---------|
| **Marketing pages** | Site Pages (home, `/pages/*`) unless a page overrides | Showcase (`events`) for a landing-style home |
| **Documentation** | `vdocs.*` routes (if vdocs installed) | Documentation (`default`) — sidebar + outline |
| **Tutorials** | `vtuts.*` routes (if vtuts installed) | Documentation (`default`) |
| **Blog / News / Events / …** | Routes registered by companion packages | Blog layout for articles, Showcase for event marketing |

**Package default** means: use the default from `config/vpress.php` → `content_channel_defaults` (code, not DB).

**Per-page override:** Admin → Pages → Publish → Sub-theme (only for that Site Page).

### Why two dropdowns?

Marketing pages (home, landings) often need a **wide landing layout** (Showcase). Docs and tutorials need a **doc layout** (sidebar, reading column). The same site can use both — that is normal, not a misconfiguration.

---

## Quick setups (CLI only)

Presets are **not shown in the admin UI**. Use them from the command line to clone configuration between environments:

| Preset | Marketing pages | Docs | Tutorials | Blog | Typical use |
|--------|-----------------|------|-----------|------|-------------|
| **Documentation only** | Documentation | Documentation | Documentation | — | Pure docs/tutorial site |
| **Cosmolab public site** | Showcase | Documentation | Documentation | Blog | Marketing home + docs + blog articles |
| **Soundmit showcase** | Showcase | Documentation | Documentation | — | Events / exhibitors marketing |

**Apply preset** → one-click configuration for a common pattern.

**Export current** → save your bindings + colours as JSON (staging → production, or backup).

**Import preset** → load JSON into the site (optional apply).

After you change bindings manually and save, **Active preset** shows *Custom* — that is expected.

---

## Common site setups

### 1. Documentation / tutorials only

- **Apply preset:** Documentation only  
- **Home:** vdocs index route, or a Site Page with RichEditor (no GrapesJS required)  
- **Docs / Tutorials:** Documentation theme (automatic with preset)

### 2. Landing page only (GrapesJS)

- **Marketing pages:** Showcase  
- **Pages → Home:** Layout = *Home* or *Landing*, Builder = *GrapesJS*, build the page in the frontend editor (`?edit=1`)  
- No vdocs/vtuts required

### 3. Landing + documentation / tutorials

- **Apply preset:** Cosmolab public site (or set manually: Showcase + Documentation for docs/tutorials)  
- **Home / landings:** GrapesJS or RichEditor under Showcase  
- **Internal docs:** vdocs/vtuts keep doc layout and dynamic content

### 4. GrapesJS home + blog / news with dynamic lists

- **Marketing pages:** Showcase (home built with GrapesJS)  
- **Blog channel:** Blog or News sub-theme (for article pages under `/pages/blog-*` or a companion blog package)  
- **Dynamic blocks:** Register RichEditor/GrapesJS blocks that render latest posts (see [GRAPESJS.md](./GRAPESJS.md))

Global settings (dark/light, menus, logo, SEO, tracking) stay under **Appearance**, **Site**, **SEO**, **Analytics** — unchanged.

---

## Built-in visual themes (sub-themes)

| ID | Admin label | Type | Best for |
|----|-------------|------|----------|
| `default` | Documentation | Content | vdocs, vtuts, doc-style pages |
| `blog` | Blog | Content | Long-form articles, section indexes |
| `news` | News | Content | Editorial / magazine layout |
| `events` | Showcase | Marketing | Landing pages, GrapesJS full-width canvas |

**Content themes** support `doc` and/or `article` capabilities.  
**Marketing themes** support `landing` capability (Site Pages, landings).

Admin dropdowns only show themes compatible with each area.

---

## How the active theme is chosen (resolution order)

```
Site Page (/ or /pages/slug)
  → page.sub_theme (if set)
  → else Settings → Marketing pages default

Route-based area (vdocs.*, vtuts.*, vevents.*, …)
  → Settings → channel override (if set)
  → else config content_channel_defaults
  → else Settings → Marketing pages default (fallback)
```

Light/dark is applied **on top** via `html.dark` and `ThemePalette` brand overrides.

---

## Creating a custom visual theme (e.g. polito.it)

### 1. Scaffold

```bash
php artisan vpress:make-subtheme polito --label="Politecnico"
```

This creates:

```
resources/vpress/themes/polito/theme.css
resources/views/vpress/themes/polito/layouts/
  home.blade.php
  landing.blade.php
  page.blade.php
```

And registers `polito` in `config/vpress.php` → `sub_themes`.

### 2. Define the look (CSS)

Edit `resources/vpress/themes/polito/theme.css`. Scope rules with:

```css
html[data-vpress-sub-theme='polito'] {
    --color-vp-brand-1: #003366;
    --width-vp-layout: 80rem;
    /* … */
}
```

Use Vpress tokens (`--color-vp-bg`, `--color-vp-text-1`, `bg-vp-bg`, `text-vp-brand-1` in Tailwind) so **dark mode works automatically**.

`vpress:make-subtheme` also appends an `@import` to the main vpress `theme.css` bundle.

### 3. Change layouts (optional)

Override Blade layouts when you need a different shell (header structure, grid, footer):

- `resources/views/vpress/themes/polito/layouts/landing.blade.php` — full-width GrapesJS canvas  
- `…/home.blade.php` — homepage wrapper  
- `…/page.blade.php` — standard CMS pages  

Extend `vpress::layouts.app` unless you replace the entire chrome.

### 4. Register capabilities

For a university marketing site + docs:

```php
// config/vpress.php — after make-subtheme, adjust type/capabilities:
'polito' => [
    'label' => 'Politecnico',
    'type' => 'marketing',           // or 'content' for doc-only
    'capabilities' => ['landing'],     // add 'doc', 'article' if needed
    'layouts' => [ /* … */ ],
    'css' => 'resources/vpress/themes/polito/theme.css',
],
```

Or register at runtime:

```php
Vpress::subTheme('polito', [ /* … */ ]);
```

### 5. Assign in admin

**Settings → Theme → Marketing pages** → Politecnico.

Or apply colours + bindings via a custom preset JSON (export an existing site, edit, import).

### 6. Compile assets

```bash
npm run build
```

Required after every CSS or `@import` change. See [BUILD.md](./BUILD.md).

---

## Theme presets — JSON format

Schema: `vpress-theme-preset/1`

```json
{
    "schema": "vpress-theme-preset/1",
    "id": "my-setup",
    "label": "My setup",
    "description": "Optional note",
    "site_pages_theme": "events",
    "channel_themes": {
        "docs": "default",
        "tutorials": "default"
    },
    "colors": {}
}
```

CLI:

```bash
php artisan vpress:theme list
php artisan vpress:theme export my-setup --output=./presets
php artisan vpress:theme import ./my-setup.json --apply
```

Bundled presets live in `resources/theme-presets/` inside the package.

---

## FAQ

**Why is “Pages” empty in theme bindings?**  
The `pages` channel is for route matching only. Site Pages use **Marketing pages** + per-page override.

**Do I need presets?**  
No. Use **Apply preset** once for a quick start, then tune bindings manually.

**Can one page use GrapesJS and another use docs layout?**  
Yes. Home = Showcase + GrapesJS. `/docs/*` = Documentation via channel binding.

**Where is light/dark?**  
Settings → Appearance. Independent from visual theme.

---

## Related docs

- [GRAPESJS.md](./GRAPESJS.md) — page builder, blocks, Tailblocks  
- [BUILD.md](./BUILD.md) — npm, Vite, compilation workflow
